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
    
    // Shortcode principal (interface complète)
    add_shortcode('newsaiige_loyalty', function($atts) {
        if (!is_user_logged_in()) {
            return '<div class="loyalty-login-required">
                <h3>🔐 Connexion requise</h3>
                <p>Veuillez vous connecter pour accéder à votre programme de fidélité.</p>
                <a href="' . wp_login_url(get_permalink()) . '" class="button">Se connecter</a>
            </div>';
        }
        
        return newsaiige_render_loyalty_dashboard();
    });
});

/**
 * Fonction pour afficher le dashboard de fidélité complet
 */
function newsaiige_render_loyalty_dashboard() {
    global $wpdb;
    
    $user_id = get_current_user_id();
    if (!$user_id) return '';
    
    // Tables
    $points_table = $wpdb->prefix . 'newsaiige_loyalty_points';
    $tiers_table = $wpdb->prefix . 'newsaiige_loyalty_tiers';
    $user_tiers_table = $wpdb->prefix . 'newsaiige_loyalty_user_tiers';
    
    // Données utilisateur
    $points_available = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(points_available) FROM $points_table WHERE user_id = %d AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())",
        $user_id
    )) ?: 0;
    
    $points_lifetime = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(points_earned) FROM $points_table WHERE user_id = %d",
        $user_id
    )) ?: 0;
    
    // Palier actuel basé sur les points disponibles
    $current_tier = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $tiers_table WHERE points_required <= %d AND is_active = 1 ORDER BY points_required DESC LIMIT 1",
        $points_available
    ));
    
    // Si aucun palier atteint avec les points disponibles, pas de palier
    if (!$current_tier && $points_available < 700) {
        $current_tier = null;
    }
    
    // Prochain palier basé sur les points disponibles
    $next_tier = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $tiers_table WHERE points_required > %d AND is_active = 1 ORDER BY points_required ASC LIMIT 1",
        $points_available
    ));
    
    // Historique récent
    $recent_activity = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $points_table WHERE user_id = %d ORDER BY created_at DESC LIMIT 10",
        $user_id
    ));
    
    // Points expirant bientôt
    $expiring_soon = $wpdb->get_results($wpdb->prepare(
        "SELECT SUM(points_available) as points, expires_at 
         FROM $points_table 
         WHERE user_id = %d AND is_active = 1 
         AND expires_at IS NOT NULL AND expires_at > NOW() AND expires_at <= DATE_ADD(NOW(), INTERVAL 30 DAY)
         GROUP BY DATE(expires_at) 
         ORDER BY expires_at ASC",
        $user_id
    ));
    
    ob_start();
    ?>
    
    <div class="newsaiige-loyalty-dashboard">
        <style>
        .newsaiige-loyalty-dashboard {
            font-family: -apple-system, 'Montserrat', Roboto, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        .loyalty-header {
            text-align: center;
            background: #82897F;
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        .loyalty-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #82897F;
        }
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
                color: #82897F;
                margin-bottom: 5px;
        }
        .stat-label {
            color: #666;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .tier-progress {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .tier-current {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        .tier-badge {
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: bold;
            color: white;
            font-size: 0.9em;
        }
        .tier-bronze { background: #CD7F32; }
        .tier-silver { background: #C0C0C0; }
        .tier-gold { background: #FFD700; color: #333; }
        .tier-platinum { background: #E5E4E2; color: #333; }
        .progress-bar {
            width: 100%;
            height: 10px;
            background: #f0f0f0;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        .progress-fill {
            height: 100%;
            background: #82897F;
            transition: width 0.3s ease;
        }
        .loyalty-sections {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        .activity-section, .actions-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        .activity-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-points {
            font-weight: bold;
            color: #28a745;
        }
        .activity-points.negative {
            color: #dc3545;
        }
        .loyalty-button {
            display: inline-block;
            padding: 12px 25px;
            background: #82897F;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            text-align: center;
            margin: 10px 5px;
            transition: transform 0.2s ease;
        }
        .loyalty-button:hover {
            transform: translateY(-2px);
            text-decoration: none;
            color: white;
        }
        .loyalty-button.secondary {
            background: #6c757d;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
            margin-bottom: 20px;
        }
        .conversion-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .conversion-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .conversion-option {
            border: 2px solid #f0f0f0;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .conversion-option:hover {
            border-color: #82897F;
            box-shadow: 0 5px 15px rgba(130, 137, 127, 0.2);
        }
        .conversion-amount {
            display: block;
            font-size: 1.5em;
            font-weight: bold;
            color: #82897F;
            margin-bottom: 5px;
        }
        .conversion-points {
            display: block;
            color: #666;
            font-size: 0.9em;
            margin-bottom: 15px;
        }
        .convert-btn {
            background: #82897F;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        .convert-btn:hover {
            background: #6d7569;
            transform: translateY(-2px);
        }
        .convert-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        .conversion-result {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-top: 20px;
        }
        .conversion-result.error {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .coupon-code {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 5px;
            border: 2px dashed #82897F;
            display: inline-block;
            margin: 10px 0;
            font-size: 1.2em;
            font-weight: bold;
            color: #82897F;
            letter-spacing: 2px;
        }
        .copy-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            margin-left: 10px;
            cursor: pointer;
            font-size: 0.8em;
        }
        .coupons-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .coupons-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .coupon-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            border: 2px solid #f0f0f0;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .coupon-item.active {
            border-color: #82897F;
            background: rgba(130, 137, 127, 0.05);
        }
        .coupon-item.used {
            border-color: #ddd;
            background: #f8f9fa;
            opacity: 0.7;
        }
        .coupon-info {
            flex: 1;
        }
        .coupon-code-display {
            font-family: 'Courier New', monospace;
            font-size: 1.1em;
            font-weight: bold;
            color: #82897F;
            margin-bottom: 5px;
        }
        .coupon-details {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        .coupon-amount {
            background: #82897F;
            color: white;
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .coupon-status {
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .coupon-status.active {
            background: #d4edda;
            color: #155724;
        }
        .coupon-status.utilisé {
            background: #f8d7da;
            color: #721c24;
        }
        .coupon-expiry {
            color: #666;
            font-size: 0.8em;
        }
        .copy-coupon-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .copy-coupon-btn:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        @media (max-width: 768px) {
            .loyalty-sections {
                grid-template-columns: 1fr;
            }
            .loyalty-stats {
                grid-template-columns: 1fr;
            }
            .conversion-options {
                grid-template-columns: 1fr;
            }
        }
        </style>
        
        <!-- En-tête -->
        <div class="loyalty-header">
            <h2>🎯 Mon programme de fidélité</h2>
            <p>Bienvenue <?php echo esc_html(wp_get_current_user()->display_name); ?> !</p>
        </div>
        
        <!-- Statistiques principales -->
        <div class="loyalty-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($points_available); ?></div>
                <div class="stat-label">Points disponibles</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($points_lifetime); ?></div>
                <div class="stat-label">Total de points cumulés</div>
            </div>
        </div>
        
        <!-- Progression palier -->
        <?php if ($next_tier): ?>
        <div class="tier-progress">
            <h3>📈 Progression vers <?php echo esc_html($next_tier->tier_name); ?></h3>
            <div class="tier-current">
                <span class="tier-badge tier-<?php echo $current_tier ? $current_tier->tier_slug : 'bronze'; ?>">
                    <?php echo $current_tier ? $current_tier->tier_name : 'Débutant'; ?>
                </span>
                <span>→</span>
                <span class="tier-badge tier-<?php echo $next_tier->tier_slug; ?>">
                    <?php echo $next_tier->tier_name; ?>
                </span>
            </div>
            
            <?php 
            $current_points = $current_tier ? $current_tier->points_required : 0;
            $next_points = $next_tier->points_required;
            $progress = max(0, min(100, (($points_available - $current_points) / ($next_points - $current_points)) * 100));
            $remaining = max(0, $next_points - $points_available);
            ?>
            
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
            </div>
            
            <p style="margin: 10px 0 0 0; color: #666; font-size: 0.9em;">
                <?php if ($remaining > 0): ?>
                    Plus que <strong><?php echo number_format($remaining); ?> points</strong> pour atteindre le palier <?php echo $next_tier->tier_name; ?> !
                <?php else: ?>
                    🎉 Félicitations ! Vous avez atteint le palier <?php echo $next_tier->tier_name; ?> !
                <?php endif; ?>
            </p>
        </div>
        <?php endif; ?>
        
        <!-- Alerte points expirants -->
        <?php if ($expiring_soon): ?>
        <div class="alert-warning">
            <strong>⚠️ Points expirant bientôt !</strong><br>
            <?php foreach ($expiring_soon as $expiry): ?>
                <span><?php echo number_format($expiry->points); ?> points expirent le <?php echo date('d/m/Y', strtotime($expiry->expires_at)); ?></span><br>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="loyalty-sections">
            <!-- Activité récente -->
            <div class="activity-section">
                <h3>📊 Activité récente</h3>
                <?php if ($recent_activity): ?>
                    <?php foreach ($recent_activity as $activity): ?>
                    <div class="activity-item">
                        <div>
                            <strong><?php echo esc_html($activity->description ?: $activity->action_type); ?></strong><br>
                            <small style="color: #666;"><?php echo date('d/m/Y H:i', strtotime($activity->created_at)); ?></small>
                        </div>
                        <div class="activity-points <?php echo $activity->points_used > 0 ? 'negative' : ''; ?>">
                            <?php if ($activity->points_used > 0): ?>
                                -<?php echo number_format($activity->points_used); ?>
                            <?php else: ?>
                                +<?php echo number_format($activity->points_earned); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 20px;">
                        Aucune activité récente.<br>
                        Passez votre première commande pour gagner des points !
                    </p>
                <?php endif; ?>
            </div>
            
            <!-- Conversion de points -->
            <?php if ($points_available >= 700): ?>
            <div class="conversion-section">
                <h3>🎁 Convertir mes points en bon d'achat</h3>
                
                <div class="conversion-options">
                    <?php
                    // Options de conversion basées sur les points disponibles
                    $conversion_options = array();
                    
                    if ($points_available >= 700) {
                        $conversion_options[] = array('points' => 700, 'value' => 5, 'label' => '5€');
                    }
                    if ($points_available >= 1300) {
                        $conversion_options[] = array('points' => 1300, 'value' => 10, 'label' => '10€');
                    }
                    if ($points_available >= 1900) {
                        $conversion_options[] = array('points' => 1900, 'value' => 20, 'label' => '20€');
                    }
                    if ($points_available >= 2500) {
                        $conversion_options[] = array('points' => 2500, 'value' => 65, 'label' => '65€');
                    }
                    
                    foreach ($conversion_options as $option): ?>
                    <div class="conversion-option" data-points="<?php echo $option['points']; ?>" data-value="<?php echo $option['value']; ?>">
                        <div class="conversion-details">
                            <span class="conversion-amount"><?php echo $option['label']; ?></span>
                            <span class="conversion-points"><?php echo number_format($option['points']); ?> points</span>
                        </div>
                        <button class="convert-btn" onclick="convertPoints(<?php echo $option['points']; ?>, <?php echo $option['value']; ?>, '<?php echo $option['label']; ?>')">
                            Convertir
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div id="conversion-result" style="display: none;"></div>
            </div>
            <?php endif; ?>
            
            <!-- Mes bons d'achat -->
            <?php
            $user_coupons = newsaiige_get_user_coupons($user_id);
            if (!empty($user_coupons)): ?>
            <div class="coupons-section">
                <h3>🎫 Mes bons d'achat</h3>
                <div class="coupons-list">
                    <?php foreach (array_slice($user_coupons, 0, 5) as $coupon): ?>
                    <div class="coupon-item <?php echo $coupon['status'] === 'utilisé' ? 'used' : 'active'; ?>">
                        <div class="coupon-info">
                            <div class="coupon-code-display"><?php echo esc_html($coupon['code']); ?></div>
                            <div class="coupon-details">
                                <span class="coupon-amount"><?php echo number_format($coupon['amount'], 2); ?>€</span>
                                <span class="coupon-status <?php echo $coupon['status']; ?>">
                                    <?php echo $coupon['status'] === 'utilisé' ? '✓ Utilisé' : '📋 Disponible'; ?>
                                </span>
                                <?php if ($coupon['expiry']): ?>
                                <span class="coupon-expiry">
                                    Expire le <?php echo $coupon['expiry']->date('d/m/Y'); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($coupon['status'] !== 'utilisé'): ?>
                        <button class="copy-coupon-btn" onclick="copyToClipboard('<?php echo esc_attr($coupon['code']); ?>')">
                            Copier
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($user_coupons) > 5): ?>
                <p style="text-align: center; margin-top: 15px;">
                    <small>Vous avez <?php echo count($user_coupons) - 5; ?> autre(s) bon(s) d'achat. 
                    <a href="#" onclick="showAllCoupons()">Voir tous</a></small>
                </p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Actions rapides -->
            <div class="actions-section">
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #f0f0f0;">
                    <h4>💡 Le saviez-vous ?</h4>
                    <ul style="font-size: 0.9em; color: #666; line-height: 1.6;">
                        <li>Gagnez 1 point par euro dépensé</li>
                        <li>Les bons expirent 6 mois après leur émission</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Animation de compteur pour les points
    document.addEventListener('DOMContentLoaded', function() {
        const numbers = document.querySelectorAll('.stat-number');
        numbers.forEach(number => {
            const finalNumber = parseInt(number.textContent.replace(/,/g, ''));
            let currentNumber = 0;
            const increment = finalNumber / 50;
            
            const timer = setInterval(() => {
                currentNumber += increment;
                if (currentNumber >= finalNumber) {
                    currentNumber = finalNumber;
                    clearInterval(timer);
                }
                number.textContent = Math.floor(currentNumber).toLocaleString();
            }, 30);
        });
    });
    
    // Fonction de conversion des points
    function convertPoints(points, value, label) {
        // Désactiver tous les boutons pendant la conversion
        const buttons = document.querySelectorAll('.convert-btn');
        buttons.forEach(btn => {
            btn.disabled = true;
            btn.textContent = 'Conversion...';
        });
        
        // Afficher un message de chargement
        const resultDiv = document.getElementById('conversion-result');
        resultDiv.style.display = 'block';
        resultDiv.className = 'conversion-result';
        resultDiv.innerHTML = '<div style="text-align: center;">⏳ Génération du bon d\'achat en cours...</div>';
        
        // Préparer les données pour la requête AJAX
        const formData = new FormData();
        formData.append('action', 'newsaiige_convert_points');
        formData.append('points', points);
        formData.append('value', value);
        formData.append('nonce', '<?php echo wp_create_nonce("newsaiige_loyalty_convert"); ?>');
        
        // Requête AJAX vers WordPress
        fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Succès - afficher le code généré
                resultDiv.className = 'conversion-result';
                resultDiv.innerHTML = `
                    <h4>🎉 Conversion réussie !</h4>
                    <p>Vous avez converti <strong>${points.toLocaleString()} points</strong> en un bon d'achat de <strong>${label}</strong></p>
                    <div class="coupon-code" id="coupon-${data.data.code}">
                        ${data.data.code}
                        <button class="copy-btn" onclick="copyToClipboard('${data.data.code}')">Copier</button>
                    </div>
                    <p><small>Ce code est valable jusqu'au ${data.data.expiry_date}</small></p>
                    <p><strong>Comment l'utiliser :</strong> Copiez ce code et collez-le dans le champ "Code promo" lors de votre commande.</p>
                `;
                
                // Mettre à jour l'affichage des points disponibles
                setTimeout(() => {
                    location.reload();
                }, 3000);
                
            } else {
                // Erreur
                resultDiv.className = 'conversion-result error';
                resultDiv.innerHTML = `
                    <h4>❌ Erreur de conversion</h4>
                    <p>${data.data.message || 'Une erreur est survenue lors de la conversion.'}</p>
                `;
                
                // Réactiver les boutons
                buttons.forEach(btn => {
                    btn.disabled = false;
                    btn.textContent = 'Convertir';
                });
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            resultDiv.className = 'conversion-result error';
            resultDiv.innerHTML = `
                <h4>❌ Erreur technique</h4>
                <p>Impossible de contacter le serveur. Veuillez réessayer plus tard.</p>
            `;
            
            // Réactiver les boutons
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.textContent = 'Convertir';
            });
        });
    }
    
    // Fonction pour copier le code dans le presse-papiers
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            // Feedback visuel
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = 'Copié !';
            button.style.background = '#28a745';
            
            setTimeout(() => {
                button.textContent = originalText;
                button.style.background = '#28a745';
            }, 2000);
        }, function(err) {
            console.error('Erreur lors de la copie: ', err);
            alert('Impossible de copier automatiquement. Copiez manuellement le code : ' + text);
        });
    }
    </script>
    
    <?php
    return ob_get_clean();
}

// AJAX Handlers pour la conversion des points
add_action('wp_ajax_newsaiige_convert_points', 'newsaiige_handle_convert_points');
add_action('wp_ajax_nopriv_newsaiige_convert_points', 'newsaiige_handle_convert_points_noauth');

/**
 * Handler pour la conversion des points en bons d'achat
 */
function newsaiige_handle_convert_points() {
    // Vérification de sécurité
    if (!wp_verify_nonce($_POST['nonce'], 'newsaiige_loyalty_convert')) {
        wp_send_json_error(array('message' => 'Nonce invalide'));
        return;
    }
    
    // Vérifier que l'utilisateur est connecté
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Vous devez être connecté'));
        return;
    }
    
    // Vérifier que WooCommerce est actif
    if (!class_exists('WooCommerce')) {
        wp_send_json_error(array('message' => 'WooCommerce non disponible'));
        return;
    }
    
    $user_id = get_current_user_id();
    $points_to_convert = intval($_POST['points']);
    $coupon_value = floatval($_POST['value']);
    
    // Validation des données
    if ($points_to_convert <= 0 || $coupon_value <= 0) {
        wp_send_json_error(array('message' => 'Paramètres invalides'));
        return;
    }
    
    global $wpdb;
    $points_table = $wpdb->prefix . 'newsaiige_loyalty_points';
    
    // Vérifier que l'utilisateur a suffisamment de points
    $available_points = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(points_available) FROM $points_table WHERE user_id = %d AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())",
        $user_id
    ));
    
    if ($available_points < $points_to_convert) {
        wp_send_json_error(array('message' => 'Points insuffisants'));
        return;
    }
    
    // Générer un code unique pour le bon d'achat
    $coupon_code = 'NEWSAIIGE' . strtoupper(wp_generate_password(8, false));
    
    // Vérifier que le code n'existe pas déjà
    while (get_page_by_title($coupon_code, OBJECT, 'shop_coupon')) {
        $coupon_code = 'NEWSAIIGE' . strtoupper(wp_generate_password(8, false));
    }
    
    // Créer le bon d'achat WooCommerce
    $coupon = new WC_Coupon();
    $coupon->set_code($coupon_code);
    $coupon->set_description('Bon d\'achat généré via le programme de fidélité Newsaiige');
    $coupon->set_discount_type('fixed_cart');
    $coupon->set_amount($coupon_value);
    $coupon->set_individual_use(true);
    $coupon->set_usage_limit(1);
    $coupon->set_usage_limit_per_user(1);
    $coupon->set_date_expires(time() + (90 * 24 * 60 * 60)); // Expire dans 90 jours
    
    // Exclure les catégories spécifiques
    $excluded_categories = array();
    $excluded_terms = get_terms(array(
        'taxonomy' => 'product_cat',
        'slug' => array('e-carte-cadeau', 'soins'),
        'hide_empty' => false
    ));
    
    if (!is_wp_error($excluded_terms)) {
        foreach ($excluded_terms as $term) {
            $excluded_categories[] = $term->term_id;
        }
    }
    
    if (!empty($excluded_categories)) {
        $coupon->set_excluded_product_categories($excluded_categories);
    }
    
    $coupon_id = $coupon->save();
    
    if (!$coupon_id) {
        wp_send_json_error(array('message' => 'Erreur lors de la création du bon d\'achat'));
        return;
    }
    
    // Déduire les points de l'utilisateur
    $deduction_success = $wpdb->insert(
        $points_table,
        array(
            'user_id' => $user_id,
            'points_earned' => 0,
            'points_used' => $points_to_convert,
            'points_available' => -$points_to_convert,
            'action_type' => 'coupon_conversion',
            'description' => sprintf('Conversion en bon d\'achat %s (%.2f€)', $coupon_code, $coupon_value),
            'created_at' => current_time('mysql'),
            'is_active' => 1
        )
    );
    
    if (!$deduction_success) {
        // Supprimer le coupon en cas d'erreur
        wp_delete_post($coupon_id, true);
        wp_send_json_error(array('message' => 'Erreur lors de la déduction des points'));
        return;
    }
    
    // Enregistrer les métadonnées du bon d'achat
    update_post_meta($coupon_id, '_newsaiige_user_id', $user_id);
    update_post_meta($coupon_id, '_newsaiige_points_used', $points_to_convert);
    update_post_meta($coupon_id, '_newsaiige_generated_date', current_time('mysql'));
    
    // Succès
    wp_send_json_success(array(
        'code' => $coupon_code,
        'value' => $coupon_value,
        'expiry_date' => date('d/m/Y', $coupon->get_date_expires()->getTimestamp()),
        'message' => sprintf('Bon d\'achat de %.2f€ généré avec succès', $coupon_value)
    ));
}

/**
 * Handler pour les utilisateurs non connectés
 */
function newsaiige_handle_convert_points_noauth() {
    wp_send_json_error(array('message' => 'Vous devez être connecté pour convertir des points'));
}

/**
 * Fonction utilitaire pour récupérer les bons d'achat d'un utilisateur
 */
function newsaiige_get_user_coupons($user_id) {
    $coupons = get_posts(array(
        'post_type' => 'shop_coupon',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_newsaiige_user_id',
                'value' => $user_id,
                'compare' => '='
            )
        ),
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    
    $user_coupons = array();
    foreach ($coupons as $coupon_post) {
        $coupon = new WC_Coupon($coupon_post->ID);
        $user_coupons[] = array(
            'code' => $coupon->get_code(),
            'amount' => $coupon->get_amount(),
            'expiry' => $coupon->get_date_expires(),
            'usage_count' => $coupon->get_usage_count(),
            'usage_limit' => $coupon->get_usage_limit(),
            'status' => $coupon->get_usage_count() >= $coupon->get_usage_limit() ? 'utilisé' : 'actif'
        );
    }
    
    return $user_coupons;
}
?>