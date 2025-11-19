<?php
function newsaiige_service_reviews_shortcode($atts) {
    $atts = shortcode_atts(array(
        'service_id' => 0,
        'service_name' => '',
        'limit' => 10,
        'show_form' => true,
        'show_all_reviews' => false // Afficher tous les avis ou seulement ceux de la prestation
    ), $atts);
    
    // Récupérer les avis depuis la base de données
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsaiige_reviews';
    
    // Construire la requête selon le filtre
    if ($atts['service_id'] > 0 && !$atts['show_all_reviews']) {
        // Avis pour une prestation spécifique
        $reviews = $wpdb->get_results($wpdb->prepare(
            "SELECT customer_name, comment, rating, created_at, service_id, service_name
             FROM $table_name 
             WHERE status = 'approved' AND service_id = %d
             ORDER BY created_at DESC 
             LIMIT %d",
            intval($atts['service_id']),
            intval($atts['limit'])
        ));
        
        // Statistiques pour cette prestation
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating
             FROM $table_name 
             WHERE status = 'approved' AND service_id = %d",
            intval($atts['service_id'])
        ));
    } else {
        // Tous les avis
        $reviews = $wpdb->get_results($wpdb->prepare(
            "SELECT customer_name, comment, rating, created_at, service_id, service_name
             FROM $table_name 
             WHERE status = 'approved' 
             ORDER BY created_at DESC 
             LIMIT %d",
            intval($atts['limit'])
        ));
        
        // Statistiques générales
        $stats = $wpdb->get_row(
            "SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating
             FROM $table_name 
             WHERE status = 'approved'"
        );
    }
    
    // Valeurs par défaut si pas de données
    if (!$reviews || empty($reviews)) {
        $reviews = array();
    }
    
    if (!$stats) {
        $stats = (object)array('total_reviews' => 0, 'average_rating' => 0);
    }
    
    // Enqueue les scripts nécessaires
    if ($atts['show_form']) {
        wp_enqueue_script('newsaiige-service-reviews-js', '', array('jquery'), '1.0', true);
        wp_add_inline_script('newsaiige-service-reviews-js', '
            const newsaiige_service_ajax = {
                ajax_url: "' . admin_url('admin-ajax.php') . '",
                nonce: "' . wp_create_nonce('newsaiige_review_nonce') . '"
            };
        ');
    }
    
    // Déterminer le nom du service
    $service_display_name = !empty($atts['service_name']) ? $atts['service_name'] : 'cette prestation';
    
    ob_start();
    ?>

    <style>
    .newsaiige-service-reviews {
        padding: 60px 20px;
        background: #ffffff;
        font-family: 'Montserrat', sans-serif;
        position: relative;
        overflow: hidden;
    }

    .service-reviews-header {
        text-align: center;
        margin-bottom: 50px;
        padding-bottom: 30px;
        border-bottom: 2px solid #f0f0f0;
    }

    .service-reviews-title {
        font-size: 28px;
        font-weight: 700;
        color: #82897F;
        margin: 0 0 15px 0;
        letter-spacing: 1px;
    }

    .service-name-badge {
        display: inline-block;
        background: linear-gradient(135deg, #82897F 0%, #6d7569 100%);
        color: white;
        padding: 10px 25px;
        border-radius: 25px;
        font-size: 14px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 20px;
    }

    .service-reviews-rating {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 20px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .service-rating-score {
        font-size: 48px;
        font-weight: 800;
        color: #82897F;
        line-height: 1;
    }

    .service-rating-stars {
        display: flex;
        gap: 5px;
        align-items: center;
    }

    .service-star {
        color: #FFD700;
        font-size: 32px;
        line-height: 1;
    }

    .service-rating-count {
        font-size: 16px;
        color: #666;
        font-weight: 600;
    }

    .service-reviews-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 25px;
        max-width: 1200px;
        margin: 0 auto 40px;
    }

    .service-review-card {
        background: #f8f9fa;
        border-radius: 20px;
        padding: 30px;
        position: relative;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .service-review-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(130, 137, 127, 0.15);
        border-color: #82897F;
    }

    .service-review-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #e0e0e0;
    }

    .service-review-author {
        font-weight: 700;
        color: #82897F;
        font-size: 16px;
    }

    .service-review-stars {
        display: flex;
        gap: 3px;
    }

    .service-review-star {
        color: #FFD700;
        font-size: 16px;
    }

    .service-review-text {
        font-size: 14px;
        line-height: 1.7;
        color: #333;
        font-style: italic;
        margin-bottom: 15px;
    }

    .service-review-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
        color: #999;
    }

    .service-review-date {
        font-weight: 500;
    }

    .service-review-service-name {
        background: #82897F;
        color: white;
        padding: 4px 12px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 11px;
    }

    .service-add-review-btn {
        display: inline-block;
        padding: 18px 45px;
        background: #82897F;
        color: white !important;
        text-decoration: none;
        border-radius: 50px;
        font-weight: 700;
        font-size: 16px;
        transition: all 0.3s ease;
        border: 2px solid #82897F;
        cursor: pointer;
        margin-top: 20px;
        box-shadow: 0 10px 25px rgba(130, 137, 127, 0.2);
    }

    .service-add-review-btn:hover {
        background: transparent;
        color: #82897F !important;
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(130, 137, 127, 0.3);
    }

    .service-no-reviews {
        text-align: center;
        padding: 60px 20px;
        color: #666;
        font-size: 16px;
    }

    .service-no-reviews-icon {
        font-size: 64px;
        margin-bottom: 20px;
        opacity: 0.3;
    }

    /* MODALE */
    .service-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.85);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 100000;
        backdrop-filter: blur(8px);
    }

    .service-modal-content {
        background: white;
        border-radius: 25px;
        padding: 50px 40px;
        max-width: 550px;
        width: 90%;
        position: relative;
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4);
        max-height: 90vh;
        overflow-y: auto;
    }

    .service-modal-close {
        position: absolute;
        top: 20px;
        right: 25px;
        font-size: 32px;
        color: #666;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .service-modal-close:hover {
        background: #f0f0f0;
        color: #82897F;
        transform: rotate(90deg);
    }

    .service-modal-title {
        font-size: 28px;
        font-weight: 700;
        color: #82897F;
        text-align: center;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .service-modal-subtitle {
        text-align: center;
        color: #666;
        margin-bottom: 30px;
        font-size: 14px;
    }

    .service-form-group {
        margin-bottom: 25px;
    }

    .service-form-label {
        display: block;
        font-weight: 600;
        color: #333;
        margin-bottom: 10px;
        font-size: 14px;
    }

    .service-form-input,
    .service-form-textarea,
    .service-form-select {
        width: 100%;
        padding: 15px;
        border: 2px solid #e9ecef;
        border-radius: 15px;
        font-family: 'Montserrat', sans-serif;
        font-size: 15px;
        transition: all 0.3s ease;
        box-sizing: border-box;
    }

    .service-form-input:focus,
    .service-form-textarea:focus,
    .service-form-select:focus {
        outline: none;
        border-color: #82897F;
        box-shadow: 0 0 0 3px rgba(130, 137, 127, 0.1);
    }

    .service-form-textarea {
        min-height: 120px;
        resize: vertical;
    }

    .service-rating-input {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin: 25px 0;
    }

    .service-rating-star {
        font-size: 40px;
        color: #ddd;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .service-rating-star:hover,
    .service-rating-star.active {
        color: #FFD700;
        transform: scale(1.15);
    }

    .service-submit-btn {
        width: 100%;
        padding: 18px;
        background: #82897F;
        color: white;
        border: none;
        border-radius: 50px;
        font-family: 'Montserrat', sans-serif;
        font-weight: 700;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 10px 25px rgba(130, 137, 127, 0.3);
    }

    .service-submit-btn:hover {
        background: #6d7569;
        transform: translateY(-2px);
        box-shadow: 0 15px 35px rgba(130, 137, 127, 0.4);
    }

    .service-submit-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
        transform: none;
    }

    /* Messages de succès/erreur */
    .service-message {
        padding: 15px 20px;
        border-radius: 15px;
        margin-bottom: 20px;
        font-weight: 600;
        text-align: center;
        animation: slideIn 0.3s ease;
    }

    .service-message.success {
        background: #d4edda;
        color: #155724;
        border: 2px solid #c3e6cb;
    }

    .service-message.error {
        background: #f8d7da;
        color: #721c24;
        border: 2px solid #f5c6cb;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .newsaiige-service-reviews {
            padding: 40px 15px;
        }

        .service-reviews-title {
            font-size: 24px;
        }
        
        .service-rating-score {
            font-size: 36px;
        }

        .service-reviews-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .service-modal-content {
            padding: 40px 25px;
        }

        .service-modal-title {
            font-size: 24px;
        }

        .service-reviews-rating {
            gap: 15px;
        }
    }

    @media (max-width: 480px) {
        .service-reviews-title {
            font-size: 20px;
        }

        .service-name-badge {
            font-size: 12px;
            padding: 8px 20px;
        }

        .service-review-card {
            padding: 20px;
        }

        .service-modal-content {
            padding: 30px 20px;
        }

        .service-rating-star {
            font-size: 36px;
        }
    }
    </style>

    <div class="newsaiige-service-reviews">
        <div class="service-reviews-header">
            <h2 class="service-reviews-title">Avis clients</h2>
            
            <?php if ($atts['service_id'] > 0 && !empty($atts['service_name'])): ?>
                <div class="service-name-badge"><?php echo esc_html($atts['service_name']); ?></div>
            <?php endif; ?>
            
            <?php if ($stats->total_reviews > 0): ?>
                <div class="service-reviews-rating">
                    <span class="service-rating-score"><?php echo number_format($stats->average_rating, 1, ',', ''); ?></span>
                    <div class="service-rating-stars">
                        <?php 
                        $full_stars = floor($stats->average_rating);
                        $half_star = ($stats->average_rating - $full_stars) >= 0.5;
                        
                        for ($i = 0; $i < $full_stars; $i++) {
                            echo '<span class="service-star">★</span>';
                        }
                        if ($half_star && $full_stars < 5) {
                            echo '<span class="service-star">☆</span>';
                        }
                        ?>
                    </div>
                    <span class="service-rating-count">(<?php echo $stats->total_reviews; ?> avis)</span>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($reviews)): ?>
            <div class="service-reviews-grid">
                <?php foreach ($reviews as $review): ?>
                <div class="service-review-card">
                    <div class="service-review-header">
                        <span class="service-review-author"><?php echo esc_html($review->customer_name); ?></span>
                        <div class="service-review-stars">
                            <?php for ($i = 0; $i < $review->rating; $i++): ?>
                                <span class="service-review-star">★</span>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <p class="service-review-text"><?php echo esc_html($review->comment); ?></p>
                    
                    <div class="service-review-footer">
                        <span class="service-review-date">
                            <?php echo date_i18n('d F Y', strtotime($review->created_at)); ?>
                        </span>
                        <?php if (!empty($review->service_name) && $atts['show_all_reviews']): ?>
                            <span class="service-review-service-name"><?php echo esc_html($review->service_name); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="service-no-reviews">
                <div class="service-no-reviews-icon">💬</div>
                <p><strong>Aucun avis pour le moment</strong></p>
                <p>Soyez le premier à partager votre expérience !</p>
            </div>
        <?php endif; ?>
        
        <?php if ($atts['show_form']): ?>
        <div style="text-align: center;">
            <button class="service-add-review-btn" onclick="openServiceModal()">
                <?php echo $stats->total_reviews > 0 ? 'Ajouter mon avis' : 'Laisser le premier avis'; ?>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- MODALE -->
    <?php if ($atts['show_form']): ?>
    <div class="service-modal-overlay" id="serviceReviewModal">
        <div class="service-modal-content">
            <span class="service-modal-close" onclick="closeServiceModal()">×</span>
            <h3 class="service-modal-title">Votre avis</h3>
            <p class="service-modal-subtitle">
                <?php if ($atts['service_id'] > 0 && !empty($atts['service_name'])): ?>
                    Sur : <strong><?php echo esc_html($atts['service_name']); ?></strong>
                <?php else: ?>
                    Partagez votre expérience avec nous
                <?php endif; ?>
            </p>
            
            <form id="serviceReviewForm">
                <div id="serviceFormMessage"></div>
                
                <div class="service-form-group">
                    <label class="service-form-label">Votre nom *</label>
                    <input type="text" name="customer_name" class="service-form-input" required placeholder="Ex: Marie Dupont">
                </div>
                
                <div class="service-form-group">
                    <label class="service-form-label">Votre email *</label>
                    <input type="email" name="customer_email" class="service-form-input" required placeholder="votre@email.com">
                </div>
                
                <?php if ($atts['service_id'] == 0): ?>
                <div class="service-form-group">
                    <label class="service-form-label">Prestation concernée</label>
                    <select name="service_id" class="service-form-select" id="serviceSelect">
                        <option value="0">Avis général</option>
                        <?php
                        // Récupérer les prestations/produits WooCommerce
                        $args = array(
                            'post_type' => 'product',
                            'posts_per_page' => -1,
                            'orderby' => 'title',
                            'order' => 'ASC',
                            'tax_query' => array(
                                array(
                                    'taxonomy' => 'product_cat',
                                    'field'    => 'slug',
                                    'terms'    => array('e-carte-cadeau'),
                                    'operator' => 'NOT IN',
                                ),
                            ),
                        );
                        $products = new WP_Query($args);
                        
                        if ($products->have_posts()) {
                            while ($products->have_posts()) {
                                $products->the_post();
                                $product_id = get_the_ID();
                                $product_title = get_the_title();
                                echo '<option value="' . $product_id . '">' . esc_html($product_title) . '</option>';
                            }
                            wp_reset_postdata();
                        }
                        ?>
                    </select>
                </div>
                <?php else: ?>
                <input type="hidden" name="service_id" value="<?php echo intval($atts['service_id']); ?>">
                <input type="hidden" name="service_name" value="<?php echo esc_attr($atts['service_name']); ?>">
                <?php endif; ?>
                
                <div class="service-form-group">
                    <label class="service-form-label">Votre note *</label>
                    <div class="service-rating-input" id="serviceRatingInput">
                        <span class="service-rating-star" data-rating="1">★</span>
                        <span class="service-rating-star" data-rating="2">★</span>
                        <span class="service-rating-star" data-rating="3">★</span>
                        <span class="service-rating-star" data-rating="4">★</span>
                        <span class="service-rating-star" data-rating="5">★</span>
                    </div>
                    <input type="hidden" name="rating" id="serviceRatingValue" required>
                </div>
                
                <div class="service-form-group">
                    <label class="service-form-label">Votre commentaire *</label>
                    <textarea name="comment" class="service-form-textarea" required placeholder="Partagez votre expérience..."></textarea>
                </div>
                
                <button type="submit" class="service-submit-btn" id="serviceSubmitBtn">Publier mon avis</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
    <?php if ($atts['show_form']): ?>
    // Modal functionality
    function openServiceModal() {
        document.getElementById('serviceReviewModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeServiceModal() {
        document.getElementById('serviceReviewModal').style.display = 'none';
        document.body.style.overflow = 'auto';
        
        // Reset form
        document.getElementById('serviceReviewForm').reset();
        document.getElementById('serviceFormMessage').innerHTML = '';
        serviceSelectedRating = 0;
        updateServiceStars();
    }

    // Rating stars functionality
    let serviceSelectedRating = 0;
    
    function initializeServiceStars() {
        const ratingStars = document.querySelectorAll('.service-rating-star');
        
        ratingStars.forEach(star => {
            star.addEventListener('click', function() {
                serviceSelectedRating = parseInt(this.getAttribute('data-rating'));
                document.getElementById('serviceRatingValue').value = serviceSelectedRating;
                updateServiceStars();
            });
            
            star.addEventListener('mouseover', function() {
                const hoverRating = parseInt(this.getAttribute('data-rating'));
                highlightServiceStars(hoverRating);
            });
        });

        const ratingInput = document.getElementById('serviceRatingInput');
        if (ratingInput) {
            ratingInput.addEventListener('mouseleave', function() {
                updateServiceStars();
            });
        }
    }

    function highlightServiceStars(rating) {
        const ratingStars = document.querySelectorAll('.service-rating-star');
        ratingStars.forEach((star, index) => {
            if (index < rating) {
                star.classList.add('active');
            } else {
                star.classList.remove('active');
            }
        });
    }

    function updateServiceStars() {
        highlightServiceStars(serviceSelectedRating);
    }

    // Form submission
    function initializeServiceForm() {
        const form = document.getElementById('serviceReviewForm');
        const messageDiv = document.getElementById('serviceFormMessage');
        const submitBtn = document.getElementById('serviceSubmitBtn');
        
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Validation
                const rating = document.getElementById('serviceRatingValue').value;
                if (!rating || rating < 1 || rating > 5) {
                    showServiceMessage('Veuillez sélectionner une note', 'error');
                    return;
                }
                
                // Désactiver le bouton
                submitBtn.disabled = true;
                submitBtn.textContent = 'Envoi en cours...';
                
                // Récupérer les données du formulaire
                const formData = new FormData(form);
                formData.append('action', 'newsaiige_submit_review');
                formData.append('nonce', newsaiige_service_ajax.nonce);
                
                // Si service_id est dans un select, récupérer le nom
                const serviceSelect = document.getElementById('serviceSelect');
                if (serviceSelect) {
                    const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
                    formData.set('service_name', selectedOption.text);
                }
                
                // Envoyer via AJAX
                fetch(newsaiige_service_ajax.ajax_url, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showServiceMessage('Merci ! Votre avis a été soumis et sera publié après modération.', 'success');
                        form.reset();
                        serviceSelectedRating = 0;
                        updateServiceStars();
                        
                        // Fermer la modale après 3 secondes
                        setTimeout(() => {
                            closeServiceModal();
                            // Recharger la page pour afficher le nouvel avis
                            location.reload();
                        }, 3000);
                    } else {
                        showServiceMessage(data.data || 'Une erreur est survenue. Veuillez réessayer.', 'error');
                    }
                })
                .catch(error => {
                    showServiceMessage('Erreur de connexion. Veuillez réessayer.', 'error');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Publier mon avis';
                });
            });
        }
    }

    function showServiceMessage(message, type) {
        const messageDiv = document.getElementById('serviceFormMessage');
        messageDiv.innerHTML = '<div class="service-message ' + type + '">' + message + '</div>';
        
        // Auto-hide après 5 secondes
        if (type === 'error') {
            setTimeout(() => {
                messageDiv.innerHTML = '';
            }, 5000);
        }
    }

    // Close modal when clicking outside
    function initializeServiceModal() {
        const modal = document.getElementById('serviceReviewModal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeServiceModal();
                }
            });
        }
    }

    // Initialisation
    document.addEventListener('DOMContentLoaded', function() {
        initializeServiceStars();
        initializeServiceForm();
        initializeServiceModal();
    });
    <?php endif; ?>
    </script>
    
    <?php
    return ob_get_clean();
}

add_shortcode('newsaiige_service_reviews', 'newsaiige_service_reviews_shortcode');
?>
