<?php
/**
 * SNIPPET BOUTON DÉCONNEXION NEWSAIIGE
 * À ajouter dans functions.php du thème ou via Code Snippets plugin
 */

// Ajouter le shortcode pour le bouton de déconnexion
add_shortcode('newsaiige_logout_button', 'newsaiige_logout_button_shortcode');

function newsaiige_logout_button_shortcode($atts) {
    // Ne rien afficher si l'utilisateur n'est pas connecté
    if (!is_user_logged_in()) {
        return '';
    }
    
    // Attributs par défaut
    $atts = shortcode_atts(array(
        'style' => 'default', // default, subtle, minimal
        'text' => 'Se déconnecter',
        'icon' => '🚪',
        'redirect' => home_url(),
        'confirm' => 'true'
    ), $atts);
    
    $logout_url = wp_logout_url($atts['redirect']);
    $confirm_text = ($atts['confirm'] === 'true') ? "onclick=\"return confirm('Êtes-vous sûr de vouloir vous déconnecter ?');\"" : '';
    
    // Styles selon le type
    $button_class = 'newsaiige-logout-btn';
    if ($atts['style'] === 'subtle') {
        $button_class .= ' subtle';
    } elseif ($atts['style'] === 'minimal') {
        $button_class .= ' minimal';
    }
    
    ob_start();
    ?>
    <style>
    .newsaiige-logout-container {
        display: inline-block;
        margin: 10px 0;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    .newsaiige-logout-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        background: #82897F;
        color: white !important;
        text-decoration: none !important;
        border-radius: 25px;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s ease;
        border: 2px solid transparent;
        box-shadow: 0 2px 10px rgba(130, 137, 127, 0.2);
    }

    .newsaiige-logout-btn:hover {
        background: #36492d;
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(130, 137, 127, 0.3);
        color: white !important;
        text-decoration: none !important;
    }

    .newsaiige-logout-btn:active {
        transform: translateY(0);
    }

    .logout-icon {
        font-size: 16px;
    }

    /* Style discret */
    .newsaiige-logout-btn.subtle {
        background: transparent;
        color: #82897F !important;
        border: 2px solid #82897F;
        box-shadow: none;
    }

    .newsaiige-logout-btn.subtle:hover {
        background: #82897F;
        color: white !important;
    }

    /* Style minimal */
    .newsaiige-logout-btn.minimal {
        background: transparent;
        color: #666 !important;
        border: none;
        box-shadow: none;
        padding: 8px 16px;
        text-decoration: underline !important;
    }

    .newsaiige-logout-btn.minimal:hover {
        color: #82897F !important;
        text-decoration: none !important;
        transform: none;
    }

    /* Version mobile */
    @media (max-width: 768px) {
        .newsaiige-logout-btn {
            padding: 10px 20px;
            font-size: 13px;
        }
    }
    </style>

    <div class="newsaiige-logout-container">
        <a href="<?php echo esc_url($logout_url); ?>" 
           class="<?php echo esc_attr($button_class); ?>" 
           <?php echo $confirm_text; ?>>
            <?php if (!empty($atts['icon'])): ?>
                <span class="logout-icon"><?php echo $atts['icon']; ?></span>
            <?php endif; ?>
            <?php echo esc_html($atts['text']); ?>
        </a>
    </div>
    <?php
    
    return ob_get_clean();
}

// Optionnel : Ajouter le bouton automatiquement aux menus
add_filter('wp_nav_menu_items', 'add_logout_button_to_menu', 10, 2);

function add_logout_button_to_menu($items, $args) {
    // Ajouter seulement au menu principal et si utilisateur connecté
    if (is_user_logged_in() && ($args->theme_location === 'primary' || $args->menu === 'Menu principal')) {
        $logout_url = wp_logout_url(home_url());
        $items .= '<li class="menu-item menu-logout"><a href="' . $logout_url . '" onclick="return confirm(\'Se déconnecter ?\');" style="color: #82897F;">🚪 Déconnexion</a></li>';
    }
    
    return $items;
}
?>