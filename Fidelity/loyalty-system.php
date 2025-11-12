<?php
/**
 * Système de fidélité Newsaiige
 * Gestion complète des points de fidélité, paliers, et bons d'achat
 * 
 * IMPORTANT: Avant d'utiliser ce système, vous devez exécuter le fichier
 * loyalty-database.sql dans votre base de données MySQL pour créer les tables nécessaires.
 */

// Vérification de la présence des tables nécessaires
function newsaiige_loyalty_check_tables() {
    global $wpdb;
    
    $required_tables = array(
        $wpdb->prefix . 'newsaiige_loyalty_points',
        $wpdb->prefix . 'newsaiige_loyalty_tiers',
        $wpdb->prefix . 'newsaiige_loyalty_vouchers',
        $wpdb->prefix . 'newsaiige_loyalty_user_tiers',
        $wpdb->prefix . 'newsaiige_loyalty_settings'
    );
    
    $missing_tables = array();
    
    foreach ($required_tables as $table) {
        $result = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($result !== $table) {
            $missing_tables[] = $table;
        }
    }
    
    if (!empty($missing_tables)) {
        add_action('admin_notices', function() use ($missing_tables) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>Système de fidélité Newsaiige:</strong> Tables manquantes en base de données: ';
            echo implode(', ', $missing_tables);
            echo '<br>Veuillez exécuter le fichier loyalty-database.sql dans votre base de données.';
            echo '</p></div>';
        });
        return false;
    }
    
    return true;
}

// Vérifier les tables au chargement
add_action('admin_init', 'newsaiige_loyalty_check_tables');

// Classe principale du système de fidélité
class NewsaiigeLoyaltySystem {
    
    private $points_table;
    private $tiers_table;
    private $vouchers_table;
    private $user_tiers_table;
    private $settings_table;
    private $conversion_rules_table;
    
    public function __construct() {
        global $wpdb;
        $this->points_table = $wpdb->prefix . 'newsaiige_loyalty_points';
        $this->tiers_table = $wpdb->prefix . 'newsaiige_loyalty_tiers';
        $this->vouchers_table = $wpdb->prefix . 'newsaiige_loyalty_vouchers';
        $this->user_tiers_table = $wpdb->prefix . 'newsaiige_loyalty_user_tiers';
        $this->settings_table = $wpdb->prefix . 'newsaiige_loyalty_settings';
        $this->conversion_rules_table = $wpdb->prefix . 'newsaiige_loyalty_conversion_rules';
        
        // Hooks WooCommerce
        add_action('woocommerce_order_status_completed', array($this, 'process_order_points'), 10, 1);
        
        // Hooks WordPress
        add_action('wp_ajax_loyalty_convert_points', array($this, 'convert_points_to_voucher'));
        add_action('wp_ajax_loyalty_get_user_data', array($this, 'get_user_loyalty_data'));
        
        // Hook pour vérifier les anniversaires quotidiennement
        add_action('wp', array($this, 'schedule_birthday_check'));
        add_action('newsaiige_daily_birthday_check', array($this, 'check_user_birthdays'));
        
        // Hook pour nettoyer les points expirés
        add_action('newsaiige_daily_cleanup', array($this, 'cleanup_expired_data'));
        
        // Intégration avec WooCommerce checkout
        add_action('woocommerce_checkout_process', array($this, 'validate_voucher_code'));
        add_action('woocommerce_cart_calculate_fees', array($this, 'apply_voucher_discount'));
    }
    
    /**
     * Obtenir une configuration du système
     */
    public function get_setting($key, $default = '') {
        global $wpdb;
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM {$this->settings_table} WHERE setting_key = %s",
            $key
        ));
        return $result !== null ? $result : $default;
    }
    
    /**
     * Vérifier si un utilisateur a un abonnement actif (produits de la catégorie soins)
     */
    public function has_active_subscription($user_id) {
        $subscription_category = $this->get_setting('subscription_category_slug', 'soins');
        
        // Récupérer les commandes récentes de l'utilisateur
        $orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'status' => array('completed', 'processing'),
            'limit' => 10,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        foreach ($orders as $order) {
            $order_items = $order->get_items();
            foreach ($order_items as $item) {
                $product = $item->get_product();
                if ($product) {
                    $terms = wp_get_post_terms($product->get_id(), 'product_cat');
                    foreach ($terms as $term) {
                        if ($term->slug === $subscription_category) {
                            // Vérifier si l'achat est récent (moins de 60 jours)
                            $order_date = $order->get_date_created();
                            $days_since_order = (time() - $order_date->getTimestamp()) / (24 * 60 * 60);
                            if ($days_since_order <= 60) {
                                return true;
                            }
                        }
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Traiter les points d'une commande
     */
    public function process_order_points($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        $user_id = $order->get_user_id();
        if (!$user_id) return;
        
        // Vérifier si l'utilisateur a un abonnement
        if ($this->get_setting('subscription_required', '1') === '1' && !$this->has_active_subscription($user_id)) {
            return;
        }
        
        // Vérifier si les points ont déjà été attribués
        global $wpdb;
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->points_table} WHERE order_id = %d AND action_type = 'purchase'",
            $order_id
        ));
        
        if ($existing > 0) return;
        
        // Calculer les points (1 euro = 1 point, sans les décimales)
        $order_total = $order->get_total();
        $points_per_euro = intval($this->get_setting('points_per_euro', '1'));
        $points_earned = floor($order_total) * $points_per_euro;
        
        if ($points_earned <= 0) return;
        
        // Calculer la date d'expiration
        $expiry_days = intval($this->get_setting('points_expiry_days', '365'));
        $expires_at = date('Y-m-d H:i:s', strtotime('+' . $expiry_days . ' days'));
        
        // Ajouter les points
        $this->add_points($user_id, $points_earned, $order_id, 'purchase', 
            "Points gagnés pour la commande #{$order->get_order_number()}", $expires_at);
        
        // Vérifier si l'utilisateur a atteint un nouveau palier
        $this->check_tier_upgrade($user_id);
    }
    
    /**
     * Ajouter des points à un utilisateur
     */
    public function add_points($user_id, $points, $order_id = null, $action_type = 'manual', $description = '', $expires_at = null) {
        global $wpdb;
        
        if (!$expires_at) {
            $expiry_days = intval($this->get_setting('points_expiry_days', '365'));
            $expires_at = date('Y-m-d H:i:s', strtotime('+' . $expiry_days . ' days'));
        }
        
        return $wpdb->insert(
            $this->points_table,
            array(
                'user_id' => $user_id,
                'points_earned' => $points,
                'points_available' => $points,
                'order_id' => $order_id,
                'action_type' => $action_type,
                'description' => $description,
                'expires_at' => $expires_at
            )
        );
    }
    
    /**
     * Obtenir le total des points disponibles d'un utilisateur
     */
    public function get_user_points($user_id) {
        global $wpdb;
        
        // Nettoyer les points expirés d'abord
        $this->cleanup_expired_points($user_id);
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(points_available) FROM {$this->points_table} 
             WHERE user_id = %d AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())",
            $user_id
        ));
        
        return intval($total);
    }
    
    /**
     * Obtenir le palier actuel d'un utilisateur
     */
    public function get_user_tier($user_id) {
        global $wpdb;
        
        $total_points = $this->get_user_lifetime_points($user_id);
        
        $tier = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tiers_table} 
             WHERE points_required <= %d AND is_active = 1 
             ORDER BY points_required DESC LIMIT 1",
            $total_points
        ));
        
        return $tier;
    }
    
    /**
     * Obtenir le total des points gagnés depuis le début
     */
    public function get_user_lifetime_points($user_id) {
        global $wpdb;
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(points_earned) FROM {$this->points_table} WHERE user_id = %d",
            $user_id
        ));
        
        return intval($total);
    }
    
    /**
     * Vérifier si un utilisateur a atteint un nouveau palier
     */
    public function check_tier_upgrade($user_id) {
        $current_tier = $this->get_user_tier($user_id);
        if (!$current_tier) return;
        
        global $wpdb;
        
        // Vérifier le dernier palier enregistré
        $last_tier = $wpdb->get_row($wpdb->prepare(
            "SELECT ut.tier_id, t.tier_name FROM {$this->user_tiers_table} ut
             JOIN {$this->tiers_table} t ON ut.tier_id = t.id
             WHERE ut.user_id = %d AND ut.is_current = 1
             ORDER BY ut.achieved_at DESC LIMIT 1",
            $user_id
        ));
        
        // Si nouveau palier
        if (!$last_tier || $last_tier->tier_id != $current_tier->id) {
            // Marquer l'ancien palier comme non actuel
            if ($last_tier) {
                $wpdb->update(
                    $this->user_tiers_table,
                    array('is_current' => 0),
                    array('user_id' => $user_id, 'is_current' => 1)
                );
            }
            
            // Enregistrer le nouveau palier
            $wpdb->insert(
                $this->user_tiers_table,
                array(
                    'user_id' => $user_id,
                    'tier_id' => $current_tier->id,
                    'achieved_at' => current_time('mysql'),
                    'is_current' => 1
                )
            );
            
            // Envoyer un email de félicitations
            $this->send_tier_achievement_email($user_id, $current_tier);
            
            // Offrir un bon d'achat selon le palier
            $this->grant_tier_voucher($user_id, $current_tier);
        }
    }
    
    /**
     * Offrir un bon d'achat selon le palier atteint
     */
    private function grant_tier_voucher($user_id, $tier) {
        $voucher_amounts = array(
            'bronze' => 0,
            'silver' => 5,
            'gold' => 15,
            'platinum' => 25
        );
        
        if (isset($voucher_amounts[$tier->tier_slug]) && $voucher_amounts[$tier->tier_slug] > 0) {
            $this->create_voucher($user_id, $voucher_amounts[$tier->tier_slug], 0, 'tier_achievement', 
                "Bon d'achat offert pour l'atteinte du palier {$tier->tier_name}");
        }
    }
    
    /**
     * Créer un bon d'achat
     */
    public function create_voucher($user_id, $amount, $points_cost, $type = 'conversion', $description = '') {
        global $wpdb;
        
        // Générer un code unique
        $voucher_code = $this->generate_voucher_code();
        $expiry_days = intval($this->get_setting('voucher_expiry_days', '90'));
        $expires_at = date('Y-m-d H:i:s', strtotime('+' . $expiry_days . ' days'));
        
        return $wpdb->insert(
            $this->vouchers_table,
            array(
                'user_id' => $user_id,
                'voucher_code' => $voucher_code,
                'voucher_type' => $type,
                'amount' => $amount,
                'points_cost' => $points_cost,
                'expires_at' => $expires_at
            )
        );
    }
    
    /**
     * Générer un code de bon d'achat unique
     */
    private function generate_voucher_code() {
        global $wpdb;
        
        do {
            $code = 'NEWS' . strtoupper(wp_generate_password(8, false));
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->vouchers_table} WHERE voucher_code = %s",
                $code
            ));
        } while ($exists > 0);
        
        return $code;
    }
    
    /**
     * Nettoyer les points expirés
     */
    public function cleanup_expired_points($user_id = null) {
        global $wpdb;
        
        $where = "expires_at IS NOT NULL AND expires_at < NOW()";
        $params = array();
        
        if ($user_id) {
            $where .= " AND user_id = %d";
            $params[] = $user_id;
        }
        
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->points_table} SET is_active = 0 WHERE $where",
            $params
        ));
    }
    
    /**
     * Nettoyer les données expirées quotidiennement
     */
    public function cleanup_expired_data() {
        $this->cleanup_expired_points();
        
        global $wpdb;
        // Marquer les bons d'achat expirés
        $wpdb->query(
            "UPDATE {$this->vouchers_table} SET is_used = -1 WHERE expires_at < NOW() AND is_used = 0"
        );
    }
    
    /**
     * Programmer la vérification quotidienne des anniversaires
     */
    public function schedule_birthday_check() {
        if (!wp_next_scheduled('newsaiige_daily_birthday_check')) {
            wp_schedule_event(time(), 'daily', 'newsaiige_daily_birthday_check');
        }
        
        if (!wp_next_scheduled('newsaiige_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'newsaiige_daily_cleanup');
        }
    }
    
    /**
     * Vérifier les anniversaires et offrir des bons d'achat
     */
    public function check_user_birthdays() {
        global $wpdb;
        
        $today = date('m-d');
        
        // Récupérer les utilisateurs dont c'est l'anniversaire
        $users = $wpdb->get_results($wpdb->prepare(
            "SELECT u.ID, u.user_email, u.display_name, 
                    DATE_FORMAT(um.meta_value, '%%m-%%d') as birthday
             FROM {$wpdb->users} u
             JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
             WHERE um.meta_key = 'billing_birthday' 
             AND DATE_FORMAT(um.meta_value, '%%m-%%d') = %s",
            $today
        ));
        
        foreach ($users as $user) {
            // Vérifier s'il a déjà reçu un bon d'anniversaire cette année
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->vouchers_table} 
                 WHERE user_id = %d AND voucher_type = 'birthday' 
                 AND YEAR(created_at) = YEAR(NOW())",
                $user->ID
            ));
            
            if ($existing == 0) {
                $tier = $this->get_user_tier($user->ID);
                if ($tier && $tier->birthday_bonus_percentage > 0) {
                    $this->create_birthday_voucher($user->ID, $tier->birthday_bonus_percentage);
                }
            }
        }
    }
    
    /**
     * Créer un bon d'achat d'anniversaire
     */
    private function create_birthday_voucher($user_id, $percentage) {
        global $wpdb;
        
        $voucher_code = $this->generate_voucher_code();
        $expiry_days = 30; // Bon d'anniversaire valable 30 jours
        $expires_at = date('Y-m-d H:i:s', strtotime('+' . $expiry_days . ' days'));
        
        $wpdb->insert(
            $this->vouchers_table,
            array(
                'user_id' => $user_id,
                'voucher_code' => $voucher_code,
                'voucher_type' => 'birthday',
                'amount' => 0,
                'percentage' => $percentage,
                'points_cost' => 0,
                'expires_at' => $expires_at
            )
        );
        
        // Envoyer un email d'anniversaire
        $this->send_birthday_email($user_id, $voucher_code, $percentage);
    }
    
    /**
     * Envoyer un email de félicitations pour un nouveau palier
     */
    private function send_tier_achievement_email($user_id, $tier) {
        if ($this->get_setting('email_notifications_enabled', '1') !== '1') return;
        
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $subject = "Félicitations ! Vous avez atteint le palier " . $tier->tier_name;
        $message = "Bonjour " . $user->display_name . ",\n\n";
        $message .= "Félicitations ! Vous venez d'atteindre le palier " . $tier->tier_name . " de notre programme de fidélité.\n\n";
        $message .= "Vos avantages : " . $tier->benefits . "\n\n";
        $message .= "Continuez vos achats pour débloquer encore plus d'avantages !\n\n";
        $message .= "L'équipe Newsaiige";
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Envoyer un email d'anniversaire
     */
    private function send_birthday_email($user_id, $voucher_code, $percentage) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $subject = "Joyeux anniversaire ! Votre cadeau vous attend";
        $message = "Joyeux anniversaire " . $user->display_name . " !\n\n";
        $message .= "Pour célébrer votre anniversaire, nous vous offrons une réduction de {$percentage}% sur votre prochaine commande.\n\n";
        $message .= "Code promo : {$voucher_code}\n";
        $message .= "Valable 30 jours.\n\n";
        $message .= "L'équipe Newsaiige vous souhaite une excellente journée !";
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Handler AJAX pour convertir les points en bon d'achat
     */
    public function convert_points_to_voucher() {
        if (!wp_verify_nonce($_POST['nonce'], 'newsaiige_loyalty_nonce')) {
            wp_die('Erreur de sécurité');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Utilisateur non connecté');
        }
        
        $user_id = get_current_user_id();
        $points_to_convert = intval($_POST['points_to_convert']);
        $min_points = intval($this->get_setting('min_points_conversion', '700'));
        
        if ($points_to_convert < $min_points) {
            wp_send_json_error("Minimum {$min_points} points requis pour la conversion");
        }
        
        $available_points = $this->get_user_points($user_id);
        if ($points_to_convert > $available_points) {
            wp_send_json_error('Points insuffisants');
        }
        
        // Calculer le montant du bon selon les règles personnalisées
        $voucher_amount = $this->calculate_voucher_amount($points_to_convert);
        if ($voucher_amount === false) {
            wp_send_json_error('Aucune règle de conversion trouvée pour ce nombre de points');
        }
        
        // Déduire les points
        if ($this->deduct_points($user_id, $points_to_convert, 'conversion')) {
            // Créer le bon d'achat
            if ($this->create_voucher($user_id, $voucher_amount, $points_to_convert, 'conversion')) {
                wp_send_json_success('Bon d\'achat créé avec succès !');
            } else {
                wp_send_json_error('Erreur lors de la création du bon d\'achat');
            }
        } else {
            wp_send_json_error('Erreur lors de la déduction des points');
        }
    }
    
    /**
     * Déduire des points d'un utilisateur
     */
    public function deduct_points($user_id, $points_to_deduct, $action_type = 'conversion') {
        global $wpdb;
        
        // Récupérer les points disponibles par ordre de date (FIFO)
        $points_entries = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->points_table} 
             WHERE user_id = %d AND points_available > 0 AND is_active = 1 
             AND (expires_at IS NULL OR expires_at > NOW())
             ORDER BY created_at ASC",
            $user_id
        ));
        
        $remaining_to_deduct = $points_to_deduct;
        
        foreach ($points_entries as $entry) {
            if ($remaining_to_deduct <= 0) break;
            
            $points_to_use = min($entry->points_available, $remaining_to_deduct);
            
            // Mettre à jour l'entrée
            $new_available = $entry->points_available - $points_to_use;
            $new_used = $entry->points_used + $points_to_use;
            
            $wpdb->update(
                $this->points_table,
                array(
                    'points_available' => $new_available,
                    'points_used' => $new_used
                ),
                array('id' => $entry->id)
            );
            
            $remaining_to_deduct -= $points_to_use;
        }
        
        // Enregistrer la transaction de déduction
        $wpdb->insert(
            $this->points_table,
            array(
                'user_id' => $user_id,
                'points_earned' => 0,
                'points_used' => $points_to_deduct,
                'points_available' => 0,
                'action_type' => $action_type,
                'description' => "Conversion de {$points_to_deduct} points en bon d'achat"
            )
        );
        
        return $remaining_to_deduct === 0;
    }
    
    /**
     * Calculer le montant du bon d'achat selon les règles personnalisées
     */
    public function calculate_voucher_amount($points_to_convert) {
        global $wpdb;
        
        // Récupérer la règle exacte ou la plus proche inférieure
        $rule = $wpdb->get_row($wpdb->prepare(
            "SELECT voucher_amount FROM {$this->conversion_rules_table} 
             WHERE points_required <= %d AND is_active = 1 
             ORDER BY points_required DESC LIMIT 1",
            $points_to_convert
        ));
        
        if ($rule) {
            return floatval($rule->voucher_amount);
        }
        
        return false;
    }
    
    /**
     * Obtenir toutes les règles de conversion actives
     */
    public function get_conversion_rules() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT * FROM {$this->conversion_rules_table} 
             WHERE is_active = 1 
             ORDER BY points_required ASC"
        );
    }
    
    /**
     * Obtenir les options de conversion disponibles pour un utilisateur
     */
    public function get_available_conversions($user_points) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->conversion_rules_table} 
             WHERE points_required <= %d AND is_active = 1 
             ORDER BY points_required DESC",
            $user_points
        ));
    }
    
    /**
     * Handler AJAX pour récupérer les données de fidélité de l'utilisateur
     */
    public function get_user_loyalty_data() {
        if (!is_user_logged_in()) {
            wp_send_json_error('Utilisateur non connecté');
        }
        
        $user_id = get_current_user_id();
        
        $data = array(
            'points_available' => $this->get_user_points($user_id),
            'points_lifetime' => $this->get_user_lifetime_points($user_id),
            'current_tier' => $this->get_user_tier($user_id),
            'vouchers' => $this->get_user_vouchers($user_id),
            'points_history' => $this->get_user_points_history($user_id),
            'next_tier' => $this->get_next_tier($user_id)
        );
        
        wp_send_json_success($data);
    }
    
    /**
     * Obtenir les bons d'achat d'un utilisateur
     */
    public function get_user_vouchers($user_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->vouchers_table} 
             WHERE user_id = %d AND is_used = 0 AND expires_at > NOW()
             ORDER BY created_at DESC",
            $user_id
        ));
    }
    
    /**
     * Obtenir l'historique des points d'un utilisateur
     */
    public function get_user_points_history($user_id, $limit = 20) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->points_table} 
             WHERE user_id = %d 
             ORDER BY created_at DESC LIMIT %d",
            $user_id, $limit
        ));
    }
    
    /**
     * Obtenir le prochain palier pour un utilisateur
     */
    public function get_next_tier($user_id) {
        global $wpdb;
        
        $current_points = $this->get_user_lifetime_points($user_id);
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tiers_table} 
             WHERE points_required > %d AND is_active = 1 
             ORDER BY points_required ASC LIMIT 1",
            $current_points
        ));
    }
}

// Initialiser le système
$newsaiige_loyalty = new NewsaiigeLoyaltySystem();

// Shortcode pour l'interface utilisateur
function newsaiige_loyalty_shortcode($atts) {
    $atts = shortcode_atts(array(
        'title' => 'Mon Programme de Fidélité',
        'subtitle' => 'Gagnez des points à chaque achat et profitez d\'avantages exclusifs.'
    ), $atts);
    
    // Vérifier si l'utilisateur est connecté
    if (!is_user_logged_in()) {
        return '<p>Vous devez être connecté pour accéder à cette page. <a href="' . wp_login_url() . '">Se connecter</a></p>';
    }
    
    global $newsaiige_loyalty;
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // Récupérer les données de l'utilisateur
    $points_available = $newsaiige_loyalty->get_user_points($user_id);
    $points_lifetime = $newsaiige_loyalty->get_user_lifetime_points($user_id);
    $current_tier = $newsaiige_loyalty->get_user_tier($user_id);
    $next_tier = $newsaiige_loyalty->get_next_tier($user_id);
    $vouchers = $newsaiige_loyalty->get_user_vouchers($user_id);
    $points_history = $newsaiige_loyalty->get_user_points_history($user_id, 10);
    
    // Enqueue les scripts nécessaires
    wp_enqueue_script('newsaiige-loyalty-js', '', array('jquery'), '1.0', true);
    wp_add_inline_script('newsaiige-loyalty-js', '
        const newsaiige_loyalty_ajax = {
            ajax_url: "' . admin_url('admin-ajax.php') . '",
            nonce: "' . wp_create_nonce('newsaiige_loyalty_nonce') . '"
        };
    ');
    
    ob_start();
    ?>

    <style>
    .newsaiige-loyalty-section {
        margin: 0 auto;
        padding: 60px 20px;
        font-family: 'Montserrat', sans-serif;
        border-radius: 20px;
        margin-top: 40px;
        margin-bottom: 40px;
    }

    .loyalty-header {
        text-align: left;
        margin-bottom: 50px;
    }

    .loyalty-title {
        font-size: 24px;
        font-weight: 700;
        color: #82897F;
        margin: 0 0 15px 0;
        letter-spacing: 1px;
    }

    .loyalty-subtitle {
        font-size: 16px;
        color: #000;
        margin: 0;
        font-weight: 400;
        line-height: 1.5;
    }

    .loyalty-container {
        background: white;
        border-radius: 15px;
        padding: 40px;
        margin-bottom: 30px;
    }

    .loyalty-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        margin-bottom: 40px;
    }

    .loyalty-card {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: 2px solid #e9ecef;
        border-radius: 15px;
        padding: 30px;
        position: relative;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        text-align: center;
    }

    .loyalty-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        border-color: #82897F;
    }

    .loyalty-card.points {
        border-left: 4px solid #82897F;
        background: linear-gradient(135deg, rgba(130, 137, 127, 0.1) 0%, rgba(130, 137, 127, 0.05) 100%);
    }

    .loyalty-card.tier {
        border-left: 4px solid #6c757d;
        background: linear-gradient(135deg, rgba(108, 117, 125, 0.1) 0%, rgba(108, 117, 125, 0.05) 100%);
    }

    .loyalty-card.vouchers {
        border-left: 4px solid #28a745;
        background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, rgba(40, 167, 69, 0.05) 100%);
    }

    .card-icon {
        font-size: 2.5rem;
        margin-bottom: 15px;
        opacity: 0.8;
    }

    .card-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 10px;
    }

    .card-value {
        font-size: 2rem;
        font-weight: 700;
        color: #82897F;
        margin-bottom: 10px;
    }

    .card-description {
        color: #666;
        font-size: 0.9rem;
    }

    .tier-progress {
        background: rgba(130, 137, 127, 0.1);
        border-radius: 10px;
        height: 8px;
        margin: 15px 0;
        overflow: hidden;
    }

    .tier-progress-bar {
        background: linear-gradient(90deg, #82897F, #6d7569);
        height: 100%;
        border-radius: 10px;
        transition: width 0.5s ease;
    }

    .tier-badge {
        display: inline-block;
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 10px 0;
    }

    .tier-bronze {
        background: linear-gradient(135deg, #cd7f32, #a0522d);
        color: white;
    }

    .tier-silver {
        background: linear-gradient(135deg, #c0c0c0, #808080);
        color: white;
    }

    .tier-gold {
        background: linear-gradient(135deg, #ffd700, #ffb347);
        color: #333;
    }

    .tier-platinum {
        background: linear-gradient(135deg, #e5e4e2, #b8b8b8);
        color: #333;
    }

    .vouchers-list {
        display: grid;
        gap: 15px;
        margin-top: 20px;
    }

    .voucher-item {
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.3s ease;
    }

    .voucher-item:hover {
        border-color: #28a745;
        transform: translateX(5px);
    }

    .voucher-info {
        flex-grow: 1;
    }

    .voucher-code {
        font-family: 'Courier New', monospace;
        font-weight: 700;
        color: #82897F;
        font-size: 1.1rem;
        margin-bottom: 5px;
    }

    .voucher-value {
        font-weight: 600;
        color: #28a745;
        margin-bottom: 3px;
    }

    .voucher-expiry {
        font-size: 0.85rem;
        color: #666;
    }

    .copy-btn {
        background: #82897F;
        color: white;
        border: none;
        border-radius: 5px;
        padding: 8px 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.85rem;
    }

    .copy-btn:hover {
        background: #6d7569;
        transform: scale(1.05);
    }

    .points-conversion {
        background: white;
        border-radius: 15px;
        padding: 30px;
        margin-top: 30px;
        border: 2px solid #e9ecef;
    }

    .conversion-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: #82897F;
        margin-bottom: 20px;
        text-align: center;
    }

    .conversion-options {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }

    .conversion-option {
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .conversion-option:hover {
        border-color: #82897F;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(130, 137, 127, 0.2);
    }

    .conversion-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .conversion-points {
        font-weight: 700;
        color: #82897F;
        font-size: 1.1rem;
    }

    .conversion-arrow {
        color: #666;
        font-size: 1.2rem;
    }

    .conversion-amount {
        font-weight: 700;
        color: #28a745;
        font-size: 1.2rem;
    }

    .conversion-select-btn {
        background: #82897F;
        color: white;
        border: none;
        border-radius: 8px;
        padding: 8px 15px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .conversion-select-btn:hover {
        background: #6d7569;
        transform: scale(1.05);
    }

    .conversion-form {
        display: flex;
        gap: 15px;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 10px;
        border: 2px solid #82897F;
    }

    .conversion-summary {
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
    }

    .cancel-btn {
        background: #6c757d;
        color: white;
        border: none;
        border-radius: 10px;
        padding: 12px 25px;
        cursor: pointer;
        font-weight: 600;
        font-size: 1rem;
        transition: all 0.3s ease;
        height: fit-content;
    }

    .cancel-btn:hover {
        background: #5a6268;
    }

    .conversion-message {
        text-align: center;
        padding: 20px;
        color: #666;
        font-size: 1.1rem;
    }

    .form-group {
        margin-bottom: 20px;
        min-width: 200px;
    }

    .form-label {
        display: block;
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
        font-size: 1rem;
    }

    .form-input {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        font-family: 'Montserrat', sans-serif;
        font-size: 1rem;
        transition: border-color 0.3s ease;
        box-sizing: border-box;
    }

    .form-input:focus {
        outline: none;
        border-color: #82897F;
    }

    .convert-btn {
        background: #82897F;
        color: white;
        border: none;
        border-radius: 10px;
        padding: 12px 25px;
        cursor: pointer;
        font-weight: 600;
        font-size: 1rem;
        transition: all 0.3s ease;
        height: fit-content;
    }

    .convert-btn:hover {
        background: #6d7569;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(130, 137, 127, 0.3);
    }

    .convert-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .points-history {
        background: white;
        border-radius: 15px;
        padding: 30px;
        margin-top: 30px;
        border: 2px solid #e9ecef;
    }

    .history-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: #82897F;
        margin-bottom: 25px;
        text-align: center;
    }

    .history-list {
        display: grid;
        gap: 10px;
    }

    .history-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #82897F;
    }

    .history-description {
        flex-grow: 1;
        color: #333;
        font-weight: 500;
    }

    .history-points {
        font-weight: 700;
        margin-right: 15px;
    }

    .history-points.earned {
        color: #28a745;
    }

    .history-points.used {
        color: #dc3545;
    }

    .history-date {
        font-size: 0.85rem;
        color: #666;
        min-width: 80px;
        text-align: right;
    }

    .no-data {
        text-align: center;
        padding: 40px 20px;
        color: #666;
    }

    .no-data-icon {
        font-size: 3rem;
        margin-bottom: 15px;
        opacity: 0.5;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .newsaiige-loyalty-section {
            padding: 40px 15px;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .loyalty-container,
        .points-conversion,
        .points-history {
            padding: 20px;
        }

        .loyalty-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .loyalty-title {
            font-size: 20px;
        }

        .loyalty-subtitle {
            font-size: 14px;
        }

        .conversion-form {
            flex-direction: column;
            align-items: stretch;
        }

        .form-group {
            min-width: auto;
        }

        .history-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }

        .history-date {
            text-align: left;
            min-width: auto;
        }
    }
    </style>

    <div class="newsaiige-loyalty-section">
        <div class="loyalty-header">
            <h2 class="loyalty-title"><?php echo esc_html($atts['title']); ?></h2>
            <p class="loyalty-subtitle"><?php echo esc_html($atts['subtitle']); ?></p>
        </div>

        <div class="loyalty-container">
            <div class="loyalty-grid">
                <!-- Carte Points -->
                <div class="loyalty-card points">
                    <div class="card-icon">🏆</div>
                    <h3 class="card-title">Mes points</h3>
                    <div class="card-value"><?php echo number_format($points_available); ?></div>
                    <p class="card-description">Points disponibles</p>
                    <p class="card-description">Total gagné: <?php echo number_format($points_lifetime); ?> points</p>
                </div>

                <!-- Carte Palier -->
                <div class="loyalty-card tier">
                    <div class="card-icon">⭐</div>
                    <h3 class="card-title">Mon palier</h3>
                    <?php if ($current_tier): ?>
                        <div class="tier-badge tier-<?php echo esc_attr($current_tier->tier_slug); ?>">
                            <?php echo esc_html($current_tier->tier_name); ?>
                        </div>
                        <p class="card-description"><?php echo esc_html($current_tier->benefits); ?></p>
                        
                        <?php if ($next_tier): 
                            $points_needed = $next_tier->points_required - $points_lifetime;
                            $progress = ($points_lifetime / $next_tier->points_required) * 100;
                        ?>
                            <div class="tier-progress">
                                <div class="tier-progress-bar" style="width: <?php echo min(100, $progress); ?>%;"></div>
                            </div>
                            <p class="card-description">
                                <?php echo $points_needed; ?> points pour atteindre <?php echo esc_html($next_tier->tier_name); ?>
                            </p>
                        <?php else: ?>
                            <p class="card-description">🎉 Palier maximum atteint !</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="card-description">Effectuez votre premier achat pour débuter</p>
                    <?php endif; ?>
                </div>

                <!-- Carte Bons d'achat -->
                <div class="loyalty-card vouchers">
                    <div class="card-icon">🎁</div>
                    <h3 class="card-title">Mes bons d'achat</h3>
                    <div class="card-value"><?php echo count($vouchers); ?></div>
                    <p class="card-description">Bons disponibles</p>
                </div>
            </div>

            <?php if (!empty($vouchers)): ?>
            <div class="vouchers-list">
                <?php foreach ($vouchers as $voucher): ?>
                <div class="voucher-item">
                    <div class="voucher-info">
                        <div class="voucher-code"><?php echo esc_html($voucher->voucher_code); ?></div>
                        <div class="voucher-value">
                            <?php if ($voucher->percentage > 0): ?>
                                <?php echo $voucher->percentage; ?>% de réduction
                            <?php else: ?>
                                <?php echo number_format($voucher->amount, 2); ?>€ de réduction
                            <?php endif; ?>
                        </div>
                        <div class="voucher-expiry">
                            Expire le <?php echo date('d/m/Y', strtotime($voucher->expires_at)); ?>
                        </div>
                    </div>
                    <button class="copy-btn" onclick="copyVoucherCode('<?php echo esc_js($voucher->voucher_code); ?>')">
                        Copier
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Conversion de points -->
        <?php 
        $min_points = intval($newsaiige_loyalty->get_setting('min_points_conversion', '700'));
        $available_conversions = $newsaiige_loyalty->get_available_conversions($points_available);
        ?>
        <?php if ($points_available >= $min_points && !empty($available_conversions)): ?>
        <div class="points-conversion">
            <h3 class="conversion-title">Convertir mes points en bon d'achat</h3>
            
            <!-- Options de conversion disponibles -->
            <div class="conversion-options">
                <?php foreach ($available_conversions as $conversion): ?>
                <div class="conversion-option" data-points="<?php echo $conversion->points_required; ?>" 
                     data-amount="<?php echo $conversion->voucher_amount; ?>">
                    <div class="conversion-info">
                        <span class="conversion-points"><?php echo number_format($conversion->points_required); ?> points</span>
                        <span class="conversion-arrow">→</span>
                        <span class="conversion-amount"><?php echo number_format($conversion->voucher_amount, 2); ?>€</span>
                    </div>
                    <button type="button" class="conversion-select-btn" onclick="selectConversion(<?php echo $conversion->points_required; ?>, <?php echo $conversion->voucher_amount; ?>)">
                        Choisir
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Formulaire de conversion -->
            <form class="conversion-form" id="conversionForm" style="display: none;">
                <input type="hidden" id="selected_points" name="points_to_convert">
                <div class="conversion-summary">
                    <span id="conversion_display"></span>
                </div>
                <button type="submit" class="convert-btn">Confirmer la conversion</button>
                <button type="button" class="cancel-btn" onclick="cancelConversion()">Annuler</button>
            </form>
        </div>
        <?php elseif ($points_available < $min_points): ?>
        <div class="points-conversion">
            <h3 class="conversion-title">Conversion de points</h3>
            <p class="conversion-message">Vous avez besoin d'au moins <?php echo $min_points; ?> points pour effectuer une conversion.</p>
        </div>
        <?php endif; ?>

        <!-- Historique des points -->
        <?php if (!empty($points_history)): ?>
        <div class="points-history">
            <h3 class="history-title">Historique de mes points</h3>
            <div class="history-list">
                <?php foreach ($points_history as $history): ?>
                <div class="history-item">
                    <div class="history-description">
                        <?php echo esc_html($history->description ?: $history->action_type); ?>
                    </div>
                    <div class="history-points <?php echo $history->points_earned > 0 ? 'earned' : 'used'; ?>">
                        <?php if ($history->points_earned > 0): ?>
                            +<?php echo $history->points_earned; ?>
                        <?php else: ?>
                            -<?php echo $history->points_used; ?>
                        <?php endif; ?>
                    </div>
                    <div class="history-date">
                        <?php echo date('d/m/Y', strtotime($history->created_at)); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
    // Fonction pour sélectionner une option de conversion
    function selectConversion(points, amount) {
        document.getElementById('selected_points').value = points;
        document.getElementById('conversion_display').textContent = points + ' points → ' + amount.toFixed(2) + '€';
        
        // Cacher les options et afficher le formulaire
        document.querySelector('.conversion-options').style.display = 'none';
        document.getElementById('conversionForm').style.display = 'flex';
    }
    
    // Fonction pour annuler la conversion
    function cancelConversion() {
        document.querySelector('.conversion-options').style.display = 'grid';
        document.getElementById('conversionForm').style.display = 'none';
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        
        // Soumission du formulaire de conversion
        const conversionForm = document.getElementById('conversionForm');
        if (conversionForm) {
            conversionForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const points = parseInt(document.getElementById('selected_points').value);
                if (!points) {
                    alert('Erreur: aucune conversion sélectionnée');
                    return;
                }
                
                const formData = new FormData();
                formData.append('action', 'loyalty_convert_points');
                formData.append('nonce', newsaiige_loyalty_ajax.nonce);
                formData.append('points_to_convert', points);
                
                const submitBtn = this.querySelector('.convert-btn');
                const cancelBtn = this.querySelector('.cancel-btn');
                submitBtn.disabled = true;
                cancelBtn.disabled = true;
                submitBtn.textContent = 'Conversion...';
                
                fetch(newsaiige_loyalty_ajax.ajax_url, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Bon d\'achat créé avec succès !');
                        location.reload();
                    } else {
                        alert('Erreur: ' + data.data);
                    }
                })
                .catch(error => {
                    alert('Erreur de connexion');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    cancelBtn.disabled = false;
                    submitBtn.textContent = 'Confirmer la conversion';
                });
            });
        }
        
        // Animations au survol
        const loyaltyCards = document.querySelectorAll('.loyalty-card');
        loyaltyCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    });
    
    function copyVoucherCode(code) {
        navigator.clipboard.writeText(code).then(function() {
            alert('Code copié: ' + code);
        }).catch(function() {
            // Fallback pour les navigateurs plus anciens
            const textArea = document.createElement('textarea');
            textArea.value = code;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            alert('Code copié: ' + code);
        });
    }
    </script>

    <?php
    return ob_get_clean();
}

add_shortcode('newsaiige_loyalty', 'newsaiige_loyalty_shortcode');
?>