<?php
/**
 * Gestionnaire AJAX pour le changement d'abonnement
 * À inclure dans functions.php : require_once get_template_directory() . '/My_account/subscription-change-handler.php';
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Récupérer les variations d'un produit variable
 */
function newsaiige_get_product_variations() {
    check_ajax_referer('subscription_change_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Vous devez être connecté');
        return;
    }
    
    $product_id = intval($_POST['product_id']);
    $current_variation_id = intval($_POST['current_variation_id']);
    
    $product = wc_get_product($product_id);
    
    if (!$product || !$product->is_type('variable')) {
        wp_send_json_error('Produit invalide ou non variable');
        return;
    }
    
    // Récupérer la variation actuelle
    $current_variation = wc_get_product($current_variation_id);
    $current_name = $current_variation ? $current_variation->get_name() : 'N/A';
    $current_price = $current_variation ? wc_price($current_variation->get_price()) : 'N/A';
    
    // Récupérer toutes les variations
    $variations = $product->get_available_variations();
    $variations_data = array();
    
    foreach ($variations as $variation) {
        $variation_obj = wc_get_product($variation['variation_id']);
        
        if (!$variation_obj) continue;
        
        // Formater les attributs
        $attributes = array();
        foreach ($variation['attributes'] as $attr_name => $attr_value) {
            $attribute_name = str_replace('attribute_', '', $attr_name);
            $attribute_name = str_replace('pa_', '', $attribute_name);
            $attribute_name = wc_attribute_label($attribute_name);
            
            // Récupérer le terme pour avoir le label propre
            if (taxonomy_exists($attr_name)) {
                $term = get_term_by('slug', $attr_value, $attr_name);
                $attr_value = $term ? $term->name : $attr_value;
            }
            
            $attributes[] = $attribute_name . ': ' . ucfirst($attr_value);
        }
        
        $variations_data[] = array(
            'variation_id' => $variation['variation_id'],
            'name' => $variation_obj->get_name(),
            'price' => wc_price($variation_obj->get_price()),
            'price_float' => floatval($variation_obj->get_price()),
            'attributes' => $variation['attributes'],
            'attributes_text' => implode(' | ', $attributes),
            'is_in_stock' => $variation_obj->is_in_stock(),
            'stock_quantity' => $variation_obj->get_stock_quantity()
        );
    }
    
    wp_send_json_success(array(
        'variations' => $variations_data,
        'current_name' => $current_name,
        'current_price' => $current_price,
        'product_name' => $product->get_name()
    ));
}

add_action('wp_ajax_get_product_variations', 'newsaiige_get_product_variations');

/**
 * Calculer la différence de prix entre deux variations
 */
function newsaiige_calculate_price_difference() {
    check_ajax_referer('subscription_change_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Vous devez être connecté');
        return;
    }
    
    $current_variation_id = intval($_POST['current_variation_id']);
    $new_variation_id = intval($_POST['new_variation_id']);
    
    $current_variation = wc_get_product($current_variation_id);
    $new_variation = wc_get_product($new_variation_id);
    
    if (!$current_variation || !$new_variation) {
        wp_send_json_error('Variations invalides');
        return;
    }
    
    $current_price = floatval($current_variation->get_price());
    $new_price = floatval($new_variation->get_price());
    $difference = $new_price - $current_price;
    
    wp_send_json_success(array(
        'current_price' => wc_price($current_price),
        'new_price' => wc_price($new_price),
        'difference' => $difference,
        'difference_formatted' => wc_price(abs($difference)),
        'is_upgrade' => $difference > 0,
        'is_downgrade' => $difference < 0
    ));
}

add_action('wp_ajax_calculate_price_difference', 'newsaiige_calculate_price_difference');

/**
 * Changer l'abonnement d'un utilisateur
 */
function newsaiige_change_subscription() {
    check_ajax_referer('subscription_change_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Vous devez être connecté');
        return;
    }
    
    $order_id = intval($_POST['order_id']);
    $item_id = intval($_POST['item_id']);
    $current_variation_id = intval($_POST['current_variation_id']);
    $new_variation_id = intval($_POST['new_variation_id']);
    
    // Vérifier que l'utilisateur est propriétaire de la commande
    $order = wc_get_order($order_id);
    
    if (!$order || $order->get_customer_id() != get_current_user_id()) {
        wp_send_json_error('Commande invalide ou non autorisée');
        return;
    }
    
    // Récupérer les variations
    $current_variation = wc_get_product($current_variation_id);
    $new_variation = wc_get_product($new_variation_id);
    
    if (!$current_variation || !$new_variation) {
        wp_send_json_error('Variations invalides');
        return;
    }
    
    // Vérifier le stock
    if (!$new_variation->is_in_stock()) {
        wp_send_json_error('Cette variation n\'est plus en stock');
        return;
    }
    
    // Calculer la différence de prix
    $current_price = floatval($current_variation->get_price());
    $new_price = floatval($new_variation->get_price());
    $price_difference = $new_price - $current_price;
    
    try {
        // Récupérer l'item de commande
        $item = $order->get_item($item_id);
        
        if (!$item) {
            wp_send_json_error('Item de commande introuvable');
            return;
        }
        
        $quantity = $item->get_quantity();
        $total_difference = $price_difference * $quantity;
        
        // Sauvegarder l'ancienne variation pour l'historique
        $old_variation_name = $current_variation->get_name();
        $new_variation_name = $new_variation->get_name();
        
        // Mettre à jour l'item avec la nouvelle variation
        $item->set_variation_id($new_variation_id);
        $item->set_product($new_variation);
        $item->set_subtotal($new_price * $quantity);
        $item->set_total($new_price * $quantity);
        
        // Mettre à jour les métadonnées de variation
        $variation_attributes = $new_variation->get_variation_attributes();
        foreach ($variation_attributes as $key => $value) {
            $item->update_meta_data($key, $value);
        }
        
        $item->save();
        
        // Mettre à jour le total de la commande
        $order->calculate_totals();
        
        // Ajouter une note à la commande
        $note = sprintf(
            'Abonnement modifié par le client : %s → %s (Différence de prix : %s - sera appliquée au prochain prélèvement)',
            $old_variation_name,
            $new_variation_name,
            wc_price($total_difference)
        );
        $order->add_order_note($note);
        
        // Stocker la date de modification pour référence
        $order->update_meta_data('_subscription_last_change', current_time('mysql'));
        $order->update_meta_data('_subscription_price_change', $total_difference);
        $order->save();
        
        // Message selon le type de changement
        if ($total_difference > 0) {
            $message = sprintf(
                'Abonnement modifié avec succès ! La différence de %s sera ajoutée à votre prochain prélèvement.',
                wc_price($total_difference)
            );
        } else if ($total_difference < 0) {
            $message = sprintf(
                'Abonnement modifié avec succès ! La différence de %s sera déduite de votre prochain prélèvement.',
                wc_price(abs($total_difference))
            );
        } else {
            $message = 'Abonnement modifié avec succès !';
        }
        
        // Envoyer un email de confirmation au client
        newsaiige_send_subscription_change_email($order, $old_variation_name, $new_variation_name, $total_difference);
        
        wp_send_json_success(array(
            'message' => $message,
            'price_difference' => $total_difference
        ));
        
    } catch (Exception $e) {
        wp_send_json_error('Erreur lors du changement d\'abonnement : ' . $e->getMessage());
    }
}

add_action('wp_ajax_change_subscription', 'newsaiige_change_subscription');



/**
 * Envoyer un email de confirmation de changement d'abonnement
 */
function newsaiige_send_subscription_change_email($order, $old_variation, $new_variation, $price_difference) {
    $customer_email = $order->get_billing_email();
    $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    
    $subject = 'Votre abonnement NewSaiige a été modifié';
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #82897F, #9EA49D); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .change-box { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #82897F; }
            .info-box { background: #e8f4f8; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2196F3; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Changement d'abonnement confirmé ✓</h1>
            </div>
            
            <div class="content">
                <p>Bonjour <?php echo esc_html($customer_name); ?>,</p>
                
                <p>Votre modification d'abonnement a été effectuée avec succès !</p>
                
                <div class="change-box">
                    <h3>Détails du changement</h3>
                    <p><strong>Commande :</strong> #<?php echo $order->get_order_number(); ?></p>
                    <p><strong>Ancien abonnement :</strong> <?php echo esc_html($old_variation); ?></p>
                    <p><strong>Nouvel abonnement :</strong> <?php echo esc_html($new_variation); ?></p>
                    
                    <?php if ($price_difference != 0): ?>
                        <p><strong>Différence de prix :</strong> <?php echo wc_price(abs($price_difference)); ?></p>
                    <?php endif; ?>
                </div>
                
                <?php if ($price_difference > 0): ?>
                    <div class="info-box">
                        <p style="margin: 0; color: #1976d2;">
                            ℹ️ <strong>Impact sur votre prochain prélèvement :</strong><br>
                            La différence de <?php echo wc_price($price_difference); ?> sera ajoutée à votre prochain prélèvement automatique.
                        </p>
                    </div>
                <?php elseif ($price_difference < 0): ?>
                    <div class="info-box">
                        <p style="margin: 0; color: #1976d2;">
                            ℹ️ <strong>Impact sur votre prochain prélèvement :</strong><br>
                            La différence de <?php echo wc_price(abs($price_difference)); ?> sera déduite de votre prochain prélèvement automatique.
                        </p>
                    </div>
                <?php endif; ?>
                
                <p>Votre nouvel abonnement est maintenant actif et sera pris en compte dès maintenant.</p>
                
                <p>Si vous avez des questions, n'hésitez pas à nous contacter.</p>
                
                <p>À bientôt chez NewSaiige !</p>
            </div>
            
            <div class="footer">
                <p>© <?php echo date('Y'); ?> NewSaiige - Tous droits réservés</p>
            </div>
        </div>
    </body>
    </html>
    <?php
    $message = ob_get_clean();
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: NewSaiige <noreply@newsaiige.com>'
    );
    
    return wp_mail($customer_email, $subject, $message, $headers);
}

?>
