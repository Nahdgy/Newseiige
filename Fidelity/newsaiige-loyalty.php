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
    
    // Palier actuel
    $current_tier = $wpdb->get_row($wpdb->prepare(
        "SELECT t.* FROM $tiers_table t 
         LEFT JOIN $user_tiers_table ut ON t.id = ut.tier_id 
         WHERE ut.user_id = %d AND ut.is_current = 1 
         ORDER BY t.tier_order DESC LIMIT 1",
        $user_id
    ));
    
    // Si pas de palier, prendre le premier disponible
    if (!$current_tier) {
        $current_tier = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tiers_table WHERE points_required <= %d AND is_active = 1 ORDER BY points_required DESC LIMIT 1",
            $points_lifetime
        ));
    }
    
    // Prochain palier
    $next_tier = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $tiers_table WHERE points_required > %d AND is_active = 1 ORDER BY points_required ASC LIMIT 1",
        $points_lifetime
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
        @media (max-width: 768px) {
            .loyalty-sections {
                grid-template-columns: 1fr;
            }
            .loyalty-stats {
                grid-template-columns: 1fr;
            }
        }
        </style>
        
        <!-- En-tête -->
        <div class="loyalty-header">
            <h2>🎯 Mon Programme de Fidélité</h2>
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
                <div class="stat-label">Points à vie</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $current_tier ? $current_tier->tier_name : 'Aucun'; ?></div>
                <div class="stat-label">Palier actuel</div>
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
            $progress = max(0, min(100, (($points_lifetime - $current_points) / ($next_points - $current_points)) * 100));
            $remaining = max(0, $next_points - $points_lifetime);
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
            
            <!-- Actions rapides -->
            <div class="actions-section">
                <h3>🚀 Actions rapides</h3>
                
                <?php if ($points_available >= 100): ?>
                <a href="<?php echo wc_get_cart_url(); ?>" class="loyalty-button">
                    💰 Utiliser mes points
                </a>
                <?php endif; ?>
                
                <a href="<?php echo wc_get_page_permalink('shop'); ?>" class="loyalty-button">
                    🛍️ Continuer mes achats
                </a>
                
                <a href="<?php echo wc_get_account_endpoint_url('orders'); ?>" class="loyalty-button secondary">
                    📦 Mes commandes
                </a>
                
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #f0f0f0;">
                    <h4>💡 Le saviez-vous ?</h4>
                    <ul style="font-size: 0.9em; color: #666; line-height: 1.6;">
                        <li>Gagnez 1 point par euro dépensé</li>
                        <li>100 points = 1€ de réduction</li>
                        <li>Les points expirent après 6 mois</li>
                        <?php if ($current_tier): ?>
                        <li>Bonus anniversaire : <?php echo $current_tier->birthday_bonus_percentage; ?>% supplémentaire</li>
                        <?php endif; ?>
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
    </script>
    
    <?php
    return ob_get_clean();
}
?>