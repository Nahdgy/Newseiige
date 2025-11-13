<?php
/**
 * Système de Fidélité Newsaiige - Version Thème
 * Description: Système complet de fidélité adapté pour utilisation dans un thème
 * Version: 1.0.1
 * Author: Newsaiige
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Éviter les doubles chargements
if (defined('NEWSAIIGE_LOYALTY_LOADED')) {
    return;
}

// Définir les constantes adaptées pour un thème
define('NEWSAIIGE_LOYALTY_LOADED', true);
define('NEWSAIIGE_LOYALTY_VERSION', '1.0.1');
define('NEWSAIIGE_LOYALTY_PATH', get_template_directory() . '/Fidelity/');
define('NEWSAIIGE_LOYALTY_URL', get_template_directory_uri() . '/Fidelity/');

// Vérifier que les fichiers existent avant de les inclure
$required_files = array(
    'loyalty-system.php',
    'loyalty-admin.php',
    'loyalty-woocommerce.php'
);

foreach ($required_files as $file) {
    $file_path = NEWSAIIGE_LOYALTY_PATH . $file;
    if (file_exists($file_path)) {
        require_once $file_path;
    } else {
        add_action('admin_notices', function() use ($file) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>NewSaiige Loyalty:</strong> Fichier manquant - ' . esc_html($file);
            echo '</p></div>';
        });
        return; // Arrêter si des fichiers sont manquants
    }
}

/**
 * Initialisation du système pour thème
 */
class NewsaiigeLoyaltyTheme {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Initialisation différée pour éviter les conflits
        add_action('after_setup_theme', array($this, 'init'), 15);
    }
    
    public function init() {
        // Vérifier que WooCommerce est actif
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Hooks pour les champs utilisateur
        $this->setup_user_hooks();
        
        // Hooks pour les scripts et styles
        $this->setup_assets_hooks();
        
        // Créer la page de fidélité si elle n'existe pas
        add_action('wp_loaded', array($this, 'maybe_create_loyalty_page'));
        
        // Hook Ajax avec vérification d'existence
        add_action('wp_ajax_loyalty_get_user_stats', array($this, 'ajax_get_user_stats'));
        add_action('wp_ajax_nopriv_loyalty_get_user_stats', array($this, 'ajax_get_user_stats'));
    }
    
    private function setup_user_hooks() {
        add_action('show_user_profile', array($this, 'add_birthday_field'));
        add_action('edit_user_profile', array($this, 'add_birthday_field'));
        add_action('personal_options_update', array($this, 'save_birthday_field'));
        add_action('edit_user_profile_update', array($this, 'save_birthday_field'));
        
        add_action('woocommerce_register_form', array($this, 'add_birthday_registration_field'));
        add_action('woocommerce_created_customer', array($this, 'save_birthday_registration_field'));
        
        add_filter('woocommerce_billing_fields', array($this, 'add_birthday_billing_field'));
        add_action('woocommerce_checkout_update_user_meta', array($this, 'save_birthday_checkout_field'));
    }
    
    private function setup_assets_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>NewSaiige Loyalty:</strong> Ce système nécessite WooCommerce pour fonctionner.';
        echo '</p></div>';
    }
    
    /**
     * Créer la page de fidélité si elle n'existe pas
     */
    public function maybe_create_loyalty_page() {
        $page_slug = 'programme-fidelite';
        $page = get_page_by_path($page_slug);
        
        if (!$page) {
            $page_id = wp_insert_post(array(
                'post_title' => 'Mon Programme de Fidélité',
                'post_content' => '[newsaiige_loyalty title="Mon Programme de Fidélité" subtitle="Gagnez des points et profitez d\'avantages exclusifs"]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => $page_slug
            ));
        }
    }
    
    /**
     * Champ anniversaire dans le profil utilisateur
     */
    public function add_birthday_field($user) {
        ?>
        <h3>Informations de fidélité</h3>
        <table class="form-table">
            <tr>
                <th><label for="birthday">Date d'anniversaire</label></th>
                <td>
                    <input type="date" 
                           id="birthday" 
                           name="birthday" 
                           value="<?php echo esc_attr(get_user_meta($user->ID, 'billing_birthday', true)); ?>" 
                           class="regular-text" />
                    <p class="description">Utilisé pour vous envoyer des offres spéciales le jour de votre anniversaire.</p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    public function save_birthday_field($user_id) {
        if (isset($_POST['birthday'])) {
            update_user_meta($user_id, 'billing_birthday', sanitize_text_field($_POST['birthday']));
        }
    }
    
    public function add_birthday_registration_field() {
        ?>
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="reg_birthday">Date d'anniversaire <span class="optional">(optionnel)</span></label>
            <input type="date" class="woocommerce-Input woocommerce-Input--text input-text" name="birthday" id="reg_birthday" />
        </p>
        <?php
    }
    
    public function save_birthday_registration_field($customer_id) {
        if (isset($_POST['birthday'])) {
            update_user_meta($customer_id, 'billing_birthday', sanitize_text_field($_POST['birthday']));
        }
    }
    
    public function add_birthday_billing_field($fields) {
        $fields['billing_birthday'] = array(
            'label' => 'Date d\'anniversaire',
            'type' => 'date',
            'required' => false,
            'class' => array('form-row-wide'),
            'priority' => 120
        );
        return $fields;
    }
    
    public function save_birthday_checkout_field($user_id) {
        if (isset($_POST['billing_birthday'])) {
            update_user_meta($user_id, 'billing_birthday', sanitize_text_field($_POST['billing_birthday']));
        }
    }
    
    public function enqueue_scripts() {
        if (file_exists(NEWSAIIGE_LOYALTY_PATH . 'assets/loyalty.css')) {
            wp_enqueue_style('newsaiige-loyalty', NEWSAIIGE_LOYALTY_URL . 'assets/loyalty.css', array(), NEWSAIIGE_LOYALTY_VERSION);
        }
        
        if (file_exists(NEWSAIIGE_LOYALTY_PATH . 'assets/loyalty.js')) {
            wp_enqueue_script('newsaiige-loyalty', NEWSAIIGE_LOYALTY_URL . 'assets/loyalty.js', array('jquery'), NEWSAIIGE_LOYALTY_VERSION, true);
            wp_localize_script('newsaiige-loyalty', 'newsaiige_loyalty_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('newsaiige_loyalty_nonce')
            ));
        }
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'newsaiige-loyalty') !== false && file_exists(NEWSAIIGE_LOYALTY_PATH . 'assets/admin.css')) {
            wp_enqueue_style('newsaiige-loyalty-admin', NEWSAIIGE_LOYALTY_URL . 'assets/admin.css', array(), NEWSAIIGE_LOYALTY_VERSION);
            wp_enqueue_script('newsaiige-loyalty-admin', NEWSAIIGE_LOYALTY_URL . 'assets/admin.js', array('jquery'), NEWSAIIGE_LOYALTY_VERSION, true);
        }
    }
    
    public function ajax_get_user_stats() {
        if (!wp_verify_nonce($_POST['nonce'], 'newsaiige_loyalty_nonce')) {
            wp_die('Erreur de sécurité');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Non connecté');
        }
        
        global $newsaiige_loyalty;
        if (isset($newsaiige_loyalty)) {
            $user_id = get_current_user_id();
            wp_send_json_success(array(
                'points' => $newsaiige_loyalty->get_user_points($user_id),
                'tier' => $newsaiige_loyalty->get_user_tier($user_id)
            ));
        } else {
            wp_send_json_error('Système non initialisé');
        }
    }
}

// Initialiser le système de manière sécurisée
add_action('init', function() {
    // Vérifier que nous sommes dans un contexte WordPress valide
    if (function_exists('is_admin') && function_exists('wp_get_current_user')) {
        NewsaiigeLoyaltyTheme::get_instance();
    }
}, 5);

/**
 * Fonctions utilitaires (compatibles avec l'ancien système)
 */
if (!function_exists('newsaiige_get_user_loyalty_points')) {
    function newsaiige_get_user_loyalty_points($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return 0;
        }
        
        global $newsaiige_loyalty;
        if (isset($newsaiige_loyalty) && method_exists($newsaiige_loyalty, 'get_user_points')) {
            return $newsaiige_loyalty->get_user_points($user_id);
        }
        
        return 0;
    }
}

if (!function_exists('newsaiige_get_user_loyalty_tier')) {
    function newsaiige_get_user_loyalty_tier($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return null;
        }
        
        global $newsaiige_loyalty;
        if (isset($newsaiige_loyalty) && method_exists($newsaiige_loyalty, 'get_user_tier')) {
            return $newsaiige_loyalty->get_user_tier($user_id);
        }
        
        return null;
    }
}

// Widget personnalisé pour les points de fidélité (optionnel)
if (class_exists('WP_Widget')) {
    class Newsaiige_Loyalty_Widget extends WP_Widget {
        
        public function __construct() {
            parent::__construct(
                'newsaiige_loyalty_widget',
                'Points de Fidélité NewSaiige',
                array('description' => 'Affiche les points de fidélité de l\'utilisateur connecté')
            );
        }
        
        public function widget($args, $instance) {
            if (!is_user_logged_in()) return;
            
            echo $args['before_widget'];
            
            $title = !empty($instance['title']) ? $instance['title'] : 'Mes Points';
            echo $args['before_title'] . apply_filters('widget_title', $title) . $args['after_title'];
            
            $points = newsaiige_get_user_loyalty_points();
            $tier = newsaiige_get_user_loyalty_tier();
            
            echo '<div class="newsaiige-loyalty-widget">';
            echo '<p><strong>Points disponibles :</strong> ' . number_format($points) . '</p>';
            if ($tier) {
                echo '<p><strong>Palier actuel :</strong> ' . esc_html($tier->tier_name) . '</p>';
            }
            echo '<p><a href="' . esc_url(get_permalink(get_page_by_path('programme-fidelite'))) . '">Voir mon programme →</a></p>';
            echo '</div>';
            
            echo $args['after_widget'];
        }
        
        public function form($instance) {
            $title = !empty($instance['title']) ? $instance['title'] : 'Mes Points';
            ?>
            <p>
                <label for="<?php echo $this->get_field_id('title'); ?>">Titre :</label>
                <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
            </p>
            <?php
        }
        
        public function update($new_instance, $old_instance) {
            $instance = array();
            $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
            return $instance;
        }
    }
    
    // Enregistrer le widget de manière sécurisée
    add_action('widgets_init', function() {
        if (class_exists('Newsaiige_Loyalty_Widget')) {
            register_widget('Newsaiige_Loyalty_Widget');
        }
    });
}
?>