function newsaiige_reviews_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit' => 10,
        'show_form' => true
    ), $atts);
    
    // Récupérer les avis directement depuis la base de données
    global $wpdb;
    $table_name = $wpdb->prefix . 'newsaiige_reviews';
    
    // Récupérer les avis approuvés
    $reviews = $wpdb->get_results($wpdb->prepare(
        "SELECT customer_name, comment, rating, created_at 
         FROM $table_name 
         WHERE status = 'approved' 
         ORDER BY created_at DESC 
         LIMIT %d",
        intval($atts['limit'])
    ));
    
    // Statistiques
    $stats = $wpdb->get_row(
        "SELECT 
            COUNT(*) as total_reviews,
            AVG(rating) as average_rating
         FROM $table_name 
         WHERE status = 'approved'"
    );
    
    // Si pas de données, utiliser des valeurs par défaut
    if (!$reviews || empty($reviews)) {
        $reviews = array(
            (object)array('customer_name' => 'Marie L.', 'comment' => 'J\'ai récemment testé l\'huile corps nacrée de Newsaiige, et je dois dire que c\'est une belle découverte ! Sa texture légère et non grasse s\'applique facilement et laisse la peau douce, hydratée et délicatement parfumée.', 'rating' => 5),
            (object)array('customer_name' => 'Sophie D.', 'comment' => 'Produit exceptionnel ! Après plusieurs semaines d\'utilisation, ma peau est visiblement plus ferme et éclatante. L\'effet nacré est subtil et très élégant. Je recommande vivement !', 'rating' => 5),
            (object)array('customer_name' => 'Amélie R.', 'comment' => 'Une huile de qualité premium ! La texture est divine, l\'absorption rapide et le parfum délicat. Mes clientes adorent et moi aussi. Un incontournable pour mes soins.', 'rating' => 5),
            (object)array('customer_name' => 'Camille B.', 'comment' => 'Enfin une huile qui tient ses promesses ! Ma peau n\'a jamais été aussi douce et lumineuse. L\'effet hydratant dure toute la journée. Un vrai coup de cœur !', 'rating' => 5),
            (object)array('customer_name' => 'Laura M.', 'comment' => 'Je suis esthéticienne et j\'utilise cette huile dans mes soins. Les résultats sont bluffants ! Mes clientes me demandent constamment quel produit j\'utilise.', 'rating' => 5),
            (object)array('customer_name' => 'Emma F.', 'comment' => 'Texture incroyable, parfum subtil et résultats visibles dès les premières applications. Cette huile a transformé ma routine beauté. Je ne peux plus m\'en passer !', 'rating' => 5)
        );
    }
    
    if (!$stats) {
        $stats = (object)array('total_reviews' => count($reviews), 'average_rating' => 5.0);
    }
    
    // Enqueue les scripts nécessaires seulement si le formulaire est activé
    if ($atts['show_form']) {
        wp_enqueue_script('newsaiige-reviews-js', '', array('jquery'), '1.0', true);
        wp_add_inline_script('newsaiige-reviews-js', '
            const newsaiige_ajax = {
                ajax_url: "' . admin_url('admin-ajax.php') . '",
                nonce: "' . wp_create_nonce('newsaiige_review_nonce') . '"
            };
        ');
    }
    
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
        max-width: 1200px;
        margin: 0 auto;
        overflow: hidden;
        padding: 12px 0px;
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
        width: calc(33.333% - 20px);
        max-width: 380px;
        min-width: 280px;
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
            width: calc(100% - 40px) !important;
            min-width: 280px;
            max-width: 400px;
            padding: 30px 20px;
        }
        
        .modal-content {
            padding: 40px 30px;
        }
        
        .carousel-track {
            gap: 20px;
            justify-content: center;
        }
    }

    @media (max-width: 480px) {
        .review-card {
            width: calc(100% - 20px) !important;
            min-width: 260px;
            max-width: 350px;
        }
        
        .modal-content {
            padding: 30px 20px;
        }
        
        .reviews-title {
            font-size: 1.8rem;
        }
        
        .carousel-track {
            gap: 15px;
        }
    }
    </style>

    <div class="newsaiige-reviews">
        <div class="reviews-header">
            <h2 class="reviews-title">Elles aiment NewSaiige !</h2>
            <div class="reviews-rating">
                <span class="rating-score"><?php echo number_format($stats->average_rating, 1, ',', ''); ?></span>
                <div class="rating-stars">
                    <span class="star">★</span>
                    <span class="star">★</span>
                    <span class="star">★</span>
                    <span class="star">★</span>
                    <span class="star">★</span>
                </div>
                <span class="rating-count">(<?php echo $stats->total_reviews; ?>)</span>
            </div>
        </div>
        
        <div class="carousel-container">
            <div class="carousel-track" id="carouselTrack">
                <?php foreach ($reviews as $review): ?>
                <div class="review-card">
                    <div class="review-text">
                        "<?php echo esc_html($review->comment); ?>"
                    </div>
                    <div class="review-author">- <?php echo esc_html($review->customer_name); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="carousel-controls">
            <button class="carousel-btn" id="prevBtn">‹</button>
            <span class="carousel-pagination">
                <span id="currentSlide">1</span> / <span id="totalSlides"><?php echo ceil(count($reviews) / 3); ?></span>
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
    // Variables globales du carousel
    let currentIndex = 0;
    let totalSlides = <?php echo count($reviews); ?>;

    // Fonction d'initialisation du carousel
    function initializeCarousel() {
        const track = document.getElementById('carouselTrack');
        const cards = track.querySelectorAll('.review-card');
        totalSlides = cards.length;
        
        if (totalSlides === 0) return;
        
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
            const containerWidth = track.parentElement.offsetWidth;
            const cardsPerGroup = window.innerWidth <= 768 ? 1 : 3; // 1 carte sur mobile, 3 sur desktop
            const currentGroup = Math.floor(currentIndex / cardsPerGroup);
            
            // Calcul de la largeur optimale pour les cartes
            const gapBetweenCards = window.innerWidth <= 480 ? 15 : 30;
            const totalGaps = Math.max(0, (cardsPerGroup - 1) * gapBetweenCards);
            const containerPadding = 40; // padding des côtés
            const availableWidth = Math.min(containerWidth - containerPadding, 1200);
            
            let cardWidth;
            if (cardsPerGroup === 1) {
                // Sur mobile : largeur maximale avec marges
                cardWidth = Math.min(availableWidth, 400);
            } else {
                // Sur desktop : largeur calculée pour 3 cartes
                cardWidth = (availableWidth - totalGaps) / cardsPerGroup;
                cardWidth = Math.max(280, Math.min(cardWidth, 380)); // Min 280px, Max 380px
            }
            
            // Appliquer la largeur aux cartes
            cards.forEach(card => {
                card.style.width = `${cardWidth}px`;
                card.style.flexShrink = '0';
            });
            
            // Centrage du groupe de cartes
            let groupWidth, offset;
            if (cardsPerGroup === 1) {
                // Centrer la carte unique
                offset = (containerWidth - cardWidth) / 2;
                const startCardIndex = currentGroup * cardsPerGroup;
                offset = offset - (startCardIndex * (cardWidth + gapBetweenCards));
            } else {
                // Centrer le groupe de 3 cartes
                groupWidth = (cardWidth * cardsPerGroup) + totalGaps;
                const startOffset = (containerWidth - groupWidth) / 2;
                const startCardIndex = currentGroup * cardsPerGroup;
                offset = startOffset - (startCardIndex * (cardWidth + gapBetweenCards));
            }
            
            track.style.transform = `translateX(${offset}px)`;
            
            // Update pagination
            const totalGroups = Math.ceil(totalSlides / cardsPerGroup);
            if (currentSlideSpan) currentSlideSpan.textContent = currentGroup + 1;
            if (totalSlidesSpan) totalSlidesSpan.textContent = totalGroups;
            
            // Update button states
            if (prevBtn) prevBtn.disabled = currentGroup === 0;
            if (nextBtn) nextBtn.disabled = currentGroup >= totalGroups - 1;
        }

        function nextSlide() {
            const cardsPerGroup = window.innerWidth <= 768 ? 1 : 3;
            const totalGroups = Math.ceil(totalSlides / cardsPerGroup);
            const currentGroup = Math.floor(currentIndex / cardsPerGroup);
            
            if (currentGroup < totalGroups - 1) {
                currentIndex = (currentGroup + 1) * cardsPerGroup;
                updateCarousel();
            }
        }

        function prevSlide() {
            const cardsPerGroup = window.innerWidth <= 768 ? 1 : 3;
            const currentGroup = Math.floor(currentIndex / cardsPerGroup);
            
            if (currentGroup > 0) {
                currentIndex = (currentGroup - 1) * cardsPerGroup;
                updateCarousel();
            }
        }

        // Event listeners
        if (nextBtn) nextBtn.addEventListener('click', nextSlide);
        if (prevBtn) prevBtn.addEventListener('click', prevSlide);

        // Auto-scroll every 5 seconds
        setInterval(() => {
            const cardsPerGroup = window.innerWidth <= 768 ? 1 : 3;
            const totalGroups = Math.ceil(totalSlides / cardsPerGroup);
            const currentGroup = Math.floor(currentIndex / cardsPerGroup);
            
            if (currentGroup >= totalGroups - 1) {
                currentIndex = 0;
            } else {
                currentIndex = (currentGroup + 1) * cardsPerGroup;
            }
            updateCarousel();
        }, 5000);

        // Update on window resize
        window.addEventListener('resize', updateCarousel);

        // Initialize
        updateCarousel();
    }

    <?php if ($atts['show_form']): ?>
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
    
    function initializeStars() {
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

        const ratingInput = document.getElementById('ratingInput');
        if (ratingInput) {
            ratingInput.addEventListener('mouseleave', function() {
                updateStars();
            });
        }
    }

    function highlightStars(rating) {
        const ratingStars = document.querySelectorAll('.rating-star');
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

    // Form submission (utilise les handlers existants de functions.php)
    function initializeForm() {
        const form = document.getElementById('reviewForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (selectedRating === 0) {
                    alert('Veuillez sélectionner une note !');
                    return;
                }
                
                // Utiliser les handlers AJAX existants de functions.php
                const formData = new FormData();
                formData.append('action', 'submit_newsaiige_review');
                formData.append('customer_name', this.querySelector('input[name="customer_name"]').value);
                formData.append('customer_email', this.querySelector('input[name="customer_email"]')?.value || '');
                formData.append('rating', selectedRating);
                formData.append('comment', this.querySelector('textarea[name="comment"]').value);
                formData.append('nonce', newsaiige_ajax.nonce);
                
                const submitBtn = this.querySelector('.submit-btn');
                const originalText = submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Envoi en cours...';
                
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
                        // Recharger la page pour voir les nouveaux avis
                        location.reload();
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
        }
    }

    // Close modal when clicking outside
    function initializeModal() {
        const modal = document.getElementById('reviewModal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal();
                }
            });
        }
    }
    <?php endif; ?>

    // Initialisation au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        initializeCarousel();
        <?php if ($atts['show_form']): ?>
        initializeStars();
        initializeForm();
        initializeModal();
        <?php endif; ?>
    });
    </script>
    <?php
        return ob_get_clean();
}

add_shortcode('newsaiige_reviews', 'newsaiige_reviews_shortcode');
?>