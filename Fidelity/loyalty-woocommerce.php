<?php
/**
 * Intégration WooCommerce pour le système de fidélité
 * Validation et application des bons d'achat lors du checkout
 */

// Ajouter les hooks WooCommerce
add_action('woocommerce_init', 'newsaiige_loyalty_woocommerce_integration');

function newsaiige_loyalty_woocommerce_integration() {
    // Ajouter un champ pour le code de bon d'achat sur la page de checkout
    add_action('woocommerce_checkout_before_order_review', 'newsaiige_loyalty_voucher_field');
    
    // Valider le code de bon d'achat
    add_action('woocommerce_checkout_process', 'newsaiige_loyalty_validate_voucher');
    
    // Appliquer la réduction
    add_action('woocommerce_cart_calculate_fees', 'newsaiige_loyalty_apply_voucher_discount');
    
    // Marquer le bon d'achat comme utilisé après paiement
    add_action('woocommerce_checkout_order_processed', 'newsaiige_loyalty_mark_voucher_used', 10, 3);
    
    // Ajouter des informations dans l'email de confirmation
    add_action('woocommerce_email_order_details', 'newsaiige_loyalty_add_points_info_to_email', 20, 4);
    
    // Nettoyer les données de session si nécessaire
    add_action('woocommerce_checkout_init', 'newsaiige_loyalty_cleanup_session');
}

/**
 * Ajouter un champ pour saisir le code de bon d'achat
 */
function newsaiige_loyalty_voucher_field() {
    if (!is_user_logged_in()) return;
    
    global $wpdb;
    $user_id = get_current_user_id();
    $vouchers_table = $wpdb->prefix . 'newsaiige_loyalty_vouchers';
    
    // Récupérer les bons d'achat disponibles
    $vouchers = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $vouchers_table 
         WHERE user_id = %d AND is_used = 0 AND expires_at > NOW()
         ORDER BY created_at DESC",
        $user_id
    ));
    
    if (empty($vouchers)) return;
    ?>
    
    <div class="newsaiige-loyalty-voucher-section">
        <h3>Mes Bons d'achat disponibles</h3>
        
        <style>
        .newsaiige-loyalty-voucher-section {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 2px solid #e9ecef;
        }
        
        .loyalty-voucher-list {
            display: grid;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .loyalty-voucher-item {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .loyalty-voucher-item:hover {
            border-color: #82897F;
        }
        
        .loyalty-voucher-item.selected {
            border-color: #82897F;
            background: rgba(130, 137, 127, 0.1);
        }
        
        .voucher-info {
            flex-grow: 1;
        }
        
        .voucher-code {
            font-family: 'Courier New', monospace;
            font-weight: 700;
            color: #82897F;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .voucher-value {
            font-weight: 600;
            color: #28a745;
            margin-bottom: 3px;
        }
        
        .voucher-expiry {
            font-size: 0.85rem;
            color: #666;
        }
        
        .voucher-select {
            margin-left: 15px;
        }
        
        .voucher-select input[type="radio"] {
            margin: 0;
        }
        
        .manual-voucher-section {
            border-top: 1px solid #e9ecef;
            padding-top: 15px;
            margin-top: 15px;
        }
        
        .manual-voucher-input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            text-transform: uppercase;
        }
        
        .manual-voucher-input:focus {
            border-color: #82897F;
            outline: none;
        }
        
        .voucher-error {
            color: #dc3545;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        .voucher-success {
            color: #28a745;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        </style>
        
        <div class="loyalty-voucher-list">
            <?php foreach ($vouchers as $voucher): ?>
            <div class="loyalty-voucher-item" data-code="<?php echo esc_attr($voucher->voucher_code); ?>">
                <div class="voucher-info">
                    <div class="voucher-code"><?php echo esc_html($voucher->voucher_code); ?></div>
                    <div class="voucher-value">
                        <?php if ($voucher->percentage > 0): ?>
                            <?php echo $voucher->percentage; ?>% de réduction
                        <?php else: ?>
                            <?php echo number_format($voucher->amount, 2); ?>€ de réduction
                        <?php endif; ?>
                    </div>
                    <div class="voucher-expiry">
                        Expire le <?php echo date('d/m/Y', strtotime($voucher->expires_at)); ?>
                    </div>
                </div>
                <div class="voucher-select">
                    <input type="radio" name="newsaiige_voucher_code" value="<?php echo esc_attr($voucher->voucher_code); ?>" id="voucher_<?php echo $voucher->id; ?>">
                    <label for="voucher_<?php echo $voucher->id; ?>">Utiliser</label>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="manual-voucher-section">
            <label for="manual_voucher_code">Ou saisissez un code manuellement :</label>
            <input type="text" id="manual_voucher_code" name="newsaiige_manual_voucher" class="manual-voucher-input" placeholder="CODE PROMO">
            <div id="voucher-feedback"></div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Gestion de la sélection des bons d'achat
        $('.loyalty-voucher-item').on('click', function() {
            $('.loyalty-voucher-item').removeClass('selected');
            $(this).addClass('selected');
            $(this).find('input[type="radio"]').prop('checked', true);
            $('#manual_voucher_code').val('');
            
            // Déclencher la mise à jour du panier
            $('body').trigger('update_checkout');
        });
        
        // Gestion de la saisie manuelle
        $('#manual_voucher_code').on('input', function() {
            const code = $(this).val().toUpperCase();
            $(this).val(code);
            
            if (code.length >= 4) {
                // Désélectionner les bons d'achat de la liste
                $('.loyalty-voucher-item').removeClass('selected');
                $('input[name="newsaiige_voucher_code"]').prop('checked', false);
                
                // Vérifier si le code correspond à un bon d'achat de la liste
                let foundInList = false;
                $('.loyalty-voucher-item').each(function() {
                    if ($(this).data('code') === code) {
                        $(this).addClass('selected');
                        $(this).find('input[type="radio"]').prop('checked', true);
                        foundInList = true;
                        return false;
                    }
                });
                
                if (!foundInList) {
                    // Déclencher la vérification AJAX pour les codes externes
                    verifyVoucherCode(code);
                }
                
                // Déclencher la mise à jour du panier
                $('body').trigger('update_checkout');
            } else {
                $('#voucher-feedback').html('');
            }
        });
        
        function verifyVoucherCode(code) {
            $.ajax({
                url: wc_checkout_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'verify_loyalty_voucher',
                    voucher_code: code,
                    nonce: '<?php echo wp_create_nonce("verify_voucher_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $('#voucher-feedback').html('<div class="voucher-success">✓ Code valide : ' + response.data.description + '</div>');
                    } else {
                        $('#voucher-feedback').html('<div class="voucher-error">✗ ' + response.data + '</div>');
                    }
                }
            });
        }
    });
    </script>
    <?php
}

/**
 * Valider le code de bon d'achat lors du checkout
 */
function newsaiige_loyalty_validate_voucher() {
    if (!is_user_logged_in()) return;
    
    $voucher_code = '';
    
    // Vérifier les bons d'achat sélectionnés
    if (!empty($_POST['newsaiige_voucher_code'])) {
        $voucher_code = sanitize_text_field($_POST['newsaiige_voucher_code']);
    } elseif (!empty($_POST['newsaiige_manual_voucher'])) {
        $voucher_code = strtoupper(sanitize_text_field($_POST['newsaiige_manual_voucher']));
    }
    
    if (empty($voucher_code)) return;
    
    // Vérifier la validité du bon d'achat
    $voucher = newsaiige_loyalty_get_valid_voucher($voucher_code, get_current_user_id());
    
    if (!$voucher) {
        wc_add_notice('Code de bon d\'achat invalide ou expiré.', 'error');
        return;
    }
    
    // Stocker le code dans la session
    WC()->session->set('newsaiige_voucher_code', $voucher_code);
}

/**
 * Appliquer la réduction du bon d'achat
 */
function newsaiige_loyalty_apply_voucher_discount() {
    if (is_admin() && !defined('DOING_AJAX')) return;
    if (!is_user_logged_in()) return;
    
    // Récupérer le code depuis la session ou POST
    $voucher_code = WC()->session->get('newsaiige_voucher_code');
    
    if (empty($voucher_code)) {
        // Vérifier dans POST pour les requêtes AJAX
        if (!empty($_POST['newsaiige_voucher_code'])) {
            $voucher_code = sanitize_text_field($_POST['newsaiige_voucher_code']);
        } elseif (!empty($_POST['newsaiige_manual_voucher'])) {
            $voucher_code = strtoupper(sanitize_text_field($_POST['newsaiige_manual_voucher']));
        }
    }
    
    if (empty($voucher_code)) return;
    
    $voucher = newsaiige_loyalty_get_valid_voucher($voucher_code, get_current_user_id());
    if (!$voucher) return;
    
    $cart_total = WC()->cart->get_subtotal();
    $discount_amount = 0;
    
    if ($voucher->percentage > 0) {
        // Réduction en pourcentage
        $discount_amount = ($cart_total * $voucher->percentage) / 100;
        $discount_label = "Bon d'achat {$voucher->voucher_code} (-{$voucher->percentage}%)";
    } else {
        // Réduction fixe
        $discount_amount = min($voucher->amount, $cart_total);
        $discount_label = "Bon d'achat {$voucher->voucher_code} (-" . wc_price($voucher->amount) . ")";
    }
    
    if ($discount_amount > 0) {
        WC()->cart->add_fee($discount_label, -$discount_amount);
        // Stocker le code validé dans la session
        WC()->session->set('newsaiige_voucher_code', $voucher_code);
    }
}

/**
 * Marquer le bon d'achat comme utilisé après le paiement
 */
function newsaiige_loyalty_mark_voucher_used($order_id, $posted_data, $order) {
    $voucher_code = WC()->session->get('newsaiige_voucher_code');
    
    if (empty($voucher_code)) return;
    
    global $wpdb;
    $vouchers_table = $wpdb->prefix . 'newsaiige_loyalty_vouchers';
    
    // Marquer le bon d'achat comme utilisé
    $updated = $wpdb->update(
        $vouchers_table,
        array(
            'is_used' => 1,
            'used_order_id' => $order_id,
            'used_at' => current_time('mysql')
        ),
        array(
            'voucher_code' => $voucher_code,
            'is_used' => 0
        )
    );
    
    if ($updated) {
        // Ajouter une note à la commande
        $order->add_order_note("Bon d'achat {$voucher_code} utilisé pour cette commande.");
        
        // Nettoyer la session
        WC()->session->__unset('newsaiige_voucher_code');
    }
}

/**
 * Vérifier la validité d'un bon d'achat
 */
function newsaiige_loyalty_get_valid_voucher($voucher_code, $user_id = null) {
    global $wpdb;
    $vouchers_table = $wpdb->prefix . 'newsaiige_loyalty_vouchers';
    
    $where_clause = "voucher_code = %s AND is_used = 0 AND expires_at > NOW()";
    $params = array($voucher_code);
    
    if ($user_id) {
        $where_clause .= " AND user_id = %d";
        $params[] = $user_id;
    }
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $vouchers_table WHERE $where_clause",
        $params
    ));
}

/**
 * AJAX handler pour vérifier un code de bon d'achat
 */
add_action('wp_ajax_verify_loyalty_voucher', 'newsaiige_loyalty_verify_voucher_ajax');
add_action('wp_ajax_nopriv_verify_loyalty_voucher', 'newsaiige_loyalty_verify_voucher_ajax');

function newsaiige_loyalty_verify_voucher_ajax() {
    if (!wp_verify_nonce($_POST['nonce'], 'verify_voucher_nonce')) {
        wp_send_json_error('Erreur de sécurité');
    }
    
    $voucher_code = strtoupper(sanitize_text_field($_POST['voucher_code']));
    $user_id = is_user_logged_in() ? get_current_user_id() : null;
    
    $voucher = newsaiige_loyalty_get_valid_voucher($voucher_code, $user_id);
    
    if ($voucher) {
        $description = '';
        if ($voucher->percentage > 0) {
            $description = "{$voucher->percentage}% de réduction";
        } else {
            $description = number_format($voucher->amount, 2) . "€ de réduction";
        }
        
        wp_send_json_success(array(
            'description' => $description,
            'type' => $voucher->voucher_type
        ));
    } else {
        wp_send_json_error('Code invalide ou expiré');
    }
}

/**
 * Ajouter des informations sur les points gagnés dans l'email de confirmation
 */
function newsaiige_loyalty_add_points_info_to_email($order, $sent_to_admin, $plain_text, $email) {
    if ($sent_to_admin || $email->id !== 'customer_completed_order') return;
    
    $user_id = $order->get_user_id();
    if (!$user_id) return;
    
    global $newsaiige_loyalty;
    if (!isset($newsaiige_loyalty)) return;
    
    // Vérifier si l'utilisateur a un abonnement pour gagner des points
    if (!$newsaiige_loyalty->has_active_subscription($user_id)) return;
    
    // Calculer les points gagnés
    $order_total = $order->get_total();
    $points_earned = floor($order_total);
    
    if ($points_earned > 0) {
        echo '<h2>Programme de Fidélité</h2>';
        echo '<p>Félicitations ! Vous avez gagné <strong>' . $points_earned . ' points</strong> avec cette commande.</p>';
        
        $total_points = $newsaiige_loyalty->get_user_points($user_id);
        echo '<p>Vous avez maintenant <strong>' . $total_points . ' points</strong> disponibles.</p>';
        
        $current_tier = $newsaiige_loyalty->get_user_tier($user_id);
        if ($current_tier) {
            echo '<p>Votre palier actuel : <strong>' . $current_tier->tier_name . '</strong></p>';
        }
        
        echo '<p><a href="' . get_permalink() . '">Voir mon programme de fidélité</a></p>';
    }
}

/**
 * Nettoyer les données de session si nécessaire
 */
function newsaiige_loyalty_cleanup_session() {
    // Nettoyer les anciennes données de session si l'utilisateur recommence un checkout
    if (!WC()->cart->is_empty() && !WC()->session->get('newsaiige_voucher_code')) {
        // Session propre, pas besoin de nettoyer
        return;
    }
}

/**
 * Ajouter un shortcode pour afficher les points sur n'importe quelle page
 */
add_shortcode('newsaiige_loyalty_points', 'newsaiige_loyalty_points_shortcode');

function newsaiige_loyalty_points_shortcode($atts) {
    $atts = shortcode_atts(array(
        'show' => 'both', // available, total, both
        'style' => 'inline' // inline, block
    ), $atts);
    
    if (!is_user_logged_in()) {
        return '<span class="loyalty-points-login">Connectez-vous pour voir vos points</span>';
    }
    
    global $newsaiige_loyalty;
    if (!isset($newsaiige_loyalty)) return '';
    
    $user_id = get_current_user_id();
    $points_available = $newsaiige_loyalty->get_user_points($user_id);
    $points_total = $newsaiige_loyalty->get_user_lifetime_points($user_id);
    
    $output = '';
    
    if ($atts['style'] === 'block') {
        $output .= '<div class="loyalty-points-display">';
    } else {
        $output .= '<span class="loyalty-points-display">';
    }
    
    if ($atts['show'] === 'available' || $atts['show'] === 'both') {
        $output .= '<span class="points-available">' . number_format($points_available) . ' points disponibles</span>';
    }
    
    if ($atts['show'] === 'both') {
        $output .= ' | ';
    }
    
    if ($atts['show'] === 'total' || $atts['show'] === 'both') {
        $output .= '<span class="points-total">' . number_format($points_total) . ' points au total</span>';
    }
    
    if ($atts['style'] === 'block') {
        $output .= '</div>';
    } else {
        $output .= '</span>';
    }
    
    return $output;
}

/**
 * Ajouter des styles CSS pour les shortcodes
 */
add_action('wp_head', 'newsaiige_loyalty_shortcode_styles');

function newsaiige_loyalty_shortcode_styles() {
    ?>
    <style>
    .loyalty-points-display {
        font-family: 'Montserrat', sans-serif;
        font-weight: 600;
        color: #82897F;
    }
    
    .loyalty-points-login {
        font-family: 'Montserrat', sans-serif;
        font-style: italic;
        color: #666;
    }
    
    .points-available {
        color: #28a745;
    }
    
    .points-total {
        color: #82897F;
    }
    </style>
    <?php
}
?>