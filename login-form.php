<?php
function newsaiige_login_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'redirect_url' => home_url(),
        'register_url' => 'https://newsaiige.com/inscription/',
        'lost_password_url' => 'https://newsaiige.com/mot-de-passe-oublie/'
    ), $atts);
    
    // // Si l'utilisateur est déjà connecté, rediriger
    // if (is_user_logged_in()) {
    //     wp_redirect($atts['redirect_url']);
    //     exit;
    // }
    
    // Traitement du formulaire de connexion
    $login_error = '';
    $login_success = '';
    
    if (isset($_POST['newsaiige_login_submit'])) {
        $username = sanitize_user($_POST['log']);
        $password = $_POST['pwd'];
        $remember = isset($_POST['rememberme']);
        
        if (empty($username) || empty($password)) {
            $login_error = 'Veuillez remplir tous les champs.';
        } else {
            $user = wp_authenticate($username, $password);
            
            if (is_wp_error($user)) {
                $login_error = 'Identifiants incorrects. Veuillez réessayer.';
            } else {
                wp_set_current_user($user->ID);
                wp_set_auth_cookie($user->ID, $remember);
                wp_redirect($atts['redirect_url']);
                exit;
            }
        }
    }
    
    ob_start();
    ?>
    
    <style>
    .newsaiige-auth-container {
        margin: 0 auto;
        padding: 60px 40px;
        backdrop-filter: blur(10px);
        font-family: 'Montserrat', sans-serif;
        position: relative;
    }

    .newsaiige-auth-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .newsaiige-auth-logo {
        font-size: 28px;
        font-weight: 700;
        color: #82897F;
        letter-spacing: 3px;
        text-transform: uppercase;
    }

    .newsaiige-auth-title {
        font-size: 28px;
        font-weight: 700;
        color: #333;
        margin-bottom: 15px;
        letter-spacing: 1px;
    }

    .newsaiige-auth-subtitle {
        color: #666;
        font-size: 14px;
        margin-bottom: 0;
    }

    .newsaiige-auth-subtitle a {
        color: #82897F;
        text-decoration: underline;
        transition: color 0.3s ease;
    }

    .newsaiige-auth-subtitle a:hover {
        color: #6d7569;
    }

    .newsaiige-auth-form {
        margin: 40px 0;
    }

    .newsaiige-form-group {
        margin-bottom: 25px;
        position: relative;
    }

    .newsaiige-form-input {
        width: 100%;
        padding: 10px 25px !important;
        border: 2px solid #e9ecef;
        border-radius: 30px !important;
        font-family: 'Montserrat', sans-serif;
        font-size: 14px;
        transition: all 0.3s ease;
        box-sizing: border-box;
        background: white;
    }

    .newsaiige-form-input:focus {
        outline: none;
        border-color: #82897F;
        box-shadow: 0 0 0 3px rgba(130, 137, 127, 0.1);
    }

    .newsaiige-form-input::placeholder {
        color: #999;
        font-weight: 400;
    }

    .newsaiige-forgot-password {
        margin-bottom: 30px;
    }

    .newsaiige-forgot-password a {
        color: #82897F;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: color 0.3s ease;
    }

    .newsaiige-forgot-password a:hover {
        color: #6d7569;
        text-decoration: underline;
    }

    .newsaiige-submit-btn {
        width: 100%;
        padding: 10px;
        background: #82897F;
        color: white;
        border: none;
        border-radius: 50px;
        font-family: 'Montserrat', sans-serif;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        letter-spacing: 1px;
    }

    .newsaiige-submit-btn:hover {
        background: #6d7569;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(130, 137, 127, 0.3);
    }

    .newsaiige-divider {
        display: flex;
        text-align: center;
        align-items: center;
        margin: 40px 0;
        width: 100%;
        justify-content: center;
        gap: 30px;
    }

    .newsaiige-divider hr {
        background: rgba(255, 255, 255, 0.95);
        width: 100%;
        padding: 0 20px;
        color: #666;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-size: 14px;
    }

    .newsaiige-social-login {
        margin-bottom: 30px;
    }

    .newsaiige-social-btn {
        width: 100%;
        padding: 10px;
        border: 2px solid #e9ecef;
        border-radius: 50px;
        font-family: 'Montserrat', sans-serif;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-bottom: 15px;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
    }

    .newsaiige-social-btn.google {
        background: white;
        color: #333;
        border-color: #ddd;
    }

    .newsaiige-social-btn.google:hover {
        border-color: #82897F;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .newsaiige-social-btn.facebook {
        background: #1877f2;
        color: white;
        border-color: #1877f2;
    }

    .newsaiige-social-btn.facebook:hover {
        background: #166fe5;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(24, 119, 242, 0.3);
    }

    .newsaiige-error-message {
        background: #fee;
        color: #c33;
        padding: 15px 20px;
        border-radius: 50px;
        margin-bottom: 20px;
        text-align: center;
        font-weight: 500;
        border: 2px solid #fcc;
    }

    .newsaiige-success-message {
        background: #efe;
        color: #393;
        padding: 15px 20px;
        border-radius: 50px;
        margin-bottom: 20px;
        text-align: center;
        font-weight: 500;
        border: 2px solid #cfc;
    }

    .newsaiige-return-link {
        color: #82897F;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s ease;
    }

    .newsaiige-return-link:hover {
        color: #6d7569;
    }

    .newsaiige-auth-top{
        display: flex;
        justify-content: space-between;
        margin-bottom: 30px;
        align-items: center;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .newsaiige-auth-container {
            margin: 20px;
            padding: 40px 30px;
        }

        .newsaiige-auth-logo {
            font-size: 24px;
        }

        .newsaiige-auth-title {
            font-size: 28px;
        }

        .newsaiige-form-input {
            padding: 18px 20px;
            font-size: 15px;
        }

        .newsaiige-submit-btn {
            padding: 18px;
            font-size: 16px;
        }
    }

    @media (max-width: 480px) {
        .newsaiige-auth-container {
            margin: 10px;
            padding: 30px 20px;
        }

        .newsaiige-social-btn {
            padding: 16px;
            font-size: 15px;
        }
    }
    </style>

    <div class="newsaiige-auth-container">
        <div class="newsaiige-auth-top">
            <div class="newsaiige-auth-logo">NEWSAIIGE</div>
            <a href="<?php echo esc_url(home_url()); ?>" class="newsaiige-return-link">
                Retour à la boutique
            </a>
        </div>
        
        <div class="newsaiige-auth-header">   
            <h2 class="newsaiige-auth-title">Se connecter</h2>
            <p class="newsaiige-auth-subtitle">
                Nouveau sur ce site ? <a href="<?php echo esc_url($atts['register_url']); ?>">S'inscrire</a>
            </p>
        </div>

        <?php if (!empty($login_error)): ?>
            <div class="newsaiige-error-message"><?php echo esc_html($login_error); ?></div>
        <?php endif; ?>

        <?php if (!empty($login_success)): ?>
            <div class="newsaiige-success-message"><?php echo esc_html($login_success); ?></div>
        <?php endif; ?>

        <form class="newsaiige-auth-form" method="post" action="">
            <?php wp_nonce_field('newsaiige_login_nonce', 'newsaiige_login_nonce'); ?>
            
            <div class="newsaiige-form-group">
                <input type="text" 
                       name="log" 
                       class="newsaiige-form-input" 
                       placeholder="E-mail" 
                       value="<?php echo isset($_POST['log']) ? esc_attr($_POST['log']) : ''; ?>"
                       required>
            </div>

            <div class="newsaiige-form-group">
                <input type="password" 
                       name="pwd" 
                       class="newsaiige-form-input" 
                       placeholder="Mot de passe" 
                       required>
            </div>

            <div class="newsaiige-forgot-password">
                <a href="<?php echo esc_url($atts['lost_password_url']); ?>">Mot de passe oublié ?</a>
            </div>

            <button type="submit" name="newsaiige_login_submit" class="newsaiige-submit-btn">
                Se connecter
            </button>
        </form>

        <div class="newsaiige-divider">
            <hr></hr>OU<hr></hr>
        </div>

        <div class="newsaiige-social-login">
            <a href="<?php echo wp_nonce_url(add_query_arg('action', 'google_login', admin_url('admin-ajax.php')), 'google_login_nonce'); ?>" 
               class="newsaiige-social-btn google">
                <svg width="20" height="20" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Se connecter avec Google
            </a>

            <a href="<?php echo wp_nonce_url(add_query_arg('action', 'facebook_login', admin_url('admin-ajax.php')), 'facebook_login_nonce'); ?>" 
               class="newsaiige-social-btn facebook">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                Se connecter avec Facebook
            </a>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animation d'entrée
        const container = document.querySelector('.newsaiige-auth-container');
        if (container) {
            container.style.opacity = '0';
            container.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                container.style.transition = 'all 0.6s ease';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
        }

        // Focus automatique sur le premier champ
        const firstInput = document.querySelector('.newsaiige-form-input');
        if (firstInput) {
            firstInput.focus();
        }

        // Gestion des erreurs de connexion sociale
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('social_error')) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'newsaiige-error-message';
            errorDiv.textContent = 'Erreur lors de la connexion sociale. Veuillez réessayer.';
            
            const form = document.querySelector('.newsaiige-auth-form');
            if (form) {
                form.parentNode.insertBefore(errorDiv, form);
            }
        }
    });
    </script>

    <?php
    return ob_get_clean();
}

add_shortcode('newsaiige_login_form', 'newsaiige_login_form_shortcode');

// Handlers AJAX pour les connexions sociales
add_action('wp_ajax_nopriv_google_login', 'newsaiige_handle_google_login');
add_action('wp_ajax_google_login', 'newsaiige_handle_google_login');

add_action('wp_ajax_nopriv_facebook_login', 'newsaiige_handle_facebook_login');
add_action('wp_ajax_facebook_login', 'newsaiige_handle_facebook_login');

function newsaiige_handle_google_login() {
    if (!wp_verify_nonce($_GET['_wpnonce'], 'google_login_nonce')) {
        wp_redirect(add_query_arg('social_error', '1', wp_login_url()));
        exit;
    }
    
    // Redirection vers Google OAuth
    // Nécessite la configuration d'un client OAuth Google
    $google_client_id = get_option('newsaiige_google_client_id', '');
    $redirect_uri = admin_url('admin-ajax.php?action=google_callback');
    
    if (empty($google_client_id)) {
        wp_redirect(add_query_arg('social_error', '1', wp_login_url()));
        exit;
    }
    
    $google_url = "https://accounts.google.com/o/oauth2/auth?" . http_build_query([
        'client_id' => $google_client_id,
        'redirect_uri' => $redirect_uri,
        'scope' => 'email profile',
        'response_type' => 'code',
        'state' => wp_create_nonce('google_oauth_state')
    ]);
    
    wp_redirect($google_url);
    exit;
}

function newsaiige_handle_facebook_login() {
    if (!wp_verify_nonce($_GET['_wpnonce'], 'facebook_login_nonce')) {
        wp_redirect(add_query_arg('social_error', '1', wp_login_url()));
        exit;
    }
    
    // Redirection vers Facebook OAuth
    // Nécessite la configuration d'une app Facebook
    $facebook_app_id = get_option('newsaiige_facebook_app_id', '');
    $redirect_uri = admin_url('admin-ajax.php?action=facebook_callback');
    
    if (empty($facebook_app_id)) {
        wp_redirect(add_query_arg('social_error', '1', wp_login_url()));
        exit;
    }
    
    $facebook_url = "https://www.facebook.com/v18.0/dialog/oauth?" . http_build_query([
        'client_id' => $facebook_app_id,
        'redirect_uri' => $redirect_uri,
        'scope' => 'email',
        'response_type' => 'code',
        'state' => wp_create_nonce('facebook_oauth_state')
    ]);
    
    wp_redirect($facebook_url);
    exit;
}

// Callbacks pour les connexions sociales (à implémenter selon vos besoins)
add_action('wp_ajax_nopriv_google_callback', 'newsaiige_google_callback');
add_action('wp_ajax_google_callback', 'newsaiige_google_callback');

add_action('wp_ajax_nopriv_facebook_callback', 'newsaiige_facebook_callback');
add_action('wp_ajax_facebook_callback', 'newsaiige_facebook_callback');

function newsaiige_google_callback() {
    // Traitement du retour de Google OAuth
    // À implémenter avec vos clés API Google
}

function newsaiige_facebook_callback() {
    // Traitement du retour de Facebook OAuth
    // À implémenter avec vos clés API Facebook
}
?>