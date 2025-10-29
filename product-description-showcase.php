<?php
function newsaiige_product_description_showcase($atts) {
    $atts = shortcode_atts(array(
        'product_ids' => '', // IDs des produits séparés par des virgules
        'background_image' => '', // URL de l'image de fond
        'limit' => 3, // Nombre de produits à afficher
        'category' => '', // Catégorie de produits
        'featured_only' => false, // Afficher seulement les produits mis en avant
        'use_current_product' => true // Utiliser le produit de la page actuelle
    ), $atts);
    
    // Vérifier si WooCommerce est actif
    if (!class_exists('WooCommerce')) {
        return '<p>WooCommerce n\'est pas installé.</p>';
    }
    
    // Si on est sur une page produit et use_current_product est activé
    if ($atts['use_current_product'] && is_product()) {
        global $post;
        $current_product_id = $post->ID;
        
        // Utiliser le produit actuel
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'post__in' => array($current_product_id)
        );
    } else {
        // Configuration normale de la requête
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['limit']),
            'meta_query' => array(
                array(
                    'key' => '_visibility',
                    'value' => array('catalog', 'visible'),
                    'compare' => 'IN'
                )
            )
        );
        
        // Filtrer par IDs spécifiques
        if (!empty($atts['product_ids'])) {
            $product_ids = array_map('trim', explode(',', $atts['product_ids']));
            $args['post__in'] = $product_ids;
            $args['orderby'] = 'post__in';
        }
        
        // Filtrer par catégorie
        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => $atts['category']
                )
            );
        }
        
        // Produits mis en avant uniquement
        if ($atts['featured_only']) {
            $args['meta_query'][] = array(
                'key' => '_featured',
                'value' => 'yes'
            );
        }
    }
    
    $products = new WP_Query($args);
    
    if (!$products->have_posts()) {
        return '<p>Aucun produit trouvé.</p>';
    }
    
    ob_start();
    ?>
    
    <style>
    .newsaiige-product-showcase {
        position: relative;
        min-height: 600px;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 80px 40px;
        font-family: 'Montserrat', sans-serif;
        overflow: hidden;
    }
    
    .newsaiige-showcase-container {
        position: relative;
        z-index: 2;
        max-width: 1200px;
        width: 100%;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 40px;
        align-items: start;
    }
    
    .newsaiige-product-card {
        background: rgba(255, 255, 255, 0.18);
        backdrop-filter: blur(30px);
        border-radius: 20px;
        padding: 40px 35px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        position: relative;
        overflow: hidden;
    }
    
    
    .newsaiige-product-card:hover::before {
        transform: scaleX(1);
    }
    
    
    .newsaiige-product-image {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 15px;
        margin-bottom: 25px;
        transition: transform 0.4s ease;
    }
    
    .newsaiige-product-card:hover .newsaiige-product-image {
        transform: scale(1.05);
    }
    
    .newsaiige-product-title {
        font-size: 22px;
        font-weight: 700;
        color: #333;
        margin-bottom: 15px;
        line-height: 1.3;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .newsaiige-product-description {
        color: #000000ff;
        font-size: 15px;
        line-height: 1.6;
        margin-bottom: 25px;
        text-align: justify;
        font-family: "Montserrat", sans-serif;
    }
    
    .newsaiige-showcase-title {
        grid-column: 1 / -1;
        text-align: center;
        margin-bottom: 40px;
    }
    
    .newsaiige-showcase-title h2 {
        font-size: 42px;
        font-weight: 700;
        color: #333;
        margin-bottom: 20px;
        text-transform: uppercase;
        letter-spacing: 2px;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .newsaiige-showcase-subtitle {
        font-size: 18px;
        color: #666;
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.6;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .newsaiige-product-showcase {
            padding: 60px 20px;
            min-height: auto;
        }
        
        .newsaiige-showcase-container {
            grid-template-columns: 1fr;
            gap: 30px;
        }
        
        .newsaiige-product-card {
            padding: 30px 25px;
        }
        
        .newsaiige-showcase-title h2 {
            font-size: 32px;
        }
        
        .newsaiige-product-title {
            font-size: 20px;
        }
    }
    
    @media (max-width: 480px) {
        .newsaiige-product-showcase {
            padding: 40px 15px;
        }
        
        .newsaiige-product-card {
            padding: 25px 20px;
        }
        
        .newsaiige-showcase-title h2 {
            font-size: 28px;
        }
    }
    </style>
    
    <div class="newsaiige-product-showcase">
        <div class="newsaiige-showcase-container">
            
            <?php while ($products->have_posts()) : $products->the_post(); 
                global $product;
                
                // Récupérer les données du produit
                $product_id = get_the_ID();
                // Récupérer la description principale (contenu complet) du produit
                $product_description = get_the_content();
                
                // Appliquer les filtres WordPress pour traiter le contenu
                $product_description = apply_filters('the_content', $product_description);
                
                // Si la description principale est vide, utiliser l'extrait comme fallback
                if (empty(trim(strip_tags($product_description)))) {
                    $product_description = get_the_excerpt();
                    if (empty($product_description)) {
                        $product_description = 'Aucune description disponible pour ce produit.';
                    }
                }
                
            ?>
            
            <div class="newsaiige-product-card"> 
                <div class="newsaiige-product-description">
                    <?php echo wp_kses_post($product_description); ?>
                </div>
            </div>
            
            <?php endwhile; ?>
        </div>
    </div>
    
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}

add_shortcode('newsaiige_product_showcase', 'newsaiige_product_description_showcase');
?>