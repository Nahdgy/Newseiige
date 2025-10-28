function newsaiige_subscription_history_shortcode($atts) {
    $atts = shortcode_atts(array(
        'title' => 'Mes Abonnements',
        'subtitle' => 'Consultez et gérez les abonnements que vous avez achetés.'
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
    
    // Filtrer les commandes pour ne garder que celles contenant des produits variables de la catégorie "soins"
    $subscription_orders = array();
    foreach ($orders as $order) {
        $order_items = $order->get_items();
        foreach ($order_items as $item) {
            $product = $item->get_product();
            if ($product && $product->is_type('variable')) {
                // Vérifier si le produit est dans la catégorie "soins"
                $terms = wp_get_post_terms($product->get_id(), 'product_cat');
                foreach ($terms as $term) {
                    if (strtolower($term->name) === 'soins' || strtolower($term->slug) === 'soins') {
                        $subscription_orders[] = array(
                            'order' => $order,
                            'item' => $item,
                            'product' => $product
                        );
                        break 2; // Sortir des deux boucles
                    }
                }
            }
        }
    }
    
    ob_start();
    ?>

    <style>
    .newsaiige-subscription-section {
        margin: 0 auto;
        padding: 60px 20px;
        font-family: 'Montserrat', sans-serif;
        border-radius: 20px;
        margin-top: 40px;
        margin-bottom: 40px;
    }

    .subscription-header {
        text-align: left;
        margin-bottom: 50px;
    }

    .subscription-title {
        font-size: 24px;
        font-weight: 700;
        color: #82897F;
        margin: 0 0 15px 0;
        letter-spacing: 1px;
    }

    .subscription-subtitle {
        font-size: 16px;
        color: #000;
        margin: 0;
        font-weight: 400;
        line-height: 1.5;
    }

    .subscription-table-container {
        background: white;
        border-radius: 15px;
        padding: 40px;
        overflow-x: auto;
    }

    .subscription-table {
        width: 100%;
        border-collapse: collapse;
        font-family: 'Montserrat', sans-serif;
    }

    .subscription-table th {
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

    .subscription-table th:first-child {
        border-radius: 15px 0 0 0;
    }

    .subscription-table th:last-child {
        border-radius: 0 15px 0 0;
    }

    .subscription-table td {
        padding: 20px;
        border-bottom: 1px solid rgba(130, 137, 127, 0.1);
        color: #7D7D7D;
        font-size: 0.95rem;
        vertical-align: top;
    }

    .subscription-table tr:hover {
        background-color: rgba(130, 137, 127, 0.05);
    }

    .subscription-table tr:last-child td {
        border-bottom: none;
    }

    .subscription-table tr:last-child td:first-child {
        border-radius: 0 0 0 15px;
    }

    .subscription-table tr:last-child td:last-child {
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

    .order-link {
        background-color: #82897F;
        padding: 15px 20px;
        border-radius:30px;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
    }

    .order-link:hover {
        color: #82897F;
        background-color: transparent;
        border: solid 2px #82897F;
    }

    .no-subscriptions {
        text-align: center;
        padding: 60px 20px;
        color: #000;
    }

    .no-subscriptions h3 {
        margin-bottom: 15px;
        font-weight: 700;
        font-size: 24px;
        color: #000;
    }

    .no-subscriptions p {
        font-size: 16px;
        font-weight: 400;
        color: #000;
        margin-bottom: 10px;
    }

    .no-subscriptions-icon {
        font-size: 3rem;
        color: #000;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .newsaiige-subscription-section {
            padding: 40px 15px;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .subscription-table-container {
            padding: 20px 10px;
        }

        .subscription-table {
            font-size: 0.85rem;
        }

        .subscription-table th,
        .subscription-table td {
            padding: 10px 8px;
        }

        .subscription-title {
            font-size: 20px;
        }

        .subscription-subtitle {
            font-size: 14px;
        }
    }

    @media (max-width: 480px) {
        .subscription-table-container {
            padding: 15px 5px;
        }

        .subscription-table th,
        .subscription-table td {
            padding: 8px 6px;
            font-size: 0.8rem;
        }

        .status-badge {
            padding: 4px 8px;
            font-size: 0.7rem;
        }
    }
    </style>

    <div class="newsaiige-subscription-section">
        <div class="subscription-header">
            <h2 class="subscription-title"><?php echo esc_html($atts['title']); ?></h2>
            <p class="subscription-subtitle"><?php echo esc_html($atts['subtitle']); ?></p>
        </div>

        <div class="subscription-table-container">
            <?php if (!empty($subscription_orders)): ?>
                <table class="subscription-table">
                    <thead>
                        <tr>
                            <th>Commande</th>
                            <th>Date</th>
                            <th>Abonnement / Soin</th>
                            <th>Statut</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscription_orders as $sub_order): 
                            $order = $sub_order['order'];
                            $item = $sub_order['item'];
                            $product = $sub_order['product'];
                            
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
                                    <div class="product-name">
                                        <?php echo esc_html($item->get_name()); ?>
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
                <div class="no-subscriptions">
                    <div class="no-subscriptions-icon">📋</div>
                    <h3>Aucun abonnement acheté</h3>
                    <p>Vous n’avez pas encore acheté d’abonnements.</p>
                    <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="order-link" style="color: #fff;">
                        Voir les abonnements
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animation au survol des lignes du tableau
        const tableRows = document.querySelectorAll('.subscription-table tbody tr');
        
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
    });
    </script>

    <?php
    return ob_get_clean();
}

add_shortcode('newsaiige_subscription_history', 'newsaiige_subscription_history_shortcode');
?>