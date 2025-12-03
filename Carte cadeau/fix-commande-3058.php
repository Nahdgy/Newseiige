<?php
/**
 * SCRIPT DE RÉPARATION - Commande #3058
 * À ajouter TEMPORAIREMENT dans functions.php
 * Puis visiter: wp-admin/?fix_gift_card_3058=1
 */

add_action('admin_init', function() {
    // Vérifier l'accès admin et le paramètre URL
    if (!isset($_GET['fix_gift_card_3058']) || !current_user_can('manage_options')) {
        return;
    }
    
    error_log("=== DÉBUT RÉPARATION COMMANDE #3058 ===");
    
    global $wpdb;
    $order_id = 3058;
    
    // 1. Récupérer la commande
    $order = wc_get_order($order_id);
    
    if (!$order) {
        wp_die('❌ Commande #3058 introuvable');
    }
    
    error_log("Commande #3058 trouvée - Statut: " . $order->get_status());
    
    // 2. Vérifier si une carte existe déjà
    $table_name = $wpdb->prefix . 'newsaiige_gift_cards';
    $existing_card = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_name WHERE order_id = %d",
        $order_id
    ));
    
    if ($existing_card) {
        wp_die('ℹ️ Une carte existe déjà pour cette commande (ID: ' . $existing_card . ')');
    }
    
    error_log("Aucune carte existante - Création en cours");
    
    // 3. Récupérer les informations de la commande
    $customer_id = $order->get_customer_id();
    $customer_email = $order->get_billing_email();
    $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    $total_amount = $order->get_total();
    
    error_log("Client: $customer_name ($customer_email) - Total: $total_amount");
    
    // 4. Analyser les items pour extraire les infos carte cadeau
    $gift_card_data = array(
        'amount' => $total_amount,
        'quantity' => 1,
        'total_amount' => $total_amount,
        'shipping_cost' => 0.00,
        'buyer_name' => $customer_name,
        'buyer_email' => $customer_email,
        'recipient_type' => 'self', // Par défaut pour soi-même
        'recipient_name' => '',
        'recipient_email' => $customer_email,
        'personal_message' => '',
        'delivery_date' => date('Y-m-d'),
        'delivery_type' => 'digital',
    );
    
    // Chercher les métadonnées de carte cadeau dans la commande
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if ($product) {
            $product_name = $product->get_name();
            error_log("Item trouvé: $product_name");
            
            // Vérifier si c'est une carte cadeau
            if (stripos($product_name, 'Carte Cadeau') !== false) {
                // Essayer de récupérer les métadonnées
                $stored_data = $product->get_meta('_newsaiige_gift_card_data');
                if ($stored_data && is_array($stored_data)) {
                    $gift_card_data = array_merge($gift_card_data, $stored_data);
                    error_log("Métadonnées trouvées: " . print_r($stored_data, true));
                }
                
                // Détecter livraison physique dans le nom
                if (stripos($product_name, 'physique') !== false || stripos($product_name, 'Livraison physique') !== false) {
                    $gift_card_data['delivery_type'] = 'physical';
                    $gift_card_data['shipping_cost'] = 2.50;
                    $gift_card_data['amount'] = $total_amount - 2.50;
                    error_log("Livraison physique détectée");
                }
            }
        }
    }
    
    // 5. Générer un code unique
    do {
        $code = 'NSGG-' . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4)) . 
                '-' . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4));
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE code = %s",
            $code
        ));
    } while ($exists > 0);
    
    error_log("Code généré: $code");
    
    // 6. Insérer la carte dans la base de données
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 year'));
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'code' => $code,
            'amount' => $gift_card_data['amount'],
            'quantity' => 1,
            'total_amount' => $gift_card_data['total_amount'],
            'shipping_cost' => $gift_card_data['shipping_cost'],
            'buyer_name' => $gift_card_data['buyer_name'],
            'buyer_email' => $gift_card_data['buyer_email'],
            'recipient_type' => $gift_card_data['recipient_type'],
            'recipient_name' => $gift_card_data['recipient_name'],
            'recipient_email' => $gift_card_data['recipient_email'],
            'personal_message' => $gift_card_data['personal_message'],
            'delivery_date' => $gift_card_data['delivery_date'],
            'delivery_type' => $gift_card_data['delivery_type'],
            'status' => 'paid',
            'order_id' => $order_id,
            'expires_at' => $expires_at
        ),
        array('%s', '%f', '%d', '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
    );
    
    if (!$result) {
        error_log("ERREUR SQL: " . $wpdb->last_error);
        wp_die('❌ Erreur lors de la création de la carte: ' . $wpdb->last_error);
    }
    
    $card_id = $wpdb->insert_id;
    error_log("✓✓✓ Carte créée avec succès - ID: $card_id - Code: $code");
    
    // 7. Récupérer la carte pour l'email
    $gift_card = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $card_id
    ));
    
    // 8. Envoyer l'email
    if (function_exists('newsaiige_send_gift_card_email')) {
        error_log("Envoi de l'email à: " . $gift_card->recipient_email);
        
        if (newsaiige_send_gift_card_email($gift_card)) {
            // Marquer comme envoyée
            $wpdb->update(
                $table_name,
                array('status' => 'sent', 'sent_at' => current_time('mysql')),
                array('id' => $card_id),
                array('%s', '%s'),
                array('%d')
            );
            error_log("✓ Email envoyé avec succès");
            $email_status = '✅ Email envoyé';
        } else {
            error_log("✗ Échec envoi email");
            $email_status = '⚠️ Carte créée mais email non envoyé (envoyer manuellement)';
        }
    } else {
        error_log("⚠️ Fonction newsaiige_send_gift_card_email non trouvée");
        $email_status = '⚠️ Fonction email non disponible (envoyer manuellement)';
    }
    
    // 9. Ajouter une note à la commande
    $order->add_order_note(
        sprintf('🔧 Carte cadeau créée manuellement par réparation - Code: %s - Montant: %.2f€', $code, $gift_card_data['amount'])
    );
    
    error_log("=== FIN RÉPARATION COMMANDE #3058 ===");
    
    // 10. Afficher le résultat
    wp_die('
        <div style="font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 30px; background: #fff; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h1 style="color: #4CAF50;">✅ Carte Cadeau Créée avec Succès!</h1>
            
            <div style="background: #e8f5e9; border-left: 4px solid #4CAF50; padding: 15px; margin: 20px 0;">
                <h3>📋 Détails de la Carte</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Code:</strong></td><td style="padding: 8px; border-bottom: 1px solid #ddd; font-size: 18px; color: #4CAF50; font-weight: bold;">' . esc_html($code) . '</td></tr>
                    <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Montant:</strong></td><td style="padding: 8px; border-bottom: 1px solid #ddd;">' . number_format($gift_card_data['amount'], 2, ',', ' ') . ' €</td></tr>
                    <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Client:</strong></td><td style="padding: 8px; border-bottom: 1px solid #ddd;">' . esc_html($gift_card_data['buyer_name']) . '</td></tr>
                    <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Email:</strong></td><td style="padding: 8px; border-bottom: 1px solid #ddd;">' . esc_html($gift_card_data['recipient_email']) . '</td></tr>
                    <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Type livraison:</strong></td><td style="padding: 8px; border-bottom: 1px solid #ddd;">' . ($gift_card_data['delivery_type'] === 'physical' ? '📮 Physique' : '📧 Digital') . '</td></tr>
                    <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Commande WC:</strong></td><td style="padding: 8px; border-bottom: 1px solid #ddd;">#' . $order_id . '</td></tr>
                    <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Expire le:</strong></td><td style="padding: 8px; border-bottom: 1px solid #ddd;">' . date('d/m/Y', strtotime($expires_at)) . '</td></tr>
                    <tr><td style="padding: 8px;"><strong>Statut email:</strong></td><td style="padding: 8px;">' . $email_status . '</td></tr>
                </table>
            </div>
            
            <div style="background: #fff3cd; border-left: 4px solid #ff9800; padding: 15px; margin: 20px 0;">
                <h3>⚠️ Actions à Faire</h3>
                <ul>
                    <li><strong>Consulter les logs:</strong> /wp-content/debug.log</li>
                    <li><strong>Vérifier dans l\'admin:</strong> <a href="' . admin_url('admin.php?page=newsaiige-gift-cards') . '">Cartes Cadeaux</a></li>
                    <li><strong>Voir la commande:</strong> <a href="' . admin_url('post.php?post=' . $order_id . '&action=edit') . '">Commande #' . $order_id . '</a></li>
                    ' . ($email_status !== '✅ Email envoyé' ? '<li><strong style="color: #f44336;">IMPORTANT: Envoyer manuellement l\'email au client avec le code: ' . esc_html($code) . '</strong></li>' : '') . '
                </ul>
            </div>
            
            <p style="text-align: center; margin-top: 30px;">
                <a href="' . admin_url('admin.php?page=newsaiige-gift-cards') . '" style="background: #4CAF50; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">Voir les Cartes Cadeaux</a>
                <a href="' . admin_url() . '" style="background: #666; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin-left: 10px;">Retour Admin</a>
            </p>
            
            <p style="text-align: center; color: #666; font-size: 12px; margin-top: 30px;">
                <strong>Note:</strong> Ce script peut maintenant être supprimé de functions.php
            </p>
        </div>
    ');
});
?>
