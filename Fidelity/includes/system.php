<?php
/**
 * Système de fidélité NewSaiige - Version corrigée et sécurisée
 * Évite les conflits WordPress et WooCommerce
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe principale du système de fidélité - Version sécurisée
 */
class NewsaiigeLoyaltySystemSafe {
    
    private $points_table;
    private $tiers_table;
    private $vouchers_table;
    private $user_tiers_table;
    private $settings_table;
    private $conversion_rules_table;
    private static $instance = null;
    
    /**
     * Instance unique (Singleton)
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructeur privé (Singleton)
     */
    private function __construct() {
        global $wpdb;
        
        // Initialiser les noms de tables seulement si WordPress est chargé
        if (isset($wpdb) && $wpdb instanceof wpdb) {
            $this->points_table = $wpdb->prefix . 'newsaiige_loyalty_points';
            $this->tiers_table = $wpdb->prefix . 'newsaiige_loyalty_tiers';
            $this->vouchers_table = $wpdb->prefix . 'newsaiige_loyalty_vouchers';
            $this->user_tiers_table = $wpdb->prefix . 'newsaiige_loyalty_user_tiers';
            $this->settings_table = $wpdb->prefix . 'newsaiige_loyalty_settings';
            $this->conversion_rules_table = $wpdb->prefix . 'newsaiige_loyalty_conversion_rules';
            
            // Initialiser les hooks de manière sécurisée
            $this->init_safe_hooks();
        }
    }
    
    /**
     * Initialiser les hooks de manière sécurisée
     */
    private function init_safe_hooks() {
        // Hooks WordPress sécurisés uniquement
        add_action('wp_ajax_loyalty_get_user_stats', array($this, 'ajax_get_user_stats'));
        add_action('wp_ajax_loyalty_convert_points', array($this, 'ajax_convert_points'));
        
        // Hook pour les tâches quotidiennes (sécurisé)
        add_action('newsaiige_daily_birthday_check', array($this, 'check_user_birthdays'));
        add_action('newsaiige_daily_cleanup', array($this, 'cleanup_expired_data'));
        
        // WooCommerce hooks - SEULEMENT si WooCommerce est disponible et qu'on n'est pas en conflit
        if (class_exists('WooCommerce') && !$this->is_processing_wc_action()) {
            add_action('woocommerce_order_status_completed', array($this, 'process_order_points'), 10, 1);
        }
    }
    
    /**
     * Vérifier si on est déjà en train de traiter une action WooCommerce (éviter les boucles)
     */
    private function is_processing_wc_action() {
        return defined('NEWSAIIGE_PROCESSING_WC') || did_action('woocommerce_cart_calculate_fees') > 0;
    }
    
    /**
     * Vérifier qu'une table existe avant de l'utiliser
     */
    private function table_exists($table_name) {
        global $wpdb;
        
        if (!isset($wpdb)) return false;
        
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s", 
            $table_name
        ));
        
        return $table_exists === $table_name;
    }
    
    /**
     * Obtenir une configuration - Version sécurisée
     */
    public function get_setting($key, $default = '') {
        if (!$this->table_exists($this->settings_table)) {
            return $default;
        }
        
        global $wpdb;
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM {$this->settings_table} WHERE setting_key = %s",
            $key
        ));
        
        return $result !== null ? $result : $default;
    }
    
    /**
     * Ajouter des points - Version sécurisée
     */
    public function add_points($user_id, $points, $order_id = null, $action_type = 'manual', $description = '') {
        if (!$this->table_exists($this->points_table) || $points <= 0) {
            return false;
        }
        
        global $wpdb;
        
        // Calculer l'expiration (6 mois par défaut)
        $expiry_days = intval($this->get_setting('points_expiry_days', 183));
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_days} days"));
        
        $result = $wpdb->insert(
            $this->points_table,
            array(
                'user_id' => $user_id,
                'points_earned' => $points,
                'points_available' => $points,
                'order_id' => $order_id,
                'action_type' => $action_type,
                'description' => $description,
                'expires_at' => $expires_at,
                'is_active' => 1
            ),
            array('%d', '%d', '%d', '%d', '%s', '%s', '%s', '%d')
        );
        
        if ($result) {
            // Vérifier si l'utilisateur mérite une promotion de palier
            $this->check_tier_upgrade($user_id);
            
            // Hook pour les actions personnalisées
            do_action('newsaiige_points_added', $user_id, $points, $order_id);
        }
        
        return $result !== false;
    }
    
    /**
     * Obtenir les points disponibles d'un utilisateur - Version sécurisée
     */
    public function get_user_points($user_id) {
        if (!$this->table_exists($this->points_table)) {
            return 0;
        }
        
        global $wpdb;
        
        $points = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(points_available) 
             FROM {$this->points_table} 
             WHERE user_id = %d 
             AND is_active = 1 
             AND (expires_at IS NULL OR expires_at > NOW())",
            $user_id
        ));
        
        return intval($points);
    }
    
    /**
     * Obtenir le total des points gagnés (à vie) - Version sécurisée
     */
    public function get_user_lifetime_points($user_id) {
        if (!$this->table_exists($this->points_table)) {
            return 0;
        }
        
        global $wpdb;
        
        $points = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(points_earned) FROM {$this->points_table} WHERE user_id = %d",
            $user_id
        ));
        
        return intval($points);
    }
    
    /**
     * Traiter les points d'une commande - Version sécurisée
     */
    public function process_order_points($order_id) {
        // Éviter les traitements multiples
        if (get_post_meta($order_id, '_newsaiige_loyalty_processed', true)) {
            return;
        }
        
        // Marquer comme en cours de traitement
        define('NEWSAIIGE_PROCESSING_WC', true);
        
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        $user_id = $order->get_user_id();
        if (!$user_id) return;
        
        // Vérifier si l'utilisateur a un abonnement actif (si requis)
        if ($this->get_setting('subscription_required', '1') === '1') {
            if (!$this->has_active_subscription($user_id)) {
                return;
            }
        }
        
        // Calculer les points (1 point par euro dépensé par défaut)
        $order_total = $order->get_total();
        $points_per_euro = floatval($this->get_setting('points_per_euro', 1));
        $points_earned = floor($order_total * $points_per_euro);
        
        if ($points_earned > 0) {
            $description = sprintf('Points gagnés pour la commande #%s', $order_id);
            
            if ($this->add_points($user_id, $points_earned, $order_id, 'order', $description)) {
                // Marquer la commande comme traitée
                update_post_meta($order_id, '_newsaiige_loyalty_processed', time());
                
                // Ajouter une note à la commande
                $order->add_order_note(
                    sprintf('Programme de fidélité : %d points ajoutés au compte client.', $points_earned)
                );
            }
        }
    }
    
    /**
     * Vérifier si un utilisateur a un abonnement WPS Subscriptions actif
     */
    public function has_active_subscription($user_id) {
        global $wpdb;
        
        // PRIORITÉ 1 : Vérifier dans wc_orders (HPOS activé - WPS Subscriptions)
        $hpos_subscription = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$wpdb->prefix}wc_orders
             WHERE type = 'wps_subscriptions'
             AND customer_id = %d
             AND status IN ('wc-active', 'wc-pending-cancel', 'wc-wps_renewal', 'active')
             LIMIT 1",
            $user_id
        ));
        
        if ($hpos_subscription > 0) {
            error_log("has_active_subscription: User {$user_id} a un abonnement WPS actif (HPOS)");
            return true;
        }
        
        // PRIORITÉ 2 : Vérifier dans wp_posts (HPOS non activé - WPS Subscriptions)
        $post_subscription = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'wps_subscriptions'
             AND pm.meta_key = '_customer_user'
             AND pm.meta_value = %d
             AND p.post_status IN ('wc-active', 'wc-pending-cancel', 'wc-wps_renewal', 'active')
             LIMIT 1",
            $user_id
        ));
        
        if ($post_subscription > 0) {
            error_log("has_active_subscription: User {$user_id} a un abonnement WPS actif (wp_posts)");
            return true;
        }
        
        // PRIORITÉ 3 : Vérifier les commandes shop_order avec statut wc-processing (HPOS)
        $hpos_processing_order = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$wpdb->prefix}wc_orders
             WHERE type = 'shop_order'
             AND customer_id = %d
             AND status = 'wc-processing'
             LIMIT 1",
            $user_id
        ));
        
        if ($hpos_processing_order > 0) {
            error_log("has_active_subscription: User {$user_id} a une commande en cours (HPOS shop_order wc-processing)");
            return true;
        }
        
        // PRIORITÉ 4 : Vérifier les commandes shop_order avec statut wc-processing (wp_posts)
        $post_processing_order = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'shop_order'
             AND pm.meta_key = '_customer_user'
             AND pm.meta_value = %d
             AND p.post_status = 'wc-processing'
             LIMIT 1",
            $user_id
        ));
        
        if ($post_processing_order > 0) {
            error_log("has_active_subscription: User {$user_id} a une commande en cours (wp_posts shop_order wc-processing)");
            return true;
        }
        
        // PRIORITÉ 3 : Méthode de secours avec les commandes classiques
        $subscription_category = $this->get_setting('subscription_category_slug', 'soins');
        
        if (empty($subscription_category)) {
            return true; // Si pas de catégorie définie, considérer comme valide
        }
        
        // Rechercher les commandes avec des produits de la catégorie soins
        $orders = wc_get_orders(array(
            'customer' => $user_id,
            'status' => array('completed', 'processing', 'on-hold'),
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        foreach ($orders as $order) {
            $order_date = $order->get_date_created();
            if (!$order_date) {
                continue;
            }
            
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if (!$product) {
                    continue;
                }
                
                // Pour les variations, vérifier le produit parent
                $product_id = $product->get_id();
                if ($product->is_type('variation')) {
                    $product_id = $product->get_parent_id();
                }
                
                // Vérifier si le produit est dans la catégorie "soins"
                if (has_term($subscription_category, 'product_cat', $product_id)) {
                    // Calculer la durée de l'abonnement basée sur les attributs de variation
                    $subscription_duration_days = 30; // Par défaut 1 mois
                    
                    // Si c'est une variation, récupérer la durée depuis les attributs
                    if ($product->is_type('variation')) {
                        $attributes = $product->get_variation_attributes();
                        
                        // Chercher l'attribut de durée (ex: "1-mois", "3-mois", "6-mois")
                        foreach ($attributes as $key => $value) {
                            if (stripos($key, 'duree') !== false || stripos($key, 'duration') !== false || stripos($key, 'mois') !== false) {
                                // Extraire le nombre de mois
                                if (preg_match('/(\d+)/', $value, $matches)) {
                                    $months = intval($matches[1]);
                                    $subscription_duration_days = $months * 30;
                                }
                                break;
                            }
                        }
                        
                        // Alternative : vérifier dans le nom du produit
                        $product_name = strtolower($item->get_name());
                        if (preg_match('/(\d+)\s*mois/', $product_name, $matches)) {
                            $months = intval($matches[1]);
                            $subscription_duration_days = $months * 30;
                        }
                    }
                    
                    // Calculer la date d'expiration de l'abonnement
                    $order_timestamp = $order_date->getTimestamp();
                    $expiration_timestamp = $order_timestamp + ($subscription_duration_days * 24 * 60 * 60);
                    $current_timestamp = current_time('timestamp');
                    
                    // Vérifier si l'abonnement est toujours actif
                    if ($current_timestamp <= $expiration_timestamp) {
                        error_log("has_active_subscription: User {$user_id} a un abonnement actif (commande #{$order->get_id()}, expire le " . date('Y-m-d', $expiration_timestamp) . ")");
                        return true;
                    }
                }
            }
        }
        
        error_log("has_active_subscription: User {$user_id} n'a pas d'abonnement actif");
        return false;
    }
    
    /**
     * Vérifier les promotions de palier - Version sécurisée (basé sur points disponibles)
     */
    public function check_tier_upgrade($user_id) {
        if (!$this->table_exists($this->tiers_table) || !$this->table_exists($this->user_tiers_table)) {
            error_log("check_tier_upgrade: Tables manquantes pour user {$user_id}");
            return false;
        }
        
        $available_points = $this->get_user_points($user_id);
        
        error_log("check_tier_upgrade: User {$user_id} a {$available_points} points disponibles");
        
        global $wpdb;
        
        // Trouver le palier approprié basé sur les points disponibles
        $new_tier = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tiers_table} 
             WHERE points_required <= %d 
             AND is_active = 1 
             ORDER BY points_required DESC 
             LIMIT 1",
            $available_points
        ));
        
        if (!$new_tier) {
            error_log("check_tier_upgrade: Aucun palier trouvé pour {$available_points} points (user {$user_id})");
            return false;
        }
        
        error_log("check_tier_upgrade: Palier trouvé pour user {$user_id}: {$new_tier->tier_name} (ID: {$new_tier->id})");
        
        // Vérifier le palier actuel
        $current_tier_id = $wpdb->get_var($wpdb->prepare(
            "SELECT tier_id FROM {$this->user_tiers_table} 
             WHERE user_id = %d AND is_current = 1",
            $user_id
        ));
        
        error_log("check_tier_upgrade: Palier actuel user {$user_id}: " . ($current_tier_id ? $current_tier_id : 'AUCUN'));
        
        if ($current_tier_id != $new_tier->id) {
            // Désactiver l'ancien palier
            $wpdb->update(
                $this->user_tiers_table,
                array('is_current' => 0),
                array('user_id' => $user_id)
            );
            
            // Activer le nouveau palier
            $result = $wpdb->insert(
                $this->user_tiers_table,
                array(
                    'user_id' => $user_id,
                    'tier_id' => $new_tier->id,
                    'is_current' => 1,
                    'achieved_at' => current_time('mysql')
                )
            );
            
            if ($result) {
                error_log("✅ check_tier_upgrade: User {$user_id} promu à {$new_tier->tier_name} (ID: {$new_tier->id})");
                
                // Hook personnalisé pour la promotion
                do_action('newsaiige_tier_upgrade', $user_id, $new_tier);
                
                return true;
            } else {
                error_log("❌ check_tier_upgrade: ERREUR lors de l'insertion du palier pour user {$user_id}: " . $wpdb->last_error);
            }
        } else {
            error_log("check_tier_upgrade: User {$user_id} déjà au bon palier ({$new_tier->tier_name})");
        }
        
        return false;
    }
    
    /**
     * AJAX pour récupérer les statistiques utilisateur
     */
    public function ajax_get_user_stats() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'newsaiige_loyalty_nonce') || !is_user_logged_in()) {
            wp_send_json_error('Accès non autorisé');
        }
        
        $user_id = get_current_user_id();
        
        $data = array(
            'points_available' => $this->get_user_points($user_id),
            'points_lifetime' => $this->get_user_lifetime_points($user_id),
            'current_tier' => $this->get_user_tier($user_id),
            'vouchers' => $this->get_user_vouchers($user_id)
        );
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX pour convertir des points
     */
    public function ajax_convert_points() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'newsaiige_loyalty_nonce') || !is_user_logged_in()) {
            wp_send_json_error('Accès non autorisé');
        }
        
        $user_id = get_current_user_id();
        $points_to_convert = intval($_POST['points'] ?? 0);
        
        if ($points_to_convert <= 0) {
            wp_send_json_error('Nombre de points invalide');
        }
        
        $available_points = $this->get_user_points($user_id);
        
        if ($points_to_convert > $available_points) {
            wp_send_json_error('Points insuffisants');
        }
        
        // Ici on ajouterait la logique de conversion
        wp_send_json_success('Conversion réussie');
    }
    
    /**
     * Nettoyer les données expirées - Version sécurisée
     */
    public function cleanup_expired_data() {
        if (!$this->table_exists($this->points_table)) {
            return;
        }
        
        global $wpdb;
        
        // Désactiver les points expirés
        $wpdb->update(
            $this->points_table,
            array('is_active' => 0),
            array('is_active' => 1),
            array('%d'),
            array('%d')
        );
        
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->points_table} 
             SET is_active = 0 
             WHERE expires_at IS NOT NULL 
             AND expires_at < NOW() 
             AND is_active = 1"
        ));
    }
    
    /**
     * Méthodes stub pour compatibilité
     */
    public function get_user_tier($user_id) { return null; }
    public function get_user_vouchers($user_id) { return array(); }
    public function check_user_birthdays() { return true; }
}

// Initialiser le système de manière sécurisée
function newsaiige_loyalty_system_init() {
    return NewsaiigeLoyaltySystemSafe::get_instance();
}

// Démarrer le système
add_action('plugins_loaded', 'newsaiige_loyalty_system_init', 20);

// Variable globale pour compatibilité
global $newsaiige_loyalty;
$newsaiige_loyalty = NewsaiigeLoyaltySystemSafe::get_instance();
?>