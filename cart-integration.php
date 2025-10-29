<?php
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
?>