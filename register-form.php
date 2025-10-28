<?php
function newsaiige_register_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'redirect_url' => home_url(),
        'login_url' => 'https://newsaiige.com/connexion/'
    ), $atts);
    
    // Si l'utilisateur est déjà connecté, rediriger
    if (is_user_logged_in()) {
        wp_redirect($atts['redirect_url']);
        exit;
    }
    
    // Traitement du formulaire d'inscription
    $register_error = '';
    $register_success = '';
    
    if (isset($_POST['newsaiige_register_submit'])) {
        // Vérification du nonce
        if (!wp_verify_nonce($_POST['newsaiige_register_nonce'], 'newsaiige_register_nonce')) {
            $register_error = 'Erreur de sécurité. Veuillez réessayer.';
        } else {
            $full_name = sanitize_text_field($_POST['full_name']);
            $email = sanitize_email($_POST['user_email']);
            $password = $_POST['user_password'];
            
            // Validation des champs
            if (empty($full_name) || empty($email) || empty($password)) {
                $register_error = 'Veuillez remplir tous les champs.';
            } elseif (!is_email($email)) {
                $register_error = 'Adresse e-mail invalide.';
            } elseif (email_exists($email)) {
                $register_error = 'Cette adresse e-mail est déjà utilisée.';
            } elseif (strlen($password) < 6) {
                $register_error = 'Le mot de passe doit contenir au moins 6 caractères.';
            } else {
                // Créer l'utilisateur
                $username = sanitize_user(strtolower(str_replace(' ', '', $full_name)) . '_' . rand(1000, 9999));
                
                // Assurer l'unicité du nom d'utilisateur
                $base_username = $username;
                $counter = 1;
                while (username_exists($username)) {
                    $username = $base_username . '_' . $counter;
                    $counter++;
                }
                
                $user_data = array(
                    'user_login' => $username,
                    'user_email' => $email,
                    'user_pass' => $password,
                    'display_name' => $full_name,
                    'first_name' => explode(' ', $full_name)[0],
                    'last_name' => isset(explode(' ', $full_name)[1]) ? implode(' ', array_slice(explode(' ', $full_name), 1)) : '',
                    'role' => 'customer'
                );
                
                $user_id = wp_insert_user($user_data);
                
                if (is_wp_error($user_id)) {
                    $register_error = 'Erreur lors de la création du compte : ' . $user_id->get_error_message();
                } else {
                    // Connexion automatique après inscription
                    wp_set_current_user($user_id);
                    wp_set_auth_cookie($user_id);
                    
                    // Envoyer un e-mail de bienvenue (optionnel)
                    wp_new_user_notification($user_id, null, 'user');
                    
                    wp_redirect($atts['redirect_url']);
                    exit;
                }
            }
        }
    }
    
    ob_start();
    ?>
    
    <style>
    .newsaiige-auth-container {
        margin: 0 auto;
        padding: 60px 40px 0px 40px;
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
        margin-bottom: 20px;
        text-transform: uppercase;
    }

    .newsaiige-auth-title {
        font-size: 32px;
        font-weight: 700;
        color: #333;
        margin-bottom: 15px;
        letter-spacing: 1px;
    }

    .newsaiige-auth-subtitle {
        color: #666;
        font-size: 16px;
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
        padding: 20px 25px !important;
        border: 2px solid #e9ecef;
        border-radius: 50px !important;
        font-family: 'Montserrat', sans-serif;
        font-size: 16px;
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

    .newsaiige-password-strength {
        margin-top: 10px;
        font-size: 12px;
        padding: 8px 15px;
        border-radius: 20px;
        display: none;
    }

    .newsaiige-password-strength.weak {
        background: #fee;
        color: #c33;
        display: block;
    }

    .newsaiige-password-strength.medium {
        background: #ffc;
        color: #cc6600;
        display: block;
    }

    .newsaiige-password-strength.strong {
        background: #efe;
        color: #393;
        display: block;
    }

    .newsaiige-submit-btn {
        width: 100%;
        padding: 20px;
        background: #82897F;
        color: white;
        border: none;
        border-radius: 50px;
        font-family: 'Montserrat', sans-serif;
        font-weight: 600;
        font-size: 18px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .newsaiige-submit-btn:hover {
        background: #6d7569;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(130, 137, 127, 0.3);
    }

    .newsaiige-submit-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
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
        padding: 18px;
        border: 2px solid #e9ecef;
        border-radius: 50px;
        font-family: 'Montserrat', sans-serif;
        font-weight: 600;
        font-size: 16px;
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
            <h2 class="newsaiige-auth-title">S'INSCRIRE</h2>
            <p class="newsaiige-auth-subtitle">
                Déjà membre ? <a href="<?php echo esc_url($atts['login_url']); ?>">Se connecter</a>
            </p>
        </div>

        <?php if (!empty($register_error)): ?>
            <div class="newsaiige-error-message"><?php echo esc_html($register_error); ?></div>
        <?php endif; ?>

        <?php if (!empty($register_success)): ?>
            <div class="newsaiige-success-message"><?php echo esc_html($register_success); ?></div>
        <?php endif; ?>

        <form class="newsaiige-auth-form" method="post" action="">
            <?php wp_nonce_field('newsaiige_register_nonce', 'newsaiige_register_nonce'); ?>
            
            <div class="newsaiige-form-group">
                <input type="text" 
                       name="full_name" 
                       class="newsaiige-form-input" 
                       placeholder="Nom Complet" 
                       value="<?php echo isset($_POST['full_name']) ? esc_attr($_POST['full_name']) : ''; ?>"
                       required>
            </div>

            <div class="newsaiige-form-group">
                <input type="email" 
                       name="user_email" 
                       class="newsaiige-form-input" 
                       placeholder="E-mail" 
                       value="<?php echo isset($_POST['user_email']) ? esc_attr($_POST['user_email']) : ''; ?>"
                       required>
            </div>

            <div class="newsaiige-form-group">
                <input type="password" 
                       name="user_password" 
                       id="user_password"
                       class="newsaiige-form-input" 
                       placeholder="Mot de passe" 
                       required>
                <div class="newsaiige-password-strength" id="password-strength"></div>
            </div>

            <button type="submit" name="newsaiige_register_submit" class="newsaiige-submit-btn" id="register-btn">
                S'inscrire
            </button>
        </form>

        <div class="newsaiige-divider">
            <hr></hr>OU<hr></hr>
        </div>

        <div class="newsaiige-social-login">
            <a href="<?php echo wp_nonce_url(add_query_arg('action', 'google_register', admin_url('admin-ajax.php')), 'google_register_nonce'); ?>" 
               class="newsaiige-social-btn google">
                <svg width="20" height="20" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                S'inscrire avec Google
            </a>

            <a href="<?php echo wp_nonce_url(add_query_arg('action', 'facebook_register', admin_url('admin-ajax.php')), 'facebook_register_nonce'); ?>" 
               class="newsaiige-social-btn facebook">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                S'inscrire avec Facebook
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

        // Validation en temps réel du mot de passe
        const passwordInput = document.getElementById('user_password');
        const strengthIndicator = document.getElementById('password-strength');
        const registerBtn = document.getElementById('register-btn');

        function checkPasswordStrength(password) {
            let strength = 0;
            let feedback = '';

            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;

            switch (strength) {
                case 0:
                case 1:
                    strengthIndicator.className = 'newsaiige-password-strength weak';
                    feedback = 'Mot de passe faible - Utilisez au moins 6 caractères';
                    break;
                case 2:
                case 3:
                    strengthIndicator.className = 'newsaiige-password-strength medium';
                    feedback = 'Mot de passe moyen - Ajoutez des majuscules et des chiffres';
                    break;
                case 4:
                case 5:
                    strengthIndicator.className = 'newsaiige-password-strength strong';
                    feedback = 'Mot de passe fort - Excellent !';
                    break;
            }

            strengthIndicator.textContent = feedback;
            return strength >= 2;
        }

        function validatePassword() {
            const password = passwordInput.value;
            const isPasswordStrong = checkPasswordStrength(password);
            
            // Activer/désactiver le bouton
            registerBtn.disabled = !(isPasswordStrong && password.length >= 6);
        }

        if (passwordInput) {
            passwordInput.addEventListener('input', validatePassword);
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
            errorDiv.textContent = 'Erreur lors de l\'inscription sociale. Veuillez réessayer.';
            
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

add_shortcode('newsaiige_register_form', 'newsaiige_register_form_shortcode');

// Handlers AJAX pour les inscriptions sociales
add_action('wp_ajax_nopriv_google_register', 'newsaiige_handle_google_register');
add_action('wp_ajax_google_register', 'newsaiige_handle_google_register');

add_action('wp_ajax_nopriv_facebook_register', 'newsaiige_handle_facebook_register');
add_action('wp_ajax_facebook_register', 'newsaiige_handle_facebook_register');

function newsaiige_handle_google_register() {
    if (!wp_verify_nonce($_GET['_wpnonce'], 'google_register_nonce')) {
        wp_redirect(add_query_arg('social_error', '1', wp_registration_url()));
        exit;
    }
    
    // Redirection vers Google OAuth pour inscription
    $google_client_id = get_option('newsaiige_google_client_id', '');
    $redirect_uri = admin_url('admin-ajax.php?action=google_register_callback');
    
    if (empty($google_client_id)) {
        wp_redirect(add_query_arg('social_error', '1', wp_registration_url()));
        exit;
    }
    
    $google_url = "https://accounts.google.com/o/oauth2/auth?" . http_build_query([
        'client_id' => $google_client_id,
        'redirect_uri' => $redirect_uri,
        'scope' => 'email profile',
        'response_type' => 'code',
        'state' => wp_create_nonce('google_register_oauth_state')
    ]);
    
    wp_redirect($google_url);
    exit;
}

function newsaiige_handle_facebook_register() {
    if (!wp_verify_nonce($_GET['_wpnonce'], 'facebook_register_nonce')) {
        wp_redirect(add_query_arg('social_error', '1', wp_registration_url()));
        exit;
    }
    
    // Redirection vers Facebook OAuth pour inscription
    $facebook_app_id = get_option('newsaiige_facebook_app_id', '');
    $redirect_uri = admin_url('admin-ajax.php?action=facebook_register_callback');
    
    if (empty($facebook_app_id)) {
        wp_redirect(add_query_arg('social_error', '1', wp_registration_url()));
        exit;
    }
    
    $facebook_url = "https://www.facebook.com/v18.0/dialog/oauth?" . http_build_query([
        'client_id' => $facebook_app_id,
        'redirect_uri' => $redirect_uri,
        'scope' => 'email',
        'response_type' => 'code',
        'state' => wp_create_nonce('facebook_register_oauth_state')
    ]);
    
    wp_redirect($facebook_url);
    exit;
}
?>