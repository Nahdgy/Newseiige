function newsaiige_orders_history_shortcode($atts) {
    $atts = shortcode_atts(array(
        'title' => 'Mes commandes',
        'subtitle' => 'Consultez l\'historique de vos commandes et achats.'
    ), $atts);
    
    // Vérifier si l'utilisateur est connecté
    if (!is_user_logged_in()) {
        return '<p>Vous devez être connecté pour accéder à cette page. <a href="' . wp_login_url() . '">Se connecter</a></p>';
    }
    
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // Récupérer les commandes de l'utilisateur
    $orders = wc_get_orders(array(
        'customer_id' => $user_id,
        'status' => array('wc-completed', 'wc-processing', 'wc-on-hold'),
        'limit' => -1,
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    
    // Filtrer les commandes pour ne garder que celles contenant des produits AUTRES que la catégorie "soins"
    $product_orders = array();
    foreach ($orders as $order) {
        $order_items = $order->get_items();
        foreach ($order_items as $item) {
            $product = $item->get_product();
            if ($product) {
                // Pour les produits variables, vérifier le produit parent
                $product_id = $product->get_id();
                if ($product->is_type('variation')) {
                    $product_id = $product->get_parent_id();
                }
                
                // Vérifier si le produit N'EST PAS dans la catégorie "soins"
                $terms = wp_get_post_terms($product_id, 'product_cat');
                $is_soins = false;
                foreach ($terms as $term) {
                    if (strtolower($term->name) === 'soins' || strtolower($term->slug) === 'soins') {
                        $is_soins = true;
                        break;
                    }
                }
                
                // Si ce n'est pas un produit "soins", l'ajouter
                if (!$is_soins) {
                    $product_orders[] = array(
                        'order' => $order,
                        'item' => $item,
                        'product' => $product
                    );
                }
            }
        }
    }
    
    ob_start();
    ?>

    <style>
    .newsaiige-orders-section {
        margin: 0 auto;
        padding: 60px 20px;
        font-family: 'Montserrat', sans-serif;
        border-radius: 20px;
        margin-top: 40px;
        margin-bottom: 40px;
    }

    .orders-header {
        text-align: left;
        margin-bottom: 50px;
    }

    .orders-title {
        font-size: 24px;
        font-weight: 700;
        color: #82897F;
        margin: 0 0 15px 0;
        letter-spacing: 1px;
    }

    .orders-subtitle {
        font-size: 16px;
        color: #000;
        margin: 0;
        font-weight: 400;
        line-height: 1.5;
    }

    .orders-table-container {
        background: white;
        border-radius: 15px;
        padding: 40px;
        overflow-x: auto;
    }

    .orders-table {
        width: 100%;
        border-collapse: collapse;
        font-family: 'Montserrat', sans-serif;
    }

    .orders-table th {
        background-color: #82897F;
        color: white;
        padding: 15px 20px;
        text-align: left;
        font-weight: 600;
        font-size: 0.95rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: none;
    }

    .orders-table th:first-child {
        border-radius: 15px 0 0 0;
    }

    .orders-table th:last-child {
        border-radius: 0 15px 0 0;
    }

    .orders-table td {
        padding: 20px;
        border-bottom: 1px solid rgba(130, 137, 127, 0.1);
        color: #7D7D7D;
        font-size: 0.95rem;
        vertical-align: top;
    }

    .orders-table tr:hover {
        background-color: rgba(130, 137, 127, 0.05);
    }

    .orders-table tr:last-child td {
        border-bottom: none;
    }

    .orders-table tr:last-child td:first-child {
        border-radius: 0 0 0 15px;
    }

    .orders-table tr:last-child td:last-child {
        border-radius: 0 0 15px 0;
    }

    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-completed {
        background-color: rgba(76, 175, 80, 0.1);
        color: #2e7d32;
    }

    .status-processing {
        background-color: rgba(255, 193, 7, 0.1);
        color: #f57c00;
    }

    .status-on-hold {
        background-color: rgba(33, 150, 243, 0.1);
        color: #1976d2;
    }

    .price-value {
        font-weight: 600;
        color: #82897F;
        font-size: 1rem;
    }

    .product-name {
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
    }

    .product-variation {
        font-size: 0.85rem;
        color: #666;
        font-style: italic;
    }

    .product-category {
        font-size: 0.8rem;
        color: #82897F;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 3px;
    }

    .order-link {
        display: inline-block;
        padding: 15px 40px;
        background: #82897F;
        color: white !important;
        text-decoration: none;
        border-radius: 50px;
        font-weight: 600;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        border: 2px solid #82897F;
        cursor: pointer;
    }

    .order-link :hover {
        background: transparent;
        color: #82897F !important;
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(130, 137, 127, 0.3);
    }

    .no-orders {
        text-align: center;
        padding: 60px 20px;
        color: #000;
    }

    .no-orders h3 {
        margin-bottom: 15px;
        font-weight: 700;
        font-size: 24px;
        color: #000;
    }

    .no-orders p {
        font-size: 16px;
        font-weight: 400;
        color: #000;
        margin-bottom: 10px;
    }

    .no-orders-icon {
        font-size: 3rem;
        color: #000;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .product-type-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-left: 8px;
    }

    .type-simple {
        background-color: rgba(33, 150, 243, 0.1);
        color: #1976d2;
    }

    .type-variable {
        background-color: rgba(156, 39, 176, 0.1);
        color: #7b1fa2;
    }

    .type-grouped {
        background-color: rgba(255, 152, 0, 0.1);
        color: #f57c00;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .newsaiige-orders-section {
            padding: 40px 15px;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .orders-table-container {
            padding: 20px 10px;
        }

        .orders-table {
            font-size: 0.85rem;
        }

        .orders-table th,
        .orders-table td {
            padding: 10px 8px;
        }

        .orders-title {
            font-size: 20px;
        }

        .orders-subtitle {
            font-size: 14px;
        }
    }

    @media (max-width: 480px) {
        .orders-table-container {
            padding: 15px 5px;
        }

        .orders-table th,
        .orders-table td {
            padding: 8px 6px;
            font-size: 0.8rem;
        }

        .status-badge {
            padding: 4px 8px;
            font-size: 0.7rem;
        }
    }
    </style>

    <div class="newsaiige-orders-section">
        <div class="orders-header">
            <h2 class="orders-title"><?php echo esc_html($atts['title']); ?></h2>
            <p class="orders-subtitle"><?php echo esc_html($atts['subtitle']); ?></p>
        </div>

        <div class="orders-table-container">
            <?php if (!empty($product_orders)): ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Commande</th>
                            <th>Date</th>
                            <th>Produit</th>
                            <th>Statut</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($product_orders as $prod_order): 
                            $order = $prod_order['order'];
                            $item = $prod_order['item'];
                            $product = $prod_order['product'];
                            
                            // Pour les variations, récupérer l'ID du produit parent
                            $product_id = $product->get_id();
                            if ($product->is_type('variation')) {
                                $product_id = $product->get_parent_id();
                            }
                            
                            // Récupérer les catégories du produit parent
                            $product_categories = wp_get_post_terms($product_id, 'product_cat');
                            $category_names = array();
                            foreach ($product_categories as $category) {
                                $category_names[] = $category->name;
                            }
                            
                            // Récupérer les informations de variation
                            $variation_data = array();
                            if ($item->get_variation_id()) {
                                $variation = wc_get_product($item->get_variation_id());
                                if ($variation) {
                                    $variation_data = $variation->get_variation_attributes();
                                }
                            }
                            
                            // Formater le statut
                            $status = $order->get_status();
                            $status_class = 'status-' . str_replace('wc-', '', $status);
                            $status_text = wc_get_order_status_name($status);
                            
                            // Type de produit
                            $product_type = $product->get_type();
                            $type_class = 'type-' . $product_type;
                        ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url($order->get_view_order_url()); ?>" class="order-link">
                                        #<?php echo esc_html($order->get_order_number()); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo esc_html($order->get_date_created()->date_i18n('d/m/Y')); ?>
                                </td>
                                <td>
                                    <?php if (!empty($category_names)): ?>
                                        <div class="product-category">
                                            <?php echo esc_html(implode(', ', $category_names)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="product-name">
                                        <?php echo esc_html(strip_tags($item->get_name())); ?>
                                        <span class="product-type-badge <?php echo esc_attr($type_class); ?>">
                                            <?php echo esc_html(ucfirst($product_type)); ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($variation_data)): ?>
                                        <div class="product-variation">
                                            <?php 
                                            $variation_text = array();
                                            foreach ($variation_data as $key => $value) {
                                                if (!empty($value)) {
                                                    $attribute_name = str_replace('attribute_', '', $key);
                                                    $attribute_name = str_replace('pa_', '', $attribute_name);
                                                    $variation_text[] = ucfirst($attribute_name) . ': ' . $value;
                                                }
                                            }
                                            echo esc_html(implode(' | ', $variation_text));
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="product-variation">
                                        Quantité: <?php echo esc_html($item->get_quantity()); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo esc_attr($status_class); ?>">
                                        <?php echo esc_html($status_text); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="price-value">
                                        <?php echo wc_price($item->get_total()); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-orders">
                    <div class="no-orders-icon">🛍️</div>
                    <h3>Aucune commande trouvée</h3>
                    <p>Vous n'avez pas encore de commandes dans votre historique.</p>
                    <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="order-link" style="color: #fff;">
                        Découvrir la boutique
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animation au survol des lignes du tableau
        const tableRows = document.querySelectorAll('.orders-table tbody tr');
        
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.01)';
                this.style.transition = 'all 0.2s ease';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });
        
        // Effet de survol sur les liens de commande
        const orderLinks = document.querySelectorAll('.order-link');
        
        orderLinks.forEach(link => {
            link.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(5px)';
                this.style.transition = 'transform 0.2s ease';
            });
            
            link.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
            });
        });
        
        // Animation des badges de type de produit
        const typeBadges = document.querySelectorAll('.product-type-badge');
        
        typeBadges.forEach(badge => {
            badge.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.1)';
                this.style.transition = 'transform 0.2s ease';
            });
            
            badge.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });
    });
    </script>

    <?php
    return ob_get_clean();
}

add_shortcode('newsaiige_orders_history', 'newsaiige_orders_history_shortcode');
?>