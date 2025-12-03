<?php
/**
 * NewSaiige Gift Cards System
 * Système de cartes cadeaux avec paiement et envoi par email
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fonction principale du shortcode pour les cartes cadeaux
 */
function newsaiige_gift_cards_shortcode($atts) {
    
    // Enqueue les scripts nécessaires
    wp_enqueue_script('jquery');
    
    ob_start();
    ?>

    <style>
    

    .gift-cards-container {
        font-family: 'Montserrat', sans-serif;
        max-width: 800px;
        margin: 0 auto;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(15px) saturate(180%);
        -webkit-backdrop-filter: blur(15px) saturate(180%);
        border-radius: 30px;
        padding: 60px 40px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .gift-cards-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: #82897F;
        margin: 0 0 20px 0;
        text-transform: uppercase;
        letter-spacing: 2px;
    }

    .gift-cards-subtitle {
        font-size: 1.2rem;
        color: #666;
        font-weight: 400;
        line-height: 1.6;
    }

    .gift-card-form {
        display: grid;
        gap: 30px;
    }

    .form-section {
        display: grid;
        gap: 25px;
        align-items: start;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group.full-width {
        grid-column: 1 / -1;
    }

    .form-label {
        font-weight: 600;
        color: #333;
        margin-bottom: 10px;
        font-size: 14px;
    }

    .form-input,
    .form-textarea,
    .form-select {
        padding: 15px 20px !important;
        border: 2px solid #e9ecef !important;
        border-radius: 30px !important;
        font-family: 'Montserrat', sans-serif;
        font-size: 11px;
        transition: all 0.3s ease;
        background: white;
        box-sizing: border-box;
    }

    .form-input:focus,
    .form-textarea:focus,
    .form-select:focus {
        outline: none;
        border-color: #82897F;
        box-shadow: 0 0 0 3px rgba(130, 137, 127, 0.1);
    }

    .form-textarea {
        min-height: 120px;
        resize: vertical;
    }

    /* Section montant personnalisé */
    .amount-section {
        background: #f8f9fa;
        border-radius: 20px;
        padding: 5px;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
    }

    .amount-section.focused {
        border-color: #82897F;
        background: white;
    }

    .amount-input-container {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .currency-symbol {
        font-size: 14px;
        font-weight: 700;
        color: #82897F;
    }

    .amount-input {
        flex: 1;
        font-size: 14px;
        font-weight: 600;
        text-align: center;
        border: none !important;
        background: transparent;
        color: #82897F;
    }

    .amount-input:focus {
        outline: none;
        box-shadow: none;
        border: none;
    }

    .quantity-controls {
        display: flex;
        align-items: center;
        gap: 15px;
        justify-content: center;
    }

    .quantity-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: 2px solid #82897F;
        background: white;
        color: #82897F;
        font-size: 1.2rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .quantity-btn:hover {
        background: #82897F;
        color: white;
        transform: scale(1.1);
    }

    .quantity-display {
        font-size: 14px;
        font-weight: 600;
        color: #82897F;
        min-width: 40px;
        text-align: center;
    }

    /* Section destinataire */
    .recipient-section {
        background: #f8f9fa;
        border-radius: 20px;
        padding: 30px;
        border: 2px solid #e9ecef;
    }

    .recipient-options {
        display: flex;
        gap: 20px;
        margin-bottom: 25px;
    }

    .recipient-option {
        flex: 1;
        padding: 15px;
        border: 2px solid #e9ecef;
        border-radius: 15px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: white;
        font-size: 14px;
    }

    .recipient-option.active {
        border-color: #82897F;
        background: #82897F;
        color: white;
    }

    .recipient-option:hover {
        border-color: #82897F;
    }

    .recipient-fields {
        display: none;
        animation: fadeIn 0.5s ease;
    }

    .recipient-fields.active {
        display: block;
    }

    /* Section livraison */
    .delivery-section {
        background: #f8f9fa;
        border-radius: 20px;
        padding: 30px;
        border: 2px solid #e9ecef;
    }

    .delivery-options {
        display: flex;
        gap: 20px;
        margin-bottom: 25px;
    }

    .delivery-option {
        flex: 1;
        padding: 15px;
        border: 2px solid #e9ecef;
        border-radius: 15px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: white;
        font-weight: 400;
        font-size: 14px;
    }

    .delivery-option.active {
        border-color: #82897F;
        background: #82897F;
        color: white;
    }

    .delivery-option:hover {
        border-color: #82897F;
    }

    .delivery-fields {
        display: none;
        animation: fadeIn 0.5s ease;
        margin-top: 20px;
    }

    .delivery-fields.active {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }

    .delivery-info {
        background: #e8f4f8;
        border: 2px solid #bee5eb;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
        color: #0c5460;
        font-size: 0.9rem;
    }

    .delivery-note {
        display: block;
        margin-top: 8px;
        color: #666;
        font-size: 0.85rem;
        font-style: italic;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Date de livraison */
    .delivery-date {
        position: relative;
    }

    .date-input {
        position: relative;
    }

    /* Bouton de soumission */
    .submit-section {
        text-align: center;
        margin-top: 40px;
    }

    .gift-card-submit {
        background: linear-gradient(45deg, #82897F, #9EA49D);
        color: white;
        padding: 9px 50px;
        border: none;
        border-radius: 50px;
        font-family: 'Montserrat', sans-serif;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        letter-spacing: 1px;
        position: relative;
        overflow: hidden;
    }

    .gift-card-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(130, 137, 127, 0.4);
    }

    .gift-card-submit:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
    }

    .submit-text {
        transition: all 0.3s ease;
    }

    .loading-spinner {
        display: none;
        width: 20px;
        height: 20px;
        border: 2px solid transparent;
        border-top: 2px solid white;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Messages de statut */
    .status-message {
        padding: 15px 20px;
        border-radius: 15px;
        margin: 20px 0;
        font-weight: 600;
        text-align: center;
        display: none;
    }

    .status-message.success {
        background: #d4edda;
        border: 2px solid #c3e6cb;
        color: #155724;
    }

    .status-message.error {
        background: #f8d7da;
        border: 2px solid #f5c6cb;
        color: #721c24;
    }

    .status-message.info {
        background: #d1ecf1;
        border: 2px solid #bee5eb;
        color: #0c5460;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .gift-cards-container {
            padding: 40px 30px;
            margin: 0 20px;
        }

        .form-section {
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .gift-cards-title {
            font-size: 2rem;
        }

        .recipient-options,
        .delivery-options {
            flex-direction: column;
            gap: 15px;
        }

        .amount-input-container {
            flex-direction: column;
            gap: 10px;
        }
    }

    @media (max-width: 480px) {
        .gift-cards-container {
            padding: 30px 20px;
            margin: 0 15px;
        }

        .gift-cards-title {
            font-size: 1.8rem;
        }

        .quantity-controls {
            gap: 10px;
        }

        .delivery-option,
        .recipient-option {
            padding: 12px;
            font-size: 0.9rem;
        }
    }
    </style>


    <div class="gift-cards-container">
        <div class="status-message" id="statusMessage"></div>

        <form class="gift-card-form" id="giftCardForm">
            <!-- Section Montant -->
            <div class="form-section">
                <div class="form-group full-width">
                    <label class="form-label">Saisissez le montant*</label>
                    <div class="amount-section" id="amountSection">
                        <div class="amount-input-container">
                            <span class="currency-symbol">€</span>
                            <input type="number" 
                                    name="amount" 
                                    id="amountInput"
                                    class="amount-input" 
                                    placeholder="0" 
                                    min="10" 
                                    max="1000" 
                                    step="1" 
                                    required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Quantité -->
            <div class="form-section">
                <div class="form-group full-width">
                    <label class="form-label">Quantité *</label>
                    <div class="quantity-controls">
                        <button type="button" class="quantity-btn" id="decreaseQty">-</button>
                        <span class="quantity-display" id="quantityDisplay">1</span>
                        <button type="button" class="quantity-btn" id="increaseQty">+</button>
                        <input type="hidden" name="quantity" id="quantityInput" value="1">
                    </div>
                </div>
            </div>

            <!-- Section Destinataire -->
            <div class="form-section">
                <div class="form-group full-width">
                    <label class="form-label">Pour qui est la carte cadeau ?</label>
                    <div class="recipient-section">
                        <div class="recipient-options">
                            <div class="recipient-option active" data-type="other">
                                Pour quelqu'un d'autre
                            </div>
                            <div class="recipient-option" data-type="self">
                                Pour moi-même
                            </div>
                        </div>
                        
                        <div class="recipient-fields active" id="recipientOther">
                            <div class="form-section">
                                <div class="form-group">
                                    <label class="form-label">E-mail du destinataire qui recevra l'e-carte cadeau*</label>
                                    <input type="email" 
                                            name="recipient_email" 
                                            class="form-input" 
                                            placeholder="destinataire@email.com"
                                            id="recipientEmail">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Nom et prénom du destinataire de l'e-carte cadeau</label>
                                    <input type="text" 
                                            name="recipient_name" 
                                            class="form-input" 
                                            placeholder="Nom Prénom"
                                            id="recipientName">
                                </div>
                            </div>
                        </div>

                        <div class="recipient-fields" id="recipientSelf">
                            <div class="form-group">
                                <label class="form-label">Votre e-mail pour recevoir la carte cadeau*</label>
                                <input type="email" 
                                        name="self_email" 
                                        class="form-input" 
                                        placeholder="votre@email.com"
                                        id="selfEmail">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Type de livraison -->
            <div class="form-section">
                <div class="form-group full-width">
                    <label class="form-label">Mode de livraison</label>
                    <div class="delivery-section">
                        <div class="delivery-options">
                            <div class="delivery-option active" data-type="digital">
                                📧 Livraison par email
                            </div>
                            <div class="delivery-option" data-type="physical">
                                📮 Livraison physique (2€50 de frais de port)
                            </div>
                        </div>
                        
                        <!-- Champs pour livraison physique -->
                        <div class="delivery-fields" id="physicalDelivery">
                            <!-- <div class="delivery-info">
                                <p><strong>Livraison physique :</strong> Votre carte cadeau sera imprimée et envoyée par courrier postal (+5€)</p>
                            </div> -->
                            <div class="form-section">
                                <div class="form-group">
                                    <label class="form-label">Adresse de livraison*</label>
                                    <input type="text" 
                                            name="delivery_address" 
                                            class="form-input" 
                                            placeholder="Numéro et nom de rue"
                                            id="deliveryAddress">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Complément d'adresse</label>
                                    <input type="text" 
                                            name="delivery_address_2" 
                                            class="form-input" 
                                            placeholder="Bâtiment, étage, boîte..."
                                            id="deliveryAddress2">
                                </div>
                            </div>
                            <div class="form-section">
                                <div class="form-group">
                                    <label class="form-label">Code postal*</label>
                                    <input type="text" 
                                            name="delivery_postal_code" 
                                            class="form-input" 
                                            placeholder="Code postal"
                                            id="deliveryPostalCode"
                                            pattern="[0-9]{5}"
                                            maxlength="5">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Ville*</label>
                                    <input type="text" 
                                            name="delivery_city" 
                                            class="form-input" 
                                            placeholder="Ville"
                                            id="deliveryCity">
                                </div>
                            </div>
                            <div class="form-section">
                                <div class="form-group">
                                    <label class="form-label">Pays</label>
                                    <select name="delivery_country" class="form-select" id="deliveryCountry">
                                        <option value="FR">France</option>
                                        <option value="BE">Belgique</option>
                                        <option value="CH">Suisse</option>
                                        <option value="LU">Luxembourg</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Téléphone (optionnel)</label>
                                    <input type="tel" 
                                            name="delivery_phone" 
                                            class="form-input" 
                                            placeholder="Téléphone de contact"
                                            id="deliveryPhone">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Date de livraison -->
            <div class="form-section">
                <div class="form-group full-width">
                    <label class="form-label">Choisissez la date d'expédition</label>
                    <input type="date" 
                            name="delivery_date" 
                            class="form-input" 
                            id="deliveryDate"
                            min="<?php echo date('Y-m-d'); ?>">
                    <small class="delivery-note" id="deliveryNote">Livraison par email instantanée ou programmée</small>
                </div>
            </div>

            <!-- Section Message personnalisé -->
            <div class="form-section">
                <div class="form-group full-width">
                    <label class="form-label">Un petit mot d'amour</label>
                    <textarea name="personal_message" 
                                class="form-textarea" 
                                placeholder="Écrivez votre message personnalisé ici..."
                                id="personalMessage"></textarea>
                </div>
            </div>

            <!-- Informations de l'acheteur -->
            <div class="form-section">
                <div class="form-group">
                    <label class="form-label">Votre nom*</label>
                    <input type="text" 
                            name="buyer_name" 
                            class="form-input" 
                            placeholder="Votre nom complet"
                            required
                            id="buyerName">
                </div>
                <div class="form-group">
                    <label class="form-label">Votre e-mail*</label>
                    <input type="email" 
                            name="buyer_email" 
                            class="form-input" 
                            placeholder="votre@email.com"
                            required
                            id="buyerEmail">
                </div>
            </div>

            <!-- Bouton de soumission -->
            <div class="submit-section">
                <button type="submit" class="gift-card-submit" id="submitBtn">
                    <span class="submit-text">Acheter</span>
                    <div class="loading-spinner" id="loadingSpinner"></div>
                </button>
            </div>

            <!-- Champs cachés -->
            <input type="hidden" name="action" value="process_gift_card">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('newsaiige_gift_card_nonce'); ?>">
            <input type="hidden" name="recipient_type" id="recipientType" value="other">
            <input type="hidden" name="delivery_type" id="deliveryType" value="digital">
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configuration AJAX
        const newsaiige_gift_ajax = {
            ajax_url: "<?php echo admin_url('admin-ajax.php'); ?>",
            nonce: "<?php echo wp_create_nonce('newsaiige_gift_card_nonce'); ?>"
        };
        
        // Éléments du formulaire
        const form = document.getElementById('giftCardForm');
        const amountInput = document.getElementById('amountInput');
        const amountSection = document.getElementById('amountSection');
        const quantityDisplay = document.getElementById('quantityDisplay');
        const quantityInput = document.getElementById('quantityInput');
        const decreaseBtn = document.getElementById('decreaseQty');
        const increaseBtn = document.getElementById('increaseQty');
        const recipientOptions = document.querySelectorAll('.recipient-option');
        const recipientFields = document.querySelectorAll('.recipient-fields');
        const recipientType = document.getElementById('recipientType');
        const deliveryOptions = document.querySelectorAll('.delivery-option');
        const deliveryFields = document.getElementById('physicalDelivery');
        const deliveryNote = document.getElementById('deliveryNote');
        const submitBtn = document.getElementById('submitBtn');
        const statusMessage = document.getElementById('statusMessage');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const submitText = document.querySelector('.submit-text');

        // Variable pour le type de livraison
        let deliveryType = 'digital';

        // Gestion du focus sur le montant
        amountInput.addEventListener('focus', function() {
            amountSection.classList.add('focused');
        });

        amountInput.addEventListener('blur', function() {
            amountSection.classList.remove('focused');
        });

        // Gestion de la quantité
        decreaseBtn.addEventListener('click', function() {
            let current = parseInt(quantityDisplay.textContent);
            if (current > 1) {
                current--;
                quantityDisplay.textContent = current;
                quantityInput.value = current;
                updateTotalPrice();
            }
        });

        increaseBtn.addEventListener('click', function() {
            let current = parseInt(quantityDisplay.textContent);
            if (current < 10) {
                current++;
                quantityDisplay.textContent = current;
                quantityInput.value = current;
                updateTotalPrice();
            }
        });

        // Mettre à jour le prix quand le montant change
        amountInput.addEventListener('input', updateTotalPrice);

        // Fonction pour mettre à jour le prix total affiché
        function updateTotalPrice() {
            const amount = parseFloat(amountInput.value) || 0;
            const quantity = parseInt(quantityInput.value) || 1;
            const shippingCost = deliveryType === 'physical' ? 2.50 : 0;
            const total = (amount * quantity) + shippingCost;
            
            // Mettre à jour le texte du bouton avec le prix total
            if (amount > 0) {
                if (shippingCost > 0) {
                    submitText.textContent = `Acheter ${total.toFixed(2)}€ (${(amount * quantity).toFixed(2)}€ + ${shippingCost.toFixed(2)}€ port)`;
                } else {
                    submitText.textContent = `Acheter ${total.toFixed(2)}€`;
                }
            } else {
                submitText.textContent = 'Acheter';
            }
        }

        // Gestion des options de destinataire
        recipientOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Retirer la classe active de toutes les options
                recipientOptions.forEach(opt => opt.classList.remove('active'));
                recipientFields.forEach(field => field.classList.remove('active'));
                
                // Activer l'option sélectionnée
                this.classList.add('active');
                const type = this.getAttribute('data-type');
                recipientType.value = type;
                
                // Afficher les champs correspondants
                if (type === 'other') {
                    document.getElementById('recipientOther').classList.add('active');
                    document.getElementById('recipientEmail').required = true;
                    document.getElementById('selfEmail').required = false;
                } else {
                    document.getElementById('recipientSelf').classList.add('active');
                    document.getElementById('selfEmail').required = true;
                    document.getElementById('recipientEmail').required = false;
                }
            });
        });

        // Gestion des options de livraison
        deliveryOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Retirer la classe active de toutes les options
                deliveryOptions.forEach(opt => opt.classList.remove('active'));
                
                // Activer l'option sélectionnée
                this.classList.add('active');
                deliveryType = this.getAttribute('data-type');
                
                // Afficher/masquer les champs de livraison physique
                if (deliveryType === 'physical') {
                    deliveryFields.classList.add('active');
                    deliveryNote.textContent = 'Livraison physique sous 3-5 jours ouvrés (+2,50€)';
                    
                    // Rendre les champs de livraison obligatoires
                    document.getElementById('deliveryAddress').required = true;
                    document.getElementById('deliveryPostalCode').required = true;
                    document.getElementById('deliveryCity').required = true;
                    
                    // Mettre à jour la date minimale (3 jours pour livraison physique)
                    const minDate = new Date();
                    minDate.setDate(minDate.getDate() + 3);
                    document.getElementById('deliveryDate').min = minDate.toISOString().split('T')[0];
                    document.getElementById('deliveryDate').value = minDate.toISOString().split('T')[0];
                } else {
                    deliveryFields.classList.remove('active');
                    deliveryNote.textContent = 'Livraison par email instantanée ou programmée';
                    
                    // Rendre les champs de livraison non obligatoires
                    document.getElementById('deliveryAddress').required = false;
                    document.getElementById('deliveryPostalCode').required = false;
                    document.getElementById('deliveryCity').required = false;
                    
                    // Remettre la date minimale à aujourd'hui
                    const today = new Date().toISOString().split('T')[0];
                    document.getElementById('deliveryDate').min = today;
                    document.getElementById('deliveryDate').value = today;
                }
                
                // Mettre à jour le prix total
                updateTotalPrice();
            });
        });

        // Définir la date minimale à aujourd'hui
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('deliveryDate').min = today;
        document.getElementById('deliveryDate').value = today;

        // Gestion de la soumission du formulaire
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validation
            if (!validateForm()) {
                return;
            }
            
            // Mettre à jour le type de livraison dans le champ caché
            document.getElementById('deliveryType').value = deliveryType;
            
            // Afficher le spinner de chargement
            submitBtn.disabled = true;
            submitText.style.display = 'none';
            loadingSpinner.style.display = 'block';
            
            // Préparer les données
            const formData = new FormData(form);
            
            // Envoyer la requête AJAX
            fetch(newsaiige_gift_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('success', 'Redirection vers le paiement...');
                    // Rediriger vers la page de paiement WooCommerce
                    if (data.data.redirect_url) {
                        window.location.href = data.data.redirect_url;
                    }
                } else {
                    showMessage('error', data.data || 'Une erreur est survenue');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showMessage('error', 'Une erreur réseau est survenue');
            })
            .finally(() => {
                // Remettre le bouton en état normal
                submitBtn.disabled = false;
                submitText.style.display = 'block';
                loadingSpinner.style.display = 'none';
            });
        });

        function validateForm() {
            const amount = parseFloat(amountInput.value);
            
            if (!amount || amount < 10) {
                showMessage('error', 'Le montant minimum est de 10€');
                amountInput.focus();
                return false;
            }
            
            if (amount > 1000) {
                showMessage('error', 'Le montant maximum est de 1000€');
                amountInput.focus();
                return false;
            }
            
            // Validation pour la livraison physique
            if (deliveryType === 'physical') {
                const address = document.getElementById('deliveryAddress').value.trim();
                const postalCode = document.getElementById('deliveryPostalCode').value.trim();
                const city = document.getElementById('deliveryCity').value.trim();
                
                if (!address) {
                    showMessage('error', 'L\'adresse de livraison est obligatoire');
                    document.getElementById('deliveryAddress').focus();
                    return false;
                }
                
                if (!postalCode || !/^[0-9]{5}$/.test(postalCode)) {
                    showMessage('error', 'Le code postal doit contenir 5 chiffres');
                    document.getElementById('deliveryPostalCode').focus();
                    return false;
                }
                
                if (!city) {
                    showMessage('error', 'La ville est obligatoire');
                    document.getElementById('deliveryCity').focus();
                    return false;
                }
            }
            
            return true;
        }

        function showMessage(type, message) {
            statusMessage.className = `status-message ${type}`;
            statusMessage.textContent = message;
            statusMessage.style.display = 'block';
            
            // Faire défiler vers le message
            statusMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            
            // Masquer après 5 secondes pour les messages de succès
            if (type === 'success') {
                setTimeout(() => {
                    statusMessage.style.display = 'none';
                }, 5000);
            }
        }

        // Initialisation
        updateTotalPrice();
    });
    </script>

    <?php
    return ob_get_clean();
}

// Enregistrer le shortcode
add_shortcode('newsaiige_gift_cards', 'newsaiige_gift_cards_shortcode');

/**
 * Création de la table pour les cartes cadeaux lors de l'activation
 */
function newsaiige_create_gift_cards_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'newsaiige_gift_cards';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        code varchar(20) NOT NULL UNIQUE,
        amount decimal(10,2) NOT NULL,
        quantity int(5) NOT NULL DEFAULT 1,
        total_amount decimal(10,2) NOT NULL,
        shipping_cost decimal(10,2) NOT NULL DEFAULT 0,
        buyer_name varchar(255) NOT NULL,
        buyer_email varchar(255) NOT NULL,
        recipient_type enum('self','other') NOT NULL DEFAULT 'other',
        recipient_name varchar(255),
        recipient_email varchar(255),
        personal_message text,
        delivery_date date,
        delivery_type enum('digital','physical') NOT NULL DEFAULT 'digital',
        delivery_address varchar(255),
        delivery_address_2 varchar(255),
        delivery_postal_code varchar(10),
        delivery_city varchar(100),
        delivery_country varchar(2) DEFAULT 'FR',
        delivery_phone varchar(20),
        status enum('pending','paid','sent','shipped','used','expired') NOT NULL DEFAULT 'pending',
        order_id int(11),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        sent_at datetime,
        shipped_at datetime,
        used_at datetime,
        expires_at datetime,
        PRIMARY KEY (id),
        KEY code (code),
        KEY status (status),
        KEY order_id (order_id),
        KEY delivery_type (delivery_type)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Hook pour créer la table
register_activation_hook(__FILE__, 'newsaiige_create_gift_cards_table');

/**
 * Générer un code unique pour la carte cadeau
 */
function newsaiige_generate_gift_card_code() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsaiige_gift_cards';
    
    do {
        // Générer un code de 12 caractères (format: NSGG-XXXX-XXXX)
        $code = 'NSGG-' . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4)) . 
                '-' . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4));
        
        // Vérifier que le code n'existe pas déjà (CORRECTION: utiliser 'code' au lieu de 'gift_card_code')
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE code = %s",
            $code
        ));
    } while ($exists > 0);
    
    return $code;
}

/**
 * Traitement AJAX pour créer une carte cadeau
 */
function newsaiige_process_gift_card() {
    // Vérification de sécurité
    if (!wp_verify_nonce($_POST['nonce'], 'newsaiige_gift_card_nonce')) {
        wp_send_json_error('Erreur de sécurité');
        return;
    }
    
    // Validation des données
    $amount = floatval($_POST['amount']);
    $quantity = intval($_POST['quantity']);
    
    if ($amount < 10 || $amount > 1000) {
        wp_send_json_error('Montant invalide (entre 10€ et 1000€)');
        return;
    }
    
    if ($quantity < 1 || $quantity > 10) {
        wp_send_json_error('Quantité invalide (entre 1 et 10)');
        return;
    }
    
    // Calculer le montant total avec frais de port
    $shipping_cost = ($_POST['delivery_type'] === 'physical') ? 2.50 : 0.00;
    $total_amount = ($amount * $quantity) + $shipping_cost;
    
    // Préparer les données
    $gift_card_data = array(
        'amount' => $amount,
        'quantity' => $quantity,
        'total_amount' => $total_amount,
        'shipping_cost' => $shipping_cost,
        'buyer_name' => sanitize_text_field($_POST['buyer_name']),
        'buyer_email' => sanitize_email($_POST['buyer_email']),
        'recipient_type' => sanitize_text_field($_POST['recipient_type']),
        'recipient_name' => sanitize_text_field($_POST['recipient_name'] ?? ''),
        'recipient_email' => sanitize_email($_POST['recipient_email'] ?? $_POST['self_email'] ?? ''),
        'personal_message' => sanitize_textarea_field($_POST['personal_message'] ?? ''),
        'delivery_date' => sanitize_text_field($_POST['delivery_date'] ?? date('Y-m-d')),
        'delivery_type' => sanitize_text_field($_POST['delivery_type'] ?? 'digital'),
        'delivery_address' => sanitize_text_field($_POST['delivery_address'] ?? ''),
        'delivery_address_2' => sanitize_text_field($_POST['delivery_address_2'] ?? ''),
        'delivery_postal_code' => sanitize_text_field($_POST['delivery_postal_code'] ?? ''),
        'delivery_city' => sanitize_text_field($_POST['delivery_city'] ?? ''),
        'delivery_country' => sanitize_text_field($_POST['delivery_country'] ?? 'FR'),
        'delivery_phone' => sanitize_text_field($_POST['delivery_phone'] ?? ''),
    );
    
    // Créer un produit WooCommerce temporaire pour le paiement
    $product_id = newsaiige_create_gift_card_product($gift_card_data);
    
    if (!$product_id) {
        wp_send_json_error('Erreur lors de la création du produit');
        return;
    }
    
    // Sauvegarder temporairement les données dans la session
    if (!session_id()) {
        session_start();
    }
    $_SESSION['newsaiige_gift_card_data'] = $gift_card_data;
    $_SESSION['newsaiige_gift_card_product_id'] = $product_id;
    
    // Créer l'URL de checkout avec le produit
    $checkout_url = wc_get_checkout_url() . '?add-to-cart=' . $product_id;
    
    wp_send_json_success(array(
        'redirect_url' => $checkout_url,
        'product_id' => $product_id
    ));
}

add_action('wp_ajax_process_gift_card', 'newsaiige_process_gift_card');
add_action('wp_ajax_nopriv_process_gift_card', 'newsaiige_process_gift_card');

/**
 * Créer un produit WooCommerce pour la carte cadeau
 */
function newsaiige_create_gift_card_product($gift_card_data) {
    if (!class_exists('WooCommerce')) {
        return false;
    }
    
    $product = new WC_Product_Simple();
    
    $product_name = 'Carte Cadeau NewSaiige - ' . $gift_card_data['amount'] . '€';
    if ($gift_card_data['delivery_type'] === 'physical') {
        $product_name .= ' (Livraison physique)';
    }
    
    $product->set_name($product_name);
    $product->set_description('Carte cadeau NewSaiige d\'une valeur de ' . $gift_card_data['amount'] . '€');
    $product->set_short_description('Carte cadeau NewSaiige');
    $product->set_price($gift_card_data['total_amount']);
    $product->set_regular_price($gift_card_data['total_amount']);
    $product->set_virtual($gift_card_data['delivery_type'] === 'digital');
    $product->set_downloadable(false);
    $product->set_manage_stock(false);
    $product->set_stock_status('instock');
    $product->set_catalog_visibility('hidden');
    
    // Métadonnées pour identifier le produit comme carte cadeau
    $product->add_meta_data('_newsaiige_gift_card', 'yes');
    $product->add_meta_data('_newsaiige_gift_card_data', $gift_card_data);
    
    $product_id = $product->save();
    
    return $product_id;
}

/**
 * Hook après paiement réussi pour traiter la carte cadeau
 * IMPORTANT: S'exécute UNIQUEMENT quand la commande est PAYÉE et COMPLÉTÉE
 */
function newsaiige_process_gift_card_after_payment($order_id) {
    error_log("newsaiige_process_gift_card_after_payment: Démarrage pour commande #$order_id");
    
    $order = wc_get_order($order_id);
    
    if (!$order) {
        error_log("newsaiige_process_gift_card_after_payment: Commande #$order_id introuvable");
        return;
    }
    
    // Vérifier que la commande est bien payée
    if (!$order->is_paid()) {
        error_log("newsaiige_process_gift_card_after_payment: Commande #$order_id PAS ENCORE PAYÉE - Abandon");
        return;
    }
    
    error_log("newsaiige_process_gift_card_after_payment: Commande #$order_id trouvée et payée - Statut: " . $order->get_status());
    
    $items_count = count($order->get_items());
    error_log("newsaiige_process_gift_card_after_payment: Commande #$order_id contient $items_count item(s)");
    
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        
        if (!$product) {
            error_log("newsaiige_process_gift_card_after_payment: Item sans produit trouvé");
            continue;
        }
        
        $is_gift_card = $product->get_meta('_newsaiige_gift_card');
        error_log("newsaiige_process_gift_card_after_payment: Produit '" . $product->get_name() . "' - Meta _newsaiige_gift_card: " . var_export($is_gift_card, true));
        
        if ($is_gift_card === 'yes') {
            error_log("newsaiige_process_gift_card_after_payment: ✓ C'est une carte cadeau!");
            $gift_card_data = $product->get_meta('_newsaiige_gift_card_data');
            
            if ($gift_card_data) {
                error_log("newsaiige_process_gift_card_after_payment: Données carte cadeau trouvées - Quantité: " . $gift_card_data['quantity']);
                
                // Créer les cartes cadeaux dans la base de données
                for ($i = 0; $i < $gift_card_data['quantity']; $i++) {
                    error_log("newsaiige_process_gift_card_after_payment: Création carte #" . ($i + 1));
                    $card_code = newsaiige_create_gift_card_record($gift_card_data, $order_id);
                    
                    if ($card_code) {
                        error_log("newsaiige_process_gift_card_after_payment: ✓✓✓ Carte créée avec succès - Code: $card_code");
                        
                        // Si c'est une livraison physique, notifier l'admin
                        if ($gift_card_data['delivery_type'] === 'physical') {
                            error_log("newsaiige_process_gift_card_after_payment: Envoi notification admin pour livraison physique");
                            newsaiige_notify_admin_physical_delivery($gift_card_data, $card_code, $order_id);
                        }
                    } else {
                        error_log("newsaiige_process_gift_card_after_payment: ✗✗✗ ÉCHEC création carte #" . ($i + 1));
                    }
                }
                
                error_log("newsaiige_process_gift_card_after_payment: Suppression produit temporaire ID: " . $product->get_id());
                // Supprimer le produit temporaire
                wp_delete_post($product->get_id(), true);
                
                // Programmer l'envoi des emails
                error_log("newsaiige_process_gift_card_after_payment: Programmation envoi emails");
                newsaiige_schedule_gift_card_emails($order_id, $gift_card_data);
            } else {
                error_log("newsaiige_process_gift_card_after_payment: ✗ Pas de données gift_card_data trouvées");
            }
        }
    }
    
    error_log("newsaiige_process_gift_card_after_payment: Fin du traitement pour commande #$order_id");
}

// CORRECTION: Utiliser 'payment_complete' qui s'exécute APRÈS confirmation du paiement
add_action('woocommerce_payment_complete', 'newsaiige_process_gift_card_after_payment', 10, 1);
// Hook de secours pour les paiements manuels (virement, chèque)
add_action('woocommerce_order_status_completed', 'newsaiige_process_gift_card_after_payment', 10, 1);

/**
 * Créer un enregistrement de carte cadeau en base
 */
function newsaiige_create_gift_card_record($gift_card_data, $order_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsaiige_gift_cards';
    
    error_log("newsaiige_create_gift_card_record: Génération code pour commande #$order_id");
    $code = newsaiige_generate_gift_card_code();
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 year'));
    
    error_log("newsaiige_create_gift_card_record: Code généré: $code - Expire: $expires_at");
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'code' => $code, // CORRECTION: 'code' au lieu de 'gift_card_code'
            'amount' => $gift_card_data['amount'],
            'quantity' => 1, // Chaque enregistrement représente une carte
            'total_amount' => $gift_card_data['amount'],
            'shipping_cost' => $gift_card_data['shipping_cost'],
            'buyer_name' => $gift_card_data['buyer_name'],
            'buyer_email' => $gift_card_data['buyer_email'],
            'recipient_type' => $gift_card_data['recipient_type'],
            'recipient_name' => $gift_card_data['recipient_name'],
            'recipient_email' => $gift_card_data['recipient_email'],
            'personal_message' => $gift_card_data['personal_message'],
            'delivery_date' => $gift_card_data['delivery_date'],
            'delivery_type' => $gift_card_data['delivery_type'],
            'delivery_address' => $gift_card_data['delivery_address'],
            'delivery_address_2' => $gift_card_data['delivery_address_2'],
            'delivery_postal_code' => $gift_card_data['delivery_postal_code'],
            'delivery_city' => $gift_card_data['delivery_city'],
            'delivery_country' => $gift_card_data['delivery_country'],
            'delivery_phone' => $gift_card_data['delivery_phone'],
            'status' => 'paid',
            'order_id' => $order_id,
            'expires_at' => $expires_at
        ),
        array('%s', '%f', '%d', '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
    );
    
    if ($result) {
        $insert_id = $wpdb->insert_id;
        error_log("newsaiige_create_gift_card_record: ✓ Carte créée avec succès - ID: $insert_id - Code: $code");
        return $code;
    } else {
        error_log("newsaiige_create_gift_card_record: ✗ ERREUR SQL: " . $wpdb->last_error);
        return false;
    }
}

/**
 * Programmer l'envoi des emails de cartes cadeaux
 */
function newsaiige_schedule_gift_card_emails($order_id, $gift_card_data) {
    $delivery_date = $gift_card_data['delivery_date'];
    $delivery_timestamp = strtotime($delivery_date . ' 09:00:00');
    
    // Si la date de livraison est aujourd'hui ou dans le passé, envoyer immédiatement
    if ($delivery_timestamp <= time()) {
        newsaiige_send_gift_card_emails($order_id);
    } else {
        // Programmer l'envoi pour la date spécifiée
        wp_schedule_single_event($delivery_timestamp, 'newsaiige_send_gift_card_emails_hook', array($order_id));
    }
}

/**
 * Hook pour l'envoi programmé des emails
 */
add_action('newsaiige_send_gift_card_emails_hook', 'newsaiige_send_gift_card_emails');

/**
 * Envoyer les emails de cartes cadeaux
 */
function newsaiige_send_gift_card_emails($order_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsaiige_gift_cards';
    
    // Récupérer toutes les cartes cadeaux pour cette commande
    $gift_cards = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE order_id = %d AND status = 'paid'",
        $order_id
    ));
    
    foreach ($gift_cards as $gift_card) {
        if (newsaiige_send_gift_card_email($gift_card)) {
            // Marquer comme envoyé
            $wpdb->update(
                $table_name,
                array('status' => 'sent', 'sent_at' => current_time('mysql')),
                array('id' => $gift_card->id),
                array('%s', '%s'),
                array('%d')
            );
        }
    }
}

/**
 * Envoyer un email de carte cadeau individuel
 */
function newsaiige_send_gift_card_email($gift_card) {
    $to = $gift_card->recipient_email;
    $subject = 'Votre carte cadeau NewSaiige est arrivée ! 🎁';
    
    // Template HTML pour l'email
    $message = newsaiige_get_gift_card_email_template($gift_card);
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: NewSaiige <noreply@newsaiige.com>'
    );
    
    return wp_mail($to, $subject, $message, $headers);
}

/**
 * Template HTML pour l'email de carte cadeau
 */
function newsaiige_get_gift_card_email_template($gift_card) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; background: #f8f9fa; }
            .container { max-width: 600px; margin: 0 auto; background: white; }
            .header { background: linear-gradient(135deg, #82897F, #9EA49D); padding: 40px; text-align: center; }
            .header h1 { color: white; margin: 0; font-size: 28px; }
            .content { padding: 40px; }
            .gift-card { background: linear-gradient(135deg, #82897F, #9EA49D); color: white; padding: 30px; border-radius: 15px; margin: 20px 0; text-align: center; }
            .code { font-size: 24px; font-weight: bold; letter-spacing: 3px; background: rgba(255,255,255,0.2); padding: 15px; border-radius: 10px; margin: 15px 0; }
            .amount { font-size: 36px; font-weight: bold; }
            .message { background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0; font-style: italic; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🎁 Votre Carte Cadeau NewSaiige</h1>
            </div>
            
            <div class="content">
                <p>Bonjour <?php echo esc_html($gift_card->recipient_name ?: 'cher(e) client(e)'); ?>,</p>
                
                <p>Vous avez reçu une magnifique carte cadeau NewSaiige de la part de <strong><?php echo esc_html($gift_card->buyer_name); ?></strong> !</p>
                
                <div class="gift-card">
                    <div class="amount"><?php echo number_format($gift_card->amount, 0, ',', ''); ?>€</div>
                    <p>Code de votre carte cadeau :</p>
                    <div class="code"><?php echo esc_html($gift_card->code); ?></div>
                    <p>Valable jusqu'au <?php echo date('d/m/Y', strtotime($gift_card->expires_at)); ?></p>
                </div>
                
                <?php if ($gift_card->personal_message): ?>
                <div class="message">
                    <strong>Message personnel :</strong><br>
                    "<?php echo nl2br(esc_html($gift_card->personal_message)); ?>"
                </div>
                <?php endif; ?>
                
                <h3>Comment utiliser votre carte cadeau :</h3>
                <ol>
                    <li>Présentez ce code lors de votre rendez-vous chez NewSaiige</li>
                    <li>Ou contactez-nous pour réserver : <a href="tel:+33123456789">01 23 45 67 89</a></li>
                    <li>Le montant sera déduit automatiquement de votre facture</li>
                </ol>
                
                <p><strong>Adresse :</strong><br>
                NewSaiige<br>
                [Votre adresse]<br>
                [Code postal] [Ville]</p>
                
                <p>Nous avons hâte de vous accueillir pour un moment d'exception !</p>
            </div>
            
            <div class="footer">
                <p>© <?php echo date('Y'); ?> NewSaiige - Tous droits réservés</p>
                <p>Cette carte cadeau est valable 1 an à partir de la date d'émission</p>
            </div>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

/**
 * Notifier l'admin d'une demande de livraison physique
 */
function newsaiige_notify_admin_physical_delivery($gift_card_data, $card_code, $order_id) {
    $admin_email = get_option('admin_email');
    $subject = '🚚 Nouvelle demande de livraison physique - Carte Cadeau NewSaiige';
    
    // Template HTML pour l'email admin
    $message = newsaiige_get_admin_physical_delivery_template($gift_card_data, $card_code, $order_id);
    
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: NewSaiige <noreply@newsaiige.com>'
    );
    
    return wp_mail($admin_email, $subject, $message, $headers);
}

/**
 * Template HTML pour l'email admin de livraison physique
 */
function newsaiige_get_admin_physical_delivery_template($gift_card_data, $card_code, $order_id) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; background: #f8f9fa; }
            .container { max-width: 600px; margin: 0 auto; background: white; }
            .header { background: linear-gradient(135deg, #82897F, #9EA49D); padding: 30px; text-align: center; }
            .header h1 { color: white; margin: 0; font-size: 24px; }
            .content { padding: 30px; }
            .alert { background: #fff3cd; border: 2px solid #ffeaa7; border-radius: 10px; padding: 20px; margin: 20px 0; }
            .card-info { background: #e8f4f8; border: 2px solid #bee5eb; border-radius: 10px; padding: 20px; margin: 20px 0; }
            .delivery-info { background: #f8d7da; border: 2px solid #f5c6cb; border-radius: 10px; padding: 20px; margin: 20px 0; }
            .code { font-size: 18px; font-weight: bold; letter-spacing: 2px; background: #82897F; color: white; padding: 10px; border-radius: 5px; display: inline-block; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; }
            table { width: 100%; border-collapse: collapse; }
            th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
            th { background: #f8f9fa; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🚚 Nouvelle Livraison Physique</h1>
            </div>
            
            <div class="content">
                <div class="alert">
                    <h3>⚠️ Action Requise</h3>
                    <p>Une nouvelle commande de carte cadeau avec livraison physique a été passée et nécessite votre attention.</p>
                </div>
                
                <div class="card-info">
                    <h3>📄 Informations de la Carte Cadeau</h3>
                    <table>
                        <tr><th>Code</th><td><span class="code"><?php echo esc_html($card_code); ?></span></td></tr>
                        <tr><th>Montant</th><td><?php echo number_format($gift_card_data['amount'], 0, ',', ''); ?>€</td></tr>
                        <tr><th>Quantité</th><td><?php echo $gift_card_data['quantity']; ?></td></tr>
                        <tr><th>Frais de port</th><td><?php echo number_format($gift_card_data['shipping_cost'], 2, ',', ''); ?>€</td></tr>
                        <tr><th>Total</th><td><strong><?php echo number_format($gift_card_data['total_amount'], 2, ',', ''); ?>€</strong></td></tr>
                        <tr><th>Commande WC</th><td>#<?php echo $order_id; ?></td></tr>
                        <tr><th>Date de livraison</th><td><?php echo date('d/m/Y', strtotime($gift_card_data['delivery_date'])); ?></td></tr>
                    </table>
                </div>
                
                <div class="delivery-info">
                    <h3>📮 Adresse de Livraison</h3>
                    <table>
                        <tr><th>Destinataire</th><td><?php echo esc_html($gift_card_data['recipient_name'] ?: $gift_card_data['buyer_name']); ?></td></tr>
                        <tr><th>Adresse</th><td><?php echo esc_html($gift_card_data['delivery_address']); ?></td></tr>
                        <?php if ($gift_card_data['delivery_address_2']): ?>
                        <tr><th>Complément</th><td><?php echo esc_html($gift_card_data['delivery_address_2']); ?></td></tr>
                        <?php endif; ?>
                        <tr><th>Code postal</th><td><?php echo esc_html($gift_card_data['delivery_postal_code']); ?></td></tr>
                        <tr><th>Ville</th><td><?php echo esc_html($gift_card_data['delivery_city']); ?></td></tr>
                        <tr><th>Pays</th><td><?php echo esc_html($gift_card_data['delivery_country']); ?></td></tr>
                        <?php if ($gift_card_data['delivery_phone']): ?>
                        <tr><th>Téléphone</th><td><?php echo esc_html($gift_card_data['delivery_phone']); ?></td></tr>
                        <?php endif; ?>
                    </table>
                </div>
                
                <div class="card-info">
                    <h3>👤 Informations Client</h3>
                    <table>
                        <tr><th>Acheteur</th><td><?php echo esc_html($gift_card_data['buyer_name']); ?></td></tr>
                        <tr><th>Email acheteur</th><td><?php echo esc_html($gift_card_data['buyer_email']); ?></td></tr>
                        <tr><th>Email destinataire</th><td><?php echo esc_html($gift_card_data['recipient_email']); ?></td></tr>
                    </table>
                    
                    <?php if ($gift_card_data['personal_message']): ?>
                    <h4>💌 Message Personnel</h4>
                    <p style="font-style: italic; background: #f8f9fa; padding: 15px; border-radius: 5px;">
                        "<?php echo nl2br(esc_html($gift_card_data['personal_message'])); ?>"
                    </p>
                    <?php endif; ?>
                </div>
                
                <div class="alert">
                    <h3>📋 Étapes à Suivre</h3>
                    <ol>
                        <li><strong>Imprimer la carte cadeau</strong> avec le code <span class="code"><?php echo esc_html($card_code); ?></span></li>
                        <li><strong>Préparer l'envoi postal</strong> pour la date du <?php echo date('d/m/Y', strtotime($gift_card_data['delivery_date'])); ?></li>
                        <li><strong>Marquer comme "Expédiée"</strong> dans l'interface d'administration</li>
                        <li><strong>Contacter le client</strong> si nécessaire</li>
                    </ol>
                </div>
                
                <p style="text-align: center;">
                    <a href="<?php echo admin_url('admin.php?page=newsaiige-gift-cards'); ?>" 
                       style="background: #82897F; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
                        🔧 Gérer dans l'Admin
                    </a>
                </p>
            </div>
            
            <div class="footer">
                <p>© <?php echo date('Y'); ?> NewSaiige - Administration</p>
                <p>Email automatique - Ne pas répondre</p>
            </div>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

// Initialisation lors du chargement de WordPress
add_action('init', function() {
    // Créer la table si elle n'existe pas
    newsaiige_create_gift_cards_table();
});

?>