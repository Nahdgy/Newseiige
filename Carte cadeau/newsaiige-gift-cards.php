<?php
/**
 * NewSaiige Gift Cards - Main Plugin File
 * Plugin principal pour le système de cartes cadeaux complet
 * 
 * @package NewSaiige_Gift_Cards
 * @version 1.0.0
 * @author NewSaiige
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe principale du plugin de cartes cadeaux
 */
class NewSaiige_Gift_Cards {
    
    /**
     * Version du plugin
     */
    const VERSION = '1.0.0';
    
    /**
     * Instance unique du plugin
     */
    private static $instance = null;
    
    /**
     * Obtenir l'instance unique du plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructeur privé pour le pattern Singleton
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialiser le plugin
     */
    private function init() {
        // Vérifier les prérequis
        add_action('admin_init', array($this, 'check_requirements'));
        
        // Charger les composants
        add_action('plugins_loaded', array($this, 'load_components'));
        
        // Hooks d'activation/désactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Scripts et styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX hooks
        $this->register_ajax_hooks();
        
        // Cron jobs
        add_action('init', array($this, 'schedule_cron_jobs'));
        
        // Nettoyage automatique
        add_action('newsaiige_cleanup_expired_cards', array($this, 'cleanup_expired_cards'));
    }
    
    /**
     * Vérifier les prérequis du plugin
     */
    public function check_requirements() {
        $errors = array();
        
        // Vérifier WordPress
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            $errors[] = 'WordPress 5.0 ou supérieur requis';
        }
        
        // Vérifier PHP
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $errors[] = 'PHP 7.4 ou supérieur requis';
        }
        
        // Vérifier WooCommerce
        if (!class_exists('WooCommerce')) {
            $errors[] = 'WooCommerce doit être installé et activé';
        }
        
        // Vérifier la compatibilité HPOS
        $this->check_hpos_compatibility();
        
        // Afficher les erreurs si nécessaire
        if (!empty($errors)) {
            add_action('admin_notices', function() use ($errors) {
                echo '<div class="notice notice-error"><p><strong>NewSaiige Gift Cards :</strong> ' . implode(', ', $errors) . '</p></div>';
            });
            return false;
        }
        
        return true;
    }
    
    /**
     * Charger les composants du plugin
     */
    public function load_components() {
        if (!$this->check_requirements()) {
            return;
        }
        
        // Charger les fichiers principaux
        $this->load_file('gift-cards.php');
        $this->load_file('gift-card-validator.php');
        
        // Charger l'admin en dernier et vérifier qu'il est chargé
        if (is_admin()) {
            $this->load_file('gift-cards-admin.php');
            
            // Vérifier que la fonction admin existe
            if (!function_exists('newsaiige_gift_cards_admin_menu')) {
                error_log('NewSaiige Gift Cards: Fonction admin menu non trouvée après chargement');
                // Fallback: créer le menu directement
                add_action('admin_menu', array($this, 'create_fallback_menu'));
            }
        }
        
        // Initialiser les composants
        $this->init_components();
    }
    
    /**
     * Charger un fichier du plugin
     */
    private function load_file($filename) {
        $file_path = plugin_dir_path(__FILE__) . $filename;
        if (file_exists($file_path)) {
            require_once $file_path;
        } else {
            error_log("NewSaiige Gift Cards: Fichier manquant - $filename");
        }
    }
    
    /**
     * Initialiser les composants
     */
    private function init_components() {
        // Créer la table si nécessaire
        $this->maybe_create_tables();
        
        // Nettoyer les cartes expirées
        $this->maybe_cleanup_expired();
    }
    
    /**
     * Enregistrer les hooks AJAX
     */
    private function register_ajax_hooks() {
        // Actions pour les utilisateurs connectés et non connectés
        $ajax_actions = array(
            'process_gift_card',
            'validate_gift_card_code',
            'get_gift_card_details',
            'resend_gift_card_email',
            'mark_gift_card_used'
        );
        
        foreach ($ajax_actions as $action) {
            add_action("wp_ajax_$action", array($this, 'handle_ajax_' . $action));
            add_action("wp_ajax_nopriv_$action", array($this, 'handle_ajax_' . $action));
        }
    }
    
    /**
     * Programmer les tâches cron
     */
    public function schedule_cron_jobs() {
        if (!wp_next_scheduled('newsaiige_cleanup_expired_cards')) {
            wp_schedule_event(time(), 'daily', 'newsaiige_cleanup_expired_cards');
        }
    }
    
    /**
     * Enqueue des scripts frontend
     */
    public function enqueue_scripts() {
        // Styles globaux pour les cartes cadeaux
        wp_add_inline_style('wp-block-library', '
            .newsaiige-gift-card-button {
                background: linear-gradient(45deg, #82897F, #9EA49D);
                color: white !important;
                padding: 15px 30px;
                border-radius: 25px;
                text-decoration: none !important;
                font-weight: 600;
                display: inline-block;
                transition: all 0.3s ease;
                border: none;
                cursor: pointer;
            }
            .newsaiige-gift-card-button:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 25px rgba(130, 137, 127, 0.3);
                color: white !important;
            }
        ');
    }
    
    /**
     * Enqueue des scripts admin
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'newsaiige-gift-cards') !== false) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('newsaiige-admin-js', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), self::VERSION, true);
            wp_enqueue_style('newsaiige-admin-css', plugin_dir_url(__FILE__) . 'assets/admin.css', array(), self::VERSION);
        }
    }
    
    /**
     * Activation du plugin
     */
    public function activate() {
        // Créer les tables
        $this->create_tables();
        
        // Programmer les tâches cron
        $this->schedule_cron_jobs();
        
        // Créer les pages par défaut
        $this->create_default_pages();
        
        // Flush les règles de réécriture
        flush_rewrite_rules();
        
        // Log de l'activation
        error_log('NewSaiige Gift Cards: Plugin activé avec succès');
    }
    
    /**
     * Désactivation du plugin
     */
    public function deactivate() {
        // Supprimer les tâches cron
        wp_clear_scheduled_hook('newsaiige_cleanup_expired_cards');
        wp_clear_scheduled_hook('newsaiige_send_gift_card_emails_hook');
        
        // Flush les règles de réécriture
        flush_rewrite_rules();
        
        // Log de la désactivation
        error_log('NewSaiige Gift Cards: Plugin désactivé');
    }
    
    /**
     * Créer les tables de base de données
     */
    private function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'newsaiige_gift_cards';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            code varchar(20) NOT NULL UNIQUE,
            amount decimal(10,2) NOT NULL,
            quantity int(5) NOT NULL DEFAULT 1,
            total_amount decimal(10,2) NOT NULL,
            buyer_name varchar(255) NOT NULL,
            buyer_email varchar(255) NOT NULL,
            recipient_type enum('self','other') NOT NULL DEFAULT 'other',
            recipient_name varchar(255),
            recipient_email varchar(255),
            personal_message text,
            delivery_date date,
            status enum('pending','paid','sent','used','expired') NOT NULL DEFAULT 'pending',
            order_id int(11),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            sent_at datetime,
            used_at datetime,
            expires_at datetime,
            PRIMARY KEY (id),
            KEY code (code),
            KEY status (status),
            KEY order_id (order_id),
            KEY created_at (created_at),
            KEY expires_at (expires_at),
            KEY buyer_email (buyer_email),
            KEY recipient_email (recipient_email),
            KEY delivery_date (delivery_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Ajouter des contraintes si la table est créée
        $wpdb->query("ALTER TABLE $table_name 
            ADD CONSTRAINT chk_amount CHECK (amount >= 10 AND amount <= 1000),
            ADD CONSTRAINT chk_quantity CHECK (quantity >= 1 AND quantity <= 10)
        ");
        
        // Mettre à jour la version de la base
        update_option('newsaiige_gift_cards_db_version', self::VERSION);
    }
    
    /**
     * Vérifier et créer les tables si nécessaire
     */
    private function maybe_create_tables() {
        $db_version = get_option('newsaiige_gift_cards_db_version', '0');
        
        if (version_compare($db_version, self::VERSION, '<')) {
            $this->create_tables();
        }
    }
    
    /**
     * Créer les pages par défaut
     */
    private function create_default_pages() {
        // Page d'achat de cartes cadeaux
        $gift_card_page = get_page_by_title('Cartes Cadeaux');
        if (!$gift_card_page) {
            wp_insert_post(array(
                'post_title' => 'Cartes Cadeaux',
                'post_content' => '[newsaiige_gift_cards title="Offrir une Carte Cadeau NewSaiige" subtitle="Faites plaisir à vos proches avec une expérience unique"]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => 'cartes-cadeaux'
            ));
        }
        
        // Page de validation de cartes cadeaux
        $validator_page = get_page_by_title('Vérifier ma Carte Cadeau');
        if (!$validator_page) {
            wp_insert_post(array(
                'post_title' => 'Vérifier ma Carte Cadeau',
                'post_content' => '[newsaiige_gift_card_validator title="Vérifier votre Carte Cadeau" subtitle="Entrez votre code pour connaître le solde et la validité"]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => 'verifier-carte-cadeau'
            ));
        }
    }
    
    /**
     * Nettoyer les cartes expirées
     */
    public function cleanup_expired_cards() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'newsaiige_gift_cards';
        
        // Marquer les cartes expirées
        $updated = $wpdb->query("
            UPDATE $table_name 
            SET status = 'expired' 
            WHERE status IN ('paid', 'sent') 
            AND expires_at < NOW()
        ");
        
        if ($updated) {
            error_log("NewSaiige Gift Cards: $updated cartes marquées comme expirées");
        }
        
        // Supprimer les cartes en attente anciennes (plus de 30 jours)
        $deleted = $wpdb->query("
            DELETE FROM $table_name 
            WHERE status = 'pending' 
            AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        if ($deleted) {
            error_log("NewSaiige Gift Cards: $deleted cartes en attente supprimées");
        }
    }
    
    /**
     * Vérifier et nettoyer si nécessaire
     */
    private function maybe_cleanup_expired() {
        $last_cleanup = get_option('newsaiige_last_cleanup', 0);
        
        // Nettoyer si ça fait plus de 24h
        if (time() - $last_cleanup > DAY_IN_SECONDS) {
            $this->cleanup_expired_cards();
            update_option('newsaiige_last_cleanup', time());
        }
    }
    
    /**
     * Gestionnaire AJAX générique
     */
    public function __call($method, $args) {
        if (strpos($method, 'handle_ajax_') === 0) {
            $action = str_replace('handle_ajax_', '', $method);
            
            // Rediriger vers la fonction appropriée si elle existe
            $function_name = 'newsaiige_' . $action;
            if (function_exists($function_name)) {
                call_user_func($function_name);
            } else {
                wp_send_json_error('Action non trouvée: ' . $action);
            }
        }
    }
    
    /**
     * Obtenir les statistiques globales
     */
    public static function get_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'newsaiige_gift_cards';
        
        return $wpdb->get_row("
            SELECT 
                COUNT(*) as total_cards,
                SUM(CASE WHEN status IN ('paid', 'sent', 'used') THEN total_amount ELSE 0 END) as total_revenue,
                SUM(CASE WHEN status IN ('paid', 'sent') THEN 1 ELSE 0 END) as active_cards,
                SUM(CASE WHEN status = 'used' THEN 1 ELSE 0 END) as used_cards,
                SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired_cards,
                AVG(CASE WHEN status IN ('paid', 'sent', 'used') THEN amount ELSE NULL END) as average_amount
            FROM $table_name
        ", ARRAY_A);
    }
    
    /**
     * Obtenir une carte cadeau par son code
     */
    public static function get_card_by_code($code) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'newsaiige_gift_cards';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE gift_card_code = %s",
            $code
        ));
    }
    
    /**
     * Valider une carte cadeau
     */
    public static function validate_card($code) {
        $card = self::get_card_by_code($code);
        
        if (!$card) {
            return array('valid' => false, 'message' => 'Code introuvable');
        }
        
        if ($card->status === 'used') {
            return array('valid' => false, 'message' => 'Carte déjà utilisée', 'card' => $card);
        }
        
        if (strtotime($card->expires_at) < time()) {
            return array('valid' => false, 'message' => 'Carte expirée', 'card' => $card);
        }
        
        if (!in_array($card->status, ['paid', 'sent'])) {
            return array('valid' => false, 'message' => 'Carte non active', 'card' => $card);
        }
        
        return array('valid' => true, 'message' => 'Carte valide', 'card' => $card);
    }
    
    /**
     * Utiliser une carte cadeau
     */
    public static function use_card($code) {
        $validation = self::validate_card($code);
        
        if (!$validation['valid']) {
            return $validation;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'newsaiige_gift_cards';
        
        $result = $wpdb->update(
            $table_name,
            array(
                'status' => 'used',
                'used_at' => current_time('mysql')
            ),
            array('gift_card_code' => $code),
            array('%s', '%s'),
            array('%s')
        );
        
        if ($result) {
            return array('success' => true, 'message' => 'Carte utilisée avec succès');
        } else {
            return array('success' => false, 'message' => 'Erreur lors de l\'utilisation');
        }
    }
    
    /**
     * Vérifier la compatibilité HPOS et afficher un message informatif
     */
    public function check_hpos_compatibility() {
        // Vérifier si HPOS est disponible et activé
        if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')) {
            $hpos_enabled = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
            
            // Ajouter une notice informative en mode admin (une seule fois par session)
            if (is_admin() && current_user_can('manage_options') && !get_transient('newsaiige_hpos_notice_shown')) {
                set_transient('newsaiige_hpos_notice_shown', true, DAY_IN_SECONDS);
                
                add_action('admin_notices', function() use ($hpos_enabled) {
                    $status = $hpos_enabled ? '✅ ACTIVÉ' : '🔄 DÉSACTIVÉ';
                    $color = $hpos_enabled ? '#d1edff' : '#fff3cd';
                    ?>
                    <div class="notice notice-info is-dismissible" style="background-color: <?php echo $color; ?>;">
                        <p>
                            <strong>NewSaiige Gift Cards - Compatibilité HPOS :</strong> 
                            <?php echo $status; ?>
                            <?php if ($hpos_enabled): ?>
                                - Le plugin utilise le stockage haute performance de WooCommerce.
                            <?php else: ?>
                                - Le plugin utilise le stockage traditionnel de WooCommerce.
                            <?php endif; ?>
                            <em>(Compatible avec les deux modes)</em>
                        </p>
                    </div>
                    <?php
                });
            }
        }
    }
}

// Initialiser le plugin
function newsaiige_gift_cards_init() {
    return NewSaiige_Gift_Cards::get_instance();
}

// Lancer le plugin
add_action('init', 'newsaiige_gift_cards_init');

/**
 * Fonctions utilitaires globales
 */

/**
 * Obtenir les statistiques des cartes cadeaux
 */
function newsaiige_get_gift_cards_statistics() {
    return NewSaiige_Gift_Cards::get_stats();
}

/**
 * Valider un code de carte cadeau
 */
function newsaiige_validate_gift_card($code) {
    return NewSaiige_Gift_Cards::validate_card($code);
}

/**
 * Utiliser une carte cadeau
 */
function newsaiige_use_gift_card($code) {
    return NewSaiige_Gift_Cards::use_card($code);
}

/**
 * Hook d'activation pour les installations via FTP
 */
if (!function_exists('newsaiige_gift_cards_activate')) {
    function newsaiige_gift_cards_activate() {
        $plugin = newsaiige_gift_cards_init();
        $plugin->activate();
    }
}

/**
 * Hook de désactivation
 */
if (!function_exists('newsaiige_gift_cards_deactivate')) {
    function newsaiige_gift_cards_deactivate() {
        $plugin = newsaiige_gift_cards_init();
        $plugin->deactivate();
    }
}

// Enregistrer les hooks si le fichier est utilisé comme plugin principal
if (defined('ABSPATH') && basename(__FILE__) === 'newsaiige-gift-cards.php') {
    register_activation_hook(__FILE__, 'newsaiige_gift_cards_activate');
    register_deactivation_hook(__FILE__, 'newsaiige_gift_cards_deactivate');
}

?>