<?php
/**
 * Plugin Name: Système de Fidélité Newsaiige
 * Description: Système complet de fidélité avec points, paliers, bons d'achat et offres anniversaire
 * Version: 1.0.0
 * Author: Newsaiige
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Définir les constantes du plugin
define('NEWSAIIGE_LOYALTY_VERSION', '1.0.0');
define('NEWSAIIGE_LOYALTY_PATH', plugin_dir_path(__FILE__));
define('NEWSAIIGE_LOYALTY_URL', plugin_dir_url(__FILE__));

// Inclure les fichiers du système
require_once NEWSAIIGE_LOYALTY_PATH . 'loyalty-system.php';
require_once NEWSAIIGE_LOYALTY_PATH . 'loyalty-admin.php';
require_once NEWSAIIGE_LOYALTY_PATH . 'loyalty-woocommerce.php';

/**
 * Initialisation du plugin
 */
class NewsaiigeLoyaltyPlugin {
    
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Vérifier que WooCommerce est actif
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Initialiser les hooks pour les champs utilisateur
        add_action('show_user_profile', array($this, 'add_birthday_field'));
        add_action('edit_user_profile', array($this, 'add_birthday_field'));
        add_action('personal_options_update', array($this, 'save_birthday_field'));
        add_action('edit_user_profile_update', array($this, 'save_birthday_field'));
        
        // Ajouter le champ d'anniversaire à l'inscription
        add_action('woocommerce_register_form', array($this, 'add_birthday_registration_field'));
        add_action('woocommerce_created_customer', array($this, 'save_birthday_registration_field'));
        
        // Ajouter le champ aux pages de facturation
        add_filter('woocommerce_billing_fields', array($this, 'add_birthday_billing_field'));
        add_action('woocommerce_checkout_update_user_meta', array($this, 'save_birthday_checkout_field'));
        
        // Styles et scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_loyalty_get_user_stats', array($this, 'ajax_get_user_stats'));
    }
    
    public function activate() {
        // Créer les tables si elles n'existent pas
        newsaiige_loyalty_create_tables();
        
        // Programmer les tâches cron
        if (!wp_next_scheduled('newsaiige_daily_birthday_check')) {
            wp_schedule_event(time(), 'daily', 'newsaiige_daily_birthday_check');
        }
        
        if (!wp_next_scheduled('newsaiige_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'newsaiige_daily_cleanup');
        }
        
        // Créer une page pour le programme de fidélité
        $this->create_loyalty_page();
        
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Supprimer les tâches cron
        wp_clear_scheduled_hook('newsaiige_daily_birthday_check');
        wp_clear_scheduled_hook('newsaiige_daily_cleanup');
        
        flush_rewrite_rules();
    }
    
    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p>';
        echo 'Le système de fidélité Newsaiige nécessite WooCommerce pour fonctionner.';
        echo '</p></div>';
    }
    
    /**
     * Créer la page du programme de fidélité
     */
    private function create_loyalty_page() {
        $page_exists = get_page_by_path('mon-programme-fidelite');
        
        if (!$page_exists) {
            $page_data = array(
                'post_title'    => 'Mon Programme de Fidélité',
                'post_content'  => '[newsaiige_loyalty]',
                'post_status'   => 'publish',
                'post_author'   => 1,
                'post_type'     => 'page',
                'post_name'     => 'mon-programme-fidelite'
            );
            
            wp_insert_post($page_data);
        }
    }
    
    /**
     * Ajouter le champ anniversaire au profil utilisateur
     */
    public function add_birthday_field($user) {
        $birthday = get_user_meta($user->ID, 'billing_birthday', true);
        ?>
        <h3>Programme de Fidélité</h3>
        <table class="form-table">
            <tr>
                <th><label for="billing_birthday">Date d'anniversaire</label></th>
                <td>
                    <input type="date" name="billing_birthday" id="billing_birthday" 
                           value="<?php echo esc_attr($birthday); ?>" class="regular-text" />
                    <p class="description">
                        Votre date d'anniversaire pour recevoir des offres spéciales.
                    </p>
                </td>
            </tr>
        </table>
        
        <?php
        // Afficher les statistiques de fidélité
        global $newsaiige_loyalty;
        if (isset($newsaiige_loyalty)) {
            $points_available = $newsaiige_loyalty->get_user_points($user->ID);
            $points_lifetime = $newsaiige_loyalty->get_user_lifetime_points($user->ID);
            $current_tier = $newsaiige_loyalty->get_user_tier($user->ID);
            $vouchers = $newsaiige_loyalty->get_user_vouchers($user->ID);
            ?>
            
            <h3>Statistiques de Fidélité</h3>
            <table class="form-table">
                <tr>
                    <th>Points disponibles</th>
                    <td><strong><?php echo number_format($points_available); ?> points</strong></td>
                </tr>
                <tr>
                    <th>Points totaux gagnés</th>
                    <td><?php echo number_format($points_lifetime); ?> points</td>
                </tr>
                <tr>
                    <th>Palier actuel</th>
                    <td>
                        <?php if ($current_tier): ?>
                            <span style="padding: 4px 8px; background: #82897F; color: white; border-radius: 4px;">
                                <?php echo esc_html($current_tier->tier_name); ?>
                            </span>
                        <?php else: ?>
                            <em>Aucun palier atteint</em>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Bons d'achat disponibles</th>
                    <td>
                        <?php if (!empty($vouchers)): ?>
                            <ul>
                                <?php foreach ($vouchers as $voucher): ?>
                                <li>
                                    <code><?php echo esc_html($voucher->voucher_code); ?></code> - 
                                    <?php if ($voucher->percentage > 0): ?>
                                        <?php echo $voucher->percentage; ?>% de réduction
                                    <?php else: ?>
                                        <?php echo number_format($voucher->amount, 2); ?>€ de réduction
                                    <?php endif; ?>
                                    (expire le <?php echo date('d/m/Y', strtotime($voucher->expires_at)); ?>)
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <em>Aucun bon d'achat disponible</em>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <?php
        }
    }
    
    /**
     * Sauvegarder le champ anniversaire
     */
    public function save_birthday_field($user_id) {
        if (current_user_can('edit_user', $user_id) && isset($_POST['billing_birthday'])) {
            update_user_meta($user_id, 'billing_birthday', sanitize_text_field($_POST['billing_birthday']));
        }
    }
    
    /**
     * Ajouter le champ anniversaire à l'inscription
     */
    public function add_birthday_registration_field() {
        ?>
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="reg_billing_birthday">Date d'anniversaire <span class="required">*</span></label>
            <input type="date" class="woocommerce-Input woocommerce-Input--text input-text" 
                   name="billing_birthday" id="reg_billing_birthday" 
                   value="<?php echo (!empty($_POST['billing_birthday'])) ? esc_attr(wp_unslash($_POST['billing_birthday'])) : ''; ?>" />
            <small style="color: #666;">Pour recevoir des offres spéciales le jour de votre anniversaire</small>
        </p>
        <?php
    }
    
    /**
     * Sauvegarder le champ anniversaire lors de l'inscription
     */
    public function save_birthday_registration_field($customer_id) {
        if (isset($_POST['billing_birthday'])) {
            update_user_meta($customer_id, 'billing_birthday', sanitize_text_field($_POST['billing_birthday']));
        }
    }
    
    /**
     * Ajouter le champ anniversaire aux champs de facturation
     */
    public function add_birthday_billing_field($fields) {
        $fields['billing_birthday'] = array(
            'label'       => 'Date d\'anniversaire',
            'placeholder' => 'JJ/MM/AAAA',
            'type'        => 'date',
            'required'    => false,
            'class'       => array('form-row-wide'),
            'clear'       => true,
            'priority'    => 110,
            'description' => 'Pour recevoir des offres spéciales le jour de votre anniversaire'
        );
        
        return $fields;
    }
    
    /**
     * Sauvegarder le champ anniversaire lors du checkout
     */
    public function save_birthday_checkout_field($user_id) {
        if (isset($_POST['billing_birthday'])) {
            update_user_meta($user_id, 'billing_birthday', sanitize_text_field($_POST['billing_birthday']));
        }
    }
    
    /**
     * Enqueue des scripts et styles frontend
     */
    public function enqueue_scripts() {
        wp_enqueue_style('newsaiige-loyalty-style', NEWSAIIGE_LOYALTY_URL . 'assets/loyalty.css', array(), NEWSAIIGE_LOYALTY_VERSION);
        wp_enqueue_script('newsaiige-loyalty-script', NEWSAIIGE_LOYALTY_URL . 'assets/loyalty.js', array('jquery'), NEWSAIIGE_LOYALTY_VERSION, true);
        
        // Localiser le script pour AJAX
        wp_localize_script('newsaiige-loyalty-script', 'newsaiige_loyalty_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('newsaiige_loyalty_nonce')
        ));
    }
    
    /**
     * Enqueue des scripts admin
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'newsaiige-loyalty') !== false) {
            wp_enqueue_style('newsaiige-loyalty-admin-style', NEWSAIIGE_LOYALTY_URL . 'assets/admin.css', array(), NEWSAIIGE_LOYALTY_VERSION);
            wp_enqueue_script('newsaiige-loyalty-admin-script', NEWSAIIGE_LOYALTY_URL . 'assets/admin.js', array('jquery'), NEWSAIIGE_LOYALTY_VERSION, true);
        }
    }
    
    /**
     * AJAX handler pour récupérer les statistiques utilisateur
     */
    public function ajax_get_user_stats() {
        if (!wp_verify_nonce($_POST['nonce'], 'newsaiige_loyalty_nonce') || !is_user_logged_in()) {
            wp_send_json_error('Accès non autorisé');
        }
        
        global $newsaiige_loyalty;
        if (!isset($newsaiige_loyalty)) {
            wp_send_json_error('Système non disponible');
        }
        
        $user_id = get_current_user_id();
        
        $data = array(
            'points_available' => $newsaiige_loyalty->get_user_points($user_id),
            'points_lifetime' => $newsaiige_loyalty->get_user_lifetime_points($user_id),
            'current_tier' => $newsaiige_loyalty->get_user_tier($user_id),
            'next_tier' => $newsaiige_loyalty->get_next_tier($user_id),
            'vouchers' => $newsaiige_loyalty->get_user_vouchers($user_id),
            'has_subscription' => $newsaiige_loyalty->has_active_subscription($user_id)
        );
        
        wp_send_json_success($data);
    }
}

// Initialiser le plugin
new NewsaiigeLoyaltyPlugin();

/**
 * Fonction utilitaire pour obtenir les points d'un utilisateur (pour les thèmes)
 */
function newsaiige_get_user_loyalty_points($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return 0;
    }
    
    global $newsaiige_loyalty;
    if (!isset($newsaiige_loyalty)) {
        return 0;
    }
    
    return $newsaiige_loyalty->get_user_points($user_id);
}

/**
 * Fonction utilitaire pour obtenir le palier d'un utilisateur
 */
function newsaiige_get_user_loyalty_tier($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return null;
    }
    
    global $newsaiige_loyalty;
    if (!isset($newsaiige_loyalty)) {
        return null;
    }
    
    return $newsaiige_loyalty->get_user_tier($user_id);
}

/**
 * Widget personnalisé pour afficher les points de fidélité
 */
class Newsaiige_Loyalty_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'newsaiige_loyalty_widget',
            'Points de Fidélité Newsaiige',
            array('description' => 'Affiche les points de fidélité de l\'utilisateur connecté')
        );
    }
    
    public function widget($args, $instance) {
        if (!is_user_logged_in()) {
            return;
        }
        
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $user_id = get_current_user_id();
        $points = newsaiige_get_user_loyalty_points($user_id);
        $tier = newsaiige_get_user_loyalty_tier($user_id);
        
        echo '<div class="loyalty-widget">';
        echo '<p class="points-display"><strong>' . number_format($points) . '</strong> points disponibles</p>';
        
        if ($tier) {
            echo '<p class="tier-display">Palier: <span class="tier-name">' . esc_html($tier->tier_name) . '</span></p>';
        }
        
        $loyalty_page = get_page_by_path('mon-programme-fidelite');
        if ($loyalty_page) {
            echo '<p><a href="' . get_permalink($loyalty_page->ID) . '" class="loyalty-link">Voir mon programme →</a></p>';
        }
        
        echo '</div>';
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Mes Points de Fidélité';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Titre:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }
}

// Enregistrer le widget
add_action('widgets_init', function() {
    register_widget('Newsaiige_Loyalty_Widget');
});

/**
 * Ajouter des liens dans l'espace client WooCommerce
 */
add_filter('woocommerce_account_menu_items', 'newsaiige_loyalty_account_menu_items');

function newsaiige_loyalty_account_menu_items($items) {
    // Ajouter le lien après "Tableau de bord"
    $new_items = array();
    
    foreach ($items as $key => $item) {
        $new_items[$key] = $item;
        
        if ($key === 'dashboard') {
            $new_items['loyalty'] = 'Programme de Fidélité';
        }
    }
    
    return $new_items;
}

// Ajouter l'endpoint pour l'espace client
add_action('init', function() {
    add_rewrite_endpoint('loyalty', EP_ROOT | EP_PAGES);
});

// Contenu de la page fidélité dans l'espace client
add_action('woocommerce_account_loyalty_endpoint', function() {
    echo do_shortcode('[newsaiige_loyalty]');
});

/**
 * Notifications sur le tableau de bord admin
 */
add_action('wp_dashboard_setup', 'newsaiige_loyalty_dashboard_widgets');

function newsaiige_loyalty_dashboard_widgets() {
    wp_add_dashboard_widget(
        'newsaiige_loyalty_dashboard_widget',
        'Programme de Fidélité - Aperçu',
        'newsaiige_loyalty_dashboard_widget_content'
    );
}

function newsaiige_loyalty_dashboard_widget_content() {
    global $wpdb;
    
    $points_table = $wpdb->prefix . 'newsaiige_loyalty_points';
    $vouchers_table = $wpdb->prefix . 'newsaiige_loyalty_vouchers';
    
    // Statistiques rapides
    $total_points = $wpdb->get_var("SELECT SUM(points_earned) FROM $points_table WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $active_vouchers = $wpdb->get_var("SELECT COUNT(*) FROM $vouchers_table WHERE is_used = 0 AND expires_at > NOW()");
    $new_users_this_month = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $points_table WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    
    echo '<div class="loyalty-dashboard-stats">';
    echo '<p><strong>Points gagnés ce mois:</strong> ' . number_format($total_points ?: 0) . '</p>';
    echo '<p><strong>Bons d\'achat actifs:</strong> ' . ($active_vouchers ?: 0) . '</p>';
    echo '<p><strong>Nouveaux membres ce mois:</strong> ' . ($new_users_this_month ?: 0) . '</p>';
    echo '<p><a href="' . admin_url('admin.php?page=newsaiige-loyalty') . '" class="button">Gérer le programme →</a></p>';
    echo '</div>';
}
?>