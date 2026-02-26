<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HELLO_ELEMENTOR_VERSION', '3.4.4' );
define( 'EHP_THEME_SLUG', 'hello-elementor' );

define( 'HELLO_THEME_PATH', get_template_directory() );
define( 'HELLO_THEME_URL', get_template_directory_uri() );
define( 'HELLO_THEME_ASSETS_PATH', HELLO_THEME_PATH . '/assets/' );
define( 'HELLO_THEME_ASSETS_URL', HELLO_THEME_URL . '/assets/' );
define( 'HELLO_THEME_SCRIPTS_PATH', HELLO_THEME_ASSETS_PATH . 'js/' );
define( 'HELLO_THEME_SCRIPTS_URL', HELLO_THEME_ASSETS_URL . 'js/' );
define( 'HELLO_THEME_STYLE_PATH', HELLO_THEME_ASSETS_PATH . 'css/' );
define( 'HELLO_THEME_STYLE_URL', HELLO_THEME_ASSETS_URL . 'css/' );
define( 'HELLO_THEME_IMAGES_PATH', HELLO_THEME_ASSETS_PATH . 'images/' );
define( 'HELLO_THEME_IMAGES_URL', HELLO_THEME_ASSETS_URL . 'images/' );

if ( ! isset( $content_width ) ) {
	$content_width = 800; // Pixels.
}

if ( ! function_exists( 'hello_elementor_setup' ) ) {
	/**
	 * Set up theme support.
	 *
	 * @return void
	 */
	function hello_elementor_setup() {
		if ( is_admin() ) {
			hello_maybe_update_theme_version_in_db();
		}

		if ( apply_filters( 'hello_elementor_register_menus', true ) ) {
			register_nav_menus( [ 'menu-1' => esc_html__( 'Header', 'hello-elementor' ) ] );
			register_nav_menus( [ 'menu-2' => esc_html__( 'Footer', 'hello-elementor' ) ] );
		}

		if ( apply_filters( 'hello_elementor_post_type_support', true ) ) {
			add_post_type_support( 'page', 'excerpt' );
		}

		if ( apply_filters( 'hello_elementor_add_theme_support', true ) ) {
			add_theme_support( 'post-thumbnails' );
			add_theme_support( 'automatic-feed-links' );
			add_theme_support( 'title-tag' );
			add_theme_support(
				'html5',
				[
					'search-form',
					'comment-form',
					'comment-list',
					'gallery',
					'caption',
					'script',
					'style',
					'navigation-widgets',
				]
			);
			add_theme_support(
				'custom-logo',
				[
					'height'      => 100,
					'width'       => 350,
					'flex-height' => true,
					'flex-width'  => true,
				]
			);
			add_theme_support( 'align-wide' );
			add_theme_support( 'responsive-embeds' );

			/*
			 * Editor Styles
			 */
			add_theme_support( 'editor-styles' );
			add_editor_style( 'editor-styles.css' );

			/*
			 * WooCommerce.
			 */
			if ( apply_filters( 'hello_elementor_add_woocommerce_support', true ) ) {
				// WooCommerce in general.
				add_theme_support( 'woocommerce' );
				// Enabling WooCommerce product gallery features (are off by default since WC 3.0.0).
				// zoom.
				add_theme_support( 'wc-product-gallery-zoom' );
				// lightbox.
				add_theme_support( 'wc-product-gallery-lightbox' );
				// swipe.
				add_theme_support( 'wc-product-gallery-slider' );
			}
		}
	}
}
add_action( 'after_setup_theme', 'hello_elementor_setup' );

function hello_maybe_update_theme_version_in_db() {
	$theme_version_option_name = 'hello_theme_version';
	// The theme version saved in the database.
	$hello_theme_db_version = get_option( $theme_version_option_name );

	// If the 'hello_theme_version' option does not exist in the DB, or the version needs to be updated, do the update.
	if ( ! $hello_theme_db_version || version_compare( $hello_theme_db_version, HELLO_ELEMENTOR_VERSION, '<' ) ) {
		update_option( $theme_version_option_name, HELLO_ELEMENTOR_VERSION );
	}
}

if ( ! function_exists( 'hello_elementor_display_header_footer' ) ) {
	/**
	 * Check whether to display header footer.
	 *
	 * @return bool
	 */
	function hello_elementor_display_header_footer() {
		$hello_elementor_header_footer = true;

		return apply_filters( 'hello_elementor_header_footer', $hello_elementor_header_footer );
	}
}

if ( ! function_exists( 'hello_elementor_scripts_styles' ) ) {
	/**
	 * Theme Scripts & Styles.
	 *
	 * @return void
	 */
	function hello_elementor_scripts_styles() {
		if ( apply_filters( 'hello_elementor_enqueue_style', true ) ) {
			wp_enqueue_style(
				'hello-elementor',
				HELLO_THEME_STYLE_URL . 'reset.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}

		if ( apply_filters( 'hello_elementor_enqueue_theme_style', true ) ) {
			wp_enqueue_style(
				'hello-elementor-theme-style',
				HELLO_THEME_STYLE_URL . 'theme.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}

		if ( hello_elementor_display_header_footer() ) {
			wp_enqueue_style(
				'hello-elementor-header-footer',
				HELLO_THEME_STYLE_URL . 'header-footer.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}
	}
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_scripts_styles' );

if ( ! function_exists( 'hello_elementor_register_elementor_locations' ) ) {
	/**
	 * Register Elementor Locations.
	 *
	 * @param ElementorPro\Modules\ThemeBuilder\Classes\Locations_Manager $elementor_theme_manager theme manager.
	 *
	 * @return void
	 */
	function hello_elementor_register_elementor_locations( $elementor_theme_manager ) {
		if ( apply_filters( 'hello_elementor_register_elementor_locations', true ) ) {
			$elementor_theme_manager->register_all_core_location();
		}
	}
}
add_action( 'elementor/theme/register_locations', 'hello_elementor_register_elementor_locations' );

if ( ! function_exists( 'hello_elementor_content_width' ) ) {
	/**
	 * Set default content width.
	 *
	 * @return void
	 */
	function hello_elementor_content_width() {
		$GLOBALS['content_width'] = apply_filters( 'hello_elementor_content_width', 800 );
	}
}
add_action( 'after_setup_theme', 'hello_elementor_content_width', 0 );

if ( ! function_exists( 'hello_elementor_add_description_meta_tag' ) ) {
	/**
	 * Add description meta tag with excerpt text.
	 *
	 * @return void
	 */
	function hello_elementor_add_description_meta_tag() {
		if ( ! apply_filters( 'hello_elementor_description_meta_tag', true ) ) {
			return;
		}

		if ( ! is_singular() ) {
			return;
		}

		$post = get_queried_object();
		if ( empty( $post->post_excerpt ) ) {
			return;
		}

		echo '<meta name="description" content="' . esc_attr( wp_strip_all_tags( $post->post_excerpt ) ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'hello_elementor_add_description_meta_tag' );

// Settings page
require get_template_directory() . '/includes/settings-functions.php';

// Header & footer styling option, inside Elementor
require get_template_directory() . '/includes/elementor-functions.php';

if ( ! function_exists( 'hello_elementor_customizer' ) ) {
	// Customizer controls
	function hello_elementor_customizer() {
		if ( ! is_customize_preview() ) {
			return;
		}

		if ( ! hello_elementor_display_header_footer() ) {
			return;
		}

		require get_template_directory() . '/includes/customizer-functions.php';
	}
}
add_action( 'init', 'hello_elementor_customizer' );

if ( ! function_exists( 'hello_elementor_check_hide_title' ) ) {
	/**
	 * Check whether to display the page title.
	 *
	 * @param bool $val default value.
	 *
	 * @return bool
	 */
	function hello_elementor_check_hide_title( $val ) {
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			$current_doc = Elementor\Plugin::instance()->documents->get( get_the_ID() );
			if ( $current_doc && 'yes' === $current_doc->get_settings( 'hide_title' ) ) {
				$val = false;
			}
		}
		return $val;
	}
}
add_filter( 'hello_elementor_page_title', 'hello_elementor_check_hide_title' );

/**
 * BC:
 * In v2.7.0 the theme removed the `hello_elementor_body_open()` from `header.php` replacing it with `wp_body_open()`.
 * The following code prevents fatal errors in child themes that still use this function.
 */
if ( ! function_exists( 'hello_elementor_body_open' ) ) {
	function hello_elementor_body_open() {
		wp_body_open();
	}
}

//Empecher de cocher un choix par défaut pour les choix de modes de livraisons
add_action('woocommerce_checkout_process', function() {
    if (isset($_POST['shipping_method'][0]) && $_POST['shipping_method'][0] == 'shipping_method_0_flat_rate10') { 
        wc_add_notice(__('Veuillez sélectionner un mode de livraison.'), 'error');
    }
});



// Ajouter le champ téléphone dans la section "Informations de contact" existante
add_filter('user_contactmethods', 'newsaiige_add_phone_contact_method');

function newsaiige_add_phone_contact_method($contact_methods) {
    // Ajouter le champ téléphone aux méthodes de contact WordPress
    $contact_methods['phone'] = 'Téléphone';
    
    return $contact_methods;
}

// Optionnel : Personnaliser l'affichage du champ téléphone avec du JavaScript
add_action('admin_footer-user-edit.php', 'newsaiige_customize_phone_field');
add_action('admin_footer-profile.php', 'newsaiige_customize_phone_field');

function newsaiige_customize_phone_field() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Modifier le type d'input pour le téléphone
        var phoneInput = $('#phone');
        if (phoneInput.length) {
            phoneInput.attr('type', 'tel');
            phoneInput.attr('placeholder', '06 01 02 03 04');
            phoneInput.attr('pattern', '[0-9+\\s\\-\\.\\(\\)]+');
            
            // Ajouter une description personnalisée
            if (!phoneInput.siblings('.description').length) {
                phoneInput.after('<p class="description">Format recommandé : 06 01 02 03 04</p>');
            }
            
            // Validation en temps réel
            phoneInput.on('blur', function() {
                var phone = $(this).val();
                if (phone && phone.length > 0) {
                    // Nettoyer le numéro
                    var cleanPhone = phone.replace(/[^0-9+]/g, '');
                    
                    // Vérification basique du format français
                    var isValid = /^(?:(?:\+|00)33|0)\s*[1-9](?:[0-9]\s*){8}$/.test(phone) || 
                                 /^[0-9]{10}$/.test(cleanPhone);
                    
                    if (!isValid) {
                        $(this).css('border-color', '#dc3232');
                        if (!$(this).siblings('.phone-error').length) {
                            $(this).after('<p class="phone-error description" style="color: #dc3232;">Format recommandé : 06 01 02 03 04 ou +33 6 01 02 03 04</p>');
                        }
                    } else {
                        $(this).css('border-color', '#46b450');
                        $(this).siblings('.phone-error').remove();
                    }
                } else {
                    $(this).css('border-color', '');
                    $(this).siblings('.phone-error').remove();
                }
            });
        }
    });
    </script>
    <?php
}

// Note: WordPress gère automatiquement la sauvegarde des champs de contact
// Le code ci-dessous est maintenu pour des fonctionnalités supplémentaires de validation

// Ajouter le champ téléphone dans la liste des utilisateurs (colonnes)
add_filter('manage_users_columns', 'newsaiige_add_phone_column');

function newsaiige_add_phone_column($columns) {
    $columns['phone'] = 'Téléphone';
    return $columns;
}

// Afficher le contenu de la colonne téléphone
add_filter('manage_users_custom_column', 'newsaiige_show_phone_column_content', 10, 3);

function newsaiige_show_phone_column_content($value, $column_name, $user_id) {
    if ($column_name == 'phone') {
        $phone = get_user_meta($user_id, 'phone', true);
        return $phone ? esc_html($phone) : '—';
    }
    return $value;
}

// Rendre la colonne téléphone triable
add_filter('manage_users_sortable_columns', 'newsaiige_make_phone_column_sortable');

function newsaiige_make_phone_column_sortable($columns) {
    $columns['phone'] = 'phone';
    return $columns;
}

// Gérer le tri de la colonne téléphone
add_action('pre_get_users', 'newsaiige_sort_users_by_phone');

function newsaiige_sort_users_by_phone($query) {
    if (!is_admin()) {
        return;
    }

    $orderby = $query->get('orderby');
    if ($orderby == 'phone') {
        $query->set('meta_key', 'phone');
        $query->set('orderby', 'meta_value');
    }
}

// Ajouter le champ téléphone dans les champs de recherche utilisateur
add_filter('user_search_columns', 'newsaiige_add_phone_to_search', 10, 3);

function newsaiige_add_phone_to_search($search_columns, $search, $wp_user_query) {
    $search_columns[] = 'phone';
    return $search_columns;
}

// Hook pour rechercher dans les métadonnées utilisateur
add_action('pre_user_query', 'newsaiige_search_phone_meta');

function newsaiige_search_phone_meta($user_query) {
    global $wpdb;
    
    if (isset($user_query->query_vars['search']) && !empty($user_query->query_vars['search'])) {
        $search = trim($user_query->query_vars['search'], '*');
        
        if (!empty($search)) {
            $user_query->query_from .= " LEFT JOIN {$wpdb->usermeta} um_phone ON {$wpdb->users}.ID = um_phone.user_id AND um_phone.meta_key = 'phone'";
            
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $user_query->query_where = str_replace(
                'WHERE 1=1 AND (',
                "WHERE 1=1 AND (um_phone.meta_value LIKE '{$search_term}' OR ",
                $user_query->query_where
            );
        }
    }
}

// Fonction utilitaire pour récupérer le téléphone d'un utilisateur (à utiliser dans vos templates)
function newsaiige_get_user_phone($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    return get_user_meta($user_id, 'phone', true);
}

// Fonction pour formater le numéro de téléphone
function newsaiige_format_phone($phone) {
    if (empty($phone)) {
        return '';
    }
    
    // Nettoyer le numéro
    $clean = preg_replace('/[^0-9]/', '', $phone);
    
    // Format français standard
    if (strlen($clean) === 10 && substr($clean, 0, 1) === '0') {
        return substr($clean, 0, 2) . ' ' . 
               substr($clean, 2, 2) . ' ' . 
               substr($clean, 4, 2) . ' ' . 
               substr($clean, 6, 2) . ' ' . 
               substr($clean, 8, 2);
    }
    
    return $phone; // Retourner tel quel si pas de format reconnu
}

// Ajouter des styles CSS pour améliorer l'affichage dans l'admin
add_action('admin_head', 'newsaiige_admin_phone_styles');

function newsaiige_admin_phone_styles() {
    ?>
    <style>
    .column-phone {
        width: 150px;
    }
    
    #phone {
        max-width: 300px;
    }
    
    .form-table th[scope="row"] {
        width: 150px;
    }
    </style>
    <?php
}

// Validation AJAX du numéro de téléphone (optionnel)
add_action('wp_ajax_validate_phone', 'newsaiige_validate_phone_ajax');
add_action('wp_ajax_nopriv_validate_phone', 'newsaiige_validate_phone_ajax');

function newsaiige_validate_phone_ajax() {
    check_ajax_referer('validate_phone_nonce', 'nonce');
    
    $phone = sanitize_text_field($_POST['phone']);
    $is_valid = false;
    $message = '';
    
    if (empty($phone)) {
        $is_valid = true;
        $message = 'Le champ téléphone peut être vide.';
    } else {
        $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
        
        if (preg_match('/^(?:(?:\+|00)33|0)\s*[1-9](?:[0-9]\s*){8}$/', $phone) || 
            preg_match('/^[0-9]{10}$/', $clean_phone)) {
            $is_valid = true;
            $message = 'Numéro de téléphone valide.';
        } else {
            $message = 'Format de téléphone incorrect. Utilisez le format : 06 01 02 03 04';
        }
    }
    
    wp_send_json(array(
        'valid' => $is_valid,
        'message' => $message
    ));
}

// 1. AJAX Handlers pour frontend
add_action('wp_ajax_newsaiige_submit_review', 'handle_submit_newsaiige_review');
add_action('wp_ajax_nopriv_newsaiige_submit_review', 'handle_submit_newsaiige_review');
add_action('wp_ajax_get_newsaiige_reviews', 'handle_get_newsaiige_reviews');
add_action('wp_ajax_nopriv_get_newsaiige_reviews', 'handle_get_newsaiige_reviews');

// 2. Menu admin
add_action('admin_menu', 'newsaiige_reviews_admin_menu');

// 3. AJAX admin
add_action('wp_ajax_newsaiige_admin_action', 'handle_newsaiige_admin_action');

// ===== FONCTIONS FRONTEND =====

function handle_submit_newsaiige_review() {
    // Vérification nonce
    if (!wp_verify_nonce($_POST['nonce'], 'newsaiige_review_nonce')) {
        wp_send_json_error('Erreur de sécurité');
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsaiige_reviews';
    
    // Validation des données
    $customer_name = sanitize_text_field($_POST['customer_name']);
    $customer_email = sanitize_email($_POST['customer_email']);
    $rating = intval($_POST['rating']);
    $comment = sanitize_textarea_field($_POST['comment']);
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    $service_name = isset($_POST['service_name']) ? sanitize_text_field($_POST['service_name']) : '';
    
    if (empty($customer_name) || empty($comment) || $rating < 1 || $rating > 5) {
        wp_send_json_error('Données invalides');
    }
    
    // Vérifier doublons (même email, dernières 24h)
    if (!empty($customer_email)) {
        $recent = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
             WHERE customer_email = %s 
             AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            $customer_email
        ));
        
        if ($recent > 0) {
            wp_send_json_error('Vous avez déjà laissé un avis récemment.');
        }
    }
    
    // Insérer en base
    $result = $wpdb->insert(
        $table_name,
        array(
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'rating' => $rating,
            'comment' => $comment,
            'service_id' => $service_id,
            'service_name' => $service_name,
            'status' => 'pending',
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ),
        array('%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s')
    );
    
    if ($result === false) {
        wp_send_json_error('Erreur lors de l\'enregistrement');
    }
    
    // Notification email admin
    $admin_email = get_option('admin_email');
    wp_mail($admin_email, '[NEWSAIIGE] Nouvel avis client', 
        "Nouvel avis de {$customer_name} ({$rating}/5 étoiles)\n\nModérer: " . admin_url('admin.php?page=newsaiige-reviews'));
    
    wp_send_json_success('Merci pour votre avis ! Il sera publié après modération.');
}

function handle_get_newsaiige_reviews() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsaiige_reviews';
    
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    
    // Récupérer avis approuvés
    $reviews = $wpdb->get_results($wpdb->prepare(
        "SELECT customer_name, rating, comment, created_at 
         FROM $table_name 
         WHERE status = 'approved' 
         ORDER BY created_at DESC 
         LIMIT %d",
        $limit
    ));
    
    // Statistiques
    $stats = $wpdb->get_row(
        "SELECT 
            COUNT(*) as total_reviews,
            AVG(rating) as average_rating
         FROM $table_name 
         WHERE status = 'approved'"
    );
    
    wp_send_json_success(array(
        'reviews' => $reviews,
        'stats' => $stats
    ));
}

// ===== INTERFACE ADMIN =====

function newsaiige_reviews_admin_menu() {
    add_menu_page(
        'Avis NEWSAIIGE',
        'Avis Clients',
        'manage_options',
        'newsaiige-reviews',
        'newsaiige_reviews_admin_page',
        'dashicons-star-filled',
        30
    );
}

function newsaiige_reviews_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsaiige_reviews';
    
    // Traitement des actions
    if (isset($_POST['action']) && wp_verify_nonce($_POST['_wpnonce'], 'newsaiige_admin_nonce')) {
        $action = sanitize_text_field($_POST['action']);
        $review_id = intval($_POST['review_id']);
        
        switch ($action) {
            case 'approve':
                $wpdb->update($table_name, array('status' => 'approved'), array('id' => $review_id), array('%s'), array('%d'));
                break;
            case 'reject':
                $wpdb->update($table_name, array('status' => 'rejected'), array('id' => $review_id), array('%s'), array('%d'));
                break;
            case 'delete':
                $wpdb->delete($table_name, array('id' => $review_id), array('%d'));
                break;
        }
        echo '<div class="notice notice-success"><p>Action effectuée avec succès !</p></div>';
    }
    
    // Récupérer tous les avis
    $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
    $where = $status_filter !== 'all' ? $wpdb->prepare("WHERE status = %s", $status_filter) : '';
    
    $reviews = $wpdb->get_results("SELECT * FROM $table_name $where ORDER BY created_at DESC");
    
    // Compter par statut
    $counts = $wpdb->get_results("SELECT status, COUNT(*) as count FROM $table_name GROUP BY status");
    $status_counts = array('pending' => 0, 'approved' => 0, 'rejected' => 0);
    foreach ($counts as $count) {
        $status_counts[$count->status] = $count->count;
    }
    ?>
    
    <div class="wrap">
        <h1>Gestion des Avis Clients NEWSAIIGE</h1>
        
        <!-- Filtres -->
        <div class="tablenav top">
            <div class="alignleft actions">
                <select onchange="window.location.href='<?php echo admin_url('admin.php?page=newsaiige-reviews'); ?>&status=' + this.value">
                    <option value="all" <?php selected($status_filter, 'all'); ?>>Tous (<?php echo array_sum($status_counts); ?>)</option>
                    <option value="pending" <?php selected($status_filter, 'pending'); ?>>En attente (<?php echo $status_counts['pending']; ?>)</option>
                    <option value="approved" <?php selected($status_filter, 'approved'); ?>>Approuvés (<?php echo $status_counts['approved']; ?>)</option>
                    <option value="rejected" <?php selected($status_filter, 'rejected'); ?>>Rejetés (<?php echo $status_counts['rejected']; ?>)</option>
                </select>
            </div>
        </div>
        
        <!-- Tableau des avis -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Note</th>
                    <th>Commentaire</th>
                    <th>Prestation</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reviews)): ?>
                    <tr><td colspan="7">Aucun avis trouvé.</td></tr>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($review->customer_name); ?></strong>
                                <?php if (!empty($review->customer_email)): ?>
                                    <br><small><?php echo esc_html($review->customer_email); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span style="color: <?php echo $i <= $review->rating ? '#FFD700' : '#ddd'; ?>;">★</span>
                                <?php endfor; ?>
                                <br><small><?php echo $review->rating; ?>/5</small>
                            </td>
                            <td>
                                <div style="max-width: 300px;">
                                    <?php echo esc_html(wp_trim_words($review->comment, 15)); ?>
                                    <?php if (str_word_count($review->comment) > 15): ?>
                                        <div class="full-comment-<?php echo $review->id; ?>" style="display: none;">
                                            <?php echo esc_html($review->comment); ?>
                                        </div>
                                        <a href="#" onclick="document.querySelector('.full-comment-<?php echo $review->id; ?>').style.display='block'; this.style.display='none'; return false;">Voir plus</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($review->service_name)): ?>
                                    <span style="background: #82897F; color: white; padding: 4px 10px; border-radius: 10px; font-size: 11px; font-weight: 600;">
                                        <?php echo esc_html($review->service_name); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #999;">Avis général</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-<?php echo $review->status; ?>" style="padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; 
                                    <?php 
                                    echo $review->status === 'pending' ? 'background: #fff3cd; color: #856404;' : 
                                        ($review->status === 'approved' ? 'background: #d4edda; color: #155724;' : 'background: #f8d7da; color: #721c24;');
                                    ?>">
                                    <?php 
                                    echo $review->status === 'pending' ? 'En attente' : 
                                        ($review->status === 'approved' ? 'Approuvé' : 'Rejeté');
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php echo date_i18n('d/m/Y H:i', strtotime($review->created_at)); ?>
                            </td>
                            <td>
                                <?php if ($review->status === 'pending'): ?>
                                    <form method="POST" style="display: inline;">
                                        <?php wp_nonce_field('newsaiige_admin_nonce'); ?>
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="review_id" value="<?php echo $review->id; ?>">
                                        <button type="submit" class="button button-primary button-small">Approuver</button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <?php wp_nonce_field('newsaiige_admin_nonce'); ?>
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="review_id" value="<?php echo $review->id; ?>">
                                        <button type="submit" class="button button-small">Rejeter</button>
                                    </form>
                                <?php elseif ($review->status === 'approved'): ?>
                                    <form method="POST" style="display: inline;">
                                        <?php wp_nonce_field('newsaiige_admin_nonce'); ?>
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="review_id" value="<?php echo $review->id; ?>">
                                        <button type="submit" class="button button-small">Rejeter</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                        <?php wp_nonce_field('newsaiige_admin_nonce'); ?>
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="review_id" value="<?php echo $review->id; ?>">
                                        <button type="submit" class="button button-primary button-small">Approuver</button>
                                    </form>
                                <?php endif; ?>
                                
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer définitivement cet avis ?');">
                                    <?php wp_nonce_field('newsaiige_admin_nonce'); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="review_id" value="<?php echo $review->id; ?>">
                                    <button type="submit" class="button button-small button-link-delete">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Statistiques rapides -->
        <?php
        $stats = $wpdb->get_row("SELECT COUNT(*) as total, AVG(rating) as avg_rating FROM $table_name WHERE status = 'approved'");
        ?>
        <div style="margin-top: 20px; padding: 15px; background: white; border: 1px solid #ccd0d4; border-radius: 5px;">
            <h3>Statistiques</h3>
            <p><strong>Total avis approuvés :</strong> <?php echo number_format($stats->total); ?></p>
            <p><strong>Note moyenne :</strong> <?php echo number_format($stats->avg_rating, 1); ?>/5</p>
        </div>
    </div>
    
    <?php
}

add_shortcode('newsaiige_reviews', 'newsaiige_reviews_shortcode');

// Initialisation et intégration du système de panier
// Ce fichier doit être inclus dans functions.php ou chargé via un plugin

// S'assurer que les scripts sont chargés dans le bon ordre
add_action('wp_enqueue_scripts', 'newsaiige_enqueue_cart_scripts');

// Fonction utilitaire pour extraire le prix proprement
function newsaiige_get_clean_price($woo_price_html) {
    // Extraire juste le prix numérique et le symbole sans les balises
    if (preg_match('/<bdi[^>]*>(.*?)<\/bdi>/', $woo_price_html, $matches)) {
        // Décoder les entités HTML pour avoir le vrai symbole €
        return html_entity_decode(strip_tags($matches[1]), ENT_QUOTES, 'UTF-8');
    }
    // Fallback : supprimer toutes les balises HTML et décoder les entités
    return html_entity_decode(strip_tags($woo_price_html), ENT_QUOTES, 'UTF-8');
}

function newsaiige_enqueue_cart_scripts() {
    // Enqueue jQuery si pas déjà fait
    wp_enqueue_script('jquery');
    
    // Ajouter les variables JavaScript nécessaires
    wp_localize_script('jquery', 'newsaiige_cart_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mini_panier_nonce'),
        'cart_url' => wc_get_cart_url(),
    ));
}

// Hook pour initialiser le compteur de panier au chargement de la page
add_action('wp_footer', 'newsaiige_init_cart_counter');

function newsaiige_init_cart_counter() {
    if (!class_exists('WooCommerce')) {
        return;
    }
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialiser les compteurs de panier
        const countBadge = document.getElementById('panier-count');
        const drawerCountBadge = document.getElementById('panier-drawer-count');
        const totalElement = document.getElementById('panier-total');
        
        // Mettre à jour les valeurs actuelles
        const currentCount = <?php echo WC()->cart->get_cart_contents_count(); ?>;
        const currentTotal = '<?php echo newsaiige_get_clean_price(WC()->cart->get_cart_total()); ?>';
        
        if (countBadge) {
            countBadge.textContent = currentCount;
            countBadge.style.display = currentCount > 0 ? 'flex' : 'none';
        }
        
        if (drawerCountBadge) {
            drawerCountBadge.textContent = currentCount;
        }
        
        if (totalElement) {
            // Utiliser textContent pour le prix nettoyé
            totalElement.textContent = currentTotal;
        }
    });
    </script>
    <?php
}

// Fonction pour rafraîchir les fragments de panier (utilisée par WooCommerce)
add_filter('woocommerce_add_to_cart_fragments', 'newsaiige_cart_fragments');

function newsaiige_cart_fragments($fragments) {
    // Fragment pour le compteur de panier
    ob_start();
    $cart_count = WC()->cart->get_cart_contents_count();
    ?>
    <span id="panier-count" style="position:absolute;top:-7px;right:-7px;background:#82897F;color:#fff;border-radius:50%;min-width:20px;height:20px;display:<?php echo $cart_count > 0 ? 'flex' : 'none'; ?>;align-items:center;justify-content:center;font-size:13px;font-weight:700;padding:0 6px;box-shadow:0 1px 4px rgba(0,0,0,0.12);z-index:2;"><?php echo $cart_count; ?></span>
    <?php
    $fragments['#panier-count'] = ob_get_clean();
    
    // Fragment pour le compteur du drawer
    ob_start();
    echo $cart_count;
    $fragments['#panier-drawer-count'] = ob_get_clean();
    
    // Fragment pour le total
    ob_start();
    echo newsaiige_get_clean_price(WC()->cart->get_cart_total());
    $fragments['#panier-total'] = ob_get_clean();
    
    // Fragment pour le contenu du mini panier
    ob_start();
    echo do_shortcode('[mini_panier_produits]');
    $fragments['.drawer-content .mini-panier-container'] = ob_get_clean();
    
    return $fragments;
}

// Action pour déclencher la mise à jour du panier après ajout/suppression
add_action('woocommerce_cart_item_removed', 'newsaiige_trigger_cart_update');
add_action('woocommerce_cart_item_set_quantity', 'newsaiige_trigger_cart_update');
add_action('woocommerce_add_to_cart', 'newsaiige_trigger_cart_update');

function newsaiige_trigger_cart_update() {
    // Cette fonction est appelée automatiquement par WooCommerce
    // Elle déclenche la mise à jour des fragments de panier
}

// Fonction helper pour obtenir les informations du panier en format JSON
function newsaiige_get_cart_data() {
    if (!class_exists('WooCommerce')) {
        return array(
            'cart_count' => 0,
            'cart_total' => '0,00 €',
            'cart_empty' => true
        );
    }
    
    return array(
        'cart_count' => WC()->cart->get_cart_contents_count(),
        'cart_total' => WC()->cart->get_cart_total(),
        'cart_empty' => WC()->cart->is_empty(),
        'cart_contents' => WC()->cart->get_cart()
    );
}
// Inclure le système de cartes cadeaux depuis les plugins
require_once WP_PLUGIN_DIR . '/newsaiige-gift-cards/newsaiige-gift-cards.php';
require_once WP_PLUGIN_DIR . '/newsaiige-gift-cards/gift-cards-admin.php';
require_once WP_PLUGIN_DIR . '/newsaiige-gift-cards/gift-card-validator.php';
//Récupération du plugging fidelity
// require_once get_template_directory() . '/newsaiige-loyalty-plugin.php';
//Admin de clefs API (google et facebook)
require_once get_template_directory() . '/newsaiige-social-loader.php';

// Système de changement d'abonnement
require_once get_template_directory() . '/subscription-change-handler.php';

//Fonction de masque du bouton d'annulation d'abonnement durant 12 mois
add_filter( 'wcs_can_user_cancel_subscription', 'annulation_uniquement_fenetre_annuelle', 10, 2 );

function annulation_uniquement_fenetre_annuelle( $can_cancel, $subscription ) {

    $start_date = $subscription->get_time( 'start' );
    $current_time = current_time( 'timestamp' );

    // Calcul du nombre de cycles de 12 mois passés
    $months_since_start = floor( ( $current_time - $start_date ) / (30 * 24 * 60 * 60) );
    $current_cycle = floor( $months_since_start / 12 );

    // Date de fin du cycle actuel
    $cycle_end = strtotime( '+' . (($current_cycle + 1) * 12) . ' months', $start_date );

    // Début de la fenêtre d'annulation (30 jours avant fin du cycle)
    $window_start = strtotime( '-30 days', $cycle_end );

    if ( $current_time >= $window_start && $current_time < $cycle_end ) {
        return true;
    }

    return false;
}
// Planifie un cron journalier si non existant
add_action('wp', function() {
    if (!wp_next_scheduled('verifier_fenetre_annuelle_abonnements')) {
        wp_schedule_event(time(), 'daily', 'verifier_fenetre_annuelle_abonnements');
    }
});

add_action('verifier_fenetre_annuelle_abonnements', 'verifier_et_envoyer_email_cycle_annuel');

function verifier_et_envoyer_email_cycle_annuel() {

    $subscriptions = wcs_get_subscriptions([
        'subscription_status' => 'active',
        'subscriptions_per_page' => -1,
    ]);

    foreach ($subscriptions as $subscription) {

        $start_date = $subscription->get_time('start');
        $current_time = current_time('timestamp');

        // Nombre de cycles 12 mois passés
        $months_since_start = floor(($current_time - $start_date) / (30 * 24 * 60 * 60));
        $current_cycle = floor($months_since_start / 12);

        $cycle_end = strtotime('+' . (($current_cycle + 1) * 12) . ' months', $start_date);
        $window_start = strtotime('-30 days', $cycle_end);

        // Vérifie si on est dans la bonne journée
        if (date('Y-m-d', $current_time) === date('Y-m-d', $window_start)) {

            // Empêche double envoi
            if (!$subscription->get_meta('_email_rappel_cycle_' . $current_cycle)) {

                envoyer_email_rappel_annuel($subscription, $cycle_end);

                $subscription->update_meta_data('_email_rappel_cycle_' . $current_cycle, 'sent');
                $subscription->save();
            }
        }
    }
}

function envoyer_email_rappel_annuel($subscription, $cycle_end) {

    $user = $subscription->get_user();
    $to = $user->user_email;

    $subject = "Information importante concernant votre abonnement";

    $message = "
    Bonjour " . $user->first_name . ",

    Votre période d'engagement arrive à échéance le " . date('d/m/Y', $cycle_end) . ".

    Vous disposez d’un délai d’un mois pour demander la résiliation avant reconduction automatique pour 12 mois supplémentaires.

    Passé ce délai, un nouveau cycle d'engagement sera lancé.

    Pour gérer votre abonnement :
    " . $subscription->get_view_order_url() . "

    Cordialement.
    ";

    wp_mail($to, $subject, $message);
}

require HELLO_THEME_PATH . '/theme.php';

HelloTheme\Theme::instance();
