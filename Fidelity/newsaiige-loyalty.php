<?php
/**
 * Plugin Name: Newsaiige Loyalty System
 * Plugin URI: https://newsaiige.com
 * Description: Système complet de fidélité avec points, paliers et bons d'achat pour WooCommerce
 * Version: 2.0.1
 * Author: Newsaiige
 * License: GPL v2 or later
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 5.0
 * WC tested up to: 8.3
 * Text Domain: newsaiige-loyalty
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Déclarer la compatibilité avec WooCommerce HPOS (High Performance Order Storage)
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('orders_cache', __FILE__, true);
    }
});

// Définir les constantes du plugin
define('NEWSAIIGE_LOYALTY_VERSION', '2.0.1');
define('NEWSAIIGE_LOYALTY_PATH', plugin_dir_path(__FILE__));
define('NEWSAIIGE_LOYALTY_URL', plugin_dir_url(__FILE__));
define('NEWSAIIGE_LOYALTY_FILE', __FILE__);

/**
 * Classe principale du plugin
 */
class NewsaiigeLoyaltyPlugin {
    
    /**
     * Instance unique du plugin
     */
    private static $instance = null;
    
    /**
     * Obtenir l'instance unique
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructeur
     */
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialisation du plugin
     */
    public function init() {
        // Vérifier que WooCommerce est actif
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Charger les fichiers nécessaires
        $this->load_dependencies();
        
        // Initialiser les composants
        $this->init_hooks();
    }
    
    /**
     * Charger les dépendances
     */
    private function load_dependencies() {
        // Charger les fichiers principaux uniquement s'ils existent
        $files_to_load = array(
            'includes/admin.php',
            'includes/system.php', 
            'includes/woocommerce.php'
        );
        
        foreach ($files_to_load as $file) {
            $file_path = NEWSAIIGE_LOYALTY_PATH . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
    
    /**
     * Initialiser les hooks WordPress
     */
    private function init_hooks() {
        // Enqueue des styles et scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Hook pour l'activation différée
        add_action('init', array($this, 'check_database_tables'));
    }
    
    /**
     * Activation du plugin
     */
    public function activate() {
        // Créer les tables de base de données
        $this->create_database_tables();
        
        // Créer une page pour le programme de fidélité
        $this->create_loyalty_page();
        
        // Programmer les tâches cron
        $this->schedule_cron_jobs();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Marquer l'activation
        update_option('newsaiige_loyalty_activated', time());
    }
    
    /**
     * Désactivation du plugin
     */
    public function deactivate() {
        // Supprimer les tâches cron
        wp_clear_scheduled_hook('newsaiige_daily_birthday_check');
        wp_clear_scheduled_hook('newsaiige_daily_cleanup');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Créer les tables de base de données
     */
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table des points de fidélité
        $points_table = $wpdb->prefix . 'newsaiige_loyalty_points';
        
        $sql_points = "CREATE TABLE IF NOT EXISTS $points_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            points_earned int(11) NOT NULL DEFAULT 0,
            points_used int(11) NOT NULL DEFAULT 0,
            points_available int(11) NOT NULL DEFAULT 0,
            order_id int(11) NULL,
            action_type varchar(50) NOT NULL,
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime NULL,
            is_active tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            KEY idx_points_user_id (user_id),
            KEY idx_points_order_id (order_id),
            KEY idx_points_expires_at (expires_at),
            KEY idx_points_is_active (is_active)
        ) $charset_collate;";
        
        // Table des paliers de fidélité
        $tiers_table = $wpdb->prefix . 'newsaiige_loyalty_tiers';
        
        $sql_tiers = "CREATE TABLE IF NOT EXISTS $tiers_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            tier_name varchar(100) NOT NULL,
            tier_slug varchar(100) NOT NULL,
            points_required int(11) NOT NULL,
            tier_order int(11) NOT NULL,
            benefits text,
            birthday_bonus_percentage int(11) DEFAULT 0,
            email_template text,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_tier_slug (tier_slug),
            KEY idx_tier_order (tier_order),
            KEY idx_points_required (points_required),
            KEY idx_is_active (is_active)
        ) $charset_collate;";
        
        // Exécuter les requêtes
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_points);
        dbDelta($sql_tiers);
        
        // Insérer des données par défaut
        $this->insert_default_data();
    }
    
    /**
     * Insérer les données par défaut
     */
    private function insert_default_data() {
        global $wpdb;
        
        $tiers_table = $wpdb->prefix . 'newsaiige_loyalty_tiers';
        
        // Vérifier si des paliers existent déjà
        $existing_tiers = $wpdb->get_var("SELECT COUNT(*) FROM $tiers_table");
        
        if ($existing_tiers == 0) {
            // Insérer les paliers par défaut
            $default_tiers = array(
                array('Bronze', 'bronze', 700, 1, 'Bienvenue dans notre programme de fidélité !', 5),
                array('Argent', 'silver', 1300, 2, 'Bon d\'achat de 10€ offert', 10),
                array('Or', 'gold', 1900, 3, 'Bon d\'achat de 20€ offert', 20),
                array('Platine', 'platinum', 2500, 4, 'Bon d\'achat de 65€ offert', 65)
            );
            
            foreach ($default_tiers as $tier) {
                $wpdb->insert(
                    $tiers_table,
                    array(
                        'tier_name' => $tier[0],
                        'tier_slug' => $tier[1],
                        'points_required' => $tier[2],
                        'tier_order' => $tier[3],
                        'benefits' => $tier[4],
                        'birthday_bonus_percentage' => $tier[5],
                        'is_active' => 1
                    )
                );
            }
        }
    }
    
    /**
     * Créer la page du programme de fidélité
     */
    private function create_loyalty_page() {
        $page_exists = get_page_by_path('mon-programme-fidelite');
        
        if (!$page_exists) {
            $page_data = array(
                'post_title' => 'Mon Programme de Fidélité',
                'post_name' => 'mon-programme-fidelite',
                'post_content' => '[newsaiige_loyalty]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_author' => 1
            );
            
            wp_insert_post($page_data);
        }
    }
    
    /**
     * Programmer les tâches cron
     */
    private function schedule_cron_jobs() {
        if (!wp_next_scheduled('newsaiige_daily_birthday_check')) {
            wp_schedule_event(time(), 'daily', 'newsaiige_daily_birthday_check');
        }
        
        if (!wp_next_scheduled('newsaiige_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'newsaiige_daily_cleanup');
        }
    }
    
    /**
     * Vérifier l'existence des tables
     */
    public function check_database_tables() {
        if (!get_option('newsaiige_loyalty_tables_checked')) {
            $this->create_database_tables();
            update_option('newsaiige_loyalty_tables_checked', true);
        }
    }
    
    /**
     * Notice si WooCommerce est manquant
     */
    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>Newsaiige Loyalty System :</strong> Ce plugin nécessite WooCommerce pour fonctionner.';
        echo '</p></div>';
    }
    
    /**
     * Enqueue des scripts frontend
     */
    public function enqueue_scripts() {
        if (file_exists(NEWSAIIGE_LOYALTY_PATH . 'assets/css/loyalty.css')) {
            wp_enqueue_style(
                'newsaiige-loyalty-style', 
                NEWSAIIGE_LOYALTY_URL . 'assets/css/loyalty.css', 
                array(), 
                NEWSAIIGE_LOYALTY_VERSION
            );
        }
        
        if (file_exists(NEWSAIIGE_LOYALTY_PATH . 'assets/js/loyalty.js')) {
            wp_enqueue_script(
                'newsaiige-loyalty-script', 
                NEWSAIIGE_LOYALTY_URL . 'assets/js/loyalty.js', 
                array('jquery'), 
                NEWSAIIGE_LOYALTY_VERSION, 
                true
            );
        }
    }
    
    /**
     * Enqueue des scripts admin
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'newsaiige-loyalty') !== false) {
            if (file_exists(NEWSAIIGE_LOYALTY_PATH . 'assets/css/admin.css')) {
                wp_enqueue_style(
                    'newsaiige-loyalty-admin-style', 
                    NEWSAIIGE_LOYALTY_URL . 'assets/css/admin.css', 
                    array(), 
                    NEWSAIIGE_LOYALTY_VERSION
                );
            }
        }
    }
}

// Initialiser le plugin
function newsaiige_loyalty_init() {
    return NewsaiigeLoyaltyPlugin::get_instance();
}

// Démarrer le plugin
newsaiige_loyalty_init();

// Hook pour les shortcodes de base
add_action('init', function() {
    // Shortcode simple pour afficher les points
    add_shortcode('newsaiige_loyalty_points', function($atts) {
        if (!is_user_logged_in()) {
            return '<span class="loyalty-login-required">Connectez-vous pour voir vos points</span>';
        }
        
        global $wpdb;
        $user_id = get_current_user_id();
        $points_table = $wpdb->prefix . 'newsaiige_loyalty_points';
        
        $points = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(points_available) FROM $points_table WHERE user_id = %d AND is_active = 1",
            $user_id
        ));
        
        return '<span class="loyalty-points-display">' . number_format($points ?: 0) . ' points</span>';
    });
    
    // Shortcode principal (basique)
    add_shortcode('newsaiige_loyalty', function($atts) {
        if (!is_user_logged_in()) {
            return '<div class="loyalty-login-required">Veuillez vous connecter pour accéder à votre programme de fidélité.</div>';
        }
        
        return '<div class="loyalty-placeholder">Programme de fidélité en cours de chargement...</div>';
    });
});
?>