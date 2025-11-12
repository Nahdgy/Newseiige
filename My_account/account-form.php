function newsaiige_account_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'title' => 'Mon compte',
        'subtitle' => 'Consultez et modifiez vos informations personnelles ci-dessous.'
    ), $atts);
    
    // Vérifier si l'utilisateur est connecté
    if (!is_user_logged_in()) {
        return '<p>Vous devez être connecté pour accéder à cette page. <a href="' . wp_login_url() . '">Se connecter</a></p>';
    }
    
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    // Traitement du formulaire
    $success_message = '';
    $error_message = '';
    
    if (isset($_POST['update_account_info']) && wp_verify_nonce($_POST['account_nonce'], 'update_account_info')) {
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $phone = sanitize_text_field($_POST['phone']);
        $email = sanitize_email($_POST['email']);
        $new_password = $_POST['new_password'];
        
        // Valider l'email
        if (!is_email($email)) {
            $error_message = 'Adresse email invalide.';
        } else {
            // Vérifier si l'email existe déjà pour un autre utilisateur
            $existing_user = get_user_by('email', $email);
            if ($existing_user && $existing_user->ID != $user_id) {
                $error_message = 'Cette adresse email est déjà utilisée par un autre compte.';
            } else {
                // Mettre à jour les informations de base
                $user_data = array(
                    'ID' => $user_id,
                    'user_email' => $email,
                    'first_name' => $first_name,
                    'last_name' => $last_name
                );
                
                // Si un nouveau mot de passe est fourni, l'ajouter
                if (!empty($new_password)) {
                    // Valider la force du mot de passe (optionnel)
                    if (strlen($new_password) < 8) {
                        $error_message = 'Le mot de passe doit contenir au moins 8 caractères.';
                    } else {
                        $user_data['user_pass'] = $new_password;
                    }
                }
                
                if (empty($error_message)) {
                    $result = wp_update_user($user_data);
                    
                    if (is_wp_error($result)) {
                        $error_message = 'Erreur lors de la mise à jour : ' . $result->get_error_message();
                    } else {
                        // Mettre à jour le numéro de téléphone
                        update_user_meta($user_id, 'phone', $phone);
                        
                        $success_message = 'Vos informations ont été mises à jour avec succès.';
                        
                        // Si le mot de passe a été changé, reconnexion automatique
                        if (!empty($new_password)) {
                            wp_set_current_user($user_id);
                            wp_set_auth_cookie($user_id, true);
                            $success_message .= ' Votre mot de passe a été modifié avec succès.';
                        }
                        
                        // Rafraîchir les données utilisateur
                        $current_user = wp_get_current_user();
                    }
                }
            }
        }
    }
    
    // Récupérer les données actuelles
    $first_name = get_user_meta($user_id, 'first_name', true);
    $last_name = get_user_meta($user_id, 'last_name', true);
    $phone = get_user_meta($user_id, 'phone', true);
    $email = $current_user->user_email;
    
    ob_start();
    ?>

    <style>
    .newsaiige-account-section {
        margin: 0 auto;
        padding: 60px 20px;
        font-family: 'Montserrat', sans-serif;
        border-radius: 20px;
        margin-top: 40px;
        margin-bottom: 40px;
    }

    .account-header {
        text-align: left;
        margin-bottom: 50px;
    }

    .account-title {
        font-size: 24px;
        font-weight: 700;
        color: #82897F;
        margin: 0 0 15px 0;
        letter-spacing: 1px;
    }

    .account-subtitle {
        font-size: 16px;
        color: #000;
        margin: 0;
        font-weight: 400;
        line-height: 1.5;
    }

    .form-section {
        margin-bottom: 100px;
        padding-top: 40px;
        border-top: 2px solid rgba(130, 137, 127, 0.1);
    }

    .form-section:last-child {
        margin-bottom: 0;
    }

    .section-title {
        font-size: 24px;
        font-weight: 700;
        color: #82897F;
        margin: 0 0 25px 0;
        letter-spacing: 0.5px;
    }
    .section-description {
        font-size: 16px;
        color: #666; 
        margin-bottom: 25px; 
        
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-weight: 500;
        color: #444;
        margin-bottom: 8px;
        font-size: 0.95rem;
    }

    .form-input {
        border-radius: 30px !important;
        font-size: 1rem;
        font-family: 'Montserrat', sans-serif;
        background: #fff;
        color: #7D7D7D;
        transition: all 0.3s ease;
        box-sizing: border-box;
    }

    .form-input:focus {
        outline: none;
        border-color: #82897F;
        box-shadow: 0 0 0 3px rgba(130, 137, 127, 0.1);
    }

    .form-input::placeholder {
        color: #aaa;
        font-style: italic;
    }

    .submit-button {
        background: #82897F;
        color: white;
        padding: 15px 40px;
        border: none;
        border-radius: 50px;
        font-size: 1rem;
        font-weight: 600;
        font-family: 'Montserrat', sans-serif;
        cursor: pointer;
        transition: all 0.3s ease;
        float: right;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .submit-button:hover {
        background: #6d7465;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(130, 137, 127, 0.3);
    }

    .submit-button:active {
        transform: translateY(0);
    }

    .message {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 30px;
        font-weight: 500;
    }

    .success-message {
        background: rgba(76, 175, 80, 0.1);
        color: #2e7d32;
        border-left: 4px solid #4caf50;
    }

    .error-message {
        background: rgba(244, 67, 54, 0.1);
        color: #c62828;
        border-left: 4px solid #f44336;
    }

    /* Clearfix uniquement pour les éléments flottants - pas sur les grids */
    .clearfix::after {
        content: "";
        display: table;
        clear: both;
    }
    
    /* Empêcher tous les pseudo-éléments d'affecter les grids CSS */
    .form-row::before,
    .form-row::after,
    .form-section::before, 
    .form-section::after,
    .account-form-container::before,
    .account-form-container::after {
        display: none !important;
    }
    
    /* Alternative moderne au clearfix pour les grids */
    .form-row {
        display: grid !important;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .newsaiige-account-section {
            padding: 40px 15px;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .account-form-container {
            padding: 30px 20px;
        }

        .form-row {
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .account-title {
            font-size: 1.8rem;
        }

        .submit-button {
            width: 100%;
            float: none;
            margin-top: 20px;
        }
    }

    @media (max-width: 480px) {
        .newsaiige-account-section {
            padding: 30px 10px;
        }

        .account-form-container {
            padding: 25px 15px;
        }

        .form-input {
            padding: 12px 15px;
        }
    }
    </style>

    <div class="newsaiige-account-section">
        <div class="account-header">
            <h2 class="account-title"><?php echo esc_html($atts['title']); ?></h2>
            <p class="account-subtitle"><?php echo esc_html($atts['subtitle']); ?></p>
        </div>

        <div class="account-form-container">
            <?php if ($success_message): ?>
                <div class="message success-message"><?php echo esc_html($success_message); ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="message error-message"><?php echo esc_html($error_message); ?></div>
            <?php endif; ?>

            <form method="post" action="">
                <?php wp_nonce_field('update_account_info', 'account_nonce'); ?>
                
                <div class="form-section">
                    <h3 class="section-title">Informations personnelles</h3>
                    <p class="section-description">Mettez vos informations personnelles à jour.</p>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">Prénom</label>
                            <input type="text" id="first_name" name="first_name" class="form-input" 
                                   value="<?php echo esc_attr($first_name); ?>" placeholder="Prénom" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Nom</label>
                            <input type="text" id="last_name" name="last_name" class="form-input" 
                                   value="<?php echo esc_attr($last_name); ?>" placeholder="Nom" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="phone">Téléphone</label>
                        <input type="tel" id="phone" name="phone" class="form-input" 
                               value="<?php echo esc_attr($phone); ?>" placeholder="06 01 02 03 04">
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">Informations de connexion</h3>
                    <p class="section-description">Mettez vos informations de connexion à jour.</p>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">E-mail</label>
                            <input type="email" id="email" name="email" class="form-input" 
                                value="<?php echo esc_attr($email); ?>" placeholder="newsaiige@gmail.com" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password">Nouveau mot de passe (optionnel)</label>
                            <input type="password" id="new_password" name="new_password" class="form-input" 
                                placeholder="••••••••" autocomplete="new-password" minlength="8">
                            <small style="color: #666; font-size: 0.85rem; margin-top: 5px; display: block;">
                                Laissez vide si vous ne souhaitez pas changer votre mot de passe. Minimum 8 caractères.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="clearfix">
                    <button type="submit" name="update_account_info" class="submit-button">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Validation côté client
        const form = document.querySelector('form');
        const newPasswordInput = document.getElementById('new_password');

        // Validation du mot de passe en temps réel
        newPasswordInput.addEventListener('input', function() {
            const password = this.value;
            const isValid = password.length === 0 || password.length >= 8;
            
            if (password.length > 0 && !isValid) {
                this.style.borderColor = '#f44336';
                if (!this.nextElementSibling || !this.nextElementSibling.classList.contains('password-error')) {
                    const errorMsg = document.createElement('small');
                    errorMsg.className = 'password-error';
                    errorMsg.style.color = '#f44336';
                    errorMsg.style.fontSize = '0.85rem';
                    errorMsg.style.marginTop = '5px';
                    errorMsg.style.display = 'block';
                    errorMsg.textContent = 'Le mot de passe doit contenir au moins 8 caractères.';
                    this.parentNode.insertBefore(errorMsg, this.nextElementSibling);
                }
            } else {
                this.style.borderColor = '';
                const errorMsg = this.parentNode.querySelector('.password-error');
                if (errorMsg) {
                    errorMsg.remove();
                }
            }
        });

        form.addEventListener('submit', function(e) {
            const password = newPasswordInput.value;
            if (password.length > 0 && password.length < 8) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 8 caractères.');
                newPasswordInput.focus();
            }
        });

        // Animation des inputs
        const inputs = document.querySelectorAll('.form-input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.style.transform = 'scale(1.02)';
            });

            input.addEventListener('blur', function() {
                this.style.transform = 'scale(1)';
            });
        });
    });
    </script>

    <?php
    return ob_get_clean();
}

add_shortcode('newsaiige_account_form', 'newsaiige_account_form_shortcode');
?>