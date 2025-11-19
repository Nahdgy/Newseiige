<?php
function newsaiige_simple_stars_reviews($atts) {
    $atts = shortcode_atts(array(
        'service_id' => 0,
        'service_name' => '',
        'show_count' => true,
        'size' => 'medium' // small, medium, large
    ), $atts);
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsaiige_reviews';
    
    // Récupérer les avis selon le filtre
    if ($atts['service_id'] > 0) {
        $reviews = $wpdb->get_results($wpdb->prepare(
            "SELECT customer_name, comment, rating, created_at 
             FROM $table_name 
             WHERE status = 'approved' AND service_id = %d
             ORDER BY created_at DESC",
            intval($atts['service_id'])
        ));
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as total_reviews, AVG(rating) as average_rating
             FROM $table_name 
             WHERE status = 'approved' AND service_id = %d",
            intval($atts['service_id'])
        ));
    } else {
        $reviews = $wpdb->get_results(
            "SELECT customer_name, comment, rating, created_at 
             FROM $table_name 
             WHERE status = 'approved' 
             ORDER BY created_at DESC"
        );
        
        $stats = $wpdb->get_row(
            "SELECT COUNT(*) as total_reviews, AVG(rating) as average_rating
             FROM $table_name 
             WHERE status = 'approved'"
        );
    }
    
    if (!$stats || $stats->total_reviews == 0) {
        $stats = (object)array('total_reviews' => 0, 'average_rating' => 0);
    }
    
    // Enqueue scripts
    wp_enqueue_script('newsaiige-simple-stars-js', '', array('jquery'), '1.0', true);
    wp_add_inline_script('newsaiige-simple-stars-js', '
        const newsaiige_stars_ajax = {
            ajax_url: "' . admin_url('admin-ajax.php') . '",
            nonce: "' . wp_create_nonce('newsaiige_review_nonce') . '"
        };
    ');
    
    $unique_id = 'stars_' . uniqid();
    $service_display = !empty($atts['service_name']) ? $atts['service_name'] : 'général';
    
    ob_start();
    ?>
    
    <style>
    .simple-stars-container {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        padding: 8px 15px;
        border-radius: 20px;
        background: rgba(130, 137, 127, 0.05);
    }
    
    .simple-stars-container:hover {
        background: rgba(130, 137, 127, 0.1);
        transform: translateY(-2px);
    }
    
    .simple-stars-display {
        display: flex;
        gap: 2px;
    }
    
    .simple-star-icon {
        color: #FFD700;
        line-height: 1;
    }
    
    .simple-star-icon.empty {
        color: #ddd;
    }
    
    /* Tailles */
    .size-small .simple-star-icon { font-size: 16px; }
    .size-medium .simple-star-icon { font-size: 20px; }
    .size-large .simple-star-icon { font-size: 28px; }
    
    .simple-stars-score {
        font-weight: 700;
        color: #82897F;
    }
    
    .size-small .simple-stars-score { font-size: 14px; }
    .size-medium .simple-stars-score { font-size: 16px; }
    .size-large .simple-stars-score { font-size: 20px; }
    
    .simple-stars-count {
        font-size: 13px;
        color: #666;
        font-weight: 500;
    }
    
    /* Modale */
    .simple-stars-modal {
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
        padding: 20px;
        overflow-y: auto;
    }
    
    .simple-stars-modal-content {
        background: white;
        border-radius: 25px;
        max-width: 800px;
        width: 100%;
        position: relative;
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4);
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .simple-stars-modal-header {
        padding: 30px 30px 20px;
        border-bottom: 2px solid #f0f0f0;
        position: sticky;
        top: 0;
        background: white;
        z-index: 10;
        border-radius: 25px 25px 0 0;
    }
    
    .simple-stars-modal-close {
        position: absolute;
        top: 20px;
        right: 25px;
        font-size: 32px;
        color: #666;
        cursor: pointer;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.3s ease;
    }
    
    .simple-stars-modal-close:hover {
        background: #f0f0f0;
        color: #82897F;
        transform: rotate(90deg);
    }
    
    .simple-stars-modal-title {
        font-size: 24px;
        font-weight: 700;
        color: #82897F;
        margin: 0 0 10px 0;
    }
    
    .simple-stars-modal-subtitle {
        font-size: 14px;
        color: #666;
        margin: 0;
    }
    
    .simple-stars-tabs {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }
    
    .simple-stars-tab {
        padding: 10px 20px;
        background: transparent;
        border: 2px solid #e0e0e0;
        border-radius: 20px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        color: #666;
        transition: all 0.3s ease;
    }
    
    .simple-stars-tab.active {
        background: #82897F;
        color: white;
        border-color: #82897F;
    }
    
    .simple-stars-tab:hover:not(.active) {
        border-color: #82897F;
        color: #82897F;
    }
    
    .simple-stars-modal-body {
        padding: 30px;
    }
    
    .simple-stars-tab-content {
        display: none;
    }
    
    .simple-stars-tab-content.active {
        display: block;
    }
    
    /* Liste des avis */
    .simple-review-item {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 15px;
        transition: all 0.3s ease;
    }
    
    .simple-review-item:hover {
        transform: translateX(5px);
        box-shadow: 0 5px 15px rgba(130, 137, 127, 0.1);
    }
    
    .simple-review-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }
    
    .simple-review-author {
        font-weight: 700;
        color: #82897F;
        font-size: 15px;
    }
    
    .simple-review-rating {
        display: flex;
        gap: 2px;
    }
    
    .simple-review-rating-star {
        color: #FFD700;
        font-size: 14px;
    }
    
    .simple-review-text {
        font-size: 14px;
        line-height: 1.6;
        color: #333;
        font-style: italic;
        margin-bottom: 10px;
    }
    
    .simple-review-date {
        font-size: 12px;
        color: #999;
    }
    
    .simple-no-reviews {
        text-align: center;
        padding: 40px 20px;
        color: #666;
    }
    
    .simple-no-reviews-icon {
        font-size: 48px;
        margin-bottom: 15px;
        opacity: 0.3;
    }
    
    /* Formulaire */
    .simple-form-group {
        margin-bottom: 20px;
    }
    
    .simple-form-label {
        display: block;
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
        font-size: 14px;
    }
    
    .simple-form-input,
    .simple-form-textarea {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        font-family: 'Montserrat', sans-serif;
        font-size: 14px;
        transition: all 0.3s ease;
        box-sizing: border-box;
    }
    
    .simple-form-input:focus,
    .simple-form-textarea:focus {
        outline: none;
        border-color: #82897F;
        box-shadow: 0 0 0 3px rgba(130, 137, 127, 0.1);
    }
    
    .simple-form-textarea {
        min-height: 100px;
        resize: vertical;
    }
    
    .simple-rating-input {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin: 20px 0;
    }
    
    .simple-rating-star {
        font-size: 36px;
        color: #ddd;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .simple-rating-star:hover,
    .simple-rating-star.active {
        color: #FFD700;
        transform: scale(1.1);
    }
    
    .simple-submit-btn {
        width: 100%;
        padding: 15px;
        background: #82897F;
        color: white;
        border: none;
        border-radius: 50px;
        font-family: 'Montserrat', sans-serif;
        font-weight: 700;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(130, 137, 127, 0.3);
    }
    
    .simple-submit-btn:hover {
        background: #6d7569;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(130, 137, 127, 0.4);
    }
    
    .simple-submit-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
        transform: none;
    }
    
    .simple-message {
        padding: 12px 15px;
        border-radius: 12px;
        margin-bottom: 15px;
        font-weight: 600;
        text-align: center;
        font-size: 14px;
        animation: slideIn 0.3s ease;
    }
    
    .simple-message.success {
        background: #d4edda;
        color: #155724;
        border: 2px solid #c3e6cb;
    }
    
    .simple-message.error {
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
        .simple-stars-modal-content {
            margin: 20px;
        }
        
        .simple-stars-modal-header,
        .simple-stars-modal-body {
            padding: 20px;
        }
        
        .simple-stars-modal-title {
            font-size: 20px;
        }
        
        .simple-stars-tabs {
            flex-direction: column;
        }
        
        .simple-stars-tab {
            width: 100%;
            text-align: center;
        }
        
        .simple-rating-star {
            font-size: 32px;
        }
    }
    </style>
    
    <div class="simple-stars-container size-<?php echo esc_attr($atts['size']); ?>" id="open_<?php echo $unique_id; ?>">
        <div class="simple-stars-display">
            <?php
            $average = $stats->average_rating;
            $full_stars = floor($average);
            $half_star = ($average - $full_stars) >= 0.5;
            
            for ($i = 1; $i <= 5; $i++) {
                if ($i <= $full_stars) {
                    echo '<span class="simple-star-icon">★</span>';
                } elseif ($i == $full_stars + 1 && $half_star) {
                    echo '<span class="simple-star-icon">★</span>';
                } else {
                    echo '<span class="simple-star-icon empty">★</span>';
                }
            }
            ?>
        </div>
        
        <?php if ($stats->total_reviews > 0): ?>
            <span class="simple-stars-score"><?php echo number_format($average, 1, ',', ''); ?></span>
            <?php if ($atts['show_count']): ?>
                <span class="simple-stars-count">(<?php echo $stats->total_reviews; ?>)</span>
            <?php endif; ?>
        <?php else: ?>
            <span class="simple-stars-count">Aucun avis</span>
        <?php endif; ?>
    </div>
    
    <!-- Modale -->
    <div class="simple-stars-modal" id="modal_<?php echo $unique_id; ?>">
        <div class="simple-stars-modal-content">
            <div class="simple-stars-modal-header">
                <span class="simple-stars-modal-close" id="close_<?php echo $unique_id; ?>">×</span>
                <h3 class="simple-stars-modal-title">Avis clients</h3>
                <p class="simple-stars-modal-subtitle">
                    <?php if (!empty($atts['service_name'])): ?>
                        <?php echo esc_html($atts['service_name']); ?>
                    <?php endif; ?>
                    <?php if ($stats->total_reviews > 0): ?>
                        • <?php echo number_format($average, 1, ',', ''); ?>/5 (<?php echo $stats->total_reviews; ?> avis)
                    <?php endif; ?>
                </p>
                
                <div class="simple-stars-tabs">
                    <button class="simple-stars-tab active" id="tab_reviews_<?php echo $unique_id; ?>" data-tab="reviews">
                        📝 Voir les avis<?php echo $stats->total_reviews > 0 ? ' (' . $stats->total_reviews . ')' : ''; ?>
                    </button>
                    <button class="simple-stars-tab" id="tab_form_<?php echo $unique_id; ?>" data-tab="form">
                        ✍️ Laisser un avis
                    </button>
                </div>
            </div>
            
            <div class="simple-stars-modal-body">
                <!-- Tab Avis -->
                <div class="simple-stars-tab-content active" id="reviews_<?php echo $unique_id; ?>">
                    <?php if (!empty($reviews)): ?>
                        <?php foreach ($reviews as $review): ?>
                        <div class="simple-review-item">
                            <div class="simple-review-header">
                                <span class="simple-review-author"><?php echo esc_html($review->customer_name); ?></span>
                                <div class="simple-review-rating">
                                    <?php for ($i = 0; $i < $review->rating; $i++): ?>
                                        <span class="simple-review-rating-star">★</span>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="simple-review-text"><?php echo esc_html($review->comment); ?></p>
                            <span class="simple-review-date"><?php echo date_i18n('d F Y', strtotime($review->created_at)); ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="simple-no-reviews">
                            <div class="simple-no-reviews-icon">💬</div>
                            <p><strong>Aucun avis pour le moment</strong></p>
                            <p>Soyez le premier à partager votre expérience !</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Tab Formulaire -->
                <div class="simple-stars-tab-content" id="form_<?php echo $unique_id; ?>">
                    <form id="form_submit_<?php echo $unique_id; ?>">
                        <div id="message_<?php echo $unique_id; ?>"></div>
                        
                        <div class="simple-form-group">
                            <label class="simple-form-label">Votre nom *</label>
                            <input type="text" name="customer_name" class="simple-form-input" required placeholder="Ex: Marie Dupont">
                        </div>
                        
                        <div class="simple-form-group">
                            <label class="simple-form-label">Votre email *</label>
                            <input type="email" name="customer_email" class="simple-form-input" required placeholder="votre@email.com">
                        </div>
                        
                        <div class="simple-form-group">
                            <label class="simple-form-label">Votre note *</label>
                            <div class="simple-rating-input" id="rating_<?php echo $unique_id; ?>">
                                <span class="simple-rating-star" data-rating="1">★</span>
                                <span class="simple-rating-star" data-rating="2">★</span>
                                <span class="simple-rating-star" data-rating="3">★</span>
                                <span class="simple-rating-star" data-rating="4">★</span>
                                <span class="simple-rating-star" data-rating="5">★</span>
                            </div>
                            <input type="hidden" name="rating" id="rating_value_<?php echo $unique_id; ?>" required>
                        </div>
                        
                        <div class="simple-form-group">
                            <label class="simple-form-label">Votre commentaire *</label>
                            <textarea name="comment" class="simple-form-textarea" required placeholder="Partagez votre expérience..."></textarea>
                        </div>
                        
                        <input type="hidden" name="service_id" value="<?php echo intval($atts['service_id']); ?>">
                        <input type="hidden" name="service_name" value="<?php echo esc_attr($service_display); ?>">
                        
                        <button type="submit" class="simple-submit-btn" id="submit_<?php echo $unique_id; ?>">
                            Publier mon avis
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    (function() {
        const uniqueId = '<?php echo $unique_id; ?>';
        let selectedRating = 0;
        
        // Rating stars
        document.addEventListener('DOMContentLoaded', function() {
            // Open modal
            const openBtn = document.getElementById('open_' + uniqueId);
            const modal = document.getElementById('modal_' + uniqueId);
            const closeBtn = document.getElementById('close_' + uniqueId);
            
            if (openBtn) {
                openBtn.addEventListener('click', function() {
                    modal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                });
            }
            
            // Close modal
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                });
            }
            
            // Close on outside click
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        modal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                    }
                });
            }
            
            // Switch tabs
            const tabReviews = document.getElementById('tab_reviews_' + uniqueId);
            const tabForm = document.getElementById('tab_form_' + uniqueId);
            const contentReviews = document.getElementById('reviews_' + uniqueId);
            const contentForm = document.getElementById('form_' + uniqueId);
            
            if (tabReviews) {
                tabReviews.addEventListener('click', function() {
                    tabReviews.classList.add('active');
                    tabForm.classList.remove('active');
                    contentReviews.classList.add('active');
                    contentForm.classList.remove('active');
                });
            }
            
            if (tabForm) {
                tabForm.addEventListener('click', function() {
                    tabForm.classList.add('active');
                    tabReviews.classList.remove('active');
                    contentForm.classList.add('active');
                    contentReviews.classList.remove('active');
                });
            }
            const ratingContainer = document.getElementById('rating_' + uniqueId);
            const ratingInput = document.getElementById('rating_value_' + uniqueId);
            const stars = ratingContainer.querySelectorAll('.simple-rating-star');
            
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    selectedRating = parseInt(this.getAttribute('data-rating'));
                    ratingInput.value = selectedRating;
                    updateStars();
                });
                
                star.addEventListener('mouseover', function() {
                    const hoverRating = parseInt(this.getAttribute('data-rating'));
                    highlightStars(hoverRating);
                });
            });
            
            ratingContainer.addEventListener('mouseleave', updateStars);
            
            function highlightStars(rating) {
                stars.forEach((star, index) => {
                    if (index < rating) {
                        star.classList.add('active');
                    } else {
                        star.classList.remove('active');
                    }
                });
            }
            
            function updateStars() {
                highlightStars(selectedRating);
            }
            
            // Form submission
            const form = document.getElementById('form_submit_' + uniqueId);
            const messageDiv = document.getElementById('message_' + uniqueId);
            const submitBtn = document.getElementById('submit_' + uniqueId);
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (!selectedRating || selectedRating < 1) {
                    showMessage('Veuillez sélectionner une note', 'error');
                    return;
                }
                
                submitBtn.disabled = true;
                submitBtn.textContent = 'Envoi en cours...';
                
                const formData = new FormData(form);
                formData.append('action', 'newsaiige_submit_review');
                formData.append('nonce', newsaiige_stars_ajax.nonce);
                
                fetch(newsaiige_stars_ajax.ajax_url, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('Merci ! Votre avis a été soumis et sera publié après modération.', 'success');
                        form.reset();
                        selectedRating = 0;
                        updateStars();
                        
                        setTimeout(() => {
                            closeSimpleStarsModal(uniqueId);
                            location.reload();
                        }, 2000);
                    } else {
                        showMessage(data.data || 'Une erreur est survenue.', 'error');
                    }
                })
                .catch(error => {
                    showMessage('Erreur de connexion.', 'error');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Publier mon avis';
                });
            });
            
            function showMessage(message, type) {
                messageDiv.innerHTML = '<div class="simple-message ' + type + '">' + message + '</div>';
                
                if (type === 'error') {
                    setTimeout(() => {
                        messageDiv.innerHTML = '';
                    }, 5000);
                }
            }
        });
    })();
    </script>
    
    <?php
    return ob_get_clean();
}

add_shortcode('newsaiige_simple_stars', 'newsaiige_simple_stars_reviews');
?>
