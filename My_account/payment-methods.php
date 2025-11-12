<?php
function newsaiige_payment_methods_shortcode($atts) {
    $atts = shortcode_atts(array(
        'title' => 'Mes moyens de paiement',
        'subtitle' => 'Gérez vos cartes bancaires et moyens de paiement en toute sécurité.'
    ), $atts);
    
    // Vérifier si l'utilisateur est connecté
    if (!is_user_logged_in()) {
        return '<p>Vous devez être connecté pour accéder à cette page. <a href="' . wp_login_url() . '">Se connecter</a></p>';
    }
    
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // Récupérer les moyens de paiement de l'utilisateur depuis la base de données
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsaiige_payment_methods';
    
    $payment_methods = $wpdb->get_results($wpdb->prepare(
        "SELECT id, card_type, card_last4, card_holder_name, expiry_date, is_default, created_at 
         FROM $table_name 
         WHERE user_id = %d AND status = 'active' 
         ORDER BY is_default DESC, created_at DESC",
        $user_id
    ));
    
    // Enqueue les scripts nécessaires
    wp_enqueue_script('newsaiige-payment-js', '', array('jquery'), '1.0', true);
    wp_add_inline_script('newsaiige-payment-js', '
        const newsaiige_payment_ajax = {
            ajax_url: "' . admin_url('admin-ajax.php') . '",
            nonce: "' . wp_create_nonce('newsaiige_payment_nonce') . '"
        };
    ');
    
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
        margin-bottom: 50px;
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

    .payment-cards-container {
        background: white;
        border-radius: 15px;
        padding: 40px;
        margin-bottom: 30px;
    }

    .payment-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .payment-card {
        background: linear-gradient(135deg, #82897F 0%, #6d7465 100%);
        border-radius: 15px;
        padding: 25px;
        color: white;
        position: relative;
        box-shadow: 0 10px 30px rgba(130, 137, 127, 0.3);
        transition: all 0.3s ease;
        min-height: 200px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .payment-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(130, 137, 127, 0.4);
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
    }

    .card-type {
        font-size: 1rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .card-brand {
        font-size: 1.2rem;
        font-weight: 700;
        opacity: 0.8;
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
        font-size: 1.3rem;
        font-weight: 600;
        letter-spacing: 3px;
        margin-bottom: 15px;
        font-family: 'Courier New', monospace;
    }

    .card-bottom {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
    }

    .card-holder {
        font-size: 0.9rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 1px;
        opacity: 0.9;
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
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .payment-card:hover .card-actions {
        opacity: 1;
    }

    .card-action-btn {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        color: white;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
    }

    .card-action-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.1);
    }

    .add-payment-btn {
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

    .add-payment-btn:hover {
        background: transparent;
        color: #82897F !important;
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(130, 137, 127, 0.3);
    }

    .no-payment-methods {
        text-align: center;
        padding: 60px 20px;
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

    .no-payment-icon {
        font-size: 3rem;
        color: #000;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    /* MODALE - Style identique à reviews.php */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 100000;
        backdrop-filter: blur(5px);
    }

    .modal-content {
        background: white;
        border-radius: 25px;
        padding: 50px 40px;
        max-width: 500px;
        width: 90%;
        position: relative;
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-close {
        position: absolute;
        top: 20px;
        right: 25px;
        font-size: 2rem;
        color: #666;
        cursor: pointer;
        transition: color 0.3s ease;
    }

    .modal-close:hover {
        color: #82897F;
    }

    .modal-title {
        font-size: 2rem;
        font-weight: 700;
        color: #82897F;
        text-align: center;
        margin-bottom: 30px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-label {
        display: block;
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
        font-size: 1rem;
    }

    .form-input {
        width: 100%;
        padding: 15px;
        border: 2px solid #e9ecef;
        border-radius: 15px;
        font-family: 'Montserrat', sans-serif;
        font-size: 1rem;
        transition: border-color 0.3s ease;
        box-sizing: border-box;
    }

    .form-input:focus {
        outline: none;
        border-color: #82897F;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    .card-type-selector {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
        gap: 10px;
        margin-bottom: 20px;
    }

    .card-type-option {
        padding: 15px 10px;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .card-type-option:hover,
    .card-type-option.selected {
        border-color: #82897F;
        background: rgba(130, 137, 127, 0.1);
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 20px 0;
    }

    .checkbox-input {
        width: auto !important;
        margin: 0;
    }

    .submit-btn {
        width: 100%;
        padding: 15px;
        background: #82897F;
        color: white;
        border: none;
        border-radius: 50px;
        font-family: 'Montserrat', sans-serif;
        font-weight: 600;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .submit-btn:hover {
        background: #6d7569;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(130, 137, 127, 0.3);
    }

    .security-info {
        background: rgba(130, 137, 127, 0.1);
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
        border-left: 4px solid #82897F;
    }

    .security-info h4 {
        margin: 0 0 10px 0;
        color: #82897F;
        font-weight: 600;
    }

    .security-info p {
        margin: 0;
        font-size: 0.9rem;
        color: #666;
    }

    /* Responsive */
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

        .modal-content {
            padding: 40px 30px;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .card-type-selector {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 480px) {
        .modal-content {
            padding: 30px 20px;
        }

        .payment-cards-container {
            padding: 15px;
        }

        .payment-card {
            padding: 20px;
            min-height: 180px;
        }
    }
    </style>

    <div class="newsaiige-payment-section">
        <div class="payment-header">
            <h2 class="payment-title"><?php echo esc_html($atts['title']); ?></h2>
            <p class="payment-subtitle"><?php echo esc_html($atts['subtitle']); ?></p>
        </div>

        <div class="payment-cards-container">
            <?php if (!empty($payment_methods)): ?>
                <div class="payment-cards-grid">
                    <?php foreach ($payment_methods as $method): ?>
                        <div class="payment-card" data-method-id="<?php echo esc_attr($method->id); ?>">
                            <div class="card-header">
                                <span class="card-type"><?php echo esc_html($method->card_type); ?></span>
                                <?php if ($method->is_default): ?>
                                    <span class="default-badge">Par défaut</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-number">
                                **** **** **** <?php echo esc_html($method->card_last4); ?>
                            </div>
                            
                            <div class="card-bottom">
                                <div class="card-holder">
                                    <?php echo esc_html($method->card_holder_name); ?>
                                </div>
                                <div class="card-expiry">
                                    <?php echo esc_html($method->expiry_date); ?>
                                </div>
                            </div>

                            <div class="card-actions">
                                <?php if (!$method->is_default): ?>
                                    <button class="card-action-btn set-default-btn" title="Définir par défaut" 
                                            onclick="setDefaultPaymentMethod(<?php echo esc_attr($method->id); ?>)">
                                        ⭐
                                    </button>
                                <?php endif; ?>
                                <button class="card-action-btn delete-btn" title="Supprimer" 
                                        onclick="deletePaymentMethod(<?php echo esc_attr($method->id); ?>)">
                                    🗑️
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-payment-methods">
                    <div class="no-payment-icon">💳</div>
                    <h3>Aucun moyen de paiement enregistré</h3>
                    <p>Ajoutez une carte bancaire pour faciliter vos futurs achats.</p>
                </div>
            <?php endif; ?>
            
            <div style="text-align: center;">
                <button class="add-payment-btn" onclick="openPaymentModal()">
                    + Ajouter un moyen de paiement
                </button>
            </div>
        </div>
    </div>

    <!-- MODALE - Structure identique à reviews.php -->
    <div class="modal-overlay" id="paymentModal">
        <div class="modal-content">
            <span class="modal-close" onclick="closePaymentModal()">×</span>
            <h3 class="modal-title">Ajouter un moyen de paiement</h3>
            
            <div class="security-info">
                <h4>🔒 Sécurité</h4>
                <p>Vos informations de paiement sont chiffrées et sécurisées. Nous ne stockons jamais les données complètes de votre carte.</p>
            </div>
            
            <form id="paymentForm">
                <div class="form-group">
                    <label class="form-label">Type de carte</label>
                    <div class="card-type-selector">
                        <div class="card-type-option" data-type="visa">Visa</div>
                        <div class="card-type-option" data-type="mastercard">MasterCard</div>
                        <div class="card-type-option" data-type="amex">Amex</div>
                        <div class="card-type-option" data-type="other">Autre</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="card_number" class="form-label">Numéro de carte</label>
                    <input type="text" id="card_number" name="card_number" class="form-input" 
                           placeholder="1234 5678 9012 3456" maxlength="19" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="expiry_date" class="form-label">Date d'expiration</label>
                        <input type="text" id="expiry_date" name="expiry_date" class="form-input" 
                               placeholder="MM/YY" maxlength="5" required>
                    </div>
                    <div class="form-group">
                        <label for="cvv" class="form-label">CVV/CVC</label>
                        <input type="text" id="cvv" name="cvv" class="form-input" 
                               placeholder="123" maxlength="4" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="card_holder_name" class="form-label">Nom du titulaire</label>
                    <input type="text" id="card_holder_name" name="card_holder_name" class="form-input" 
                           placeholder="Nom complet sur la carte" required>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="is_default" name="is_default" class="checkbox-input">
                    <label for="is_default" class="form-label">Définir comme moyen de paiement par défaut</label>
                </div>
                
                <button type="submit" class="submit-btn">Ajouter la carte</button>
            </form>
        </div>
    </div>

    <script>
    // Variables globales
    let selectedCardType = '';

    // Fonctions modal - identiques à reviews.php
    function openPaymentModal() {
        document.getElementById('paymentModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closePaymentModal() {
        document.getElementById('paymentModal').style.display = 'none';
        document.body.style.overflow = 'auto';
        // Reset form
        document.getElementById('paymentForm').reset();
        selectedCardType = '';
        updateCardTypeSelection();
    }

    // Gestion du type de carte
    function initializeCardTypeSelector() {
        const cardTypeOptions = document.querySelectorAll('.card-type-option');
        
        cardTypeOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Remove selection from all options
                cardTypeOptions.forEach(opt => opt.classList.remove('selected'));
                
                // Add selection to clicked option
                this.classList.add('selected');
                selectedCardType = this.getAttribute('data-type');
            });
        });
    }

    function updateCardTypeSelection() {
        const cardTypeOptions = document.querySelectorAll('.card-type-option');
        cardTypeOptions.forEach(opt => {
            if (opt.getAttribute('data-type') === selectedCardType) {
                opt.classList.add('selected');
            } else {
                opt.classList.remove('selected');
            }
        });
    }

    // Formatage des champs
    function initializeCardFormatting() {
        const cardNumberInput = document.getElementById('card_number');
        const expiryInput = document.getElementById('expiry_date');
        const cvvInput = document.getElementById('cvv');

        // Formatage numéro de carte
        cardNumberInput.addEventListener('input', function() {
            let value = this.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            if (formattedValue !== this.value) {
                this.value = formattedValue;
            }
        });

        // Formatage date d'expiration
        expiryInput.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            this.value = value;
        });

        // CVV numérique uniquement
        cvvInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Nom en majuscules
        const cardHolderInput = document.getElementById('card_holder_name');
        cardHolderInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }

    // Soumission du formulaire
    function initializePaymentForm() {
        const form = document.getElementById('paymentForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (!selectedCardType) {
                    alert('Veuillez sélectionner le type de carte.');
                    return;
                }

                // Validation des champs
                const cardNumber = this.querySelector('input[name="card_number"]').value.replace(/\s/g, '');
                const expiryDate = this.querySelector('input[name="expiry_date"]').value;
                const cvv = this.querySelector('input[name="cvv"]').value;
                const cardHolderName = this.querySelector('input[name="card_holder_name"]').value;

                if (cardNumber.length < 13 || cardNumber.length > 19) {
                    alert('Numéro de carte invalide.');
                    return;
                }

                if (!/^\d{2}\/\d{2}$/.test(expiryDate)) {
                    alert('Date d\'expiration invalide (MM/YY).');
                    return;
                }

                if (cvv.length < 3 || cvv.length > 4) {
                    alert('CVV invalide.');
                    return;
                }

                // Préparer les données pour l'AJAX
                const formData = new FormData();
                formData.append('action', 'add_payment_method');
                formData.append('card_type', selectedCardType);
                formData.append('card_number', cardNumber);
                formData.append('expiry_date', expiryDate);
                formData.append('cvv', cvv);
                formData.append('card_holder_name', cardHolderName);
                formData.append('is_default', this.querySelector('input[name="is_default"]').checked ? 1 : 0);
                formData.append('nonce', newsaiige_payment_ajax.nonce);

                // Envoi AJAX
                fetch(newsaiige_payment_ajax.ajax_url, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Moyen de paiement ajouté avec succès !');
                        closePaymentModal();
                        location.reload(); // Recharger pour afficher la nouvelle carte
                    } else {
                        alert('Erreur : ' + (data.message || 'Une erreur est survenue.'));
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur de connexion. Veuillez réessayer.');
                });
            });
        }
    }

    // Fonctions de gestion des cartes
    function setDefaultPaymentMethod(methodId) {
        if (confirm('Définir cette carte comme moyen de paiement par défaut ?')) {
            const formData = new FormData();
            formData.append('action', 'set_default_payment_method');
            formData.append('method_id', methodId);
            formData.append('nonce', newsaiige_payment_ajax.nonce);

            fetch(newsaiige_payment_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur : ' + (data.message || 'Une erreur est survenue.'));
                }
            });
        }
    }

    function deletePaymentMethod(methodId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce moyen de paiement ?')) {
            const formData = new FormData();
            formData.append('action', 'delete_payment_method');
            formData.append('method_id', methodId);
            formData.append('nonce', newsaiige_payment_ajax.nonce);

            fetch(newsaiige_payment_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur : ' + (data.message || 'Une erreur est survenue.'));
                }
            });
        }
    }

    // Fermeture modal en cliquant à l'extérieur
    function initializeModalClosing() {
        const modal = document.getElementById('paymentModal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closePaymentModal();
                }
            });
        }
    }

    // Animation des cartes
    function initializeCardAnimations() {
        const paymentCards = document.querySelectorAll('.payment-card');
        
        paymentCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px) scale(1.02)';
                this.style.transition = 'all 0.3s ease';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    }

    // Initialisation au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        initializeCardTypeSelector();
        initializeCardFormatting();
        initializePaymentForm();
        initializeModalClosing();
        initializeCardAnimations();
    });
    </script>

    <?php
    return ob_get_clean();
}

add_shortcode('newsaiige_payment_methods', 'newsaiige_payment_methods_shortcode');

// Handlers AJAX pour la gestion des moyens de paiement
add_action('wp_ajax_add_payment_method', 'newsaiige_add_payment_method_handler');
add_action('wp_ajax_set_default_payment_method', 'newsaiige_set_default_payment_method_handler');
add_action('wp_ajax_delete_payment_method', 'newsaiige_delete_payment_method_handler');

function newsaiige_add_payment_method_handler() {
    // Vérification du nonce
    if (!wp_verify_nonce($_POST['nonce'], 'newsaiige_payment_nonce')) {
        wp_die('Erreur de sécurité');
    }
    
    // Vérification utilisateur connecté
    if (!is_user_logged_in()) {
        wp_send_json_error('Utilisateur non connecté');
    }
    
    $user_id = get_current_user_id();
    
    // Sanitisation des données
    $card_type = sanitize_text_field($_POST['card_type']);
    $card_number = sanitize_text_field($_POST['card_number']);
    $expiry_date = sanitize_text_field($_POST['expiry_date']);
    $cvv = sanitize_text_field($_POST['cvv']);
    $card_holder_name = sanitize_text_field($_POST['card_holder_name']);
    $is_default = intval($_POST['is_default']);
    
    // Validation
    if (empty($card_type) || empty($card_number) || empty($expiry_date) || empty($cvv) || empty($card_holder_name)) {
        wp_send_json_error('Tous les champs sont requis');
    }
    
    // Chiffrement sécurisé des données sensibles
    $card_last4 = substr($card_number, -4);
    $encrypted_card_number = wp_hash($card_number . wp_salt()); // Hash sécurisé, ne stocke pas le numéro complet
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsaiige_payment_methods';
    
    // Si c'est la carte par défaut, retirer le statut des autres
    if ($is_default) {
        $wpdb->update(
            $table_name,
            array('is_default' => 0),
            array('user_id' => $user_id),
            array('%d'),
            array('%d')
        );
    }
    
    // Insérer la nouvelle carte
    $result = $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'card_type' => $card_type,
            'card_last4' => $card_last4,
            'card_holder_name' => $card_holder_name,
            'expiry_date' => $expiry_date,
            'encrypted_data' => $encrypted_card_number,
            'is_default' => $is_default,
            'status' => 'active',
            'created_at' => current_time('mysql')
        ),
        array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
    );
    
    if ($result) {
        wp_send_json_success('Moyen de paiement ajouté avec succès');
    } else {
        wp_send_json_error('Erreur lors de l\'ajout du moyen de paiement');
    }
}

function newsaiige_set_default_payment_method_handler() {
    if (!wp_verify_nonce($_POST['nonce'], 'newsaiige_payment_nonce')) {
        wp_die('Erreur de sécurité');
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Utilisateur non connecté');
    }
    
    $user_id = get_current_user_id();
    $method_id = intval($_POST['method_id']);
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsaiige_payment_methods';
    
    // Vérifier que la carte appartient à l'utilisateur
    $method = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
        $method_id, $user_id
    ));
    
    if (!$method) {
        wp_send_json_error('Moyen de paiement non trouvé');
    }
    
    // Retirer le statut par défaut des autres cartes
    $wpdb->update(
        $table_name,
        array('is_default' => 0),
        array('user_id' => $user_id),
        array('%d'),
        array('%d')
    );
    
    // Définir cette carte comme par défaut
    $result = $wpdb->update(
        $table_name,
        array('is_default' => 1),
        array('id' => $method_id, 'user_id' => $user_id),
        array('%d'),
        array('%d', '%d')
    );
    
    if ($result !== false) {
        wp_send_json_success('Moyen de paiement défini par défaut');
    } else {
        wp_send_json_error('Erreur lors de la mise à jour');
    }
}

function newsaiige_delete_payment_method_handler() {
    if (!wp_verify_nonce($_POST['nonce'], 'newsaiige_payment_nonce')) {
        wp_die('Erreur de sécurité');
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Utilisateur non connecté');
    }
    
    $user_id = get_current_user_id();
    $method_id = intval($_POST['method_id']);
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsaiige_payment_methods';
    
    // Vérifier que la carte appartient à l'utilisateur
    $method = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
        $method_id, $user_id
    ));
    
    if (!$method) {
        wp_send_json_error('Moyen de paiement non trouvé');
    }
    
    // Supprimer la carte (soft delete)
    $result = $wpdb->update(
        $table_name,
        array('status' => 'deleted'),
        array('id' => $method_id, 'user_id' => $user_id),
        array('%s'),
        array('%d', '%d')
    );
    
    if ($result) {
        wp_send_json_success('Moyen de paiement supprimé');
    } else {
        wp_send_json_error('Erreur lors de la suppression');
    }
}

// Création de la table lors de l'activation
function newsaiige_create_payment_methods_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'newsaiige_payment_methods';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id int(11) NOT NULL AUTO_INCREMENT,
        user_id int(11) NOT NULL,
        card_type varchar(20) NOT NULL,
        card_last4 varchar(4) NOT NULL,
        card_holder_name varchar(100) NOT NULL,
        expiry_date varchar(5) NOT NULL,
        encrypted_data text NOT NULL,
        is_default tinyint(1) DEFAULT 0,
        status varchar(20) DEFAULT 'active',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Hook d'activation (à appeler lors de l'activation du thème/plugin)
register_activation_hook(__FILE__, 'newsaiige_create_payment_methods_table');
?>