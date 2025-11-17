<?php
function newsaiige_mobile_products_grid_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => 12,
        'categories' => '',
        'featured' => false
    ), $atts);
    
    // Enqueue les scripts nécessaires
    wp_enqueue_script('jquery');
    
    ob_start();
    ?>

    <style>
    .newsaiige-mobile-grid {
        padding: 40px 20px;
        font-family: 'Montserrat', sans-serif;
        min-height: 100vh;
    }

    .mobile-grid-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .mobile-grid-title {
        font-size: 2rem;
        font-weight: 700;
        color: #82897F;
        margin: 0 0 15px 0;
        letter-spacing: 1.5px;
        text-transform: uppercase;
    }

    .mobile-grid-subtitle {
        font-size: 1.1rem;
        color: #666;
        font-weight: 400;
        line-height: 1.5;
        margin-bottom: 30px;
    }

    /* SECTION CATÉGORIE */
    .mobile-category-section {
        margin-bottom: 50px;
    }

    .mobile-category-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #82897F;
        margin: 0 0 25px 0;
        text-transform: capitalize;
        text-align: center;
        letter-spacing: 1px;
        text-transform: lowercase;
    }

    .mobile-category-title:first-letter {
        text-transform: uppercase;
    }

    /* CARROUSEL PAR CATÉGORIE */
    .mobile-category-carousel {
        position: relative;
        width: 100%;
        max-width: 350px;
        margin: 0 auto;
        overflow: hidden;
    }

    .mobile-category-track {
        display: flex;
        transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        width: 100%;
    }

    .mobile-grid-product-slide {
        min-width: 100%;
        flex-shrink: 0;
        padding: 0 10px;
        box-sizing: border-box;
    }

    .mobile-grid-product-card {
        background: rgba(255, 255, 255, 0.25);
        backdrop-filter: blur(20px) saturate(180%);
        border-radius: 20px;
        overflow: hidden;
        position: relative;
        transition: all 0.4s ease;
        border: 1px solid rgba(255, 255, 255, 0.3);
        cursor: pointer;
    }

    .mobile-grid-product-card:active {
        transform: scale(0.98);
    }

    .mobile-grid-product-image {
        height: 280px;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        position: relative;
        overflow: hidden;
    }

    .mobile-grid-category-badge {
        position: absolute;
        top: 15px;
        left: 15px;
        background: rgba(255, 255, 255, 25%);
        backdrop-filter: blur(10px);
        color: #fff;
        padding: 6px 12px;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 0.5px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        text-transform: lowercase;
    }

    .mobile-grid-category-badge:first-letter {
        text-transform: uppercase;
    }

    .mobile-grid-cart-icon {
        position: absolute;
        top: 15px;
        right: 15px;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(255, 255, 255, 0.3);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .mobile-grid-cart-icon.in-cart {
        background: #82897F;
        color: white;
    }

    .mobile-grid-cart-icon:active {
        transform: scale(0.9);
    }

    .mobile-grid-cart-icon img {
        width: 20px;
        height: 20px;
        object-fit: contain;
    }

    .mobile-grid-product-info {
        padding: 20px 15px;
        text-align: center;
    }

    .mobile-grid-product-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: #333;
        margin: 0 0 10px 0;
        line-height: 1.3;
    }

    .mobile-grid-product-price {
        font-size: 1.4rem;
        font-weight: 600;
        color: #82897F;
        margin: 0 0 15px 0;
        max-width: 70vw;

    }

    /* CONTRÔLES CARROUSEL */
    .mobile-category-controls {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 20px;
        margin-top: 30px;
    }

    .mobile-category-arrow {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(130, 137, 127, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 1.1rem;
        color: #82897F;
        user-select: none;
    }

    .mobile-category-arrow:active {
        transform: scale(0.95);
        background: #82897F;
        color: white;
    }

    .mobile-category-arrow:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }

    .mobile-category-arrow:disabled:active {
        background: rgba(255, 255, 255, 0.9);
        color: #82897F;
        transform: none;
    }

    /* PAGINATION */
    .mobile-category-dots {
        display: flex;
        gap: 8px;
        justify-content: center;
    }

    .mobile-category-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: rgba(130, 137, 127, 0.3);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .mobile-category-dot.active {
        background: #82897F;
        transform: scale(1.3);
    }

    .mobile-category-dot:active {
        transform: scale(1.1);
    }

    /* MESSAGE VIDE */
    .mobile-no-products {
        text-align: center;
        padding: 40px 20px;
    }

    .mobile-no-products-title {
        font-size: 1.3rem;
        color: #82897F;
        margin-bottom: 10px;
    }

    .mobile-no-products-text {
        font-size: 1rem;
        color: #666;
    }


    .mobile-grid-discover-btn {
        display: inline-block;
        background: linear-gradient(135deg, #82897F 0%, #6b7a68 100%);
        color: white;
        padding: 15px 40px;
        border-radius: 25px;
        text-decoration: none;
        font-weight: 700;
        font-size: 1.1rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: all 0.3s ease;
        box-shadow: 0 10px 25px rgba(130, 137, 127, 0.3);
        border: none;
        cursor: pointer;
    }

    .mobile-grid-discover-btn:active {
        transform: translateY(2px) scale(0.98);
        box-shadow: 0 5px 15px rgba(130, 137, 127, 0.4);
    }

    .mobile-grid-discover-btn:hover {
        text-decoration: none;
        color: white;
    }

    /* RESPONSIVE */
    @media (max-width: 400px) {
        .newsaiige-mobile-grid {
            padding: 30px 15px;
        }

        .mobile-grid-title {
            font-size: 1.7rem;
        }

        .mobile-category-title {
            font-size: 1.3rem;
        }

        .mobile-grid-product-card {
            min-width: 250px;
        }

        .mobile-grid-product-image {
            height: 220px;
        }

        .mobile-grid-discover-btn {
            padding: 12px 30px;
            font-size: 1rem;
        }
    }

    /* ANIMATIONS */
    @keyframes mobileGridFadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .mobile-category-section {
        animation: mobileGridFadeIn 0.6s ease;
    }

    /* NOTIFICATIONS */
    .mobile-grid-notification {
        position: fixed;
        bottom: 20px;
        left: 20px;
        right: 20px;
        padding: 15px;
        border-radius: 10px;
        color: white;
        font-weight: 600;
        text-align: center;
        transform: translateY(100px);
        transition: transform 0.3s ease;
        z-index: 10000;
        backdrop-filter: blur(10px);
    }

    .mobile-grid-notification.success {
        background: rgba(130, 137, 127, 0.95);
    }

    .mobile-grid-notification.error {
        background: rgba(231, 76, 60, 0.95);
    }

    .mobile-grid-notification.show {
        transform: translateY(0);
    }
    </style>

    <div class="newsaiige-mobile-grid">
        <div class="mobile-grid-header">
            <h2 class="mobile-grid-title">E-shop</h2>
            <p class="mobile-grid-subtitle">Vous les adorez ! Découvrez nos produits best-sellers.</p>
        </div>

        <?php 
        // Récupérer les produits par catégorie
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => array('e-carte-cadeau', 'soins'),
                    'operator' => 'NOT IN'
                )
            )
        );

        $products_query = new WP_Query($args);
        $products_by_category = array();
        
        if ($products_query->have_posts()) {
            while ($products_query->have_posts()) {
                $products_query->the_post();
                
                $product_id = get_the_ID();
                $product_title = get_the_title();
                $product_url = get_permalink($product_id);
                $product_image = get_the_post_thumbnail_url($product_id, 'large');
                
                $product_price = 'Prix non disponible';
                $category_name = 'Produits';
                $category_slug = 'produits';
                
                // Récupérer le prix WooCommerce
                if (class_exists('WC_Product') && function_exists('wc_get_product')) {
                    $wc_product = wc_get_product($product_id);
                    if ($wc_product) {
                        $product_price = $wc_product->get_price_html();
                    }
                }
                
                // Récupérer la catégorie
                $product_categories = get_the_terms($product_id, 'product_cat');
                if ($product_categories && !is_wp_error($product_categories)) {
                    $category_name = $product_categories[0]->name;
                    $category_slug = $product_categories[0]->slug;
                }
                
                // Image par défaut
                if (!$product_image) {
                    $product_image = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjgwIiBoZWlnaHQ9IjI4MCIgdmlld0JveD0iMCAwIDI4MCAyODAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyODAiIGhlaWdodD0iMjgwIiBmaWxsPSIjRjVGNUY1Ii8+CjxyZWN0IHg9IjkwIiB5PSI5MCIgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMDAiIGZpbGw9IiM4Mjg5N0YiIGZpbGwtb3BhY2l0eT0iMC4zIi8+Cjx0ZXh0IHg9IjE0MCIgeT0iMjIwIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmaWxsPSIjODI4OTdGIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTIiIGZvbnQtd2VpZ2h0PSI2MDAiPlByb2R1aXQgTmV3c2FpaWdlPC90ZXh0Pgo8L3N2Zz4K';
                }
                
                // Organiser par catégorie
                if (!isset($products_by_category[$category_slug])) {
                    $products_by_category[$category_slug] = array(
                        'name' => $category_name,
                        'products' => array()
                    );
                }
                
                $products_by_category[$category_slug]['products'][] = array(
                    'id' => $product_id,
                    'title' => $product_title,
                    'url' => $product_url,
                    'image' => $product_image,
                    'price' => $product_price,
                    'category' => $category_name
                );
            }
            wp_reset_postdata();
        }

        // Afficher les carrousels par catégorie
        if (!empty($products_by_category)) {
            $category_index = 0;
            foreach ($products_by_category as $category_slug => $category_data) {
                $products = $category_data['products'];
                $slides_count = ceil(count($products) / 1); // 1 produit par slide pour mobile
                
                echo '<div class="mobile-category-section" data-category="' . esc_attr($category_slug) . '">';
                echo '<h3 class="mobile-category-title">' . esc_html($category_data['name']) . '</h3>';
                
                echo '<div class="mobile-category-carousel" id="mobileCarousel_' . $category_index . '">';
                echo '<div class="mobile-category-track" id="mobileTrack_' . $category_index . '">';
                
                foreach ($products as $product_index => $product) {
                    echo '
                    <div class="mobile-grid-product-slide" data-slide="' . $product_index . '">
                        <div class="mobile-grid-product-card" data-product-url="' . esc_url($product['url']) . '">
                            <div class="mobile-grid-product-image" style="background-image: url(' . esc_url($product['image']) . ');">
                                <div class="mobile-grid-category-badge">' . esc_html($product['category']) . '</div>
                                <div class="mobile-grid-cart-icon" data-product-id="' . $product['id'] . '">
                                    <img draggable="false" alt="🛒" src="http://newsaiige.com/wp-content/uploads/2025/10/panier_noir.png">
                                </div>
                            </div>
                        </div>
                        <div class="mobile-grid-product-info">
                            <h3 class="mobile-grid-product-title">' . esc_html($product['title']) . '</h3>
                            <p class="mobile-grid-product-price">' . $product['price'] . '</p>
                        </div>
                    </div>';
                }
                
                echo '</div>'; // fin track
                
                // Contrôles seulement s'il y a plus d'un produit
                if (count($products) > 1) {
                    echo '<div class="mobile-category-controls">
                        <button class="mobile-category-arrow" data-category="' . $category_index . '" data-direction="prev">‹</button>
                        <div class="mobile-category-dots" id="mobileDots_' . $category_index . '">';
                    
                    for ($i = 0; $i < count($products); $i++) {
                        echo '<div class="mobile-category-dot ' . ($i === 0 ? 'active' : '') . '" data-category="' . $category_index . '" data-slide="' . $i . '"></div>';
                    }
                    
                    echo '</div>
                        <button class="mobile-category-arrow" data-category="' . $category_index . '" data-direction="next">›</button>
                    </div>';
                }
                
                echo '</div>'; // fin carousel
                echo '</div>'; // fin section
                
                $category_index++;
            }
        } else {
            echo '<div class="mobile-no-products">
                <h3 class="mobile-no-products-title">Aucun produit disponible</h3>
                <p class="mobile-no-products-text">Revenez bientôt pour découvrir nos nouveautés !</p>
            </div>';
        }
        ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        const ajaxNonce = '<?php echo wp_create_nonce('newsaiige_mobile_grid_nonce'); ?>';
        
        // Gestion des carrousels par catégorie
        const carousels = [];
        const categoryTracks = document.querySelectorAll('.mobile-category-track');
        
        categoryTracks.forEach((track, categoryIndex) => {
            const slides = track.querySelectorAll('.mobile-grid-product-slide');
            const prevButton = document.querySelector(`[data-category="${categoryIndex}"][data-direction="prev"]`);
            const nextButton = document.querySelector(`[data-category="${categoryIndex}"][data-direction="next"]`);
            const dots = document.querySelectorAll(`[data-category="${categoryIndex}"].mobile-category-dot`);
            
            if (slides.length === 0) return;
            
            const carousel = {
                track: track,
                slides: slides,
                prevButton: prevButton,
                nextButton: nextButton,
                dots: dots,
                currentSlide: 0,
                totalSlides: slides.length,
                categoryIndex: categoryIndex
            };
            
            carousels.push(carousel);
            
            // Initialiser le carrousel
            updateCarousel(carousel);
            
            // Event listeners pour les boutons
            if (prevButton) {
                prevButton.addEventListener('click', () => prevSlide(carousel));
            }
            
            if (nextButton) {
                nextButton.addEventListener('click', () => nextSlide(carousel));
            }
            
            // Event listeners pour les dots
            dots.forEach((dot, index) => {
                dot.addEventListener('click', () => goToSlide(carousel, index));
            });
            
            // Support tactile pour chaque carrousel
            setupTouchSupport(carousel);
        });

        // Fonction pour mettre à jour un carrousel
        function updateCarousel(carousel) {
            const translateX = -carousel.currentSlide * 100;
            carousel.track.style.transform = `translateX(${translateX}%)`;
            
            // Mettre à jour les boutons
            if (carousel.prevButton) {
                carousel.prevButton.disabled = carousel.currentSlide === 0;
            }
            if (carousel.nextButton) {
                carousel.nextButton.disabled = carousel.currentSlide === carousel.totalSlides - 1;
            }
            
            // Mettre à jour les dots
            carousel.dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === carousel.currentSlide);
            });
        }

        // Navigation
        function goToSlide(carousel, slideIndex) {
            if (slideIndex >= 0 && slideIndex < carousel.totalSlides) {
                carousel.currentSlide = slideIndex;
                updateCarousel(carousel);
            }
        }

        function nextSlide(carousel) {
            if (carousel.currentSlide < carousel.totalSlides - 1) {
                goToSlide(carousel, carousel.currentSlide + 1);
            }
        }

        function prevSlide(carousel) {
            if (carousel.currentSlide > 0) {
                goToSlide(carousel, carousel.currentSlide - 1);
            }
        }

        // Support tactile pour chaque carrousel
        function setupTouchSupport(carousel) {
            let startX = 0;
            let startY = 0;
            let endX = 0;
            let isDragging = false;
            let isHorizontalSwipe = false;

            carousel.track.addEventListener('touchstart', function(e) {
                startX = e.touches[0].clientX;
                startY = e.touches[0].clientY;
                isDragging = true;
                isHorizontalSwipe = false;
            });

            carousel.track.addEventListener('touchmove', function(e) {
                if (!isDragging) return;
                
                const currentX = e.touches[0].clientX;
                const currentY = e.touches[0].clientY;
                const diffX = Math.abs(currentX - startX);
                const diffY = Math.abs(currentY - startY);
                
                // Déterminer si c'est un swipe horizontal ou vertical
                if (diffX > 10 || diffY > 10) {
                    if (diffX > diffY && diffX > 20) {
                        // Mouvement horizontal détecté - bloquer le scroll vertical
                        isHorizontalSwipe = true;
                        e.preventDefault();
                    } else if (diffY > diffX) {
                        // Mouvement vertical détecté - arrêter la détection du swipe horizontal
                        isDragging = false;
                        isHorizontalSwipe = false;
                    }
                }
            });

            carousel.track.addEventListener('touchend', function(e) {
                if (!isDragging || !isHorizontalSwipe) return;
                
                endX = e.changedTouches[0].clientX;
                const diffX = startX - endX;
                
                if (Math.abs(diffX) > 50) {
                    if (diffX > 0) {
                        nextSlide(carousel);
                    } else {
                        prevSlide(carousel);
                    }
                }
                
                isDragging = false;
                isHorizontalSwipe = false;
            });
        }

        // Gestion des boutons panier
        function initializeMobileGridCartButtons() {
            const cartButtons = document.querySelectorAll('.mobile-grid-cart-icon');
            cartButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const productId = this.getAttribute('data-product-id');
                    
                    if (!this.classList.contains('in-cart')) {
                        addToMobileGridCart(productId, this);
                    } else {
                        window.location.href = 'https://newsaiige.com/panier/';
                    }
                });
            });

            // Clic sur les cartes
            const productCards = document.querySelectorAll('.mobile-grid-product-card');
            productCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    if (e.target.closest('.mobile-grid-cart-icon')) {
                        return;
                    }
                    
                    const productUrl = this.getAttribute('data-product-url');
                    if (productUrl && productUrl !== '#') {
                        window.location.href = productUrl;
                    }
                });
            });
        }

        function addToMobileGridCart(productId, buttonElement) {
            const originalContent = buttonElement.innerHTML;
            buttonElement.innerHTML = '<div style="width: 16px; height: 16px; border: 2px solid #82897F; border-top: 2px solid transparent; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>';
            buttonElement.style.pointerEvents = 'none';

            const formData = new FormData();
            formData.append('action', 'woocommerce_add_to_cart');
            formData.append('product_id', productId);
            formData.append('quantity', 1);
            formData.append('security', ajaxNonce);

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data && !data.error) {
                    buttonElement.classList.add('in-cart');
                    buttonElement.innerHTML = '✓';
                    buttonElement.style.background = '#82897F';
                    buttonElement.style.color = 'white';
                    
                    showMobileGridNotification('Produit ajouté au panier !', 'success');
                } else {
                    showMobileGridNotification('Erreur lors de l\'ajout', 'error');
                    buttonElement.innerHTML = originalContent;
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showMobileGridNotification('Erreur de connexion', 'error');
                buttonElement.innerHTML = originalContent;
            })
            .finally(() => {
                buttonElement.style.pointerEvents = 'auto';
            });
        }

        function showMobileGridNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `mobile-grid-notification ${type}`;
            notification.textContent = message;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.classList.add('show');
            }, 100);

            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        // Initialisation
        initializeMobileGridCartButtons();

        // Ajouter le CSS pour l'animation de chargement
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    });
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode('newsaiige_mobile_products_grid', 'newsaiige_mobile_products_grid_shortcode');

// AJAX Handler pour ajouter au panier (mobile grid)
add_action('wp_ajax_woocommerce_add_to_cart', 'handle_mobile_grid_ajax_add_to_cart');
add_action('wp_ajax_nopriv_woocommerce_add_to_cart', 'handle_mobile_grid_ajax_add_to_cart');

function handle_mobile_grid_ajax_add_to_cart() {
    // Vérifier le nonce
    if (!wp_verify_nonce($_POST['security'], 'newsaiige_mobile_grid_nonce')) {
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
            'cart_count' => WC()->cart->get_cart_contents_count()
        ));
    } else {
        wp_send_json_error('Erreur lors de l\'ajout au panier');
    }
}
?>