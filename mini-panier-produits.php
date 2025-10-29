<?php
function mini_panier_produits_shortcode() {
    // Vérifier si WooCommerce est actif
    if (!class_exists('WooCommerce')) {
        return '<p>WooCommerce n\'est pas installé.</p>';
    }
    
    ob_start();
    ?>
    
    <style>
    .mini-panier-container {
        width: 100%;
        max-height: 400px;
        overflow-y: auto;
    }
    
    .mini-panier-item {
        display: flex;
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px solid #eee;
        gap: 15px;
    }
    
    .mini-panier-item:last-child {
        border-bottom: none;
    }
    
    .mini-panier-image {
        width: 60px;
        height: 60px;
        border-radius: 10px;
        overflow: hidden;
        flex-shrink: 0;
        background: #f5f5f5;
    }
    
    .mini-panier-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .mini-panier-details {
        flex: 1;
        min-width: 0;
    }
    
    .mini-panier-title {
        font-size: 14px;
        font-weight: 600;
        color: #333;
        margin: 0 0 5px 0;
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .mini-panier-price {
        font-size: 13px;
        color: #82897F;
        font-weight: 600;
        margin: 0;
    }
    
    .mini-panier-quantity {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 8px;
    }
    
    .qty-btn {
        width: 25px;
        height: 25px;
        border: 1px solid #ddd;
        background: #fff;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.2s ease;
    }
    
    .qty-btn:hover {
        background: #82897F;
        color: white;
        border-color: #82897F;
    }
    
    .qty-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .qty-btn:disabled:hover {
        background: #fff;
        color: #333;
        border-color: #ddd;
    }
    
    .qty-input {
        width: 35px;
        height: 25px;
        border: 1px solid #ddd;
        border-radius: 4px;
        text-align: center;
        font-size: 13px;
        font-weight: 600;
    }
    
    .mini-panier-remove {
        width: 30px;
        height: 30px;
        border: none;
        background: #fff;
        color: #999;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }
    
    .mini-panier-remove:hover {
        background: #f5f5f5;
        color: #e74c3c;
        transform: scale(1.1);
    }
    
    .mini-panier-empty {
        text-align: center;
        padding: 40px 20px;
        color: #666;
    }
    
    .mini-panier-empty-icon {
        font-size: 48px;
        margin-bottom: 15px;
        opacity: 0.3;
    }
    
    .mini-panier-empty-text {
        font-size: 16px;
        margin: 0;
    }
    
    /* Animations */
    .mini-panier-item {
        opacity: 0;
        transform: translateX(-20px);
        animation: slideIn 0.3s ease forwards;
    }
    
    @keyframes slideIn {
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .removing {
        opacity: 0.5;
        transform: scale(0.95);
        transition: all 0.3s ease;
    }
    
    /* Scrollbar custom */
    .mini-panier-container::-webkit-scrollbar {
        width: 6px;
    }
    
    .mini-panier-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }
    
    .mini-panier-container::-webkit-scrollbar-thumb {
        background: #82897F;
        border-radius: 3px;
    }
    
    .mini-panier-container::-webkit-scrollbar-thumb:hover {
        background: #6b7a68;
    }
    </style>
    
    <div class="mini-panier-container" id="miniPanierContainer">
        <?php
        // Récupérer le contenu du panier WooCommerce
        if (WC()->cart && !WC()->cart->is_empty()) {
            $cart_items = WC()->cart->get_cart();
            
            foreach ($cart_items as $cart_item_key => $cart_item) {
                $product = $cart_item['data'];
                $product_id = $cart_item['product_id'];
                $quantity = $cart_item['quantity'];
                
                // Récupérer les informations du produit
                $product_name = $product->get_name();
                $product_price = WC()->cart->get_product_price($product);
                $product_image = get_the_post_thumbnail_url($product_id, 'thumbnail');
                
                // Image par défaut si pas d'image
                if (!$product_image) {
                    $product_image = wc_placeholder_img_src('thumbnail');
                }
                
                // Prix total pour cette ligne
                $line_total = WC()->cart->get_product_subtotal($product, $quantity);
                ?>
                <div class="mini-panier-item" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" data-product-id="<?php echo esc_attr($product_id); ?>">
                    <div class="mini-panier-image">
                        <img src="<?php echo esc_url($product_image); ?>" alt="<?php echo esc_attr($product_name); ?>" loading="lazy">
                    </div>
                    
                    <div class="mini-panier-details">
                        <h4 class="mini-panier-title"><?php echo esc_html($product_name); ?></h4>
                        <p class="mini-panier-price"><?php echo $line_total; ?></p>
                        
                        <div class="mini-panier-quantity">
                            <button class="qty-btn qty-decrease" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" <?php echo ($quantity <= 1) ? 'disabled' : ''; ?>>-</button>
                            <input type="number" class="qty-input" value="<?php echo esc_attr($quantity); ?>" min="1" readonly>
                            <button class="qty-btn qty-increase" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">+</button>
                        </div>
                    </div>
                    
                    <button class="mini-panier-remove" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" title="Supprimer du panier">
                        ×
                    </button>
                </div>
                <?php
            }
        } else {
            ?>
            <div class="mini-panier-empty">
                <div class="mini-panier-empty-icon">🛒</div>
                <p class="mini-panier-empty-text">Votre panier est vide</p>
            </div>
            <?php
        }
        ?>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const miniPanierContainer = document.getElementById('miniPanierContainer');
        
        if (miniPanierContainer) {
            // Gestion des boutons quantité
            miniPanierContainer.addEventListener('click', function(e) {
                if (e.target.classList.contains('qty-decrease') || e.target.classList.contains('qty-increase')) {
                    e.preventDefault();
                    
                    const button = e.target;
                    const cartKey = button.getAttribute('data-cart-key');
                    const isIncrease = button.classList.contains('qty-increase');
                    const quantityInput = button.parentNode.querySelector('.qty-input');
                    const currentQty = parseInt(quantityInput.value);
                    const newQty = isIncrease ? currentQty + 1 : Math.max(1, currentQty - 1);
                    
                    // Mettre à jour la quantité via AJAX
                    updateCartQuantity(cartKey, newQty);
                }
                
                // Gestion de la suppression
                if (e.target.classList.contains('mini-panier-remove')) {
                    e.preventDefault();
                    
                    const button = e.target;
                    const cartKey = button.getAttribute('data-cart-key');
                    const item = button.closest('.mini-panier-item');
                    
                    // Animation de suppression
                    item.classList.add('removing');
                    
                    // Supprimer du panier via AJAX
                    setTimeout(() => {
                        removeFromCart(cartKey);
                    }, 300);
                }
            });
        }
        
        // Fonction pour mettre à jour la quantité
        function updateCartQuantity(cartKey, quantity) {
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'update_cart_quantity',
                    cart_key: cartKey,
                    quantity: quantity,
                    security: '<?php echo wp_create_nonce('mini_panier_nonce'); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Recharger le contenu du mini panier
                    updateMiniPanier();
                    
                    // Mettre à jour les compteurs
                    updateCartCounters(data.data.cart_count, data.data.cart_total);
                } else {
                    console.error('Erreur lors de la mise à jour:', data.data);
                }
            })
            .catch(error => {
                console.error('Erreur AJAX:', error);
            });
        }
        
        // Fonction pour supprimer du panier
        function removeFromCart(cartKey) {
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'remove_from_cart',
                    cart_key: cartKey,
                    security: '<?php echo wp_create_nonce('mini_panier_nonce'); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Recharger le contenu du mini panier
                    updateMiniPanier();
                    
                    // Mettre à jour les compteurs
                    updateCartCounters(data.data.cart_count, data.data.cart_total);
                } else {
                    console.error('Erreur lors de la suppression:', data.data);
                }
            })
            .catch(error => {
                console.error('Erreur AJAX:', error);
            });
        }
        
        // Fonction pour recharger le mini panier
        function updateMiniPanier() {
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_mini_panier',
                    security: '<?php echo wp_create_nonce('mini_panier_nonce'); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    miniPanierContainer.innerHTML = data.data.html;
                }
            })
            .catch(error => {
                console.error('Erreur lors du rechargement:', error);
            });
        }
        
        // Fonction pour mettre à jour les compteurs dans le header
        function updateCartCounters(count, total) {
            // Mettre à jour le badge de comptage
            const countBadge = document.getElementById('panier-count');
            const drawerCountBadge = document.getElementById('panier-drawer-count');
            const totalElement = document.getElementById('panier-total');
            
            if (countBadge) {
                countBadge.textContent = count;
                countBadge.style.display = count > 0 ? 'flex' : 'none';
            }
            
            if (drawerCountBadge) {
                drawerCountBadge.textContent = count;
            }
            
            if (totalElement) {
                totalElement.textContent = total;
            }
        }
    });
    
    // Fonction globale pour ajouter au panier (utilisée par les autres scripts)
    window.addToWooCommerceCart = function(productId, quantity = 1, callback = null) {
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'add_to_cart',
                product_id: productId,
                quantity: quantity,
                security: '<?php echo wp_create_nonce('mini_panier_nonce'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recharger le contenu du mini panier
                const miniPanierContainer = document.getElementById('miniPanierContainer');
                if (miniPanierContainer) {
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'get_mini_panier',
                            security: '<?php echo wp_create_nonce('mini_panier_nonce'); ?>'
                        })
                    })
                    .then(response => response.json())
                    .then(miniData => {
                        if (miniData.success) {
                            miniPanierContainer.innerHTML = miniData.data.html;
                        }
                    });
                }
                
                // Mettre à jour les compteurs
                const countBadge = document.getElementById('panier-count');
                const drawerCountBadge = document.getElementById('panier-drawer-count');
                const totalElement = document.getElementById('panier-total');
                
                if (countBadge) {
                    countBadge.textContent = data.data.cart_count;
                    countBadge.style.display = data.data.cart_count > 0 ? 'flex' : 'none';
                }
                
                if (drawerCountBadge) {
                    drawerCountBadge.textContent = data.data.cart_count;
                }
                
                if (totalElement) {
                    totalElement.textContent = data.data.cart_total;
                }
                
                // Callback si fourni
                if (callback && typeof callback === 'function') {
                    callback(data);
                }
            } else {
                console.error('Erreur lors de l\'ajout au panier:', data.data);
            }
        })
        .catch(error => {
            console.error('Erreur AJAX:', error);
        });
    };
    </script>
    
    <?php
    return ob_get_clean();
}

add_shortcode('mini_panier_produits', 'mini_panier_produits_shortcode');

// Handlers AJAX pour le mini panier
add_action('wp_ajax_add_to_cart', 'handle_ajax_add_to_cart_mini');
add_action('wp_ajax_nopriv_add_to_cart', 'handle_ajax_add_to_cart_mini');

add_action('wp_ajax_update_cart_quantity', 'handle_ajax_update_cart_quantity');
add_action('wp_ajax_nopriv_update_cart_quantity', 'handle_ajax_update_cart_quantity');

add_action('wp_ajax_remove_from_cart', 'handle_ajax_remove_from_cart');
add_action('wp_ajax_nopriv_remove_from_cart', 'handle_ajax_remove_from_cart');

add_action('wp_ajax_get_mini_panier', 'handle_ajax_get_mini_panier');
add_action('wp_ajax_nopriv_get_mini_panier', 'handle_ajax_get_mini_panier');

function handle_ajax_add_to_cart_mini() {
    // Vérifier le nonce
    if (!wp_verify_nonce($_POST['security'], 'mini_panier_nonce')) {
        wp_send_json_error('Nonce invalide');
        return;
    }

    // Vérifier si WooCommerce est actif
    if (!class_exists('WooCommerce')) {
        wp_send_json_error('WooCommerce n\'est pas actif');
        return;
    }

    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']) ?: 1;

    // Ajouter le produit au panier
    $result = WC()->cart->add_to_cart($product_id, $quantity);

    if ($result) {
        wp_send_json_success(array(
            'message' => 'Produit ajouté au panier',
            'cart_count' => WC()->cart->get_cart_contents_count(),
            'cart_total' => WC()->cart->get_cart_total()
        ));
    } else {
        wp_send_json_error('Erreur lors de l\'ajout au panier');
    }
}

function handle_ajax_update_cart_quantity() {
    // Vérifier le nonce
    if (!wp_verify_nonce($_POST['security'], 'mini_panier_nonce')) {
        wp_send_json_error('Nonce invalide');
        return;
    }

    // Vérifier si WooCommerce est actif
    if (!class_exists('WooCommerce')) {
        wp_send_json_error('WooCommerce n\'est pas actif');
        return;
    }

    $cart_key = sanitize_text_field($_POST['cart_key']);
    $quantity = intval($_POST['quantity']);

    // Mettre à jour la quantité
    $result = WC()->cart->set_quantity($cart_key, $quantity);

    if ($result) {
        wp_send_json_success(array(
            'message' => 'Quantité mise à jour',
            'cart_count' => WC()->cart->get_cart_contents_count(),
            'cart_total' => WC()->cart->get_cart_total()
        ));
    } else {
        wp_send_json_error('Erreur lors de la mise à jour de la quantité');
    }
}

function handle_ajax_remove_from_cart() {
    // Vérifier le nonce
    if (!wp_verify_nonce($_POST['security'], 'mini_panier_nonce')) {
        wp_send_json_error('Nonce invalide');
        return;
    }

    // Vérifier si WooCommerce est actif
    if (!class_exists('WooCommerce')) {
        wp_send_json_error('WooCommerce n\'est pas actif');
        return;
    }

    $cart_key = sanitize_text_field($_POST['cart_key']);

    // Supprimer l'item du panier
    $result = WC()->cart->remove_cart_item($cart_key);

    if ($result) {
        wp_send_json_success(array(
            'message' => 'Produit supprimé du panier',
            'cart_count' => WC()->cart->get_cart_contents_count(),
            'cart_total' => WC()->cart->get_cart_total()
        ));
    } else {
        wp_send_json_error('Erreur lors de la suppression du produit');
    }
}

function handle_ajax_get_mini_panier() {
    // Vérifier le nonce
    if (!wp_verify_nonce($_POST['security'], 'mini_panier_nonce')) {
        wp_send_json_error('Nonce invalide');
        return;
    }

    // Générer le HTML du mini panier
    ob_start();
    
    if (WC()->cart && !WC()->cart->is_empty()) {
        $cart_items = WC()->cart->get_cart();
        
        foreach ($cart_items as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $product_id = $cart_item['product_id'];
            $quantity = $cart_item['quantity'];
            
            // Récupérer les informations du produit
            $product_name = $product->get_name();
            $product_price = WC()->cart->get_product_price($product);
            $product_image = get_the_post_thumbnail_url($product_id, 'thumbnail');
            
            // Image par défaut si pas d'image
            if (!$product_image) {
                $product_image = wc_placeholder_img_src('thumbnail');
            }
            
            // Prix total pour cette ligne
            $line_total = WC()->cart->get_product_subtotal($product, $quantity);
            ?>
            <div class="mini-panier-item" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" data-product-id="<?php echo esc_attr($product_id); ?>">
                <div class="mini-panier-image">
                    <img src="<?php echo esc_url($product_image); ?>" alt="<?php echo esc_attr($product_name); ?>" loading="lazy">
                </div>
                
                <div class="mini-panier-details">
                    <h4 class="mini-panier-title"><?php echo esc_html($product_name); ?></h4>
                    <p class="mini-panier-price"><?php echo $line_total; ?></p>
                    
                    <div class="mini-panier-quantity">
                        <button class="qty-btn qty-decrease" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" <?php echo ($quantity <= 1) ? 'disabled' : ''; ?>>-</button>
                        <input type="number" class="qty-input" value="<?php echo esc_attr($quantity); ?>" min="1" readonly>
                        <button class="qty-btn qty-increase" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">+</button>
                    </div>
                </div>
                
                <button class="mini-panier-remove" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" title="Supprimer du panier">
                    ×
                </button>
            </div>
            <?php
        }
    } else {
        ?>
        <div class="mini-panier-empty">
            <div class="mini-panier-empty-icon">🛒</div>
            <p class="mini-panier-empty-text">Votre panier est vide</p>
        </div>
        <?php
    }
    
    $html = ob_get_clean();
    
    wp_send_json_success(array(
        'html' => $html,
        'cart_count' => WC()->cart->get_cart_contents_count(),
        'cart_total' => WC()->cart->get_cart_total()
    ));
}
?>