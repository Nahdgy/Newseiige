<?php
function newsaiige_reset_password_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'login_url' => 'https://newsaiige.com/connexion/',
        'register_url' => 'https://newsaiige.com/inscription/'
    ), $atts);
    
    // Si l'utilisateur est déjà connecté, rediriger vers l'accueil
    if (is_user_logged_in()) {
        wp_redirect(home_url());
        exit;
    }
    
    // Traitement du formulaire de réinitialisation
    $reset_error = '';
    $reset_success = '';
    $step = 'request'; // 'request' ou 'reset'
    
    // Vérifier si nous sommes dans l'étape de réinitialisation avec clé
    if (isset($_GET['key']) && isset($_GET['login'])) {
        $step = 'reset';
        $reset_key = sanitize_text_field($_GET['key']);
        $user_login = sanitize_text_field($_GET['login']);
        
        // Vérifier la validité de la clé
        $user = check_password_reset_key($reset_key, $user_login);
        if (is_wp_error($user)) {
            $reset_error = 'Ce lien de réinitialisation n\'est pas valide ou a expiré.';
            $step = 'request';
        }
    }
    
    if (isset($_POST['newsaiige_reset_submit'])) {
        // Vérification du nonce
        if (!wp_verify_nonce($_POST['newsaiige_reset_nonce'], 'newsaiige_reset_nonce')) {
            $reset_error = 'Erreur de sécurité. Veuillez réessayer.';
        } else {
            if ($step === 'request') {
                // Étape 1 : Demande de réinitialisation
                $user_login = sanitize_text_field($_POST['user_login']);
                
                if (empty($user_login)) {
                    $reset_error = 'Veuillez saisir votre adresse e-mail.';
                } else {
                    // Vérifier si l'utilisateur existe
                    if (strpos($user_login, '@') !== false) {
                        $user = get_user_by('email', $user_login);
                    } else {
                        $user = get_user_by('login', $user_login);
                    }
                    
                    if (!$user) {
                        $reset_error = 'Aucun compte trouvé avec cette adresse e-mail.';
                    } else {
                        // Générer et envoyer le lien de réinitialisation
                        $reset_key = get_password_reset_key($user);
                        
                        if (is_wp_error($reset_key)) {
                            $reset_error = 'Erreur lors de la génération du lien de réinitialisation.';
                        } else {
                            // Construire l'URL de réinitialisation
                            $reset_url = add_query_arg(array(
                                'key' => $reset_key,
                                'login' => rawurlencode($user->user_login)
                            ), get_permalink());
                            
                            // Envoyer l'e-mail
                            $subject = 'Réinitialisation de votre mot de passe - Newsaiige';
                            $message = "Bonjour,\n\n";
                            $message .= "Vous avez demandé la réinitialisation de votre mot de passe pour votre compte Newsaiige.\n\n";
                            $message .= "Cliquez sur le lien suivant pour créer un nouveau mot de passe :\n";
                            $message .= $reset_url . "\n\n";
                            $message .= "Si vous n'avez pas demandé cette réinitialisation, ignorez simplement cet e-mail.\n\n";
                            $message .= "Ce lien expirera dans 24 heures.\n\n";
                            $message .= "Cordialement,\nL'équipe Newsaiige";
                            
                            $headers = array('Content-Type: text/plain; charset=UTF-8');
                            
                            if (wp_mail($user->user_email, $subject, $message, $headers)) {
                                $reset_success = 'Un lien de réinitialisation a été envoyé à votre adresse e-mail.';
                            } else {
                                $reset_error = 'Erreur lors de l\'envoi de l\'e-mail. Veuillez réessayer.';
                            }
                        }
                    }
                }
            } else {
                // Étape 2 : Nouveau mot de passe
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                if (empty($new_password) || empty($confirm_password)) {
                    $reset_error = 'Veuillez remplir tous les champs.';
                } elseif ($new_password !== $confirm_password) {
                    $reset_error = 'Les mots de passe ne correspondent pas.';
                } elseif (strlen($new_password) < 6) {
                    $reset_error = 'Le mot de passe doit contenir au moins 6 caractères.';
                } else {
                    // Réinitialiser le mot de passe
                    reset_password($user, $new_password);
                    
                    // Connecter automatiquement l'utilisateur
                    wp_set_current_user($user->ID);
                    wp_set_auth_cookie($user->ID);
                    
                    wp_redirect(home_url());
                    exit;
                }
            }
        }
    }
    
    ob_start();
    ?>
    
    <style>
    .newsaiige-auth-container {
        max-width: 500px;
        margin: 0 auto;
        padding: 60px 40px;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 25px;
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.1);
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
        font-size: 28px;
        font-weight: 700;
        color: #333;
        margin-bottom: 15px;
        letter-spacing: 1px;
        line-height: 1.2;
    }

    .newsaiige-auth-description {
        color: #666;
        font-size: 15px;
        line-height: 1.6;
        margin-bottom: 0;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
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
        padding: 20px 25px;
        border: 2px solid #e9ecef;
        border-radius: 50px;
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

    .newsaiige-back-links {
        text-align: center;
        margin-top: 30px;
        padding-top: 30px;
        border-top: 1px solid #e9ecef;
    }

    .newsaiige-back-links a {
        color: #82897F;
        text-decoration: none;
        font-weight: 500;
        margin: 0 15px;
        transition: color 0.3s ease;
    }

    .newsaiige-back-links a:hover {
        color: #6d7569;
        text-decoration: underline;
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
        position: absolute;
        top: 20px;
        right: 25px;
        color: #82897F;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s ease;
    }

    .newsaiige-return-link:hover {
        color: #6d7569;
    }

    .newsaiige-info-box {
        background: rgba(130, 137, 127, 0.1);
        border: 2px solid rgba(130, 137, 127, 0.2);
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 30px;
        text-align: center;
    }

    .newsaiige-info-box h4 {
        color: #82897F;
        margin: 0 0 10px 0;
        font-weight: 600;
    }

    .newsaiige-info-box p {
        color: #666;
        margin: 0;
        font-size: 14px;
        line-height: 1.5;
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
            font-size: 24px;
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

        .newsaiige-back-links a {
            display: block;
            margin: 10px 0;
        }
    }
    </style>

    <div class="newsaiige-auth-container">
        <a href="<?php echo esc_url(home_url()); ?>" class="newsaiige-return-link">
            Retour à la boutique
        </a>
        
        <div class="newsaiige-auth-header">
            <div class="newsaiige-auth-logo">NEWSAIIGE</div>
            <h2 class="newsaiige-auth-title">
                <?php echo $step === 'request' ? 'Réinitialiser le mot de passe' : 'Nouveau mot de passe'; ?>
            </h2>
            <?php if ($step === 'request'): ?>
                <p class="newsaiige-auth-description">
                    Saisissez votre adresse e-mail de connexion et nous vous enverrons un lien pour réinitialiser votre mot de passe
                </p>
            <?php else: ?>
                <p class="newsaiige-auth-description">
                    Créez un nouveau mot de passe sécurisé pour votre compte
                </p>
            <?php endif; ?>
        </div>

        <?php if (!empty($reset_error)): ?>
            <div class="newsaiige-error-message"><?php echo esc_html($reset_error); ?></div>
        <?php endif; ?>

        <?php if (!empty($reset_success)): ?>
            <div class="newsaiige-success-message"><?php echo esc_html($reset_success); ?></div>
            <div class="newsaiige-info-box">
                <h4>E-mail envoyé !</h4>
                <p>Vérifiez votre boîte de réception et cliquez sur le lien dans l'e-mail pour continuer.</p>
            </div>
        <?php else: ?>

        <form class="newsaiige-auth-form" method="post" action="">
            <?php wp_nonce_field('newsaiige_reset_nonce', 'newsaiige_reset_nonce'); ?>
            
            <?php if ($step === 'request'): ?>
                <div class="newsaiige-form-group">
                    <input type="email" 
                           name="user_login" 
                           class="newsaiige-form-input" 
                           placeholder="E-mail" 
                           value="<?php echo isset($_POST['user_login']) ? esc_attr($_POST['user_login']) : ''; ?>"
                           required>
                </div>

                <button type="submit" name="newsaiige_reset_submit" class="newsaiige-submit-btn">
                    Réinitialiser le mot de passe
                </button>
                
            <?php else: ?>
                <input type="hidden" name="reset_key" value="<?php echo esc_attr($reset_key); ?>">
                <input type="hidden" name="user_login" value="<?php echo esc_attr($user_login); ?>">
                
                <div class="newsaiige-form-group">
                    <input type="password" 
                           name="new_password" 
                           id="new_password"
                           class="newsaiige-form-input" 
                           placeholder="Nouveau mot de passe" 
                           required>
                    <div class="newsaiige-password-strength" id="password-strength"></div>
                </div>

                <div class="newsaiige-form-group">
                    <input type="password" 
                           name="confirm_password" 
                           id="confirm_password"
                           class="newsaiige-form-input" 
                           placeholder="Confirmer le nouveau mot de passe" 
                           required>
                </div>

                <button type="submit" name="newsaiige_reset_submit" class="newsaiige-submit-btn" id="reset-btn">
                    Changer le mot de passe
                </button>
            <?php endif; ?>
        </form>

        <?php endif; ?>

        <div class="newsaiige-back-links">
            <a href="<?php echo esc_url($atts['login_url']); ?>">← Retour à la connexion</a>
            <a href="<?php echo esc_url($atts['register_url']); ?>">Créer un compte</a>
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

        // Validation pour l'étape de nouveau mot de passe
        <?php if ($step === 'reset'): ?>
        const passwordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const strengthIndicator = document.getElementById('password-strength');
        const resetBtn = document.getElementById('reset-btn');

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

        function validatePasswords() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            const isPasswordStrong = checkPasswordStrength(password);
            const passwordsMatch = password === confirmPassword && password.length > 0;
            
            if (confirmPassword.length > 0) {
                if (passwordsMatch) {
                    confirmPasswordInput.style.borderColor = '#28a745';
                } else {
                    confirmPasswordInput.style.borderColor = '#dc3545';
                }
            } else {
                confirmPasswordInput.style.borderColor = '#e9ecef';
            }
            
            // Activer/désactiver le bouton
            if (resetBtn) {
                resetBtn.disabled = !(isPasswordStrong && passwordsMatch && password.length >= 6);
            }
        }

        if (passwordInput) {
            passwordInput.addEventListener('input', validatePasswords);
        }

        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', validatePasswords);
        }
        <?php endif; ?>

        // Focus automatique sur le premier champ
        const firstInput = document.querySelector('.newsaiige-form-input');
        if (firstInput) {
            firstInput.focus();
        }
    });
    </script>

    <?php
    return ob_get_clean();
}

add_shortcode('newsaiige_reset_password_form', 'newsaiige_reset_password_form_shortcode');
?>