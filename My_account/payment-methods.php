<?php
function newsaiige_is_wc_payment_tokens_available() {
    return class_exists('WooCommerce') && class_exists('WC_Payment_Tokens');
}

function newsaiige_get_add_payment_method_url() {
    if (function_exists('wc_get_account_endpoint_url')) {
        return wc_get_account_endpoint_url('add-payment-method');
    }

    if (function_exists('wc_get_endpoint_url') && function_exists('wc_get_page_permalink')) {
        return wc_get_endpoint_url('add-payment-method', '', wc_get_page_permalink('myaccount'));
    }

    return home_url('/');
}

function newsaiige_payment_methods_shortcode($atts) {
    $atts = shortcode_atts(
        array(
            'title' => 'Mes moyens de paiement',
            'subtitle' => 'Gérez vos cartes enregistrées via le module de paiement sécurisé de WooCommerce.',
        ),
        $atts
    );

    if (!is_user_logged_in()) {
        return '<p>Vous devez être connecté pour accéder à cette page. <a href="' . esc_url(wp_login_url()) . '">Se connecter</a></p>';
    }

    if (!newsaiige_is_wc_payment_tokens_available()) {
        return '<p>WooCommerce n\'est pas disponible. Impossible de gérer les moyens de paiement.</p>';
    }

    $user_id = get_current_user_id();
    $tokens = WC_Payment_Tokens::get_customer_tokens($user_id);
    $default_token_id = (int) WC_Payment_Tokens::get_customer_default_token($user_id);
    $add_payment_url = newsaiige_get_add_payment_method_url();
    $ajax_url = admin_url('admin-ajax.php');
    $nonce = wp_create_nonce('newsaiige_payment_nonce');

    $add_payment_form_html = '';
    ob_start();
    do_action('woocommerce_account_add-payment-method_endpoint');
    $add_payment_form_html = trim((string) ob_get_clean());

    $has_add_payment_form = !empty($add_payment_form_html);

    ob_start();
    ?>
    <style>
    .newsaiige-payment-section {
        margin: 0 auto;
        padding: 60px 20px;
        font-family: 'Montserrat', sans-serif;
        border-radius: 20px;
        margin-top: 40px;
        margin-bottom: 40px;
    }

    .payment-header {
        text-align: left;
        margin-bottom: 35px;
    }

    .payment-title {
        font-size: 24px;
        font-weight: 700;
        color: #82897F;
        margin: 0 0 15px 0;
        letter-spacing: 1px;
    }

    .payment-subtitle {
        font-size: 16px;
        color: #000;
        margin: 0;
        font-weight: 400;
        line-height: 1.5;
    }

    .payment-notice {
        border-left: 4px solid #82897F;
        background: rgba(130, 137, 127, 0.08);
        padding: 12px 16px;
        border-radius: 10px;
        margin-bottom: 22px;
        color: #2f332c;
        font-size: 14px;
    }

    .payment-cards-container {
        background: white;
        border-radius: 15px;
        padding: 30px;
    }

    .payment-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }

    .payment-card {
        background: linear-gradient(135deg, #82897F 0%, #6d7465 100%);
        border-radius: 15px;
        padding: 25px;
        color: white;
        position: relative;
        box-shadow: 0 10px 30px rgba(130, 137, 127, 0.3);
        min-height: 175px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 10px;
    }

    .card-type {
        font-size: 1rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .default-badge {
        background: rgba(255, 255, 255, 0.2);
        padding: 4px 10px;
        border-radius: 15px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .card-number {
        font-size: 1.1rem;
        font-weight: 600;
        letter-spacing: 2px;
        margin: 10px 0;
        font-family: 'Courier New', monospace;
    }

    .card-expiry {
        font-size: 0.9rem;
        font-weight: 500;
        opacity: 0.9;
    }

    .card-actions {
        position: absolute;
        top: 10px;
        right: 10px;
        display: flex;
        gap: 5px;
    }

    .card-action-btn {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        color: white;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
    }

    .card-action-btn:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    .add-payment-btn {
        display: inline-block;
        padding: 14px 28px;
        background: #82897F;
        color: white !important;
        text-decoration: none;
        border-radius: 50px;
        font-weight: 600;
        font-size: 1rem;
        border: 2px solid #82897F;
    }

    .add-payment-btn:hover {
        background: transparent;
        color: #82897F !important;
    }

    .payment-actions {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .payment-add-panel {
        display: none;
        margin-top: 20px;
        border: 1px solid rgba(130, 137, 127, 0.25);
        border-radius: 12px;
        padding: 16px;
        background: #fff;
    }

    .payment-add-panel.is-open {
        display: block;
    }

    .payment-fallback-help {
        margin-top: 12px;
        text-align: center;
        font-size: 14px;
        color: #555;
    }

    .payment-fallback-help a {
        color: #82897F;
        text-decoration: underline;
    }

    .no-payment-methods {
        text-align: center;
        padding: 40px 20px;
        color: #000;
    }

    .no-payment-methods h3 {
        margin-bottom: 15px;
        font-weight: 700;
        font-size: 24px;
        color: #000;
    }

    .no-payment-methods p {
        font-size: 16px;
        font-weight: 400;
        color: #000;
        margin-bottom: 20px;
    }

    @media (max-width: 768px) {
        .newsaiige-payment-section {
            padding: 40px 15px;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .payment-cards-container {
            padding: 20px;
        }

        .payment-cards-grid {
            grid-template-columns: 1fr;
        }

        .payment-title {
            font-size: 20px;
        }

        .payment-subtitle {
            font-size: 14px;
        }
    }
    </style>

    <div class="newsaiige-payment-section">
        <div class="payment-header">
            <h2 class="payment-title"><?php echo esc_html($atts['title']); ?></h2>
            <p class="payment-subtitle"><?php echo esc_html($atts['subtitle']); ?></p>
        </div>

        <div class="payment-notice">
            Les cartes affichées ici sont les vrais moyens de paiement WooCommerce utilisés pour vos commandes et renouvellements d'abonnement.
        </div>

        <div class="payment-cards-container">
            <?php if (!empty($tokens)) : ?>
                <div class="payment-cards-grid">
                    <?php foreach ($tokens as $token) :
                        $token_id = (int) $token->get_id();
                        $is_default = $default_token_id === $token_id;
                        $card_type = method_exists($token, 'get_card_type') ? strtoupper((string) $token->get_card_type()) : strtoupper((string) $token->get_type());
                        $card_last4 = method_exists($token, 'get_last4') ? $token->get_last4() : '';
                        $expiry_month = method_exists($token, 'get_expiry_month') ? $token->get_expiry_month() : '';
                        $expiry_year = method_exists($token, 'get_expiry_year') ? $token->get_expiry_year() : '';
                        $expiry = ($expiry_month && $expiry_year) ? sprintf('%02d/%s', (int) $expiry_month, substr((string) $expiry_year, -2)) : '';
                        ?>
                        <div class="payment-card">
                            <div class="card-actions">
                                <?php if (!$is_default) : ?>
                                    <button class="card-action-btn" type="button" title="Définir par défaut" onclick="setDefaultPaymentMethod(<?php echo esc_attr($token_id); ?>)">⭐</button>
                                <?php endif; ?>
                                <button class="card-action-btn" type="button" title="Supprimer" onclick="deletePaymentMethod(<?php echo esc_attr($token_id); ?>)">🗑️</button>
                            </div>

                            <div class="card-header">
                                <span class="card-type"><?php echo esc_html($card_type ?: 'CARTE'); ?></span>
                                <?php if ($is_default) : ?>
                                    <span class="default-badge">Par défaut</span>
                                <?php endif; ?>
                            </div>

                            <div class="card-number">**** **** **** <?php echo esc_html($card_last4); ?></div>
                            <?php if (!empty($expiry)) : ?>
                                <div class="card-expiry">Expire: <?php echo esc_html($expiry); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="no-payment-methods">
                    <h3>Aucun moyen de paiement enregistré</h3>
                    <p>Ajoutez une carte bancaire pour faciliter vos prochains paiements.</p>
                </div>
            <?php endif; ?>

            <div class="payment-actions" style="margin-top:12px;">
                <?php if ($has_add_payment_form) : ?>
                    <button class="add-payment-btn" type="button" id="newsaiige-toggle-add-payment">+ Ajouter ou mettre à jour une carte</button>
                <?php else : ?>
                    <a class="add-payment-btn" href="<?php echo esc_url($add_payment_url); ?>">+ Ajouter ou mettre à jour une carte</a>
                <?php endif; ?>
            </div>

            <?php if ($has_add_payment_form) : ?>
                <div class="payment-add-panel" id="newsaiige-add-payment-panel">
                    <?php echo $add_payment_form_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            <?php else : ?>
                <p class="payment-fallback-help">
                    Si la page d'ajout de carte est vide, vérifiez qu'un moyen de paiement compatible tokenisation est activé dans WooCommerce.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <script>
    const newsaiigePaymentAjax = {
        ajaxUrl: "<?php echo esc_url($ajax_url); ?>",
        nonce: "<?php echo esc_js($nonce); ?>"
    };

    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('newsaiige-toggle-add-payment');
        const panel = document.getElementById('newsaiige-add-payment-panel');

        if (!toggleBtn || !panel) {
            return;
        }

        toggleBtn.addEventListener('click', function() {
            panel.classList.toggle('is-open');
            if (panel.classList.contains('is-open')) {
                panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    function setDefaultPaymentMethod(tokenId) {
        if (!confirm('Définir cette carte comme moyen de paiement par défaut ?')) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'newsaiige_set_default_payment_method');
        formData.append('token_id', tokenId);
        formData.append('nonce', newsaiigePaymentAjax.nonce);

        fetch(newsaiigePaymentAjax.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
                return;
            }

            alert(data.data && data.data.message ? data.data.message : 'Erreur lors de la mise à jour.');
        })
        .catch(() => {
            alert('Erreur de connexion. Veuillez réessayer.');
        });
    }

    function deletePaymentMethod(tokenId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce moyen de paiement ?')) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'newsaiige_delete_payment_method');
        formData.append('token_id', tokenId);
        formData.append('nonce', newsaiigePaymentAjax.nonce);

        fetch(newsaiigePaymentAjax.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
                return;
            }

            alert(data.data && data.data.message ? data.data.message : 'Erreur lors de la suppression.');
        })
        .catch(() => {
            alert('Erreur de connexion. Veuillez réessayer.');
        });
    }
    </script>
    <?php

    return ob_get_clean();
}

add_shortcode('newsaiige_payment_methods', 'newsaiige_payment_methods_shortcode');

add_action('wp_ajax_newsaiige_set_default_payment_method', 'newsaiige_set_default_payment_method_handler');
add_action('wp_ajax_newsaiige_delete_payment_method', 'newsaiige_delete_payment_method_handler');

function newsaiige_get_user_token_or_error($token_id, $user_id) {
    $token = WC_Payment_Tokens::get($token_id);

    if (!$token || (int) $token->get_user_id() !== (int) $user_id) {
        return new WP_Error('invalid_token', 'Moyen de paiement introuvable.');
    }

    return $token;
}

function newsaiige_set_default_payment_method_handler() {
    check_ajax_referer('newsaiige_payment_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Utilisateur non connecté.'));
    }

    if (!newsaiige_is_wc_payment_tokens_available()) {
        wp_send_json_error(array('message' => 'WooCommerce indisponible.'));
    }

    $user_id = get_current_user_id();
    $token_id = isset($_POST['token_id']) ? absint($_POST['token_id']) : 0;

    if (!$token_id) {
        wp_send_json_error(array('message' => 'Moyen de paiement invalide.'));
    }

    $token = newsaiige_get_user_token_or_error($token_id, $user_id);
    if (is_wp_error($token)) {
        wp_send_json_error(array('message' => $token->get_error_message()));
    }

    WC_Payment_Tokens::set_users_default($user_id, $token->get_id());

    wp_send_json_success(array('message' => 'Moyen de paiement par défaut mis à jour.'));
}

function newsaiige_delete_payment_method_handler() {
    check_ajax_referer('newsaiige_payment_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Utilisateur non connecté.'));
    }

    if (!newsaiige_is_wc_payment_tokens_available()) {
        wp_send_json_error(array('message' => 'WooCommerce indisponible.'));
    }

    $user_id = get_current_user_id();
    $token_id = isset($_POST['token_id']) ? absint($_POST['token_id']) : 0;

    if (!$token_id) {
        wp_send_json_error(array('message' => 'Moyen de paiement invalide.'));
    }

    $token = newsaiige_get_user_token_or_error($token_id, $user_id);
    if (is_wp_error($token)) {
        wp_send_json_error(array('message' => $token->get_error_message()));
    }

    $was_default = ((int) WC_Payment_Tokens::get_customer_default_token($user_id) === (int) $token_id);
    $deleted = $token->delete();

    if (!$deleted) {
        wp_send_json_error(array('message' => 'Suppression impossible.'));
    }

    if ($was_default) {
        $remaining_tokens = WC_Payment_Tokens::get_customer_tokens($user_id);
        if (!empty($remaining_tokens)) {
            $first_token = reset($remaining_tokens);
            if ($first_token) {
                WC_Payment_Tokens::set_users_default($user_id, (int) $first_token->get_id());
            }
        }
    }

    wp_send_json_success(array('message' => 'Moyen de paiement supprimé.'));
}
?>