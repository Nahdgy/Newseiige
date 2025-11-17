<?php
function newsaiige_product_carroussel_shortcode($atts) {
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
    .newsaiige-products-section {
        padding: 20px 20px;
        font-family: 'Montserrat', sans-serif;
        min-height: 100vh;
    }

    .products-container {
        max-width: 1400px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 40px;
        align-items: start;
    }

    /* SIDEBAR FILTRES */
    .products-sidebar {
        position: sticky;
        top: 120px;
        height: fit-content;
        z-index: 10;
    }

    .sidebar-title {
        font-size: 18px;
        font-weight: 700;
        color: #82897F;
        margin-bottom: 25px;
        letter-spacing: 1px;
        border-bottom: 2px solid rgba(130, 137, 127, 0.2);
        padding-bottom: 15px;
    }

    .filter-categories {
        list-style: none;
        padding: 0;
        margin: 0;
        border-bottom: 2px solid rgba(130, 137, 127, 0.2);
    }

    .filter-link {
        display: block;
        color: #000;
        text-decoration: none;
        border-radius: 15px;
        transition: all 0.3s ease;
        font-weight: 500;
        position: relative;
        padding: 5px;
        text-transform: lowercase !important;
    }

    .filter-link:first-letter {
        text-transform: uppercase !important;
    }

    .filter-link:hover,
    .filter-link.active {
        font-weight: 700;
        transform: translateX(10px);
        color: #82897F !important;
    }

    .filter-link::before {
        content: '';
        position: absolute;
        left: -2px;
        top: 50%;
        transform: translateY(-50%);
        width: 4px;
        height: 0;
        transition: height 0.3s ease;
    }

    .filter-link.active::before {
        height: 60%;
    }

    /* SECTION TITRE */
    .products-header {
        margin-bottom: 50px;
    }

    .products-title {
        font-size: 28px;
        font-weight: 700;
        color: #82897F;
        margin: 0 0 20px 0;
        text-transform: uppercase;
        letter-spacing: 2px;
    }

    .products-subtitle {
        font-size: 14px;
        color: #666;
        font-weight: 400;
        line-height: 1.6;
    }

    /* CARROUSEL PRODUITS - Nouveau système sans overflow */
    .products-carousel-container {
        position: relative;
        margin-top: 30px;
        width: 100%;
        overflow: hidden;
        max-width: 100%;
        z-index: 1;
    }

    .products-grid {
        position: relative;
        width: 100%;
        height: auto;
        overflow: hidden;
        box-sizing: border-box;
        padding-top: 20px;
    }

    .carousel-page {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
        opacity: 0;
        transform: translateX(30px);
        transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        pointer-events: none;
        box-sizing: border-box;
        padding: 0;
        margin: 0;
    }

    .carousel-page.active {
        opacity: 1;
        transform: translateX(0);
        pointer-events: auto;
        position: relative;
    }

    .carousel-page .product-wrapper {
        width: 100%;
        max-width: 100%;
        min-width: 0;
        box-sizing: border-box;
    }

    .product-card {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(20px) saturate(180%);
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        border-radius: 25px;
        overflow: hidden;
        position: relative;
        transition: all 0.4s ease;
        border: 1px solid rgba(255, 255, 255, 0.3);
        cursor: pointer;
        width: 100%;
    }

    .product-card:hover {
        transform: translateY(-15px) scale(1.02);
    }

    /* CONTRÔLES CARROUSEL */
    .carousel-controls {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 30px;
        margin-top: 40px;
        z-index: 5;
        position: relative;
        padding: 10px;
    }

    .carousel-arrow {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(130, 137, 127, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 1.2rem;
        color: #82897F;
    }

    .carousel-arrow:hover,
    .carousel-arrow:focus {
        background: #82897F;
        color: white;
        transform: scale(1.1);
    }

    .carousel-arrow:disabled {
        opacity: 0.3;
        cursor: not-allowed;
        transform: none;
    }

    .carousel-arrow:disabled:hover {
        background: rgba(255, 255, 255, 0.9);
        color: #82897F;
    }

    /* INDICATEURS PAGINATION */
    .carousel-pagination {
        display: flex;
        gap: 10px;
        justify-content: center;
    }

    .pagination-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: rgba(130, 137, 127, 0.3);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .pagination-dot.active {
        background: #82897F;
        transform: scale(1.2);
    }

    .pagination-dot:hover {
        background: #82897F;
        transform: scale(1.1);
    }

    .products-image {
        height: 500px;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        position: relative;
        overflow: hidden;
    }

    .product-category-badge {
        position: absolute;
        top: 20px;
        left: 20px;
        background: rgba(255, 255, 255, 20%);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        color: #ffffffff;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 400;
        letter-spacing: 0.5px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        text-transform: lowercase !important;
    }

    .product-category-badge:first-letter {
        text-transform: uppercase !important;
    }

    .cart-status-icon {
        position: absolute;
        top: 20px;
        right: 20px;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        border: 1px solid rgba(255, 255, 255, 0.3);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .cart-status-icon.in-cart {
        background: #82897F;
        color: white;
    }

    .cart-status-icon:hover {
        transform: scale(1.1);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }

    .product-info {
        display: flex;
        flex-direction: row;
        padding: 25px 20px;
        text-align: center;
        justify-content: space-between;
    }

    .product-title {
        font-size: 14px;
        font-weight: 600;
        color: #333;
        margin: 0 0 10px 0;
        line-height: 1.4;
    }

    .product-price {
        font-size: 14px;
        font-weight: 400;
        color: #000000ff;
        margin: 0;
    }

    .loading-message {
        text-align: center;
        padding: 60px 20px;
        font-size: 1.2rem;
        color: #666;
    }

    .no-products {
        text-align: center;
        padding: 60px 20px;
        grid-column: 1 / -1;
    }

    .no-products-title {
        font-size: 1.8rem;
        color: #82897F;
        margin-bottom: 15px;
    }

    .no-products-text {
        font-size: 1.1rem;
        color: #666;
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .products-container {
            grid-template-columns: 1fr;
            gap: 30px;
        }

        .products-sidebar {
            display: none;
        }

        .carousel-page {
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
    }

    @media (max-width: 768px) {
        .products-sidebar {
            display: none;
        }

        .carousel-page {
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .product-card {
            max-width: 400px;
            margin: 0 auto;
        }

        .products-title {
            font-size: 2rem;
        }

        .carousel-controls {
            gap: 20px;
        }

        .carousel-arrow {
            width: 45px;
            height: 45px;
        }

        .carousel-pagination {
            display: none;
        }
    }

    @media (max-width: 480px) {
        .newsaiige-products-section {
            padding: 40px 15px;
        }

        .products-sidebar {
            padding: 20px;
        }

        .product-info {
            padding: 20px 15px;
        }

        .product-title {
            font-size: 1.1rem;
        }

        .product-price {
            font-size: 1.3rem;
        }
    }

    /* Animations */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .product-card {
        animation: fadeIn 0.5s ease;
    }
    </style>

    <div class="newsaiige-products-section">
        <div class="products-header">
            <h2 class="products-title">E-shop</h2>
            <p class="products-subtitle">Vous les adorez ! Découvrez nos produits best-sellers.</p>
        </div>
        <div class="products-container">
            <!-- SIDEBAR FILTRES -->
            <div class="products-sidebar">
                <h3 class="sidebar-title">Notre sélection</h3>
                <ul class="filter-categories">
                    <li class="filter-category">
                        <a href="#" class="filter-link active" data-category="all">Tout</a>
                    </li>
                    <?php
                    // Récupérer d'abord la catégorie parent "product"
                    $parent_category = get_term_by('slug', 'product', 'product_cat');
                    
                    if ($parent_category && !is_wp_error($parent_category)) {
                        // Récupérer les sous-catégories de la catégorie "product"
                        $product_categories = get_terms(array(
                            'taxonomy' => 'product_cat',
                            'hide_empty' => true,
                            'parent' => $parent_category->term_id // Sous-catégories de "product"
                        ));
                    } else {
                        // Si pas de catégorie "product", récupérer toutes les catégories
                        $product_categories = get_terms(array(
                            'taxonomy' => 'product_cat',
                            'hide_empty' => true,
                            'parent' => 0
                        ));
                    }

                    if (!is_wp_error($product_categories) && !empty($product_categories)) {
                        // Filtrer les catégories à exclure
                        $excluded_categories = array('e-carte-cadeau', 'soins');
                        
                        foreach ($product_categories as $category) {
                            // Exclure les catégories spécifiques
                            if (!in_array($category->slug, $excluded_categories)) {
                                echo '<li class="filter-category">
                                    <a href="#" class="filter-link" data-category="' . esc_attr($category->slug) . '">' . esc_html($category->name) . '</a>
                                </li>';
                            }
                        }
                    } else {
                        // Catégories par défaut si aucune catégorie WooCommerce trouvée
                        echo '<li class="filter-category">
                            <a href="#" class="filter-link" data-category="les-huiles">les huiles</a>
                        </li>
                        <li class="filter-category">
                            <a href="#" class="filter-link" data-category="les-outils">les outils</a>
                        </li>
                        <li class="filter-category">
                            <a href="#" class="filter-link" data-category="le-livre">le livre</a>
                        </li>';
                    }
                    ?>
                </ul>
            </div>

            <!-- CONTENU PRINCIPAL -->
            <div class="products-main">
                <div class="products-carousel-container" id="productsCarousel">
                    <div class="products-grid" id="productsGrid">
                        <?php 
                        // Essayer plusieurs méthodes pour récupérer les produits
                        $products_found = false;
                        $debug_info = array();
                        
                        // Méthode 1: Requête WP_Query simple
                        $args = array(
                            'post_type' => 'product',
                            'posts_per_page' => 12,
                            'post_status' => 'publish',
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
                        $debug_info['method1_found'] = $products_query->found_posts;
                        
                        $all_products = array();
                        
                        if ($products_query->have_posts()) {
                            $products_found = true;
                            while ($products_query->have_posts()) {
                                $products_query->the_post();
                                
                                // Récupérer les informations du produit
                                $product_id = get_the_ID();
                                $product_title = get_the_title();
                                $product_url = get_permalink($product_id);
                                $product_image = get_the_post_thumbnail_url($product_id, 'large');
                                
                                // Initialiser les variables par défaut
                                $product_price = 'Prix non disponible';
                                $category_name = 'Produit';
                                $in_cart = false;
                                
                                // Essayer de récupérer le produit WooCommerce
                                if (class_exists('WC_Product') && function_exists('wc_get_product')) {
                                    $wc_product = wc_get_product($product_id);
                                    if ($wc_product) {
                                        $product_price = $wc_product->get_price_html();
                                    }
                                }
                                
                                // Récupérer les catégories du produit
                                $product_categories = get_the_terms($product_id, 'product_cat');
                                if ($product_categories && !is_wp_error($product_categories)) {
                                    $category_name = $product_categories[0]->name;
                                }
                                
                                // Image par défaut si pas d'image
                                if (!$product_image) {
                                    $product_image = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgdmlld0JveD0iMCAwIDQwMCAzMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSI0MDAiIGhlaWdodD0iMzAwIiBmaWxsPSIjRjVGNUY1Ii8+CjxyZWN0IHg9IjE1MCIgeT0iMTAwIiB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iIzgyODk3RiIgZmlsbC1vcGFjaXR5PSIwLjMiLz4KPHRleHQgeD0iMjAwIiB5PSIyNDAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IiM4Mjg5N0YiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZm9udC13ZWlnaHQ9IjYwMCI+UHJvZHVpdCBOZXdzYWlpZ2U8L3RleHQ+Cjwvc3ZnPgo=';
                                }
                                
                                $all_products[] = array(
                                    'id' => $product_id,
                                    'title' => $product_title,
                                    'url' => $product_url,
                                    'image' => $product_image,
                                    'price' => $product_price,
                                    'category' => $category_name
                                );
                            }
                            wp_reset_postdata();
                            
                            // Organiser les produits par pages de 3
                            $products_per_page = 3;
                            $product_pages = array_chunk($all_products, $products_per_page);
                            $total_pages = count($product_pages);
                            
                            foreach ($product_pages as $page_index => $page_products) {
                                $is_active = $page_index === 0 ? 'active' : '';
                                echo '<div class="carousel-page ' . $is_active . '" data-page="' . $page_index . '">';
                                
                                foreach ($page_products as $product) {
                                    echo '
                                    <div class="product-wrapper" data-category="' . esc_attr($product['category']) . '">
                                        <div class="product-card" data-product-url="' . esc_url($product['url']) . '" data-category="' . esc_attr($product['category']) . '">
                                            <div class="products-image" style="background-image: url(' . esc_url($product['image']) . ');">
                                                <div class="product-category-badge">' . esc_html($product['category']) . '</div>
                                                <div class="cart-status-icon" data-product-id="' . $product['id'] . '"><img draggable="false" role="img" class="emoji" alt="🛒" src="http://newsaiige.com/wp-content/uploads/2025/10/panier_noir.png"></div>
                                            </div>  
                                        </div>
                                        <div class="product-info">
                                                <h3 class="product-title">' . esc_html($product['title']) . '</h3>
                                                <p class="product-price">' . $product['price'] . '</p>
                                        </div>
                                    </div>';
                                }
                                
                                echo '</div>';
                            }
                        }
                        
                        // Si toujours pas de produits, afficher un message de debug détaillé
                        if (!$products_found) {
                            global $wpdb;
                            $product_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'");
                            $debug_info['direct_db_count'] = $product_count;
                            
                            echo '<div class="no-products">
                                <h3 class="no-products-title">Aucun produit trouvé</h3>
                                <p class="no-products-text">Informations de débogage :</p>
                                <ul style="text-align: left; max-width: 600px; margin: 0 auto;">
                                    <li>WooCommerce actif: ' . (class_exists('WooCommerce') ? 'Oui' : 'Non') . '</li>
                                    <li>WC_Product class: ' . (class_exists('WC_Product') ? 'Oui' : 'Non') . '</li>
                                    <li>Méthode 1 (WP_Query): ' . $debug_info['method1_found'] . ' produits</li>
                                    <li>Base de données directe: ' . $debug_info['direct_db_count'] . ' produits</li>
                                    <li>Suggestions: Vérifiez que vous avez des produits publiés dans WooCommerce</li>
                                </ul>
                            </div>';
                        }
                        ?>
                    </div>
                    
                    <?php if ($products_found && $total_pages > 1): ?>
                    <div class="carousel-controls">
                        <button class="carousel-arrow prev-arrow" id="prevArrow">‹</button>
                        <div class="carousel-pagination" id="carouselPagination">
                            <?php for ($i = 0; $i < $total_pages; $i++): ?>
                                <div class="pagination-dot <?php echo $i === 0 ? 'active' : ''; ?>" data-page="<?php echo $i; ?>"></div>
                            <?php endfor; ?>
                        </div>
                        <button class="carousel-arrow next-arrow" id="nextArrow">›</button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Variables AJAX pour WordPress
        const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        const ajaxNonce = '<?php echo wp_create_nonce('newsaiige_products_nonce'); ?>';
        
        const filterLinks = document.querySelectorAll('.filter-link');
        const productsGrid = document.getElementById('productsGrid');
        const carouselContainer = document.getElementById('productsCarousel');
        const prevArrow = document.getElementById('prevArrow');
        const nextArrow = document.getElementById('nextArrow');
        const paginationDots = document.querySelectorAll('.pagination-dot');
        
        let currentPage = 0;
        let allPages = Array.from(document.querySelectorAll('.carousel-page'));
        let visiblePages = [];
        let totalPages = allPages.length;

        // Initialiser le carrousel
        function initCarousel() {
            updateVisiblePages();
            setCarouselHeight();
            updateCarouselDisplay();
            updateControls();
        }

        // Mettre à jour la liste des pages visibles
        function updateVisiblePages() {
            visiblePages = allPages.filter(page => {
                const style = window.getComputedStyle(page);
                return style.display !== 'none';
            });
            totalPages = visiblePages.length;
        }

        // Définir la hauteur du conteneur carrousel
        function setCarouselHeight() {
            if (visiblePages.length === 0) return;
            
            let maxHeight = 0;
            visiblePages.forEach(page => {
                // Temporairement rendre visible pour mesurer
                const originalDisplay = page.style.display;
                const originalPosition = page.style.position;
                const originalOpacity = page.style.opacity;
                
                page.style.display = 'grid';
                page.style.position = 'relative';
                page.style.opacity = '0';
                page.classList.add('active');
                
                const height = page.offsetHeight;
                if (height > maxHeight) {
                    maxHeight = height;
                }
                
                // Restaurer les styles
                page.style.display = originalDisplay;
                page.style.position = originalPosition;
                page.style.opacity = originalOpacity;
                page.classList.remove('active');
            });
            
            productsGrid.style.height = maxHeight + 'px';
        }

        // Mettre à jour l'affichage du carrousel
        function updateCarouselDisplay() {
            // Masquer toutes les pages
            allPages.forEach(page => {
                page.classList.remove('active');
            });
            
            // Afficher la page courante
            if (visiblePages[currentPage]) {
                visiblePages[currentPage].classList.add('active');
            }
        }

        // Mettre à jour les contrôles
        function updateControls() {
            // Mettre à jour les flèches
            if (prevArrow) {
                prevArrow.disabled = currentPage === 0;
            }
            if (nextArrow) {
                nextArrow.disabled = currentPage >= totalPages - 1;
            }

            // Mettre à jour la pagination
            paginationDots.forEach((dot, index) => {
                if (index < totalPages) {
                    dot.style.display = 'block';
                    dot.classList.toggle('active', index === currentPage);
                } else {
                    dot.style.display = 'none';
                }
            });
        }

        // Navigation
        function goToPage(pageIndex) {
            if (pageIndex >= 0 && pageIndex < totalPages) {
                currentPage = pageIndex;
                updateCarouselDisplay();
                updateControls();
            }
        }

        function nextPage() {
            if (currentPage < totalPages - 1) {
                goToPage(currentPage + 1);
            }
        }

        function prevPage() {
            if (currentPage > 0) {
                goToPage(currentPage - 1);
            }
        }

        // Gestion des filtres
        function setupFilterHandlers() {
            // Seulement sur desktop
            if (window.innerWidth <= 1024) return;
            
            filterLinks.forEach(link => {
                link.addEventListener('click', handleFilterClick);
            });
        }

        function handleFilterClick(e) {
            e.preventDefault();
            
            // Mise à jour des classes actives
            filterLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            
            // Récupérer la catégorie et filtrer
            const category = this.getAttribute('data-category');
            filterProducts(category);
        }

        function filterProducts(category) {
            allPages.forEach(page => {
                const productWrappers = page.querySelectorAll('.product-wrapper');
                let hasVisibleProducts = false;
                
                productWrappers.forEach(wrapper => {
                    const productCategory = wrapper.getAttribute('data-category');
                    const badge = wrapper.querySelector('.product-category-badge');
                    const categoryName = badge ? badge.textContent.trim() : '';
                    const categorySlug = getCategorySlug(categoryName);
                    
                    const matches = category === 'all' || 
                                  categorySlug === category || 
                                  productCategory === category;
                    
                    if (matches) {
                        wrapper.style.display = 'block';
                        hasVisibleProducts = true;
                    } else {
                        wrapper.style.display = 'none';
                    }
                });
                
                // Afficher/masquer la page
                page.style.display = hasVisibleProducts ? 'grid' : 'none';
            });
            
            // Réinitialiser le carrousel
            currentPage = 0;
            initCarousel();
        }

        function getCategorySlug(categoryName) {
            return categoryName.toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }

        // Event listeners
        if (prevArrow) {
            prevArrow.addEventListener('click', prevPage);
        }
        
        if (nextArrow) {
            nextArrow.addEventListener('click', nextPage);
        }

        paginationDots.forEach((dot, index) => {
            dot.addEventListener('click', () => goToPage(index));
        });

        // Support clavier
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft') {
                prevPage();
            } else if (e.key === 'ArrowRight') {
                nextPage();
            }
        });

        // Support tactile
        let startX = 0;
        let endX = 0;

        carouselContainer.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
        });

        carouselContainer.addEventListener('touchend', function(e) {
            endX = e.changedTouches[0].clientX;
            const diffX = startX - endX;
            
            if (Math.abs(diffX) > 50) {
                if (diffX > 0) {
                    nextPage();
                } else {
                    prevPage();
                }
            }
        });

        // Initialisation des boutons panier
        function initializeCartButtons() {
            const cartButtons = document.querySelectorAll('.cart-status-icon');
            cartButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const productId = this.getAttribute('data-product-id');
                    
                    this.style.transform = 'scale(0.9)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                    
                    if (!this.classList.contains('in-cart')) {
                        addToWooCommerceCart(productId, this);
                    } else {
                        window.location.href = '<?php echo wc_get_cart_url(); ?>';
                    }
                });
            });

            // Clic sur les cartes
            const productCards = document.querySelectorAll('.product-card');
            productCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    if (e.target.closest('.cart-status-icon')) {
                        return;
                    }
                    
                    const productUrl = this.getAttribute('data-product-url');
                    if (productUrl && productUrl !== '#') {
                        window.location.href = productUrl;
                    }
                });
            });
        }

        function addToWooCommerceCart(productId, buttonElement) {
            const originalContent = buttonElement.innerHTML;
            buttonElement.innerHTML = '⏳';
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
                    
                    document.dispatchEvent(new CustomEvent('cartUpdated'));
                    showNotification('Produit ajouté au panier !', 'success');
                } else {
                    showNotification('Erreur lors de l\'ajout au panier', 'error');
                    buttonElement.innerHTML = originalContent;
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur de connexion', 'error');
                buttonElement.innerHTML = originalContent;
            })
            .finally(() => {
                buttonElement.style.pointerEvents = 'auto';
            });
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `cart-notification ${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#82897F' : '#e74c3c'};
                color: white;
                padding: 15px 20px;
                border-radius: 10px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.2);
                z-index: 10000;
                transform: translateX(400px);
                transition: transform 0.3s ease;
                backdrop-filter: blur(10px);
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);

            setTimeout(() => {
                notification.style.transform = 'translateX(400px)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        // Initialisation
        initCarousel();
        initializeCartButtons();
        setupFilterHandlers();

        // Redimensionnement
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                currentPage = 0;
                initCarousel();
                setupFilterHandlers();
            }, 250);
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode('newsaiige_products_carousel', 'newsaiige_product_carroussel_shortcode');

// AJAX Handler pour ajouter au panier
add_action('wp_ajax_woocommerce_add_to_cart', 'handle_ajax_add_to_cart');
add_action('wp_ajax_nopriv_woocommerce_add_to_cart', 'handle_ajax_add_to_cart');

function handle_ajax_add_to_cart_carroussel() {
    // Vérifier le nonce
    if (!wp_verify_nonce($_POST['security'], 'newsaiige_products_nonce')) {
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