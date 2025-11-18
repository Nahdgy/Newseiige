<?php
function newsaiige_featured_products_showcase($atts) {
    $atts = shortcode_atts(array(
        'title' => 'Nos clients aiment',
        'limit' => 3
    ), $atts);
    
    // Vérifier si WooCommerce est actif
    if (!class_exists('WooCommerce')) {
        return '<p>WooCommerce n\'est pas installé.</p>';
    }
    
    // Configuration de la requête pour les produits avec étoile (featured)
    // Méthode 1: Utilisation de WooCommerce si disponible
    if (function_exists('wc_get_featured_product_ids')) {
        $featured_ids = wc_get_featured_product_ids();
        
        if (!empty($featured_ids)) {
            $args = array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => intval($atts['limit']),
                'post__in' => $featured_ids,
                'orderby' => 'post__in'
            );
        } else {
            // Fallback si pas de produits featured trouvés avec WooCommerce
            $args = array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => intval($atts['limit']),
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_visibility',
                        'field' => 'name',
                        'terms' => 'featured',
                    ),
                ),
            );
        }
    } else {
        // Méthode 2: Requête directe avec meta_query améliorée
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['limit']),
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_featured',
                    'value' => 'yes',
                    'compare' => '='
                )
            ),
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_visibility',
                    'field'    => 'name',
                    'terms'    => array('exclude-from-catalog', 'exclude-from-search'),
                    'operator' => 'NOT IN',
                ),
            )
        );
    }
    
    /* ALTERNATIVE: Configuration pour les meilleures ventes
    $args = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => intval($atts['limit']),
        'meta_key' => 'total_sales',
        'orderby' => 'meta_value_num',
        'order' => 'DESC',
        'meta_query' => array(
            array(
                'key' => '_visibility',
                'value' => array('exclude-from-catalog', 'exclude-from-search'),
                'compare' => 'NOT IN'
            )
        )
    );
    */
    
    /* ALTERNATIVE SIMPLE: Si aucune méthode ne fonctionne, utiliser tous les produits
    $args = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => intval($atts['limit']),
        'orderby' => 'date',
        'order' => 'DESC'
    );
    */
    
    $products = new WP_Query($args);

    ob_start();
    ?>
    
    <style>
    .newsaiige-featured-showcase {
        position: relative;
        padding: 80px 20px;
        font-family: 'Montserrat', sans-serif;
        overflow: hidden;
    }
    
    .newsaiige-featured-container {
        max-width: 1400px;
        margin: 0 auto;
        position: relative;
    }
    
    .newsaiige-featured-header {
        position: relative;
    }
    
    .newsaiige-featured-title {
        font-size: 24px;
        font-weight: 700;
        color: #82897F;
        margin-bottom: 20px;
        letter-spacing: 2px;
        line-height: 1.2;
    }
    
    .newsaiige-featured-grid {
        display: grid;
        padding-top: 20px;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 20px;
        position: relative;
        overflow: hidden;
    }
    
    .newsaiige-product-wrapper{
        max-width: 340px;
    }
    .newsaiige-featured-product {
        border-radius: 25px;
        overflow: hidden;
        position: relative;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(255, 255, 255, 0.3);
        cursor: pointer;
    }
    
    .newsaiige-featured-product:hover {
        transform: translateY(-15px) scale(1.02);
    }
    
    .newsaiige-product-image-container {
        height: 500px;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        position: relative;
        overflow: hidden;
    }
    
    .newsaiige-product-image-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.1) 100%);
        z-index: 1;
    }
    
    .newsaiige-product-category-badge {
        position: absolute;
        top: 20px;
        left: 20px;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        color: #82897F;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        z-index: 2;
    }
    
    .newsaiige-cart-icon {
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
        border: 1px solid rgba(255, 255, 255, 0.3);
        cursor: pointer;
        transition: all 0.3s ease;
        z-index: 2;
    }
    
    .newsaiige-cart-icon:hover {
        transform: scale(1.1);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        color: white;
    }
    
    .newsaiige-cart-icon.in-cart {
        background: #82897F;
        color: white;
    }
    
    .newsaiige-product-info {
        padding: 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .newsaiige-product-details {
        flex: 1;
    }
    
    .newsaiige-product-title {
        font-size: 16px;
        font-weight: 400;
        color: #000000ff;
        margin: 0 0 0 0;
        line-height: 1.4;
    }
    
    .newsaiige-product-price {
        font-size: 16px;
        font-weight: 400;
        color: #000000ff;
        margin: 0;
    }
    
    .newsaiige-product-original-price {
        font-size: 16px;
        color: #999;
        text-decoration: line-through;
        margin-left: 10px;
    }
    
    .newsaiige-product-description-below {
        margin-top: 20px;
        padding: 0 10px;
        color: #666;
        font-size: 14px;
        line-height: 1.6;
        text-align: justify;
        font-weight: 400;
        max-width: 100%;
        opacity: 0.9;
    }
    
    /* Responsive */
    @media (max-width: 1200px) {
        .newsaiige-featured-header {
            margin-bottom: 40px;
        }
    }
    
    @media (max-width: 768px) {
        .newsaiige-featured-showcase {
            padding: 60px 15px;
        }
        
        .newsaiige-featured-grid {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 30px;
        }
        .newsaiige-product-wrapper{
            min-width: 100%;
        }
        
        .newsaiige-featured-title {
            font-size: 28px;
            text-align: center;
        }

        .newsaiige-product-image-container {
            height: 500px;
        }
        
        .newsaiige-product-info {
            padding: 20px;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 10px;
        }
    }
    
    @media (max-width: 480px) {
        .newsaiige-featured-showcase {
            padding: 40px 10px;
        }
        
        .newsaiige-featured-title {
            font-size: 28px;
        }
        
        .newsaiige-product-title {
            font-size: 16px;
        }
        
        .newsaiige-product-price {
            font-size: 18px;
        }
    }
    
    /* Animations */
    @keyframes fadeInUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    </style>
    
    <div class="newsaiige-featured-showcase">
        <div class="newsaiige-featured-container">
            <div class="newsaiige-featured-header">
                <h2 class="newsaiige-featured-title"><?php echo esc_html($atts['title']); ?></h2>

            </div>
            
            <div class="newsaiige-featured-grid" id="featuredGrid">
                <?php 
                while ($products->have_posts()) : $products->the_post();
                    global $product;
                    
                    // Récupérer les données du produit
                    $product_id = get_the_ID();
                    $product_title = get_the_title();
                    $product_url = get_permalink($product_id);
                    $product_image = get_the_post_thumbnail_url($product_id, 'large');
                    
                    // Récupérer les informations WooCommerce
                    $wc_product = wc_get_product($product_id);
                    $product_price = $wc_product ? $wc_product->get_price_html() : 'Prix non disponible';
                    
                    // Récupérer les catégories
                    $product_categories = get_the_terms($product_id, 'product_cat');
                    $category_name = 'Produit';
                    if ($product_categories && !is_wp_error($product_categories)) {
                        $category_name = $product_categories[0]->name;
                    }
                    
                    // Image par défaut si pas d'image
                    if (!$product_image) {
                        $product_image = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAw' . 
                            'IiBoZWlnaHQ9IjMwMCIgdmlld0JveD0iMCAwIDQwMCAzMDAiIGZpbGw9Im5vbmUi' .
                            'IHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRo' .
                            'PSI0MDAiIGhlaWdodD0iMzAwIiBmaWxsPSIjRjVGNUY1Ii8+Cjwvc3ZnPgo=';
                    }
                    
                    // Vérifier si le produit est en promotion
                    $is_on_sale = $wc_product && $wc_product->is_on_sale();
                    $regular_price = '';
                    if ($is_on_sale && $wc_product->get_regular_price()) {
                        $regular_price = wc_price($wc_product->get_regular_price());
                    }
                ?>
                
                <div class="newsaiige-product-wrapper">
                    <div class="newsaiige-featured-product" data-product-url="<?php echo esc_url($product_url); ?>">
                        <div class="newsaiige-product-image-container" style="background-image: url('<?php echo esc_url($product_image); ?>');">
                            <div class="newsaiige-product-category-badge"><?php echo esc_html($category_name); ?></div>
                            
                            <div class="newsaiige-cart-icon" data-product-id="<?php echo $product_id; ?>">
                                <img draggable="false" role="img" class="emoji" alt="🛒" src="http://newsaiige.com/wp-content/uploads/2025/10/panier_noir.png">
                            </div>
                        </div>
                        
                    </div>
                    <div class="newsaiige-product-info">
                            <div class="newsaiige-product-details">
                                <h3 class="newsaiige-product-title"><?php echo esc_html($product_title); ?></h3>
                            </div>
                            <div class="newsaiige-product-price">
                                <?php echo $product_price; ?>
                                <?php if ($regular_price) : ?>
                                    <span class="newsaiige-product-original-price"><?php echo $regular_price; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                </div>
                
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Variables pour les produits
        const products = document.querySelectorAll('.newsaiige-featured-product');
        
        // Gestion des clics sur les produits
        products.forEach(product => {
            product.addEventListener('click', function(e) {
                // Ne pas rediriger si on clique sur le panier
                if (e.target.classList.contains('newsaiige-cart-icon') || 
                    e.target.closest('.newsaiige-cart-icon')) {
                    return;
                }
                
                const productUrl = this.getAttribute('data-product-url');
                if (productUrl) {
                    window.location.href = productUrl;
                }
            });
        });
        
        // Gestion des boutons panier
        const cartButtons = document.querySelectorAll('.newsaiige-cart-icon');
        cartButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const productId = this.getAttribute('data-product-id');
                
                // Animation de clic
                this.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
                
                // Vérifier si déjà ajouté au panier
                if (!this.classList.contains('in-cart')) {
                    // Ajouter au panier via AJAX
                    if (typeof window.addToWooCommerceCart === 'function') {
                        window.addToWooCommerceCart(productId, 1, (data) => {
                            // Marquer comme ajouté
                            this.classList.add('in-cart');
                            this.innerHTML = '<img draggable="false" role="img" class="emoji" alt="✓" src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTkgMTJMMTEgMTRMMTUgMTAiIHN0cm9rZT0iIzgyODk3RiIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz4KPC9zdmc+" style="width: 20px; height: 20px;">';
                            
                            // Notification de succès
                            showNotification('Produit ajouté au panier !', 'success');
                        });
                    } else {
                        // Fallback - redirection vers le panier
                        window.location.href = '<?php echo wc_get_cart_url(); ?>';
                    }
                } else {
                    // Déjà dans le panier, rediriger vers le panier
                    window.location.href = '<?php echo wc_get_cart_url(); ?>';
                }
            });
        });
        
    });
    </script>
    
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}

add_shortcode('newsaiige_featured_showcase', 'newsaiige_featured_products_showcase');
?>