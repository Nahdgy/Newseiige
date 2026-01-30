<?php
function newsaiige_subscription_history_shortcode($atts) {
    $atts = shortcode_atts(array(
        'title' => 'Mes abonnements',
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
            if ($product) {
                // Pour les produits variables/variations, vérifier le produit parent
                $product_id = $product->get_id();
                if ($product->is_type('variation')) {
                    $product_id = $product->get_parent_id();
                }
                
                // Vérifier si le produit est dans la catégorie "soins"
                $terms = wp_get_post_terms($product_id, 'product_cat');
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

    .subscription-content {
        background: white;
        border-radius: 15px;
        padding: 40px;
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

    .order-link:hover {
        background: transparent;
        color: #82897F !important;
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(130, 137, 127, 0.3);
    }

    /* Masquer le bouton de changement de méthode de paiement WPS */
    .wps_wsp_payment_method_change,
    button.wps_wsp_payment_method_change {
        display: none !important;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .newsaiige-subscription-section {
            padding: 40px 15px;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .subscription-content {
            padding: 20px 10px;
        }

        .subscription-title {
            font-size: 20px;
        }

        .subscription-subtitle {
            font-size: 14px;
        }
    }
    </style>

    <div class="newsaiige-subscription-section">
        <div class="subscription-header">
            <h2 class="subscription-title"><?php echo esc_html($atts['title']); ?></h2>
            <p class="subscription-subtitle"><?php echo esc_html($atts['subtitle']); ?></p>
        </div>

        <div class="subscription-content">
            <?php if (!empty($subscription_orders)): ?>
                <?php 
                // Récupérer le contenu du shortcode WPS
                $wps_content = do_shortcode('[wps-subscription-dashboard]');
                
                // Corriger les URLs générées par WPS : /show-subscription/ID/ → ?wps-show-subscription=ID
                if (strpos($wps_content, 'show-subscription') !== false) {
                    $current_url = get_permalink();
                    $wps_content = preg_replace_callback(
                        '/(href=["\'])(.*?)\/show-subscription\/(\d+)\/?(["\'])/i',
                        function($matches) use ($current_url) {
                            return $matches[1] . add_query_arg('wps-show-subscription', $matches[3], $current_url) . $matches[4];
                        },
                        $wps_content
                    );
                }
                
                echo $wps_content;
                ?>
            <?php else: ?>
                <div class="no-subscriptions">
                    <div class="no-subscriptions-icon">📋</div>
                    <h3>Aucun abonnement acheté</h3>
                    <p>Vous n'avez pas encore acheté d'abonnements.</p>
                    <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="order-link" style="color: #fff;">
                        Voir les abonnements
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php
    return ob_get_clean();
}

add_shortcode('newsaiige_subscription_history', 'newsaiige_subscription_history_shortcode');
?>
