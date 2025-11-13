<?php
/**
 * Page d'administration pour les connexions sociales NewSaiige
 * À ajouter dans functions.php : require_once get_template_directory() . '/social-login-admin.php';
 */

// Ajouter le menu dans l'admin WordPress
add_action('admin_menu', 'newsaiige_social_admin_menu');

function newsaiige_social_admin_menu() {
    add_options_page(
        'Connexions Sociales NewSaiige',
        'Connexions Sociales', 
        'manage_options',
        'newsaiige-social-config',
        'newsaiige_social_config_page'
    );
}

function newsaiige_social_config_page() {
    // Traitement du formulaire
    if (isset($_POST['submit']) && wp_verify_nonce($_POST['newsaiige_social_nonce'], 'newsaiige_social_config')) {
        update_option('newsaiige_google_client_id', sanitize_text_field($_POST['google_client_id']));
        update_option('newsaiige_google_client_secret', sanitize_text_field($_POST['google_client_secret']));
        update_option('newsaiige_facebook_app_id', sanitize_text_field($_POST['facebook_app_id']));
        update_option('newsaiige_facebook_app_secret', sanitize_text_field($_POST['facebook_app_secret']));
        
        echo '<div class="notice notice-success is-dismissible"><p><strong>✅ Configuration sauvegardée avec succès !</strong></p></div>';
    }
    
    // Récupérer les valeurs actuelles
    $google_client_id = get_option('newsaiige_google_client_id', '');
    $google_client_secret = get_option('newsaiige_google_client_secret', '');
    $facebook_app_id = get_option('newsaiige_facebook_app_id', '');
    $facebook_app_secret = get_option('newsaiige_facebook_app_secret', '');
    
    // URLs de callback pour information
    $site_url = home_url();
    $google_callback = admin_url('admin-ajax.php?action=google_login_callback');
    $facebook_callback = admin_url('admin-ajax.php?action=facebook_login_callback');
    ?>
    <div class="wrap">
        <h1>🔐 Configuration des Connexions Sociales</h1>
        
        <!-- Informations importantes -->
        <div class="notice notice-info">
            <p><strong>📋 URLs de redirection à configurer dans vos applications :</strong></p>
            <ul>
                <li><strong>Google :</strong> <code><?php echo esc_url($google_callback); ?></code></li>
                <li><strong>Facebook :</strong> <code><?php echo esc_url($facebook_callback); ?></code></li>
            </ul>
        </div>
        
        <?php if (empty($google_client_id) || empty($facebook_app_id)): ?>
        <div class="notice notice-warning">
            <p>⚠️ <strong>Configuration incomplète.</strong> Veuillez renseigner au moins les clés Google ou Facebook.</p>
        </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <?php wp_nonce_field('newsaiige_social_config', 'newsaiige_social_nonce'); ?>
            
            <!-- Configuration Google -->
            <h2>🔵 Configuration Google OAuth</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="google_client_id">Google Client ID</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="google_client_id"
                               name="google_client_id" 
                               value="<?php echo esc_attr($google_client_id); ?>" 
                               class="regular-text" 
                               placeholder="123456789-abc123.apps.googleusercontent.com" />
                        <p class="description">Récupérable sur <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="google_client_secret">Google Client Secret</label>
                    </th>
                    <td>
                        <input type="password" 
                               id="google_client_secret"
                               name="google_client_secret" 
                               value="<?php echo esc_attr($google_client_secret); ?>" 
                               class="regular-text" 
                               placeholder="GOCSPX-..." />
                        <p class="description">Clé secrète de votre application Google</p>
                    </td>
                </tr>
            </table>
            
            <!-- Configuration Facebook -->
            <h2>🔵 Configuration Facebook Login</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="facebook_app_id">Facebook App ID</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="facebook_app_id"
                               name="facebook_app_id" 
                               value="<?php echo esc_attr($facebook_app_id); ?>" 
                               class="regular-text" 
                               placeholder="123456789012345" />
                        <p class="description">ID numérique sur <a href="https://developers.facebook.com/" target="_blank">Facebook Developers</a></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="facebook_app_secret">Facebook App Secret</label>
                    </th>
                    <td>
                        <input type="password" 
                               id="facebook_app_secret"
                               name="facebook_app_secret" 
                               value="<?php echo esc_attr($facebook_app_secret); ?>" 
                               class="regular-text" 
                               placeholder="a1b2c3d4e5f6..." />
                        <p class="description">Clé secrète de votre application Facebook</p>
                    </td>
                </tr>
            </table>
            
            <!-- Statut de la configuration -->
            <h2>📊 Statut de la Configuration</h2>
            <table class="form-table">
                <tr>
                    <th>Google OAuth</th>
                    <td>
                        <?php if (!empty($google_client_id) && !empty($google_client_secret)): ?>
                            <span style="color: green;">✅ Configuré</span>
                        <?php else: ?>
                            <span style="color: orange;">⚠️ Incomplet</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Facebook Login</th>
                    <td>
                        <?php if (!empty($facebook_app_id) && !empty($facebook_app_secret)): ?>
                            <span style="color: green;">✅ Configuré</span>
                        <?php else: ?>
                            <span style="color: orange;">⚠️ Incomplet</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Callbacks OAuth</th>
                    <td>
                        <?php if (file_exists(get_template_directory() . '/oauth-callbacks.php')): ?>
                            <span style="color: green;">✅ Fichier présent</span>
                        <?php else: ?>
                            <span style="color: red;">❌ Fichier manquant</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('💾 Sauvegarder la Configuration'); ?>
        </form>
        
        <!-- Aide rapide -->
        <div class="postbox" style="margin-top: 20px;">
            <h3 class="hndle" style="padding: 10px;">🆘 Aide Rapide</h3>
            <div class="inside" style="padding: 10px;">
                <p><strong>Problèmes fréquents :</strong></p>
                <ul>
                    <li>🔒 <strong>HTTPS obligatoire</strong> - OAuth ne fonctionne qu'en HTTPS</li>
                    <li>📧 <strong>Permission email</strong> - Activez-la dans Facebook Developers</li>
                    <li>🔄 <strong>URLs exactes</strong> - Copiez-collez les URLs de redirection ci-dessus</li>
                </ul>
                
                <p><strong>Test rapide :</strong></p>
                <p>Après configuration, testez sur : <code><?php echo home_url('/wp-login.php'); ?></code></p>
            </div>
        </div>
    </div>
    
    <style>
    .form-table th {
        width: 200px;
    }
    .notice ul {
        margin: 5px 0;
    }
    .postbox h3.hndle {
        cursor: default;
    }
    </style>
    <?php
}

// Fonction utilitaire pour vérifier si la config est complète
function newsaiige_social_is_configured() {
    $google_ok = !empty(get_option('newsaiige_google_client_id')) && !empty(get_option('newsaiige_google_client_secret'));
    $facebook_ok = !empty(get_option('newsaiige_facebook_app_id')) && !empty(get_option('newsaiige_facebook_app_secret'));
    
    return $google_ok || $facebook_ok;
}

// Ajouter un avertissement sur le tableau de bord si pas configuré
add_action('admin_notices', 'newsaiige_social_config_notice');

function newsaiige_social_config_notice() {
    if (!newsaiige_social_is_configured() && current_user_can('manage_options')) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong>🔐 Connexions Sociales NewSaiige :</strong> 
                Configuration incomplète. 
                <a href="<?php echo admin_url('options-general.php?page=newsaiige-social-config'); ?>">
                    Configurer maintenant
                </a>
            </p>
        </div>
        <?php
    }
}
?>