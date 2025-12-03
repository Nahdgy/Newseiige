<?php
/**
 * NewSaiige Gift Card Validation Frontend
 * Interface publique pour vérifier et valider les cartes cadeaux
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode pour la validation publique des cartes cadeaux
 */
function newsaiige_gift_card_validator_shortcode($atts) {
    $atts = shortcode_atts(array(
        'title' => 'Vérifier votre Carte Cadeau',
        'subtitle' => 'Entrez votre code pour vérifier la validité et le solde'
    ), $atts);
    
    // Enqueue les scripts
    wp_enqueue_script('jquery');
    wp_enqueue_script('newsaiige-validator-js', '', array('jquery'), '1.0', true);
    wp_add_inline_script('newsaiige-validator-js', '
        const newsaiige_validator_ajax = {
            ajax_url: "' . admin_url('admin-ajax.php') . '",
            nonce: "' . wp_create_nonce('newsaiige_validate_code_nonce') . '"
        };
    ');
    
    ob_start();
    ?>

    <style>
    .newsaiige-validator {
        padding: 60px 20px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        font-family: 'Montserrat', sans-serif;
        position: relative;
        overflow: hidden;
    }

    .validator-container {
        max-width: 600px;
        margin: 0 auto;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(15px) saturate(180%);
        -webkit-backdrop-filter: blur(15px) saturate(180%);
        border-radius: 25px;
        padding: 50px 40px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        text-align: center;
    }

    .validator-header {
        margin-bottom: 40px;
    }

    .validator-title {
        font-size: 2.2rem;
        font-weight: 700;
        color: #82897F;
        margin: 0 0 15px 0;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .validator-subtitle {
        font-size: 1.1rem;
        color: #666;
        font-weight: 400;
        line-height: 1.6;
        margin: 0;
    }

    .validator-form {
        margin: 40px 0;
    }

    .code-input-group {
        display: flex;
        flex-direction: column;
        gap: 20px;
        margin-bottom: 30px;
    }

    .code-input {
        padding: 20px 25px;
        border: 3px solid #e9ecef;
        border-radius: 15px;
        font-family: 'Courier New', monospace;
        font-size: 1.8rem;
        font-weight: bold;
        text-align: center;
        letter-spacing: 3px;
        text-transform: uppercase;
        transition: all 0.3s ease;
        background: white;
        color: #82897F;
    }

    .code-input:focus {
        outline: none;
        border-color: #82897F;
        box-shadow: 0 0 0 4px rgba(130, 137, 127, 0.1);
        transform: scale(1.02);
    }

    .code-input::placeholder {
        color: #bbb;
        font-weight: normal;
    }

    .validate-btn {
        background: linear-gradient(45deg, #82897F, #9EA49D);
        color: white;
        padding: 18px 40px;
        border: none;
        border-radius: 50px;
        font-family: 'Montserrat', sans-serif;
        font-weight: 700;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 1px;
        width: 100%;
        max-width: 300px;
        position: relative;
        overflow: hidden;
    }

    .validate-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(130, 137, 127, 0.3);
    }

    .validate-btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
    }

    .btn-text {
        transition: opacity 0.3s ease;
    }

    .btn-spinner {
        display: none;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 20px;
        height: 20px;
        border: 2px solid transparent;
        border-top: 2px solid white;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: translate(-50%, -50%) rotate(0deg); }
        100% { transform: translate(-50%, -50%) rotate(360deg); }
    }

    /* Messages de statut */
    .status-message {
        padding: 20px 25px;
        border-radius: 15px;
        margin: 30px 0;
        font-weight: 600;
        display: none;
        animation: fadeInUp 0.5s ease;
    }

    .status-message.success {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        border: 2px solid #28a745;
        color: #155724;
    }

    .status-message.error {
        background: linear-gradient(135deg, #f8d7da, #f5c6cb);
        border: 2px solid #dc3545;
        color: #721c24;
    }

    .status-message.warning {
        background: linear-gradient(135deg, #fff3cd, #ffeaa7);
        border: 2px solid #ffc107;
        color: #856404;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Carte de résultat */
    .result-card {
        background: linear-gradient(135deg, #82897F, #9EA49D);
        color: white;
        padding: 30px;
        border-radius: 20px;
        margin: 30px 0;
        display: none;
        animation: fadeInUp 0.5s ease;
    }

    .result-card.valid {
        background: linear-gradient(135deg, #28a745, #20c997);
    }

    .result-card.invalid {
        background: linear-gradient(135deg, #dc3545, #e74c3c);
    }

    .result-card.expired {
        background: linear-gradient(135deg, #6c757d, #495057);
    }

    .result-header {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
        margin-bottom: 20px;
    }

    .result-icon {
        font-size: 2.5rem;
    }

    .result-title {
        font-size: 1.8rem;
        font-weight: 700;
        margin: 0;
    }

    .result-details {
        display: grid;
        gap: 15px;
        margin-top: 20px;
    }

    .result-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }

    .result-row:last-child {
        border-bottom: none;
    }

    .result-label {
        font-weight: 600;
        opacity: 0.9;
    }

    .result-value {
        font-weight: 700;
        font-size: 1.1rem;
    }

    .amount-display {
        font-size: 2.5rem !important;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    /* Instructions d'utilisation */
    .usage-instructions {
        background: #f8f9fa;
        border: 2px solid #e9ecef;
        border-radius: 15px;
        padding: 25px;
        margin: 30px 0;
        text-align: left;
        display: none;
    }

    .usage-instructions h4 {
        color: #82897F;
        margin-top: 0;
        font-size: 1.3rem;
    }

    .usage-instructions ol {
        margin: 15px 0;
        padding-left: 20px;
    }

    .usage-instructions li {
        margin: 8px 0;
        line-height: 1.6;
    }

    .contact-info {
        background: #82897F;
        color: white;
        padding: 15px;
        border-radius: 10px;
        margin: 15px 0;
    }

    .contact-info a {
        color: white;
        text-decoration: none;
        font-weight: 600;
    }

    .contact-info a:hover {
        text-decoration: underline;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .validator-container {
            padding: 40px 30px;
            margin: 0 20px;
        }

        .validator-title {
            font-size: 1.8rem;
        }

        .code-input {
            font-size: 1.5rem;
            padding: 18px 20px;
        }

        .result-header {
            flex-direction: column;
            gap: 10px;
        }

        .result-row {
            flex-direction: column;
            text-align: center;
            gap: 5px;
        }
    }

    @media (max-width: 480px) {
        .validator-container {
            padding: 30px 20px;
            margin: 0 15px;
        }

        .newsaiige-validator {
            padding: 40px 15px;
        }

        .code-input {
            font-size: 1.3rem;
            letter-spacing: 2px;
        }

        .amount-display {
            font-size: 2rem !important;
        }
    }
    </style>

    <div class="newsaiige-validator">
        <div class="validator-container">
            <div class="validator-header">
                <h2 class="validator-title"><?php echo esc_html($atts['title']); ?></h2>
                <p class="validator-subtitle"><?php echo esc_html($atts['subtitle']); ?></p>
            </div>

            <form class="validator-form" id="validatorForm">
                <div class="code-input-group">
                    <input type="text" 
                           id="giftCardCode"
                           name="gift_card_code"
                           class="code-input" 
                           placeholder="NSGG-XXXX-XXXX"
                           maxlength="14"
                           autocomplete="off"
                           required>
                </div>

                <button type="submit" class="validate-btn" id="validateBtn">
                    <span class="btn-text">Vérifier la carte</span>
                    <div class="btn-spinner" id="btnSpinner"></div>
                </button>
            </form>

            <!-- Message de statut -->
            <div class="status-message" id="statusMessage"></div>

            <!-- Carte de résultat -->
            <div class="result-card" id="resultCard">
                <div class="result-header">
                    <span class="result-icon" id="resultIcon">🎁</span>
                    <h3 class="result-title" id="resultTitle">Carte Cadeau Valide</h3>
                </div>
                
                <div class="result-details" id="resultDetails">
                    <!-- Les détails seront remplis dynamiquement -->
                </div>
            </div>

            <!-- Instructions d'utilisation -->
            <div class="usage-instructions" id="usageInstructions">
                <h4>💡 Comment utiliser votre carte cadeau :</h4>
                <ol>
                    <li><strong>Réservez votre rendez-vous</strong> par téléphone ou en ligne</li>
                    <li><strong>Présentez ce code</strong> lors de votre arrivée chez NewSaiige</li>
                    <li><strong>Le montant sera automatiquement déduit</strong> de votre facture</li>
                    <li>Si le montant dépasse la valeur de la carte, payez la différence</li>
                    <li>Si le montant est inférieur, le solde reste disponible pour une prochaine visite</li>
                </ol>
                
                <div class="contact-info">
                    <strong>📍 NewSaiige</strong><br>
                    [Votre adresse complète]<br>
                    📞 <a href="tel:+33123456789">01 23 45 67 89</a><br>
                    ✉️ <a href="mailto:contact@newsaiige.com">contact@newsaiige.com</a><br>
                    🌐 <a href="https://newsaiige.com" target="_blank">www.newsaiige.com</a>
                </div>
            </div>

            <!-- Informations générales -->
            <div style="background: #f8f9fa; border-radius: 15px; padding: 20px; margin: 30px 0; text-align: left; font-size: 0.9rem; color: #666;">
                <h4 style="color: #82897F; margin-top: 0;">ℹ️ Informations importantes :</h4>
                <ul style="margin: 0; padding-left: 20px;">
                    <li>Les cartes cadeaux sont valables 1 an à partir de la date d'émission</li>
                    <li>Elles ne peuvent pas être échangées contre de l'argent</li>
                    <li>En cas de perte, contactez-nous avec vos informations d'achat</li>
                    <li>Utilisables pour tous nos soins et services</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('validatorForm');
        const codeInput = document.getElementById('giftCardCode');
        const validateBtn = document.getElementById('validateBtn');
        const btnText = document.querySelector('.btn-text');
        const btnSpinner = document.getElementById('btnSpinner');
        const statusMessage = document.getElementById('statusMessage');
        const resultCard = document.getElementById('resultCard');
        const resultIcon = document.getElementById('resultIcon');
        const resultTitle = document.getElementById('resultTitle');
        const resultDetails = document.getElementById('resultDetails');
        const usageInstructions = document.getElementById('usageInstructions');

        // Auto-formatage du code
        codeInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^A-Z0-9]/gi, '').toUpperCase();
            
            // Format NSGG-XXXX-XXXX
            if (value.length > 4 && value.length <= 8) {
                value = value.slice(0, 4) + '-' + value.slice(4);
            } else if (value.length > 8) {
                value = value.slice(0, 4) + '-' + value.slice(4, 8) + '-' + value.slice(8, 12);
            }
            
            e.target.value = value;
        });

        // Soumission du formulaire
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const code = codeInput.value.trim();
            
            if (!code) {
                showMessage('error', 'Veuillez saisir un code de carte cadeau');
                codeInput.focus();
                return;
            }

            if (code.length < 12) {
                showMessage('error', 'Le code doit être au format NSGG-XXXX-XXXX');
                codeInput.focus();
                return;
            }

            validateCard(code);
        });

        function validateCard(code) {
            // Interface de chargement
            validateBtn.disabled = true;
            btnText.style.opacity = '0';
            btnSpinner.style.display = 'block';
            hideMessages();

            // Requête AJAX
            const formData = new FormData();
            formData.append('action', 'validate_gift_card_code');
            formData.append('gift_card_code', code);
            formData.append('nonce', newsaiige_validator_ajax.nonce);

            fetch(newsaiige_validator_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showResult(data.data);
                } else {
                    showMessage('error', data.data || 'Code de carte cadeau invalide');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showMessage('error', 'Une erreur est survenue. Veuillez réessayer.');
            })
            .finally(() => {
                // Restaurer le bouton
                validateBtn.disabled = false;
                btnText.style.opacity = '1';
                btnSpinner.style.display = 'none';
            });
        }

        function showResult(cardData) {
            hideMessages();

            // Déterminer le type de résultat
            let cardClass = 'valid';
            let icon = '✅';
            let title = 'Carte Cadeau Valide';

            if (cardData.status === 'used') {
                cardClass = 'invalid';
                icon = '❌';
                title = 'Carte Déjà Utilisée';
            } else if (cardData.is_expired) {
                cardClass = 'expired';
                icon = '⏰';
                title = 'Carte Expirée';
            } else if (!cardData.is_active) {
                cardClass = 'invalid';
                icon = '⚠️';
                title = 'Carte Non Active';
            }

            // Mise à jour de l'interface
            resultCard.className = `result-card ${cardClass}`;
            resultIcon.textContent = icon;
            resultTitle.textContent = title;

            // Construire les détails
            let detailsHTML = '';
            
            detailsHTML += `
                <div class="result-row">
                    <span class="result-label">Code :</span>
                    <span class="result-value" style="font-family: monospace;">${cardData.code}</span>
                </div>
            `;

            if (cardData.status !== 'used' && !cardData.is_expired) {
                detailsHTML += `
                    <div class="result-row">
                        <span class="result-label">Valeur :</span>
                        <span class="result-value amount-display">${cardData.amount}€</span>
                    </div>
                `;
            }

            if (cardData.buyer_name) {
                detailsHTML += `
                    <div class="result-row">
                        <span class="result-label">Offerte par :</span>
                        <span class="result-value">${cardData.buyer_name}</span>
                    </div>
                `;
            }

            if (cardData.personal_message) {
                detailsHTML += `
                    <div class="result-row">
                        <span class="result-label">Message :</span>
                        <span class="result-value" style="font-style: italic;">"${cardData.personal_message}"</span>
                    </div>
                `;
            }

            detailsHTML += `
                <div class="result-row">
                    <span class="result-label">Expire le :</span>
                    <span class="result-value">${cardData.expires_at_formatted}</span>
                </div>
            `;

            if (cardData.status === 'used') {
                detailsHTML += `
                    <div class="result-row">
                        <span class="result-label">Utilisée le :</span>
                        <span class="result-value">${cardData.used_at_formatted}</span>
                    </div>
                `;
            }

            resultDetails.innerHTML = detailsHTML;
            resultCard.style.display = 'block';

            // Afficher les instructions si la carte est valide
            if (cardData.status !== 'used' && !cardData.is_expired && cardData.is_active) {
                usageInstructions.style.display = 'block';
            }

            // Faire défiler vers le résultat
            resultCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function showMessage(type, message) {
            statusMessage.className = `status-message ${type}`;
            statusMessage.textContent = message;
            statusMessage.style.display = 'block';
            statusMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function hideMessages() {
            statusMessage.style.display = 'none';
            resultCard.style.display = 'none';
            usageInstructions.style.display = 'none';
        }

        // Focus automatique sur le champ
        codeInput.focus();
    });
    </script>

    <?php
    return ob_get_clean();
}

// Enregistrer le shortcode
add_shortcode('newsaiige_gift_card_validator', 'newsaiige_gift_card_validator_shortcode');

/**
 * Action AJAX pour valider un code de carte cadeau (public)
 */
function newsaiige_validate_gift_card_code_public() {
    // Vérification de sécurité
    if (!wp_verify_nonce($_POST['nonce'], 'newsaiige_validate_code_nonce')) {
        wp_send_json_error('Erreur de sécurité');
        return;
    }
    
    $code = sanitize_text_field($_POST['gift_card_code']);
    
    if (empty($code)) {
        wp_send_json_error('Code requis');
        return;
    }
    
    // Rechercher la carte en base
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsaiige_gift_cards';
    
    $gift_card = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE code = %s",
        $code
    ));
    
    if (!$gift_card) {
        wp_send_json_error('Code de carte cadeau introuvable');
        return;
    }
    
    // Vérifier le statut et l'expiration
    $is_expired = strtotime($gift_card->expires_at) < time();
    $is_active = in_array($gift_card->status, ['paid', 'sent']);
    
    // Préparer les données de réponse
    $response_data = array(
        'code' => $gift_card->code,
        'amount' => number_format($gift_card->amount, 0, ',', ' '),
        'status' => $gift_card->status,
        'buyer_name' => $gift_card->buyer_name,
        'personal_message' => $gift_card->personal_message,
        'expires_at_formatted' => date('d/m/Y', strtotime($gift_card->expires_at)),
        'is_expired' => $is_expired,
        'is_active' => $is_active
    );
    
    if ($gift_card->used_at) {
        $response_data['used_at_formatted'] = date('d/m/Y à H:i', strtotime($gift_card->used_at));
    }
    
    // Déterminer le message de statut
    if ($gift_card->status === 'used') {
        $response_data['message'] = 'Cette carte cadeau a déjà été utilisée.';
    } elseif ($is_expired) {
        $response_data['message'] = 'Cette carte cadeau a expiré.';
    } elseif (!$is_active) {
        $response_data['message'] = 'Cette carte cadeau n\'est pas encore active.';
    } else {
        $response_data['message'] = 'Carte cadeau valide et utilisable !';
    }
    
    wp_send_json_success($response_data);
}

add_action('wp_ajax_validate_gift_card_code', 'newsaiige_validate_gift_card_code_public');
add_action('wp_ajax_nopriv_validate_gift_card_code', 'newsaiige_validate_gift_card_code_public');

/**
 * Shortcode simplifié pour afficher juste le lien vers la validation
 */
function newsaiige_gift_card_check_link_shortcode($atts) {
    $atts = shortcode_atts(array(
        'text' => 'Vérifier ma carte cadeau',
        'class' => 'gift-card-check-link'
    ), $atts);
    
    return '<a href="#" onclick="jQuery(\'html, body\').animate({scrollTop: jQuery(\'.newsaiige-validator\').offset().top - 100}, 800); return false;" class="' . esc_attr($atts['class']) . '">' . esc_html($atts['text']) . '</a>';
}

add_shortcode('newsaiige_gift_card_check_link', 'newsaiige_gift_card_check_link_shortcode');

?>