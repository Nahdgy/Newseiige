<?php
function newsaiige_mobile_product_carousel_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => 6,
        'categories' => '',
        'featured' => false
    ), $atts);
    
    // Enqueue les scripts nécessaires
    wp_enqueue_script('jquery');
    
    ob_start();
    ?>

    <style>
    .newsaiige-mobile-products {
        padding: 40px 20px;
        font-family: 'Montserrat', sans-serif;
        min-height: 70vh;
    }

    .mobile-products-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .mobile-products-title {
        font-size: 2rem;
        font-weight: 700;
        color: #82897F;
        margin: 0 0 15px 0;
        text-transform: uppercase;
        letter-spacing: 1.5px;
    }

    .mobile-products-subtitle {
        font-size: 1.1rem;
        color: #666;
        font-weight: 400;
        line-height: 1.5;
        margin-bottom: 30px;
    }

    /* CARROUSEL MOBILE */
    .mobile-carousel-container {
        position: relative;
        width: 100%;
        max-width: 350px;
        margin: 0 auto;
        overflow: hidden;
    }

    .mobile-carousel-track {
        display: flex;
        transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        width: 100%;
    }

    .mobile-product-slide {
        min-width: 100%;
        flex-shrink: 0;
        padding: 0 10px;
        box-sizing: border-box;
    }

    .mobile-product-card {
        background: rgba(255, 255, 255, 0.25);
        backdrop-filter: blur(20px) saturate(180%);
        border-radius: 20px;
        overflow: hidden;
        position: relative;
        transition: all 0.4s ease;
        border: 1px solid rgba(255, 255, 255, 0.3);
        cursor: pointer;
    }

    .mobile-product-card:active {
        transform: scale(0.98);
    }

    .mobile-product-image {
        height: 280px;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        position: relative;
        overflow: hidden;
    }

    .mobile-category-badge {
        position: absolute;
        top: 15px;
        left: 15px;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        color: #82897F;
        padding: 6px 12px;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .mobile-cart-icon {
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

    .mobile-cart-icon.in-cart {
        background: #82897F;
        color: white;
    }

    .mobile-cart-icon:active {
        transform: scale(0.9);
    }

    .mobile-cart-icon img {
        width: 20px;
        height: 20px;
        object-fit: contain;
    }

    .mobile-product-info {
        padding: 20px 15px;
        text-align: center;
    }

    .mobile-product-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: #333;
        margin: 0 0 10px 0;
        line-height: 1.3;
    }

    .mobile-product-price {
        font-size: 1.4rem;
        font-weight: 600;
        color: #82897F;
        margin: 0 0 15px 0;
    }

    /* CONTRÔLES CARROUSEL MOBILE */
    .mobile-carousel-controls {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 20px;
        margin-top: 30px;
    }

    .mobile-carousel-arrow {
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

    .mobile-carousel-arrow:active {
        transform: scale(0.95);
        background: #82897F;
        color: white;
    }

    .mobile-carousel-arrow:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }

    .mobile-carousel-arrow:disabled:active {
        background: rgba(255, 255, 255, 0.9);
        color: #82897F;
        transform: none;
    }

    /* PAGINATION MOBILE */
    .mobile-carousel-dots {
        display: flex;
        gap: 8px;
        justify-content: center;
        margin-top: 20px;
    }

    .mobile-pagination-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: rgba(130, 137, 127, 0.3);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .mobile-pagination-dot.active {
        background: #82897F;
        transform: scale(1.3);
    }

    .mobile-pagination-dot:active {
        transform: scale(1.1);
    }

    /* BOUTON DÉCOUVRIR */
    .mobile-discover-button {
        text-align: center;
        margin-top: 40px;
    }

    .mobile-discover-btn {
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

    .mobile-discover-btn:active {
        transform: translateY(2px) scale(0.98);
        box-shadow: 0 5px 15px rgba(130, 137, 127, 0.4);
    }

    .mobile-discover-btn:hover {
        text-decoration: none;
        color: white;
    }

    /* RESPONSIVE */
    @media (max-width: 400px) {
        .newsaiige-mobile-products {
            padding: 30px 15px;
        }

        .mobile-products-title {
            font-size: 1.7rem;
        }

        .mobile-carousel-container {
            max-width: 320px;
        }

        .mobile-product-image {
            height: 250px;
        }

        .mobile-discover-btn {
            padding: 12px 30px;
            font-size: 1rem;
        }
    }

    /* ANIMATIONS */
    @keyframes mobileSlideIn {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .mobile-product-card {
        animation: mobileSlideIn 0.6s ease;
    }

    /* NOTIFICATIONS MOBILES */
    .mobile-notification {
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

    .mobile-notification.success {
        background: rgba(130, 137, 127, 0.95);
    }

    .mobile-notification.error {
        background: rgba(231, 76, 60, 0.95);
    }

    .mobile-notification.show {
        transform: translateY(0);
    }
    </style>

    <div class="newsaiige-mobile-products">
        <div class="mobile-products-header">
            <h2 class="mobile-products-title">Nos Produits</h2>
            <p class="mobile-products-subtitle">Découvrez notre sélection coup de cœur</p>
        </div>

        <div class="mobile-carousel-container" id="mobileCarousel">
            <div class="mobile-carousel-track" id="mobileCarouselTrack">
                <?php 
                // Récupérer les produits
                $args = array(
                    'post_type' => 'product',
                    'posts_per_page' => intval($atts['limit']),
                    'post_status' => 'publish',
                    'orderby' => 'menu_order',
                    'order' => 'ASC'
                );

                $products_query = new WP_Query($args);
                $mobile_products = array();
                
                if ($products_query->have_posts()) {
                    while ($products_query->have_posts()) {
                        $products_query->the_post();
                        
                        $product_id = get_the_ID();
                        $product_title = get_the_title();
                        $product_url = get_permalink($product_id);
                        $product_image = get_the_post_thumbnail_url($product_id, 'large');
                        
                        $product_price = 'Prix non disponible';
                        $category_name = 'Produit';
                        
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
                        }
                        
                        // Image par défaut
                        if (!$product_image) {
                            $product_image = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjI4MCIgdmlld0JveD0iMCAwIDMwMCAyODAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIzMDAiIGhlaWdodD0iMjgwIiBmaWxsPSIjRjVGNUY1Ii8+CjxyZWN0IHg9IjEwMCIgeT0iOTAiIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiBmaWxsPSIjODI4OTdGIiBmaWxsLW9wYWNpdHk9IjAuMyIvPgo8dGV4dCB4PSIxNTAiIHk9IjIyMCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZmlsbD0iIzgyODk3RiIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjEyIiBmb250LXdlaWdodD0iNjAwIj5Qcm9kdWl0IE5ld3NhaWlnZTwvdGV4dD4KPC9zdmc+Cg==';
                        }
                        
                        $mobile_products[] = array(
                            'id' => $product_id,
                            'title' => $product_title,
                            'url' => $product_url,
                            'image' => $product_image,
                            'price' => $product_price,
                            'category' => $category_name
                        );
                    }
                    wp_reset_postdata();
                    
                    // Afficher les produits
                    foreach ($mobile_products as $index => $product) {
                        echo '
                        <div class="mobile-product-slide" data-slide="' . $index . '">
                            <div class="mobile-product-card" data-product-url="' . esc_url($product['url']) . '">
                                <div class="mobile-product-image" style="background-image: url(' . esc_url($product['image']) . ');">
                                    <div class="mobile-category-badge">' . esc_html($product['category']) . '</div>
                                    <div class="mobile-cart-icon" data-product-id="' . $product['id'] . '">
                                        <img draggable="false" alt="🛒" src="http://newsaiige.com/wp-content/uploads/2025/10/panier_noir.png">
                                    </div>
                                </div>
                            </div>
                            <div class="mobile-product-info">
                                <h3 class="mobile-product-title">' . esc_html($product['title']) . '</h3>
                                <p class="mobile-product-price">' . $product['price'] . '</p>
                            </div>
                        </div>';
                    }
                } else {
                    echo '<div class="mobile-product-slide">
                        <div class="mobile-product-card">
                            <div class="mobile-product-info" style="padding: 40px 20px; text-align: center;">
                                <h3 class="mobile-product-title">Aucun produit disponible</h3>
                                <p style="color: #666; margin: 15px 0;">Revenez bientôt pour découvrir nos nouveautés !</p>
                            </div>
                        </div>
                    </div>';
                }
                ?>
            </div>

            <?php if (count($mobile_products) > 1): ?>
            <div class="mobile-carousel-controls">
                <button class="mobile-carousel-arrow" id="mobilePrevArrow">‹</button>
                <div class="mobile-carousel-dots" id="mobileCarouselDots">
                    <?php for ($i = 0; $i < count($mobile_products); $i++): ?>
                        <div class="mobile-pagination-dot <?php echo $i === 0 ? 'active' : ''; ?>" data-slide="<?php echo $i; ?>"></div>
                    <?php endfor; ?>
                </div>
                <button class="mobile-carousel-arrow" id="mobileNextArrow">›</button>
            </div>
            <?php endif; ?>
        </div>

        <div class="mobile-discover-button">
            <a href="https://newsaiige.com/boutique/" class="mobile-discover-btn">
                Découvrir la Boutique
            </a>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        const ajaxNonce = '<?php echo wp_create_nonce('newsaiige_mobile_products_nonce'); ?>';
        
        const carouselTrack = document.getElementById('mobileCarouselTrack');
        const prevArrow = document.getElementById('mobilePrevArrow');
        const nextArrow = document.getElementById('mobileNextArrow');
        const dots = document.querySelectorAll('.mobile-pagination-dot');
        const totalSlides = document.querySelectorAll('.mobile-product-slide').length;
        
        let currentSlide = 0;

        // Fonction pour mettre à jour le carrousel
        function updateMobileCarousel() {
            const translateX = -currentSlide * 100;
            carouselTrack.style.transform = `translateX(${translateX}%)`;
            
            // Mettre à jour les flèches
            if (prevArrow) {
                prevArrow.disabled = currentSlide === 0;
            }
            if (nextArrow) {
                nextArrow.disabled = currentSlide === totalSlides - 1;
            }
            
            // Mettre à jour les dots
            dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === currentSlide);
            });
        }

        // Navigation
        function goToSlide(slideIndex) {
            if (slideIndex >= 0 && slideIndex < totalSlides) {
                currentSlide = slideIndex;
                updateMobileCarousel();
            }
        }

        function nextSlide() {
            if (currentSlide < totalSlides - 1) {
                goToSlide(currentSlide + 1);
            }
        }

        function prevSlide() {
            if (currentSlide > 0) {
                goToSlide(currentSlide - 1);
            }
        }

        // Event listeners pour les flèches
        if (prevArrow) {
            prevArrow.addEventListener('click', prevSlide);
        }
        
        if (nextArrow) {
            nextArrow.addEventListener('click', nextSlide);
        }

        // Event listeners pour les dots
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => goToSlide(index));
        });

        // Support tactile
        let startX = 0;
        let endX = 0;
        let isDragging = false;

        carouselTrack.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
            isDragging = true;
        });

        carouselTrack.addEventListener('touchmove', function(e) {
            if (!isDragging) return;
            e.preventDefault();
        });

        carouselTrack.addEventListener('touchend', function(e) {
            if (!isDragging) return;
            
            endX = e.changedTouches[0].clientX;
            const diffX = startX - endX;
            
            if (Math.abs(diffX) > 50) {
                if (diffX > 0) {
                    nextSlide();
                } else {
                    prevSlide();
                }
            }
            
            isDragging = false;
        });

        // Gestion des boutons panier
        function initializeMobileCartButtons() {
            const cartButtons = document.querySelectorAll('.mobile-cart-icon');
            cartButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const productId = this.getAttribute('data-product-id');
                    
                    if (!this.classList.contains('in-cart')) {
                        addToMobileCart(productId, this);
                    } else {
                        window.location.href = 'https://newsaiige.com/panier/';
                    }
                });
            });

            // Clic sur les cartes
            const productCards = document.querySelectorAll('.mobile-product-card');
            productCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    if (e.target.closest('.mobile-cart-icon')) {
                        return;
                    }
                    
                    const productUrl = this.getAttribute('data-product-url');
                    if (productUrl && productUrl !== '#') {
                        window.location.href = productUrl;
                    }
                });
            });
        }

        function addToMobileCart(productId, buttonElement) {
            const originalContent = buttonElement.innerHTML;
            buttonElement.innerHTML = '<div style="width: 20px; height: 20px; border: 2px solid #82897F; border-top: 2px solid transparent; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>';
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
                    
                    showMobileNotification('Produit ajouté au panier !', 'success');
                } else {
                    showMobileNotification('Erreur lors de l\'ajout', 'error');
                    buttonElement.innerHTML = originalContent;
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showMobileNotification('Erreur de connexion', 'error');
                buttonElement.innerHTML = originalContent;
            })
            .finally(() => {
                buttonElement.style.pointerEvents = 'auto';
            });
        }

        function showMobileNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `mobile-notification ${type}`;
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

        // Auto-play optionnel (désactivé par défaut pour mobile)
        let autoPlayInterval;
        function startAutoPlay() {
            autoPlayInterval = setInterval(() => {
                if (currentSlide < totalSlides - 1) {
                    nextSlide();
                } else {
                    goToSlide(0);
                }
            }, 4000);
        }

        function stopAutoPlay() {
            if (autoPlayInterval) {
                clearInterval(autoPlayInterval);
            }
        }

        // Initialisation
        updateMobileCarousel();
        initializeMobileCartButtons();

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

add_shortcode('newsaiige_mobile_products', 'newsaiige_mobile_product_carousel_shortcode');
?>