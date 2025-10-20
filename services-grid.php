<?php
function newsaiige_services_grid_shortcode($atts) {
    $atts = shortcode_atts(array(
        'title' => 'Les Soins',
        'subtitle' => 'Venez passer un moment de détente au sein de notre institut Newsaiige, le 1er institut dédié à la Madérothérapie Corps et Visage'
    ), $atts);
    
    ob_start();
    ?>

    <style>
    .newsaiige-services-section {
        padding: 80px 20px;
        font-family: 'Montserrat', sans-serif;
        background: #f8f9fa;
        min-height: 100vh;
    }

    .services-container {
        max-width: 1400px;
        margin: 0 auto;
    }

    /* SECTION TITRE */
    .services-header {
        margin-bottom: 60px;
    }

    .services-title {
        font-size: 3.2rem;
        font-weight: 700;
        color: #82897F;
        margin: 0 0 30px 0;
        text-align: left;
        line-height: 1.2;
    }

    .services-subtitle {
        font-size: 1.1rem;
        color: #000;
        font-weight: 400;
        line-height: 1.6;
        max-width: 600px;
        margin: 0;
    }

    /* GRILLE SERVICES */
    .services-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 30px;
        margin-top: 50px;
    }

    .service-card {
        border-radius: 30px;
        overflow: hidden;
        position: relative;
        transition: all 0.4s ease;
        cursor: pointer;
        height: 450px;
        text-decoration: none;
        color: inherit;
    }

    .first-card{
        background: #A79683;
        color: #fff;
        font-family: 'Montserrat', sans-serif;
    }

    .second-card{
        background: #E9E2DD;
        color: #444444;
        font-family: 'Montserrat', sans-serif;
    }

    .service-card:hover {
        transform: translateY(-10px) scale(1.02);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        text-decoration: none;
        color: #fff;
    }

    .service-image {
        height: 60%;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        position: relative;
    }

    .service-info {
        padding: 25px 20px;
        height: 40%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-align: center;
    }

    .service-name {
        font-size: 1.4rem;
        font-weight: 700;
        margin: 0 0 8px 0;
        line-height: 1.3;
    }

    .service-duration-price {
        font-size: 1.8rem;
        font-weight: 700;
        margin: 0 0 15px 0;
        font-style: italic;
    }

    .service-description {
        font-size: 0.95rem;
        font-weight: 400;
        line-height: 1.4;
        margin: 0;
    }

    /* BOUTON DÉCOUVRIR */
    .services-cta {
        text-align: center;
        margin-top: 60px;
    }

    .discover-button {
        display: inline-block;
        background: #000;
        color: white;
        padding: 15px 40px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }

    .discover-button:hover {
        background: #333;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        text-decoration: none;
        color: white;
    }

    /* Responsive */
    @media (max-width: 1200px) {
        .services-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }
    }

    @media (max-width: 900px) {
        .services-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .service-card {
            height: 400px;
        }

        .services-title {
            font-size: 2.5rem;
        }
    }

    @media (max-width: 600px) {
        .services-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .service-card {
            height: 350px;
        }

        .services-title {
            font-size: 2rem;
            text-align: center;
        }

        .services-subtitle {
            text-align: center;
            margin: 0 auto;
        }

        .newsaiige-services-section {
            padding: 40px 15px;
        }
    }

    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .service-card {
        animation: fadeInUp 0.6s ease;
    }

    .service-card:nth-child(1) { animation-delay: 0.1s; }
    .service-card:nth-child(2) { animation-delay: 0.2s; }
    .service-card:nth-child(3) { animation-delay: 0.3s; }
    .service-card:nth-child(4) { animation-delay: 0.4s; }
    </style>

    <div class="newsaiige-services-section">
        <div class="services-container">
            <div class="services-header">
                <h2 class="services-title"><?php echo esc_html($atts['title']); ?></h2>
                <p class="services-subtitle"><?php echo esc_html($atts['subtitle']); ?></p>
            </div>

            <div class="services-grid">
                <?php
                // Services avec leurs informations
                $services = array(
                    array(
                        'name' => 'La Madérothérapie',
                        'duration_price' => '50min / 65€',
                        'description' => 'Le Massage Anti-Cellulite n°1',
                        'image' => 'http://newsaiige.com/wp-content/uploads/2025/10/maderotherapie.jpg',
                        'url' => 'https://www.planity.com/maderotherapie-newsaiige-83130-la-garde/reservation',
                        'alt' => 'Séance de madérothérapie avec outils en bois',
                        'style' => 'first-card'
                    ),
                    array(
                        'name' => 'La Madérothérapie Énergétique',
                        'duration_price' => '50min / 70€',
                        'description' => 'Le soin drainant et détoxifiant.',
                        'image' => 'http://newsaiige.com/wp-content/uploads/2025/10/madero_energie.jpg',
                        'url' => 'https://www.planity.com/maderotherapie-newsaiige-83130-la-garde/reservation',
                        'alt' => 'Soin énergétique avec huiles essentielles',
                        'style' => 'second-card'
                    ),
                    array(
                        'name' => 'La Face Madéro',
                        'duration_price' => '50min / 65€',
                        'description' => 'Un visage aux mille éclats !',
                        'image' => 'http://newsaiige.com/wp-content/uploads/2025/10/face_medero-scaled.jpg',
                        'url' => 'https://www.planity.com/maderotherapie-newsaiige-83130-la-garde/reservation',
                        'alt' => 'Soin du visage avec outils de madérothérapie',
                        'style' => 'first-card'
                    ),
                    array(
                        'name' => 'La Nutrition Comportementale',
                        'duration_price' => '50min / 70€',
                        'description' => 'Une approche sans régime !',
                        'image' => 'http://newsaiige.com/wp-content/uploads/2025/10/nutrition_comportementale.jpg',
                        'url' => 'https://www.planity.com/maderotherapie-newsaiige-83130-la-garde/reservation',
                        'alt' => 'Consultation en nutrition comportementale',
                        'style' => 'second-card'
                    )
                );

                foreach ($services as $service) {
                    // Image par défaut si pas d'image spécifiée
                    $service_image = !empty($service['image']) ? $service['image'] : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgdmlld0JveD0iMCAwIDQwMCAzMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSI0MDAiIGhlaWdodD0iMzAwIiBmaWxsPSIjQzhCNUEwIi8+CjxyZWN0IHg9IjE1MCIgeT0iMTAwIiB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iIzgyODk3RiIgZmlsbC1vcGFjaXR5PSIwLjMiLz4KPHRleHQgeD0iMjAwIiB5PSIyNDAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IiM4Mjg5N0YiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZm9udC13ZWlnaHQ9IjYwMCI+U2VydmljZSBOZXdzYWlpZ2U8L3RleHQ+Cjwvc3ZnPgo=';

                    echo '
                    <a href="' . esc_url($service['url']) . '" class="service-card ' . esc_attr($service['style']) . '">
                        <div class="service-image" style="background-image: url(' . esc_url($service_image) . ');" aria-label="' . esc_attr($service['alt']) . '"></div>
                        <div class="service-info">
                            <h3 class="service-name">' . esc_html($service['name']) . '</h3>
                            <p class="service-duration-price">' . esc_html($service['duration_price']) . '</p>
                            <p class="service-description">' . esc_html($service['description']) . '</p>
                        </div>
                    </a>';
                }
                ?>
            </div>

            <div class="services-cta">
                <a href="/soins/" class="discover-button">Découvrir</a>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion des clics sur les cartes de service
        const serviceCards = document.querySelectorAll('.service-card');
        
        serviceCards.forEach(card => {
            card.addEventListener('click', function(e) {
                // Effet de clic
                this.style.transform = 'scale(0.98)';
                
                setTimeout(() => {
                    // Navigation vers la page
                    const url = this.getAttribute('href');
                    if (url && url !== '#') {
                        window.location.href = url;
                    }
                }, 150);
            });

            // Animation au hover améliorée
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
            });

            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Gestion du bouton Découvrir
        const discoverButton = document.querySelector('.discover-button');
        if (discoverButton) {
            discoverButton.addEventListener('click', function(e) {
                // Effet de clic
                this.style.transform = 'translateY(0) scale(0.98)';
                
                setTimeout(() => {
                    this.style.transform = 'translateY(-2px) scale(1)';
                }, 150);
            });
        }
    });
    </script>

    <?php
    return ob_get_clean();
}

add_shortcode('newsaiige_services_grid', 'newsaiige_services_grid_shortcode');
?>