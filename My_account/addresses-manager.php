<?php
function newsaiige_addresses_shortcode($atts) {
    $atts = shortcode_atts(array(
        'title' => 'Mes adresses',
        'subtitle' => 'Gérez vos adresses de livraison et de facturation pour faciliter vos commandes.'
    ), $atts);
    
    // Vérifier si l'utilisateur est connecté
    if (!is_user_logged_in()) {
        return '<p>Vous devez être connecté pour accéder à cette page. <a href="' . wp_login_url() . '">Se connecter</a></p>';
    }
    
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // Récupérer les adresses de l'utilisateur depuis wp_usermeta (système WordPress natif)
    $addresses = array();
    
    // Récupérer l'adresse de facturation
    $billing_address = array(
        'id' => 'billing',
        'address_type' => 'billing',
        'title' => get_user_meta($user_id, 'billing_address_title', true) ?: '',
        'first_name' => get_user_meta($user_id, 'billing_first_name', true) ?: '',
        'last_name' => get_user_meta($user_id, 'billing_last_name', true) ?: '',
        'company' => get_user_meta($user_id, 'billing_company', true) ?: '',
        'address_1' => get_user_meta($user_id, 'billing_address_1', true) ?: '',
        'address_2' => get_user_meta($user_id, 'billing_address_2', true) ?: '',
        'city' => get_user_meta($user_id, 'billing_city', true) ?: '',
        'postcode' => get_user_meta($user_id, 'billing_postcode', true) ?: '',
        'state' => get_user_meta($user_id, 'billing_state', true) ?: '',
        'country' => get_user_meta($user_id, 'billing_country', true) ?: '',
        'phone' => get_user_meta($user_id, 'billing_phone', true) ?: '',
        'is_default_billing' => 1,
        'is_default_shipping' => 0,
        'created_at' => get_user_meta($user_id, 'billing_address_created', true) ?: current_time('mysql')
    );
    
    // Récupérer l'adresse de livraison
    $shipping_address = array(
        'id' => 'shipping',
        'address_type' => 'shipping',
        'title' => get_user_meta($user_id, 'shipping_address_title', true) ?: '',
        'first_name' => get_user_meta($user_id, 'shipping_first_name', true) ?: '',
        'last_name' => get_user_meta($user_id, 'shipping_last_name', true) ?: '',
        'company' => get_user_meta($user_id, 'shipping_company', true) ?: '',
        'address_1' => get_user_meta($user_id, 'shipping_address_1', true) ?: '',
        'address_2' => get_user_meta($user_id, 'shipping_address_2', true) ?: '',
        'city' => get_user_meta($user_id, 'shipping_city', true) ?: '',
        'postcode' => get_user_meta($user_id, 'shipping_postcode', true) ?: '',
        'state' => get_user_meta($user_id, 'shipping_state', true) ?: '',
        'country' => get_user_meta($user_id, 'shipping_country', true) ?: '',
        'phone' => get_user_meta($user_id, 'shipping_phone', true) ?: '',
        'is_default_billing' => 0,
        'is_default_shipping' => 1,
        'created_at' => get_user_meta($user_id, 'shipping_address_created', true) ?: current_time('mysql')
    );
    
    // Ajouter les adresses au tableau seulement si elles contiennent des données
    if (!empty($billing_address['first_name']) || !empty($billing_address['address_1'])) {
        $addresses[] = (object) $billing_address;
    }
    if (!empty($shipping_address['first_name']) || !empty($shipping_address['address_1'])) {
        $addresses[] = (object) $shipping_address;
    }
    
    // Récupérer les adresses supplémentaires (système étendu)
    $additional_addresses = get_user_meta($user_id, 'additional_addresses', true);
    if (is_array($additional_addresses)) {
        foreach ($additional_addresses as $key => $addr) {
            $addr['id'] = 'additional_' . $key;
            $addr['is_default_billing'] = 0;
            $addr['is_default_shipping'] = 0;
            $addresses[] = (object) $addr;
        }
    }
    
    // Enqueue les scripts nécessaires
    wp_enqueue_script('newsaiige-addresses-js', '', array('jquery'), '1.0', true);
    wp_add_inline_script('newsaiige-addresses-js', '
        const newsaiige_addresses_ajax = {
            ajax_url: "' . admin_url('admin-ajax.php') . '",
            nonce: "' . wp_create_nonce('newsaiige_addresses_nonce') . '"
        };
    ');
    
    ob_start();
    ?>

    <style>
    .newsaiige-addresses-section {
        margin: 0 auto;
        padding: 60px 20px;
        font-family: 'Montserrat', sans-serif;
        border-radius: 20px;
        margin-top: 40px;
        margin-bottom: 40px;
    }

    .addresses-header {
        text-align: left;
        margin-bottom: 50px;
    }

    .addresses-title {
        font-size: 24px;
        font-weight: 700;
        color: #82897F;
        margin: 0 0 15px 0;
        letter-spacing: 1px;
    }

    .addresses-subtitle {
        font-size: 16px;
        color: #000;
        margin: 0;
        font-weight: 400;
        line-height: 1.5;
    }

    .addresses-container {
        background: white;
        border-radius: 15px;
        padding: 40px;
        margin-bottom: 30px;
    }

    .addresses-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .address-card {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: 2px solid #e9ecef;
        border-radius: 15px;
        padding: 25px;
        position: relative;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        min-height: 220px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .address-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        border-color: #82897F;
    }

    .address-card.billing {
        border-left: 4px solid #82897F;
        background: linear-gradient(135deg, rgba(130, 137, 127, 0.1) 0%, rgba(130, 137, 127, 0.05) 100%);
    }

    .address-card.shipping {
        border-left: 4px solid #6c757d;
        background: linear-gradient(135deg, rgba(108, 117, 125, 0.1) 0%, rgba(108, 117, 125, 0.05) 100%);
    }

    .address-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
    }

    .address-type {
        font-size: 0.9rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 4px 12px;
        border-radius: 15px;
        color: white;
    }

    .address-type.billing {
        background: #82897F;
    }

    .address-type.shipping {
        background: #6c757d;
    }

    .default-badges {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .default-badge {
        background: rgba(130, 137, 127, 0.2);
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #82897F;
    }

    .address-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 10px;
    }

    .address-content {
        flex-grow: 1;
        margin-bottom: 15px;
    }

    .address-name {
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
        font-size: 1rem;
    }

    .address-company {
        font-style: italic;
        color: #666;
        margin-bottom: 8px;
        font-size: 0.9rem;
    }

    .address-line {
        color: #555;
        margin-bottom: 3px;
        font-size: 0.9rem;
    }

    .address-location {
        font-weight: 500;
        color: #333;
        margin-top: 8px;
    }

    .address-phone {
        color: #82897F;
        font-weight: 500;
        margin-top: 5px;
        font-size: 0.9rem;
    }

    .address-actions {
        position: absolute;
        top: 15px;
        right: 15px;
        display: flex;
        gap: 5px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .address-card:hover .address-actions {
        opacity: 1;
    }

    .address-action-btn {
        background: rgba(130, 137, 127, 0.8);
        border: none;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        color: white;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
    }

    .address-action-btn:hover {
        background: #82897F;
        transform: scale(1.1);
    }

    .add-address-btn {
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

    .add-address-btn:hover {
        background: transparent;
        color: #82897F !important;
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(130, 137, 127, 0.3);
    }

    .no-addresses {
        text-align: center;
        padding: 60px 20px;
        color: #000;
    }

    .no-addresses h3 {
        margin-bottom: 15px;
        font-weight: 700;
        font-size: 24px;
        color: #000;
    }

    .no-addresses p {
        font-size: 16px;
        font-weight: 400;
        color: #000;
        margin-bottom: 20px;
    }

    .no-addresses-icon {
        font-size: 3rem;
        color: #000;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    /* MODALE - Style identique aux autres */
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
        max-width: 600px;
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

    .form-select {
        width: 100%;
        padding: 15px;
        border: 2px solid #e9ecef;
        border-radius: 15px;
        font-family: 'Montserrat', sans-serif;
        font-size: 1rem;
        transition: border-color 0.3s ease;
        box-sizing: border-box;
        background: white;
    }

    .form-select:focus {
        outline: none;
        border-color: #82897F;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    .address-type-selector {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 20px;
    }

    .address-type-option {
        padding: 15px;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 600;
        font-size: 1rem;
    }

    .address-type-option:hover,
    .address-type-option.selected {
        border-color: #82897F;
        background: rgba(130, 137, 127, 0.1);
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 15px 0;
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

    /* Responsive */
    @media (max-width: 768px) {
        .newsaiige-addresses-section {
            padding: 40px 15px;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .addresses-container {
            padding: 20px;
        }

        .addresses-grid {
            grid-template-columns: 1fr;
        }

        .addresses-title {
            font-size: 20px;
        }

        .addresses-subtitle {
            font-size: 14px;
        }

        .modal-content {
            padding: 40px 30px;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .address-type-selector {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 480px) {
        .modal-content {
            padding: 30px 20px;
        }

        .addresses-container {
            padding: 15px;
        }

        .address-card {
            padding: 20px;
            min-height: 200px;
        }
    }
    </style>

    <div class="newsaiige-addresses-section">
        <div class="addresses-header">
            <h2 class="addresses-title"><?php echo esc_html($atts['title']); ?></h2>
            <p class="addresses-subtitle"><?php echo esc_html($atts['subtitle']); ?></p>
        </div>

        <div class="addresses-container">
            <?php if (!empty($addresses)): ?>
                <div class="addresses-grid">
                    <?php foreach ($addresses as $address): ?>
                        <div class="address-card <?php echo esc_attr($address->address_type); ?>" data-address-id="<?php echo esc_attr($address->id); ?>">
                            <div class="address-header">
                                <span class="address-type <?php echo esc_attr($address->address_type); ?>">
                                    <?php echo $address->address_type === 'billing' ? 'Facturation' : 'Livraison'; ?>
                                </span>
                                <div class="default-badges">
                                    <?php if ($address->is_default_billing): ?>
                                        <span class="default-badge">Fact. par défaut</span>
                                    <?php endif; ?>
                                    <?php if ($address->is_default_shipping): ?>
                                        <span class="default-badge">Livr. par défaut</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($address->title)): ?>
                                <div class="address-title"><?php echo esc_html($address->title); ?></div>
                            <?php endif; ?>
                            
                            <div class="address-content">
                                <div class="address-name">
                                    <?php echo esc_html($address->first_name . ' ' . $address->last_name); ?>
                                </div>
                                
                                <?php if (!empty($address->company)): ?>
                                    <div class="address-company"><?php echo esc_html($address->company); ?></div>
                                <?php endif; ?>
                                
                                <div class="address-line"><?php echo esc_html($address->address_1); ?></div>
                                <?php if (!empty($address->address_2)): ?>
                                    <div class="address-line"><?php echo esc_html($address->address_2); ?></div>
                                <?php endif; ?>
                                
                                <div class="address-location">
                                    <?php echo esc_html($address->postcode . ' ' . $address->city); ?>
                                    <?php if (!empty($address->state)): ?>
                                        <br><?php echo esc_html($address->state); ?>
                                    <?php endif; ?>
                                    <?php if (!empty($address->country)): ?>
                                        <br><?php echo esc_html($address->country); ?>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($address->phone)): ?>
                                    <div class="address-phone">📞 <?php echo esc_html($address->phone); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="address-actions">
                                <button class="address-action-btn edit-btn" title="Modifier" 
                                        onclick="editAddress('<?php echo esc_attr($address->id); ?>')">
                                    ✏️
                                </button>
                                <?php if (!$address->is_default_billing && !$address->is_default_shipping): ?>
                                    <button class="address-action-btn set-default-btn" title="Définir par défaut" 
                                            onclick="setDefaultAddress('<?php echo esc_attr($address->id); ?>')">
                                        ⭐
                                    </button>
                                <?php endif; ?>
                                <button class="address-action-btn delete-btn" title="Supprimer" 
                                        onclick="deleteAddress('<?php echo esc_attr($address->id); ?>')">
                                    🗑️
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-addresses">
                    <div class="no-addresses-icon">🏠</div>
                    <h3>Aucune adresse enregistrée</h3>
                    <p>Ajoutez une adresse pour faciliter vos commandes et livraisons.</p>
                </div>
            <?php endif; ?>
            
            <div style="text-align: center;">
                <button class="add-address-btn" onclick="openAddressModal()">
                    + Ajouter une adresse
                </button>
            </div>
        </div>
    </div>

    <!-- MODALE - Structure identique aux autres -->
    <div class="modal-overlay" id="addressModal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeAddressModal()">×</span>
            <h3 class="modal-title" id="modalTitle">Ajouter une adresse</h3>
            
            <form id="addressForm">
                <input type="hidden" id="address_id" name="address_id" value="">
                
                <div class="form-group">
                    <label class="form-label">Type d'adresse</label>
                    <div class="address-type-selector">
                        <div class="address-type-option" data-type="billing">
                            📄 Facturation
                        </div>
                        <div class="address-type-option" data-type="shipping">
                            📦 Livraison
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address_title" class="form-label">Titre de l'adresse (optionnel)</label>
                    <input type="text" id="address_title" name="title" class="form-input" 
                           placeholder="Ex: Maison, Bureau, Parents...">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name" class="form-label">Prénom *</label>
                        <input type="text" id="first_name" name="first_name" class="form-input" 
                               placeholder="Prénom" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name" class="form-label">Nom *</label>
                        <input type="text" id="last_name" name="last_name" class="form-input" 
                               placeholder="Nom de famille" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="company" class="form-label">Entreprise</label>
                    <input type="text" id="company" name="company" class="form-input" 
                           placeholder="Nom de l'entreprise (optionnel)">
                </div>
                
                <div class="form-group">
                    <label for="address_1" class="form-label">Adresse *</label>
                    <input type="text" id="address_1" name="address_1" class="form-input" 
                           placeholder="Numéro et nom de rue" required>
                </div>
                
                <div class="form-group">
                    <label for="address_2" class="form-label">Complément d'adresse</label>
                    <input type="text" id="address_2" name="address_2" class="form-input" 
                           placeholder="Appartement, étage, bâtiment...">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="postcode" class="form-label">Code postal *</label>
                        <input type="text" id="postcode" name="postcode" class="form-input" 
                               placeholder="Code postal" required>
                    </div>
                    <div class="form-group">
                        <label for="city" class="form-label">Ville *</label>
                        <input type="text" id="city" name="city" class="form-input" 
                               placeholder="Ville" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="state" class="form-label">Région/État</label>
                        <input type="text" id="state" name="state" class="form-input" 
                               placeholder="Région ou État">
                    </div>
                    <div class="form-group">
                        <label for="country" class="form-label">Pays *</label>
                        <select id="country" name="country" class="form-select" required>
                            <option value="">Sélectionner un pays</option>
                            <option value="FR">France</option>
                            <option value="BE">Belgique</option>
                            <option value="CH">Suisse</option>
                            <option value="CA">Canada</option>
                            <option value="DE">Allemagne</option>
                            <option value="ES">Espagne</option>
                            <option value="IT">Italie</option>
                            <option value="GB">Royaume-Uni</option>
                            <option value="US">États-Unis</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="phone" class="form-label">Téléphone</label>
                    <input type="tel" id="phone" name="phone" class="form-input" 
                           placeholder="Numéro de téléphone">
                </div>
                
                <div class="checkbox-group" id="billingDefaultGroup">
                    <input type="checkbox" id="is_default_billing" name="is_default_billing" class="checkbox-input">
                    <label for="is_default_billing" class="form-label">Adresse de facturation par défaut</label>
                </div>
                
                <div class="checkbox-group" id="shippingDefaultGroup">
                    <input type="checkbox" id="is_default_shipping" name="is_default_shipping" class="checkbox-input">
                    <label for="is_default_shipping" class="form-label">Adresse de livraison par défaut</label>
                </div>
                
                <button type="submit" class="submit-btn" id="submitBtn">Ajouter l'adresse</button>
            </form>
        </div>
    </div>

    <script>
    // Variables globales
    let selectedAddressType = '';
    let editingAddressId = null;
    let addresses = <?php echo json_encode($addresses); ?>;

    // Fonctions modal - identiques aux autres
    function openAddressModal(addressId = null) {
        editingAddressId = addressId;
        
        if (addressId) {
            // Mode modification
            const address = addresses.find(a => a.id === addressId);
            if (address) {
                fillFormWithAddress(address);
                document.getElementById('modalTitle').textContent = 'Modifier l\'adresse';
                document.getElementById('submitBtn').textContent = 'Modifier l\'adresse';
            }
        } else {
            // Mode ajout
            document.getElementById('addressForm').reset();
            selectedAddressType = '';
            updateAddressTypeSelection();
            document.getElementById('modalTitle').textContent = 'Ajouter une adresse';
            document.getElementById('submitBtn').textContent = 'Ajouter l\'adresse';
        }
        
        document.getElementById('addressModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeAddressModal() {
        document.getElementById('addressModal').style.display = 'none';
        document.body.style.overflow = 'auto';
        document.getElementById('addressForm').reset();
        selectedAddressType = '';
        editingAddressId = null;
        updateAddressTypeSelection();
    }

    function editAddress(addressId) {
        openAddressModal(addressId);
    }

    function fillFormWithAddress(address) {
        document.getElementById('address_id').value = address.id;
        document.getElementById('address_title').value = address.title || '';
        document.getElementById('first_name').value = address.first_name || '';
        document.getElementById('last_name').value = address.last_name || '';
        document.getElementById('company').value = address.company || '';
        document.getElementById('address_1').value = address.address_1 || '';
        document.getElementById('address_2').value = address.address_2 || '';
        document.getElementById('postcode').value = address.postcode || '';
        document.getElementById('city').value = address.city || '';
        document.getElementById('state').value = address.state || '';
        document.getElementById('country').value = address.country || '';
        document.getElementById('phone').value = address.phone || '';
        document.getElementById('is_default_billing').checked = address.is_default_billing == 1;
        document.getElementById('is_default_shipping').checked = address.is_default_shipping == 1;
        
        selectedAddressType = address.address_type;
        updateAddressTypeSelection();
        updateDefaultCheckboxes();
    }

    // Gestion du type d'adresse
    function initializeAddressTypeSelector() {
        const addressTypeOptions = document.querySelectorAll('.address-type-option');
        
        addressTypeOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Remove selection from all options
                addressTypeOptions.forEach(opt => opt.classList.remove('selected'));
                
                // Add selection to clicked option
                this.classList.add('selected');
                selectedAddressType = this.getAttribute('data-type');
                updateDefaultCheckboxes();
            });
        });
    }

    function updateAddressTypeSelection() {
        const addressTypeOptions = document.querySelectorAll('.address-type-option');
        addressTypeOptions.forEach(opt => {
            if (opt.getAttribute('data-type') === selectedAddressType) {
                opt.classList.add('selected');
            } else {
                opt.classList.remove('selected');
            }
        });
    }

    function updateDefaultCheckboxes() {
        const billingGroup = document.getElementById('billingDefaultGroup');
        const shippingGroup = document.getElementById('shippingDefaultGroup');
        
        if (selectedAddressType === 'billing') {
            billingGroup.style.display = 'flex';
            shippingGroup.style.display = 'none';
            document.getElementById('is_default_shipping').checked = false;
        } else if (selectedAddressType === 'shipping') {
            billingGroup.style.display = 'none';
            shippingGroup.style.display = 'flex';
            document.getElementById('is_default_billing').checked = false;
        } else {
            billingGroup.style.display = 'flex';
            shippingGroup.style.display = 'flex';
        }
    }

    // Formatage des champs
    function initializeFieldFormatting() {
        const postcodeInput = document.getElementById('postcode');
        const phoneInput = document.getElementById('phone');

        // Formatage code postal français
        postcodeInput.addEventListener('input', function() {
            let value = this.value.replace(/[^0-9]/g, '');
            if (value.length > 5) {
                value = value.substring(0, 5);
            }
            this.value = value;
        });

        // Formatage téléphone français
        phoneInput.addEventListener('input', function() {
            let value = this.value.replace(/[^0-9+\s\-\(\)]/g, '');
            this.value = value;
        });
    }

    // Soumission du formulaire
    function initializeAddressForm() {
        const form = document.getElementById('addressForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (!selectedAddressType) {
                    alert('Veuillez sélectionner le type d\'adresse.');
                    return;
                }

                // Validation des champs obligatoires
                const firstName = this.querySelector('input[name="first_name"]').value.trim();
                const lastName = this.querySelector('input[name="last_name"]').value.trim();
                const address1 = this.querySelector('input[name="address_1"]').value.trim();
                const postcode = this.querySelector('input[name="postcode"]').value.trim();
                const city = this.querySelector('input[name="city"]').value.trim();
                const country = this.querySelector('select[name="country"]').value;

                if (!firstName || !lastName || !address1 || !postcode || !city || !country) {
                    alert('Veuillez remplir tous les champs obligatoires.');
                    return;
                }

                // Préparer les données pour l'AJAX
                const formData = new FormData(this);
                formData.append('address_type', selectedAddressType);
                formData.append('nonce', newsaiige_addresses_ajax.nonce);
                
                if (editingAddressId) {
                    formData.append('action', 'update_address');
                    formData.append('address_id', editingAddressId);
                } else {
                    formData.append('action', 'add_address');
                }

                // Envoi AJAX
                fetch(newsaiige_addresses_ajax.ajax_url, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(editingAddressId ? 'Adresse modifiée avec succès !' : 'Adresse ajoutée avec succès !');
                        closeAddressModal();
                        location.reload();
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

    // Fonctions de gestion des adresses
    function setDefaultAddress(addressId) {
        if (confirm('Définir cette adresse comme adresse par défaut ?')) {
            const formData = new FormData();
            formData.append('action', 'set_default_address');
            formData.append('address_id', addressId);
            formData.append('nonce', newsaiige_addresses_ajax.nonce);

            fetch(newsaiige_addresses_ajax.ajax_url, {
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

    function deleteAddress(addressId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette adresse ?')) {
            const formData = new FormData();
            formData.append('action', 'delete_address');
            formData.append('address_id', addressId);
            formData.append('nonce', newsaiige_addresses_ajax.nonce);

            fetch(newsaiige_addresses_ajax.ajax_url, {
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
        const modal = document.getElementById('addressModal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeAddressModal();
                }
            });
        }
    }

    // Initialisation au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        initializeAddressTypeSelector();
        initializeFieldFormatting();
        initializeAddressForm();
        initializeModalClosing();
        updateDefaultCheckboxes();
    });
    </script>

    <?php
    return ob_get_clean();
}

add_shortcode('newsaiige_addresses', 'newsaiige_addresses_shortcode');

// Handlers AJAX pour la gestion des adresses
add_action('wp_ajax_add_address', 'newsaiige_add_address_handler');
add_action('wp_ajax_update_address', 'newsaiige_update_address_handler');
add_action('wp_ajax_set_default_address', 'newsaiige_set_default_address_handler');
add_action('wp_ajax_delete_address', 'newsaiige_delete_address_handler');

function newsaiige_add_address_handler() {
    // Vérification du nonce
    if (!wp_verify_nonce($_POST['nonce'], 'newsaiige_addresses_nonce')) {
        wp_die('Erreur de sécurité');
    }
    
    // Vérification utilisateur connecté
    if (!is_user_logged_in()) {
        wp_send_json_error('Utilisateur non connecté');
    }
    
    $user_id = get_current_user_id();
    
    // Sanitisation des données
    $address_type = sanitize_text_field($_POST['address_type']);
    $title = sanitize_text_field($_POST['title']);
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $company = sanitize_text_field($_POST['company']);
    $address_1 = sanitize_text_field($_POST['address_1']);
    $address_2 = sanitize_text_field($_POST['address_2']);
    $city = sanitize_text_field($_POST['city']);
    $postcode = sanitize_text_field($_POST['postcode']);
    $state = sanitize_text_field($_POST['state']);
    $country = sanitize_text_field($_POST['country']);
    $phone = sanitize_text_field($_POST['phone']);
    $is_default_billing = intval($_POST['is_default_billing'] ?? 0);
    $is_default_shipping = intval($_POST['is_default_shipping'] ?? 0);
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($address_1) || empty($city) || empty($postcode) || empty($country)) {
        wp_send_json_error('Tous les champs obligatoires doivent être remplis');
    }
    
    // Enregistrer l'adresse dans wp_usermeta selon le type (compatible WooCommerce)
    if ($address_type === 'billing') {
        update_user_meta($user_id, 'billing_address_title', $title);
        update_user_meta($user_id, 'billing_first_name', $first_name);
        update_user_meta($user_id, 'billing_last_name', $last_name);
        update_user_meta($user_id, 'billing_company', $company);
        update_user_meta($user_id, 'billing_address_1', $address_1);
        update_user_meta($user_id, 'billing_address_2', $address_2);
        update_user_meta($user_id, 'billing_city', $city);
        update_user_meta($user_id, 'billing_postcode', $postcode);
        update_user_meta($user_id, 'billing_state', $state);
        update_user_meta($user_id, 'billing_country', $country);
        update_user_meta($user_id, 'billing_phone', $phone);
        update_user_meta($user_id, 'billing_address_created', current_time('mysql'));
    } elseif ($address_type === 'shipping') {
        update_user_meta($user_id, 'shipping_address_title', $title);
        update_user_meta($user_id, 'shipping_first_name', $first_name);
        update_user_meta($user_id, 'shipping_last_name', $last_name);
        update_user_meta($user_id, 'shipping_company', $company);
        update_user_meta($user_id, 'shipping_address_1', $address_1);
        update_user_meta($user_id, 'shipping_address_2', $address_2);
        update_user_meta($user_id, 'shipping_city', $city);
        update_user_meta($user_id, 'shipping_postcode', $postcode);
        update_user_meta($user_id, 'shipping_state', $state);
        update_user_meta($user_id, 'shipping_country', $country);
        update_user_meta($user_id, 'shipping_phone', $phone);
        update_user_meta($user_id, 'shipping_address_created', current_time('mysql'));
    } else {
        // Pour les adresses supplémentaires, les stocker dans un array
        $additional_addresses = get_user_meta($user_id, 'additional_addresses', true) ?: array();
        $new_key = uniqid();
        $additional_addresses[$new_key] = array(
            'address_type' => $address_type,
            'title' => $title,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'company' => $company,
            'address_1' => $address_1,
            'address_2' => $address_2,
            'city' => $city,
            'postcode' => $postcode,
            'state' => $state,
            'country' => $country,
            'phone' => $phone,
            'created_at' => current_time('mysql')
        );
        update_user_meta($user_id, 'additional_addresses', $additional_addresses);
    }
    
    wp_send_json_success('Adresse ajoutée avec succès');
}

function newsaiige_update_address_handler() {
    if (!wp_verify_nonce($_POST['nonce'], 'newsaiige_addresses_nonce')) {
        wp_die('Erreur de sécurité');
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Utilisateur non connecté');
    }
    
    $user_id = get_current_user_id();
    $address_id = sanitize_text_field($_POST['address_id']);
    
    // Sanitisation des données
    $address_type = sanitize_text_field($_POST['address_type']);
    $title = sanitize_text_field($_POST['title']);
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $company = sanitize_text_field($_POST['company']);
    $address_1 = sanitize_text_field($_POST['address_1']);
    $address_2 = sanitize_text_field($_POST['address_2']);
    $city = sanitize_text_field($_POST['city']);
    $postcode = sanitize_text_field($_POST['postcode']);
    $state = sanitize_text_field($_POST['state']);
    $country = sanitize_text_field($_POST['country']);
    $phone = sanitize_text_field($_POST['phone']);
    $is_default_billing = intval($_POST['is_default_billing'] ?? 0);
    $is_default_shipping = intval($_POST['is_default_shipping'] ?? 0);
    
    // Validation des champs obligatoires
    if (empty($first_name) || empty($last_name) || empty($address_1) || empty($city) || empty($postcode) || empty($country)) {
        wp_send_json_error('Tous les champs obligatoires doivent être remplis');
    }
    
    // Mettre à jour selon le type d'adresse
    if ($address_id === 'billing' || $address_type === 'billing') {
        update_user_meta($user_id, 'billing_address_title', $title);
        update_user_meta($user_id, 'billing_first_name', $first_name);
        update_user_meta($user_id, 'billing_last_name', $last_name);
        update_user_meta($user_id, 'billing_company', $company);
        update_user_meta($user_id, 'billing_address_1', $address_1);
        update_user_meta($user_id, 'billing_address_2', $address_2);
        update_user_meta($user_id, 'billing_city', $city);
        update_user_meta($user_id, 'billing_postcode', $postcode);
        update_user_meta($user_id, 'billing_state', $state);
        update_user_meta($user_id, 'billing_country', $country);
        update_user_meta($user_id, 'billing_phone', $phone);
        
    } elseif ($address_id === 'shipping' || $address_type === 'shipping') {
        update_user_meta($user_id, 'shipping_address_title', $title);
        update_user_meta($user_id, 'shipping_first_name', $first_name);
        update_user_meta($user_id, 'shipping_last_name', $last_name);
        update_user_meta($user_id, 'shipping_company', $company);
        update_user_meta($user_id, 'shipping_address_1', $address_1);
        update_user_meta($user_id, 'shipping_address_2', $address_2);
        update_user_meta($user_id, 'shipping_city', $city);
        update_user_meta($user_id, 'shipping_postcode', $postcode);
        update_user_meta($user_id, 'shipping_state', $state);
        update_user_meta($user_id, 'shipping_country', $country);
        update_user_meta($user_id, 'shipping_phone', $phone);
        
    } elseif (strpos($address_id, 'additional_') === 0) {
        // Pour les adresses supplémentaires
        $additional_addresses = get_user_meta($user_id, 'additional_addresses', true) ?: array();
        $key = str_replace('additional_', '', $address_id);
        
        if (isset($additional_addresses[$key])) {
            $additional_addresses[$key] = array(
                'address_type' => $address_type,
                'title' => $title,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'company' => $company,
                'address_1' => $address_1,
                'address_2' => $address_2,
                'city' => $city,
                'postcode' => $postcode,
                'state' => $state,
                'country' => $country,
                'phone' => $phone,
                'created_at' => $additional_addresses[$key]['created_at'] ?? current_time('mysql')
            );
            update_user_meta($user_id, 'additional_addresses', $additional_addresses);
        } else {
            wp_send_json_error('Adresse supplémentaire non trouvée');
            return;
        }
    }
    
    wp_send_json_success('Adresse modifiée avec succès');
}

function newsaiige_set_default_address_handler() {
    if (!wp_verify_nonce($_POST['nonce'], 'newsaiige_addresses_nonce')) {
        wp_die('Erreur de sécurité');
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Utilisateur non connecté');
    }
    
    $user_id = get_current_user_id();
    $address_id = sanitize_text_field($_POST['address_id']);
    
    // Les adresses billing et shipping sont déjà par défaut par nature
    // Cette fonction est principalement pour les adresses supplémentaires
    if (strpos($address_id, 'additional_') === 0) {
        $additional_addresses = get_user_meta($user_id, 'additional_addresses', true) ?: array();
        $key = str_replace('additional_', '', $address_id);
        
        if (isset($additional_addresses[$key])) {
            $address_type = $additional_addresses[$key]['address_type'];
            
            // Copier l'adresse supplémentaire vers l'adresse principale
            if ($address_type === 'billing') {
                $addr = $additional_addresses[$key];
                update_user_meta($user_id, 'billing_address_title', $addr['title']);
                update_user_meta($user_id, 'billing_first_name', $addr['first_name']);
                update_user_meta($user_id, 'billing_last_name', $addr['last_name']);
                update_user_meta($user_id, 'billing_company', $addr['company']);
                update_user_meta($user_id, 'billing_address_1', $addr['address_1']);
                update_user_meta($user_id, 'billing_address_2', $addr['address_2']);
                update_user_meta($user_id, 'billing_city', $addr['city']);
                update_user_meta($user_id, 'billing_postcode', $addr['postcode']);
                update_user_meta($user_id, 'billing_state', $addr['state']);
                update_user_meta($user_id, 'billing_country', $addr['country']);
                update_user_meta($user_id, 'billing_phone', $addr['phone']);
            } elseif ($address_type === 'shipping') {
                $addr = $additional_addresses[$key];
                update_user_meta($user_id, 'shipping_address_title', $addr['title']);
                update_user_meta($user_id, 'shipping_first_name', $addr['first_name']);
                update_user_meta($user_id, 'shipping_last_name', $addr['last_name']);
                update_user_meta($user_id, 'shipping_company', $addr['company']);
                update_user_meta($user_id, 'shipping_address_1', $addr['address_1']);
                update_user_meta($user_id, 'shipping_address_2', $addr['address_2']);
                update_user_meta($user_id, 'shipping_city', $addr['city']);
                update_user_meta($user_id, 'shipping_postcode', $addr['postcode']);
                update_user_meta($user_id, 'shipping_state', $addr['state']);
                update_user_meta($user_id, 'shipping_country', $addr['country']);
                update_user_meta($user_id, 'shipping_phone', $addr['phone']);
            }
            
            wp_send_json_success('Adresse définie par défaut');
        } else {
            wp_send_json_error('Adresse non trouvée');
        }
    } else {
        wp_send_json_success('Les adresses principales sont déjà par défaut');
    }
}

function newsaiige_delete_address_handler() {
    if (!wp_verify_nonce($_POST['nonce'], 'newsaiige_addresses_nonce')) {
        wp_die('Erreur de sécurité');
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Utilisateur non connecté');
    }
    
    $user_id = get_current_user_id();
    $address_id = sanitize_text_field($_POST['address_id']);
    
    if ($address_id === 'billing') {
        // Vider l'adresse de facturation
        delete_user_meta($user_id, 'billing_address_title');
        delete_user_meta($user_id, 'billing_first_name');
        delete_user_meta($user_id, 'billing_last_name');
        delete_user_meta($user_id, 'billing_company');
        delete_user_meta($user_id, 'billing_address_1');
        delete_user_meta($user_id, 'billing_address_2');
        delete_user_meta($user_id, 'billing_city');
        delete_user_meta($user_id, 'billing_postcode');
        delete_user_meta($user_id, 'billing_state');
        delete_user_meta($user_id, 'billing_country');
        delete_user_meta($user_id, 'billing_phone');
        
    } elseif ($address_id === 'shipping') {
        // Vider l'adresse de livraison
        delete_user_meta($user_id, 'shipping_address_title');
        delete_user_meta($user_id, 'shipping_first_name');
        delete_user_meta($user_id, 'shipping_last_name');
        delete_user_meta($user_id, 'shipping_company');
        delete_user_meta($user_id, 'shipping_address_1');
        delete_user_meta($user_id, 'shipping_address_2');
        delete_user_meta($user_id, 'shipping_city');
        delete_user_meta($user_id, 'shipping_postcode');
        delete_user_meta($user_id, 'shipping_state');
        delete_user_meta($user_id, 'shipping_country');
        delete_user_meta($user_id, 'shipping_phone');
        
    } elseif (strpos($address_id, 'additional_') === 0) {
        // Supprimer une adresse supplémentaire
        $additional_addresses = get_user_meta($user_id, 'additional_addresses', true) ?: array();
        $key = str_replace('additional_', '', $address_id);
        
        if (isset($additional_addresses[$key])) {
            unset($additional_addresses[$key]);
            update_user_meta($user_id, 'additional_addresses', $additional_addresses);
        } else {
            wp_send_json_error('Adresse non trouvée');
            return;
        }
    }
    
    wp_send_json_success('Adresse supprimée');
}

?>