<?php
/**
 * Plugin Name: NewSaiige Reviews System
 * Description: Système de gestion des avis clients pour NewSaiige avec carousel et notation étoilée
 * Version: 1.0.0
 * Author: NewSaiige
 */

// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

function newsaiige_reviews_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => 10,
        'show_form' => true
    ), $atts);
    
    // Enqueue les scripts nécessaires
    wp_enqueue_script('newsaiige-reviews-js', '', array('jquery'), '1.0', true);
    wp_add_inline_script('newsaiige-reviews-js', '
        const newsaiige_ajax = {
            ajax_url: "' . admin_url('admin-ajax.php') . '",
            nonce: "' . wp_create_nonce('newsaiige_review_nonce') . '"
        };
    ');
    
    ob_start();
    ?>

    <style>
    .newsaiige-reviews {
        padding: 80px 20px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        font-family: 'Montserrat', sans-serif;
        position: relative;
        overflow: hidden;
    }
    .reviews-header {
        text-align: center;
        margin-bottom: 60px;
    }

    .reviews-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: #82897F;
        margin: 0 0 20px 0;
        text-transform: uppercase;
        letter-spacing: 2px;
    }

    .reviews-rating {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
        margin-bottom: 30px;
    }

    .rating-score {
        font-size: 3rem;
        font-weight: 800;
        color: #82897F;
    }

    .rating-stars {
        display: flex;
        gap: 5px;
    }

    .star {
        color: #FFD700;
        font-size: 2rem;
    }

    .rating-count {
        font-size: 1.2rem;
        color: #666;
        font-weight: 600;
    }

    .carousel-container {
        position: relative;
        max-width: 1400px;
        margin: 0 auto;
        overflow: hidden;
    }

    .carousel-track {
        display: flex;
        transition: transform 0.5s ease;
        gap: 30px;
    }

    .review-card {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(15px) saturate(180%);
        -webkit-backdrop-filter: blur(15px) saturate(180%);
        border-radius: 25px;
        padding: 40px 30px;
        min-width: 400px;
        box-shadow: 
            0 20px 40px rgba(0, 0, 0, 0.1),
            0 10px 20px rgba(0, 0, 0, 0.05),
            inset 0 1px 0 rgba(255, 255, 255, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.2);
        position: relative;
        transition: all 0.3s ease;
        flex-shrink: 0;
    }

    .review-card:hover {
        transform: translateY(-10px) scale(1.02);
        box-shadow: 
            0 30px 60px rgba(0, 0, 0, 0.15),
            0 15px 30px rgba(0, 0, 0, 0.1),
            inset 0 1px 0 rgba(255, 255, 255, 0.4);
    }

    .review-text {
        font-size: 1.1rem;
        line-height: 1.6;
        color: #333;
        font-style: italic;
        margin-bottom: 25px;
        text-align: center;
    }

    .review-author {
        text-align: center;
        color: #82897F;
        font-weight: 600;
        font-size: 1rem;
    }

    .carousel-controls {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 20px;
        margin-top: 40px;
    }

    .carousel-btn {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: rgba(130, 137, 127, 0.1);
        border: 2px solid #82897F;
        color: #82897F;
        font-size: 1.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .carousel-btn:hover {
        background: #82897F;
        color: white;
        transform: scale(1.1);
    }

    .carousel-btn:disabled {
        opacity: 0.3;
        cursor: not-allowed;
        transform: none;
    }

    .carousel-pagination {
        font-size: 1.2rem;
        color: #82897F;
        font-weight: 600;
    }

    .add-review-btn {
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
        margin-top: 30px;
    }

    .add-review-btn:hover {
        background: transparent;
        color: #82897F !important;
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(130, 137, 127, 0.3);
    }

    /* MODALE */
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
        z-index: 10000;
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

    .form-input,
    .form-textarea {
        width: 100%;
        padding: 15px;
        border: 2px solid #e9ecef;
        border-radius: 15px;
        font-family: 'Montserrat', sans-serif;
        font-size: 1rem;
        transition: border-color 0.3s ease;
        box-sizing: border-box;
    }

    .form-input:focus,
    .form-textarea:focus {
        outline: none;
        border-color: #82897F;
    }

    .form-textarea {
        min-height: 120px;
        resize: vertical;
    }

    .rating-input {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin: 20px 0;
    }

    .rating-star {
        font-size: 2.5rem;
        color: #ddd;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .rating-star:hover,
    .rating-star.active {
        color: #FFD700;
        transform: scale(1.1);
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
        .reviews-title {
            font-size: 2rem;
        }
        
        .rating-score {
            font-size: 2.5rem;
        }
        
        .review-card {
            min-width: 300px;
            padding: 30px 20px;
        }
        
        .modal-content {
            padding: 40px 30px;
        }
        
        .carousel-track {
            gap: 20px;
        }
    }

    @media (max-width: 480px) {
        .review-card {
            min-width: 280px;
        }
        
        .modal-content {
            padding: 30px 20px;
        }
        
        .reviews-title {
            font-size: 1.8rem;
        }
    }
    </style>

    <div class="newsaiige-reviews">
        <div class="reviews-header">
            <h2 class="reviews-title">Elles aiment NewSaiige !</h2>
            <div class="reviews-rating">
                <span class="rating-score">5,0</span>
                <div class="rating-stars">
                    <span class="star">★</span>
                    <span class="star">★</span>
                    <span class="star">★</span>
                    <span class="star">★</span>
                    <span class="star">★</span>
                </div>
                <span class="rating-count">(242)</span>
            </div>
        </div>
        
        <div class="carousel-container">
            <div class="carousel-track" id="carouselTrack">
                <div class="review-card">
                    <div class="review-text">
                        "J'ai récemment testé l'huile corps nacrée de Newsaiige, et je dois dire que c'est une belle découverte ! Sa texture légère et non grasse s'applique facilement et laisse la peau douce, hydratée et délicatement parfumée."
                    </div>
                    <div class="review-author">- Marie L.</div>
                </div>
                
                <div class="review-card">
                    <div class="review-text">
                        "Produit exceptionnel ! Après plusieurs semaines d'utilisation, ma peau est visiblement plus ferme et éclatante. L'effet nacré est subtil et très élégant. Je recommande vivement !"
                    </div>
                    <div class="review-author">- Sophie D.</div>
                </div>
                
                <div class="review-card">
                    <div class="review-text">
                        "Une huile de qualité premium ! La texture est divine, l'absorption rapide et le parfum délicat. Mes clientes adorent et moi aussi. Un incontournable pour mes soins."
                    </div>
                    <div class="review-author">- Amélie R.</div>
                </div>
                
                <div class="review-card">
                    <div class="review-text">
                        "Enfin une huile qui tient ses promesses ! Ma peau n'a jamais été aussi douce et lumineuse. L'effet hydratant dure toute la journée. Un vrai coup de cœur !"
                    </div>
                    <div class="review-author">- Camille B.</div>
                </div>
                
                <div class="review-card">
                    <div class="review-text">
                        "Je suis esthéticienne et j'utilise cette huile dans mes soins. Les résultats sont bluffants ! Mes clientes me demandent constamment quel produit j'utilise."
                    </div>
                    <div class="review-author">- Laura M.</div>
                </div>
                
                <div class="review-card">
                    <div class="review-text">
                        "Texture incroyable, parfum subtil et résultats visibles dès les premières applications. Cette huile a transformé ma routine beauté. Je ne peux plus m'en passer !"
                    </div>
                    <div class="review-author">- Emma F.</div>
                </div>
            </div>
        </div>
        
        <div class="carousel-controls">
            <button class="carousel-btn" id="prevBtn">‹</button>
            <span class="carousel-pagination">
                <span id="currentSlide">1</span> / <span id="totalSlides">6</span>
            </span>
            <button class="carousel-btn" id="nextBtn">›</button>
        </div>
        
        <div style="text-align: center;">
            <button class="add-review-btn" onclick="openModal()">Partager votre expérience</button>
        </div>
    </div>

    <!-- MODALE -->
    <div class="modal-overlay" id="reviewModal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal()">×</span>
            <h3 class="modal-title">Partagez votre avis</h3>
            
            <form id="reviewForm">
                <div class="form-group">
                    <label class="form-label">Votre nom</label>
                    <input type="text" class="form-input" name="customer_name" placeholder="Entrez votre nom" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Votre email (optionnel)</label>
                    <input type="email" class="form-input" name="customer_email" placeholder="votre@email.com">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Votre note</label>
                    <div class="rating-input" id="ratingInput">
                        <span class="rating-star" data-rating="1">★</span>
                        <span class="rating-star" data-rating="2">★</span>
                        <span class="rating-star" data-rating="3">★</span>
                        <span class="rating-star" data-rating="4">★</span>
                        <span class="rating-star" data-rating="5">★</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Votre commentaire</label>
                    <textarea class="form-textarea" name="comment" placeholder="Partagez votre expérience avec nos produits..." required></textarea>
                </div>
                
                <button type="submit" class="submit-btn">Publier mon avis</button>
            </form>
        </div>
    </div>

    <script>
    // Carousel functionality
    let currentIndex = 0;
    const track = document.getElementById('carouselTrack');
    const cards = document.querySelectorAll('.review-card');
    const totalSlides = cards.length;
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const currentSlideSpan = document.getElementById('currentSlide');
    const totalSlidesSpan = document.getElementById('totalSlides');

    // Calculate visible cards based on screen width
    function getVisibleCards() {
        if (window.innerWidth <= 480) return 1;
        if (window.innerWidth <= 768) return 1;
        if (window.innerWidth <= 1200) return 2;
        return 3;
    }

    function updateCarousel() {
        const visibleCards = getVisibleCards();
        const cardWidth = cards[0].offsetWidth + 30; // card width + gap
        const offset = currentIndex * cardWidth;
        
        track.style.transform = `translateX(-${offset}px)`;
        
        // Update pagination
        currentSlideSpan.textContent = currentIndex + 1;
        totalSlidesSpan.textContent = Math.ceil(totalSlides / visibleCards);
        
        // Update button states
        prevBtn.disabled = currentIndex === 0;
        nextBtn.disabled = currentIndex >= totalSlides - visibleCards;
    }

    function nextSlide() {
        const visibleCards = getVisibleCards();
        if (currentIndex < totalSlides - visibleCards) {
            currentIndex++;
            updateCarousel();
        }
    }

    function prevSlide() {
        if (currentIndex > 0) {
            currentIndex--;
            updateCarousel();
        }
    }

    // Event listeners
    nextBtn.addEventListener('click', nextSlide);
    prevBtn.addEventListener('click', prevSlide);

    // Auto-scroll every 5 seconds
    setInterval(() => {
        const visibleCards = getVisibleCards();
        if (currentIndex >= totalSlides - visibleCards) {
            currentIndex = 0;
        } else {
            currentIndex++;
        }
        updateCarousel();
    }, 5000);

    // Update on window resize
    window.addEventListener('resize', updateCarousel);

    // Initialize
    updateCarousel();

    // Modal functionality
    function openModal() {
        document.getElementById('reviewModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        document.getElementById('reviewModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Rating stars functionality
    let selectedRating = 0;
    const ratingStars = document.querySelectorAll('.rating-star');

    ratingStars.forEach(star => {
        star.addEventListener('click', function() {
            selectedRating = this.getAttribute('data-rating');
            updateStars();
        });
        
        star.addEventListener('mouseover', function() {
            const hoverRating = this.getAttribute('data-rating');
            highlightStars(hoverRating);
        });
    });

    document.getElementById('ratingInput').addEventListener('mouseleave', function() {
        updateStars();
    });

    function highlightStars(rating) {
        ratingStars.forEach((star, index) => {
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
    document.getElementById('reviewForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (selectedRating === 0) {
            alert('Veuillez sélectionner une note !');
            return;
        }
        
        // Récupérer les données du formulaire
        const formData = new FormData();
        formData.append('action', 'submit_newsaiige_review');
        formData.append('customer_name', this.querySelector('input[type="text"]').value);
        formData.append('customer_email', this.querySelector('input[type="email"]')?.value || '');
        formData.append('rating', selectedRating);
        formData.append('comment', this.querySelector('textarea').value);
        formData.append('nonce', newsaiige_ajax.nonce);
        
        // Désactiver le bouton de soumission
        const submitBtn = this.querySelector('.submit-btn');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Envoi en cours...';
        
        // Envoyer la requête AJAX
        fetch(newsaiige_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Merci pour votre avis ! Il sera publié après modération.');
                closeModal();
                this.reset();
                selectedRating = 0;
                updateStars();
                
                // Recharger les avis
                loadReviews();
            } else {
                alert('Erreur: ' + (data.data || 'Une erreur est survenue'));
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue. Veuillez réessayer.');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });

    // Fonction pour charger les avis depuis WordPress
    function loadReviews() {
        fetch(`${newsaiige_ajax.ajax_url}?action=get_newsaiige_reviews&limit=20`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCarouselWithNewReviews(data.data.reviews);
                updateStats(data.data.stats);
            }
        })
        .catch(error => console.error('Erreur lors du chargement des avis:', error));
    }

    // Fonction pour mettre à jour le carousel
    function updateCarouselWithNewReviews(reviews) {
        if (!reviews || reviews.length === 0) return;
        
        const track = document.getElementById('carouselTrack');
        track.innerHTML = '';
        
        reviews.forEach(review => {
            const card = document.createElement('div');
            card.className = 'review-card';
            
            card.innerHTML = `
                <div class="review-text">"${review.comment}"</div>
                <div class="review-author">- ${review.customer_name}</div>
            `;
            
            track.appendChild(card);
        });
        
        // Réinitialiser le carousel
        currentIndex = 0;
        updateCarousel();
    }

    // Fonction pour mettre à jour les statistiques
    function updateStats(stats) {
        if (stats) {
            const ratingScore = document.querySelector('.rating-score');
            if (ratingScore && stats.average_rating) {
                ratingScore.textContent = parseFloat(stats.average_rating).toFixed(1);
            }
            
            const ratingCount = document.querySelector('.rating-count');
            if (ratingCount && stats.total_reviews) {
                ratingCount.textContent = `(${stats.total_reviews})`;
            }
        }
    }

    // Charger les avis au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        // Vérifier si les variables AJAX sont disponibles
        if (typeof newsaiige_ajax !== 'undefined') {
            loadReviews();
        }
    });

    // Close modal when clicking outside
    document.getElementById('reviewModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
    </script>
    <?php
        return ob_get_clean();
}

add_shortcode('newsaiige_reviews', 'newsaiige_reviews_shortcode');

// Handler AJAX pour soumettre un avis
function handle_submit_newsaiige_review() {
    // Vérifier le nonce
    if (!wp_verify_nonce($_POST['nonce'], 'newsaiige_review_nonce')) {
        wp_send_json_error('Erreur de sécurité');
        return;
    }
    
    global $wpdb;
    
    // Récupérer les données
    $customer_name = sanitize_text_field($_POST['customer_name']);
    $customer_email = sanitize_email($_POST['customer_email'] ?? '');
    $rating = intval($_POST['rating']);
    $comment = sanitize_textarea_field($_POST['comment']);
    
    // Validation
    if (empty($customer_name) || empty($comment) || $rating < 1 || $rating > 5) {
        wp_send_json_error('Veuillez remplir tous les champs obligatoires');
        return;
    }
    
    // Insérer dans la base de données
    $table_name = $wpdb->prefix . 'newsaiige_reviews';
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'rating' => $rating,
            'comment' => $comment,
            'status' => 'pending',
            'created_at' => current_time('mysql')
        ),
        array('%s', '%s', '%d', '%s', '%s', '%s')
    );
    
    if ($result === false) {
        wp_send_json_error('Erreur lors de l\'enregistrement');
    } else {
        wp_send_json_success('Avis enregistré avec succès');
    }
}

add_action('wp_ajax_submit_newsaiige_review', 'handle_submit_newsaiige_review');
add_action('wp_ajax_nopriv_submit_newsaiige_review', 'handle_submit_newsaiige_review');

// Handler AJAX pour récupérer les avis
function handle_get_newsaiige_reviews() {
    global $wpdb;
    
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $table_name = $wpdb->prefix . 'newsaiige_reviews';
    
    // Vérifier si la table existe
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        wp_send_json_error('Table des avis non trouvée');
        return;
    }
    
    // Récupérer les avis approuvés
    $reviews = $wpdb->get_results($wpdb->prepare(
        "SELECT customer_name, comment, rating, created_at 
         FROM $table_name 
         WHERE status = 'approved' 
         ORDER BY created_at DESC 
         LIMIT %d",
        $limit
    ));
    
    // Statistiques
    $stats = $wpdb->get_row(
        "SELECT 
            COUNT(*) as total_reviews,
            AVG(rating) as average_rating
         FROM $table_name 
         WHERE status = 'approved'"
    );
    
    wp_send_json_success(array(
        'reviews' => $reviews ? $reviews : array(),
        'stats' => $stats ? $stats : (object)array('total_reviews' => 0, 'average_rating' => 5.0)
    ));
}

add_action('wp_ajax_get_newsaiige_reviews', 'handle_get_newsaiige_reviews');
add_action('wp_ajax_nopriv_get_newsaiige_reviews', 'handle_get_newsaiige_reviews');

// Fonction d'activation du plugin
function newsaiige_reviews_activate() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'newsaiige_reviews';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id int(11) NOT NULL AUTO_INCREMENT,
        customer_name varchar(255) NOT NULL,
        customer_email varchar(255) DEFAULT '',
        rating int(1) NOT NULL,
        comment text NOT NULL,
        status varchar(20) DEFAULT 'pending',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY status_created (status, created_at),
        KEY rating_idx (rating)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Insérer des données de test si la table est vide
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    if ($count == 0) {
        $test_reviews = array(
            array('Marie L.', 'marie@example.com', 5, 'J\'ai récemment testé l\'huile corps nacrée de Newsaiige, et je dois dire que c\'est une belle découverte ! Sa texture légère et non grasse s\'applique facilement et laisse la peau douce, hydratée et délicatement parfumée.', 'approved'),
            array('Sophie D.', 'sophie@example.com', 5, 'Produit exceptionnel ! Après plusieurs semaines d\'utilisation, ma peau est visiblement plus ferme et éclatante. L\'effet nacré est subtil et très élégant. Je recommande vivement !', 'approved'),
            array('Amélie R.', 'amelie@example.com', 5, 'Une huile de qualité premium ! La texture est divine, l\'absorption rapide et le parfum délicat. Mes clientes adorent et moi aussi. Un incontournable pour mes soins.', 'approved'),
            array('Camille B.', 'camille@example.com', 5, 'Enfin une huile qui tient ses promesses ! Ma peau n\'a jamais été aussi douce et lumineuse. L\'effet hydratant dure toute la journée. Un vrai coup de cœur !', 'approved'),
            array('Laura M.', 'laura@example.com', 5, 'Je suis esthéticienne et j\'utilise cette huile dans mes soins. Les résultats sont bluffants ! Mes clientes me demandent constamment quel produit j\'utilise.', 'approved'),
            array('Emma F.', 'emma@example.com', 5, 'Texture incroyable, parfum subtil et résultats visibles dès les premières applications. Cette huile a transformé ma routine beauté. Je ne peux plus m\'en passer !', 'approved')
        );
        
        foreach ($test_reviews as $review) {
            $wpdb->insert(
                $table_name,
                array(
                    'customer_name' => $review[0],
                    'customer_email' => $review[1],
                    'rating' => $review[2],
                    'comment' => $review[3],
                    'status' => $review[4],
                    'created_at' => current_time('mysql')
                ),
                array('%s', '%s', '%d', '%s', '%s', '%s')
            );
        }
    }
}

register_activation_hook(__FILE__, 'newsaiige_reviews_activate');
?>

