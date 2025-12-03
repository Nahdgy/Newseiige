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

    .change-subscription-btn {
        padding: 10px 20px;
        background: linear-gradient(135deg, #82897F, #9EA49D);
        color: white;
        border: none;
        border-radius: 25px;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-family: 'Montserrat', sans-serif;
    }

    .change-subscription-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(130, 137, 127, 0.4);
    }

    /* Modal */
    .subscription-modal {
        display: none;
        position: fixed;
        z-index: 10000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(5px);
        animation: fadeIn 0.3s ease;
    }

    .subscription-modal.active {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background: white;
        padding: 40px;
        border-radius: 20px;
        max-width: 600px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
        position: relative;
        animation: slideUp 0.3s ease;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes slideUp {
        from { 
            opacity: 0;
            transform: translateY(50px);
        }
        to { 
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-close {
        position: absolute;
        top: 20px;
        right: 20px;
        font-size: 28px;
        font-weight: bold;
        color: #999;
        cursor: pointer;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.3s ease;
    }

    .modal-close:hover {
        background: rgba(130, 137, 127, 0.1);
        color: #82897F;
        transform: rotate(90deg);
    }

    .modal-header {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid rgba(130, 137, 127, 0.2);
    }

    .modal-header h3 {
        margin: 0 0 10px 0;
        color: #82897F;
        font-size: 24px;
        font-weight: 700;
    }

    .modal-header p {
        margin: 0;
        color: #666;
        font-size: 14px;
    }

    .current-subscription {
        background: rgba(130, 137, 127, 0.1);
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 25px;
        border-left: 4px solid #82897F;
    }

    .current-subscription strong {
        color: #82897F;
        font-weight: 700;
    }

    .variations-list {
        display: grid;
        gap: 15px;
        margin-bottom: 25px;
    }

    .variation-option {
        border: 2px solid #e0e0e0;
        border-radius: 12px;
        padding: 20px;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .variation-option:hover {
        border-color: #82897F;
        background: rgba(130, 137, 127, 0.05);
        transform: translateX(5px);
    }

    .variation-option.selected {
        border-color: #82897F;
        background: rgba(130, 137, 127, 0.15);
        box-shadow: 0 4px 12px rgba(130, 137, 127, 0.2);
    }

    .variation-option.current {
        border-color: #4CAF50;
        background: rgba(76, 175, 80, 0.1);
    }

    .variation-option input[type="radio"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: #82897F;
    }

    .variation-details {
        flex: 1;
    }

    .variation-name {
        font-weight: 700;
        color: #333;
        font-size: 16px;
        margin-bottom: 5px;
    }

    .variation-attributes {
        color: #666;
        font-size: 13px;
        margin-bottom: 5px;
    }

    .variation-price {
        color: #82897F;
        font-weight: 700;
        font-size: 18px;
    }

    .variation-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: #4CAF50;
        color: white;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .price-difference {
        margin: 20px 0;
        padding: 15px;
        background: rgba(33, 150, 243, 0.1);
        border-left: 4px solid #2196F3;
        border-radius: 8px;
    }

    .price-difference strong {
        font-size: 16px;
        color: #1976d2;
    }

    .price-difference p {
        margin: 5px 0;
        color: #666;
        font-size: 14px;
    }

    .price-difference .impact-text {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid rgba(33, 150, 243, 0.2);
        font-weight: 600;
        color: #1976d2;
    }

    .modal-actions {
        display: flex;
        gap: 15px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 2px solid rgba(130, 137, 127, 0.2);
    }

    .modal-btn {
        flex: 1;
        padding: 15px 30px;
        border: none;
        border-radius: 50px;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-family: 'Montserrat', sans-serif;
    }

    .modal-btn-cancel {
        background: #f5f5f5;
        color: #666;
    }

    .modal-btn-cancel:hover {
        background: #e0e0e0;
    }

    .modal-btn-confirm {
        background: linear-gradient(135deg, #82897F, #9EA49D);
        color: white;
    }

    .modal-btn-confirm:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(130, 137, 127, 0.4);
    }

    .modal-btn-confirm:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }

    .loading-spinner {
        display: none;
        width: 40px;
        height: 40px;
        border: 4px solid rgba(130, 137, 127, 0.2);
        border-top: 4px solid #82897F;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 20px auto;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .modal-message {
        padding: 15px 20px;
        border-radius: 10px;
        margin: 20px 0;
        font-weight: 600;
        display: none;
    }

    .modal-message.success {
        background: rgba(76, 175, 80, 0.15);
        color: #2e7d32;
        border-left: 4px solid #4CAF50;
    }

    .modal-message.error {
        background: rgba(244, 67, 54, 0.15);
        color: #c62828;
        border-left: 4px solid #f44336;
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
                            <th>Actions</th>
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
                                        <?php echo esc_html(strip_tags($item->get_name())); ?>
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
                                <td>
                                    <?php 
                                    // Récupérer l'ID du produit parent pour les variations
                                    $parent_product_id = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
                                    $current_variation_id = $item->get_variation_id();
                                    ?>
                                    <button class="change-subscription-btn" 
                                            data-order-id="<?php echo esc_attr($order->get_id()); ?>"
                                            data-item-id="<?php echo esc_attr($item->get_id()); ?>"
                                            data-product-id="<?php echo esc_attr($parent_product_id); ?>"
                                            data-current-variation-id="<?php echo esc_attr($current_variation_id); ?>"
                                            data-product-name="<?php echo esc_attr(strip_tags($item->get_name())); ?>">
                                        Modifier l'abonnement
                                    </button>
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

    <!-- Modal de changement d'abonnement -->
    <div id="subscriptionModal" class="subscription-modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeSubscriptionModal()">&times;</span>
            
            <div class="modal-header">
                <h3>Modifier votre abonnement</h3>
                <p id="modalProductName"></p>
            </div>

            <div class="current-subscription" id="currentSubscriptionInfo"></div>

            <div id="variationsList" class="variations-list"></div>

            <div id="priceDifference" class="price-difference" style="display: none;"></div>

            <div class="loading-spinner" id="modalLoading"></div>
            <div id="modalMessage" class="modal-message"></div>

            <div class="modal-actions">
                <button type="button" class="modal-btn modal-btn-cancel" onclick="closeSubscriptionModal()">
                    Annuler
                </button>
                <button type="button" class="modal-btn modal-btn-confirm" id="confirmChangeBtn" onclick="confirmSubscriptionChange()" disabled>
                    Confirmer le changement
                </button>
            </div>
        </div>
    </div>

    <script>
    let currentModalData = {
        orderId: null,
        itemId: null,
        productId: null,
        currentVariationId: null,
        selectedVariationId: null,
        productName: ''
    };

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

        // Gestion des boutons de changement d'abonnement
        const changeSubBtns = document.querySelectorAll('.change-subscription-btn');
        changeSubBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                openSubscriptionModal(this);
            });
        });

        // Fermer le modal en cliquant en dehors
        document.getElementById('subscriptionModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeSubscriptionModal();
            }
        });
    });

    function openSubscriptionModal(button) {
        // Récupérer les données du bouton
        currentModalData.orderId = button.dataset.orderId;
        currentModalData.itemId = button.dataset.itemId;
        currentModalData.productId = button.dataset.productId;
        currentModalData.currentVariationId = button.dataset.currentVariationId;
        currentModalData.productName = button.dataset.productName;
        currentModalData.selectedVariationId = null;

        // Afficher le nom du produit
        document.getElementById('modalProductName').textContent = currentModalData.productName;

        // Afficher le modal
        document.getElementById('subscriptionModal').classList.add('active');

        // Charger les variations
        loadVariations();
    }

    function closeSubscriptionModal() {
        document.getElementById('subscriptionModal').classList.remove('active');
        resetModal();
    }

    function resetModal() {
        document.getElementById('variationsList').innerHTML = '';
        document.getElementById('priceDifference').style.display = 'none';
        document.getElementById('modalMessage').style.display = 'none';
        document.getElementById('confirmChangeBtn').disabled = true;
        currentModalData.selectedVariationId = null;
    }

    function loadVariations() {
        const loadingSpinner = document.getElementById('modalLoading');
        const variationsList = document.getElementById('variationsList');
        const currentInfo = document.getElementById('currentSubscriptionInfo');

        loadingSpinner.style.display = 'block';
        variationsList.innerHTML = '';

        // Appel AJAX pour récupérer les variations
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'get_product_variations',
                product_id: currentModalData.productId,
                current_variation_id: currentModalData.currentVariationId,
                nonce: '<?php echo wp_create_nonce('subscription_change_nonce'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            loadingSpinner.style.display = 'none';

            if (data.success && data.data.variations) {
                // Afficher l'abonnement actuel
                currentInfo.innerHTML = `<strong>Abonnement actuel :</strong> ${data.data.current_name} - ${data.data.current_price}`;

                // Afficher les variations
                data.data.variations.forEach(variation => {
                    const isCurrent = variation.variation_id == currentModalData.currentVariationId;
                    const variationHtml = `
                        <label class="variation-option ${isCurrent ? 'current' : ''}" data-variation-id="${variation.variation_id}" data-price="${variation.price_float}">
                            <input type="radio" name="variation" value="${variation.variation_id}" ${isCurrent ? 'checked disabled' : ''}>
                            <div class="variation-details">
                                <div class="variation-name">${variation.name}</div>
                                <div class="variation-attributes">${variation.attributes_text}</div>
                                <div class="variation-price">${variation.price}</div>
                            </div>
                            ${isCurrent ? '<span class="variation-badge">Actuel</span>' : ''}
                        </label>
                    `;
                    variationsList.innerHTML += variationHtml;
                });

                // Ajouter les événements de sélection
                document.querySelectorAll('.variation-option input[type="radio"]:not(:disabled)').forEach(radio => {
                    radio.addEventListener('change', function() {
                        selectVariation(this);
                    });
                });
            } else {
                showModalMessage('error', data.data || 'Erreur lors du chargement des variations');
            }
        })
        .catch(error => {
            loadingSpinner.style.display = 'none';
            showModalMessage('error', 'Erreur réseau lors du chargement des variations');
            console.error('Error:', error);
        });
    }

    function selectVariation(radio) {
        // Retirer la classe selected de toutes les options
        document.querySelectorAll('.variation-option').forEach(opt => {
            opt.classList.remove('selected');
        });

        // Ajouter la classe selected à l'option choisie
        radio.closest('.variation-option').classList.add('selected');

        currentModalData.selectedVariationId = radio.value;

        // Calculer et afficher la différence de prix
        const selectedOption = radio.closest('.variation-option');
        const newPrice = parseFloat(selectedOption.dataset.price);
        
        // Récupérer le prix actuel
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'calculate_price_difference',
                current_variation_id: currentModalData.currentVariationId,
                new_variation_id: currentModalData.selectedVariationId,
                nonce: '<?php echo wp_create_nonce('subscription_change_nonce'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayPriceDifference(data.data);
                document.getElementById('confirmChangeBtn').disabled = false;
            }
        });
    }

    function displayPriceDifference(data) {
        const priceDiv = document.getElementById('priceDifference');
        const difference = data.difference;

        priceDiv.className = 'price-difference';
        
        let message = '';
        if (difference > 0) {
            message = `<strong>Changement d'abonnement</strong>`;
            message += `<p>Vous passerez de ${data.current_price} à ${data.new_price}</p>`;
            message += `<p>Différence de prix : <strong>+${data.difference_formatted}</strong></p>`;
            message += `<div class="impact-text">➜ Cette différence sera ajoutée à votre prochain prélèvement</div>`;
        } else if (difference < 0) {
            message = `<strong>Changement d'abonnement</strong>`;
            message += `<p>Vous passerez de ${data.current_price} à ${data.new_price}</p>`;
            message += `<p>Différence de prix : <strong>${data.difference_formatted}</strong></p>`;
            message += `<div class="impact-text">➜ Cette différence sera déduite de votre prochain prélèvement</div>`;
        } else {
            message = `<strong>Changement d'abonnement</strong>`;
            message += `<p>${data.current_price} = ${data.new_price}</p>`;
            message += `<div class="impact-text">➜ Aucun impact sur le montant de votre prélèvement</div>`;
        }

        priceDiv.innerHTML = message;
        priceDiv.style.display = 'block';
    }

    function confirmSubscriptionChange() {
        if (!currentModalData.selectedVariationId) {
            showModalMessage('error', 'Veuillez sélectionner un nouvel abonnement');
            return;
        }

        const confirmBtn = document.getElementById('confirmChangeBtn');
        const loadingSpinner = document.getElementById('modalLoading');

        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Changement en cours...';
        loadingSpinner.style.display = 'block';

        // Appel AJAX pour changer l'abonnement
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'change_subscription',
                order_id: currentModalData.orderId,
                item_id: currentModalData.itemId,
                current_variation_id: currentModalData.currentVariationId,
                new_variation_id: currentModalData.selectedVariationId,
                nonce: '<?php echo wp_create_nonce('subscription_change_nonce'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            loadingSpinner.style.display = 'none';

            if (data.success) {
                showModalMessage('success', data.data.message || 'Abonnement modifié avec succès !');
                
                // Recharger la page après 2 secondes
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                showModalMessage('error', data.data || 'Erreur lors du changement d\'abonnement');
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Confirmer le changement';
            }
        })
        .catch(error => {
            loadingSpinner.style.display = 'none';
            showModalMessage('error', 'Erreur réseau lors du changement d\'abonnement');
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Confirmer le changement';
            console.error('Error:', error);
        });
    }

    function showModalMessage(type, message) {
        const messageDiv = document.getElementById('modalMessage');
        messageDiv.className = `modal-message ${type}`;
        messageDiv.textContent = message;
        messageDiv.style.display = 'block';

        // Masquer après 5 secondes pour les erreurs
        if (type === 'error') {
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
        }
    }
    </script>

    <?php
    return ob_get_clean();
}

add_shortcode('newsaiige_subscription_history', 'newsaiige_subscription_history_shortcode');
?>