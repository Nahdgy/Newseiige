<?php
/**
 * NEWSAIIGE SOCIAL LOGIN - Configuration centralisée pour shortcodes
 * À inclure dans functions.php : require_once get_template_directory() . '/newsaiige-social-loader.php';
 */

// ===== CHARGEMENT CONDITIONNEL =====

// Détecter si on est sur une page avec formulaires sociaux
add_action('wp', 'newsaiige_detect_social_forms');

function newsaiige_detect_social_forms() {
    global $post;
    
    // Vérifier si la page contient les shortcodes de connexion sociale
    if (is_object($post) && (
        has_shortcode($post->post_content, 'newsaiige_login_form') ||
        has_shortcode($post->post_content, 'newsaiige_register_form') ||
        strpos($post->post_content, 'newsaiige_login_form') !== false ||
        strpos($post->post_content, 'newsaiige_register_form') !== false
    )) {
        // Charger les callbacks OAuth
        if (file_exists(get_template_directory() . '/oauth-callbacks.php')) {
            require_once get_template_directory() . '/oauth-callbacks.php';
        }
    }
}

// ===== ADMINISTRATION =====

// Menu admin pour les connexions sociales
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
    $google_login_callback = admin_url('admin-ajax.php?action=google_login_callback');
    $google_register_callback = admin_url('admin-ajax.php?action=google_register_callback');
    $facebook_login_callback = admin_url('admin-ajax.php?action=facebook_login_callback');
    $facebook_register_callback = admin_url('admin-ajax.php?action=facebook_register_callback');
    ?>
    
    <div class="wrap">
        <h1>🔐 Configuration des Connexions Sociales NewSaiige</h1>
        
        <!-- Info configuration Elementor -->
        <div class="notice notice-info">
            <h3>📋 Configuration pour Elementor/Shortcodes :</h3>
            <p><strong>✅ Votre système est configuré pour :</strong></p>
            <ul>
                <li>Formulaires via shortcodes <code>[newsaiige_login_form]</code> et <code>[newsaiige_register_form]</code></li>
                <li>Chargement automatique des callbacks OAuth sur les pages contenant ces shortcodes</li>
                <li>Administration centralisée des clés API</li>
            </ul>
        </div>
        
        <!-- URLs de callback -->
        <div class="postbox">
            <h3 class="hndle" style="padding: 10px;">🔗 URLs de redirection à configurer</h3>
            <div class="inside" style="padding: 10px;">
                <p><strong>Pour Google Cloud Console :</strong></p>
                <ul>
                    <li><code style="background: #f0f0f1; padding: 2px 6px;"><?php echo esc_url($google_login_callback); ?></code></li>
                    <li><code style="background: #f0f0f1; padding: 2px 6px;"><?php echo esc_url($google_register_callback); ?></code></li>
                </ul>
                
                <p><strong>Pour Facebook Developers :</strong></p>
                <ul>
                    <li><code style="background: #f0f0f1; padding: 2px 6px;"><?php echo esc_url($facebook_login_callback); ?></code></li>
                    <li><code style="background: #f0f0f1; padding: 2px 6px;"><?php echo esc_url($facebook_register_callback); ?></code></li>
                </ul>
            </div>
        </div>
        
        <!-- Configuration -->
        <?php 
        $google_configured = !empty($google_client_id) && !empty($google_client_secret);
        $facebook_configured = !empty($facebook_app_id) && !empty($facebook_app_secret);
        ?>
        
        <form method="post" action="">
            <?php wp_nonce_field('newsaiige_social_config', 'newsaiige_social_nonce'); ?>
            
            <!-- Configuration Google -->
            <div class="postbox">
                <h3 class="hndle" style="padding: 10px;">
                    🔵 Configuration Google OAuth 
                    <?php if ($google_configured): ?>
                        <span style="color: green;">✅ Configuré</span>
                    <?php else: ?>
                        <span style="color: orange;">⚠️ À configurer</span>
                    <?php endif; ?>
                </h3>
                <div class="inside" style="padding: 15px;">
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
                                       placeholder="Votre client ID" />
                                <p class="description">Récupérable sur Google Cloud Console > Identifiants</p>
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
                                       placeholder="Votre secret key" />
                                <p class="description">Clé secrète de votre application Google OAuth</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Configuration Facebook -->
            <div class="postbox">
                <h3 class="hndle" style="padding: 10px;">
                    🔵 Configuration Facebook Login 
                    <?php if ($facebook_configured): ?>
                        <span style="color: green;">✅ Configuré</span>
                    <?php else: ?>
                        <span style="color: orange;">⚠️ À configurer</span>
                    <?php endif; ?>
                </h3>
                <div class="inside" style="padding: 15px;">
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
                                <p class="description">ID numérique sur Facebook Developers</p>
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
                </div>
            </div>
            
            <?php submit_button('💾 Sauvegarder la Configuration', 'primary', 'submit', false, array('style' => 'margin-top: 20px;')); ?>
        </form>
        
        <!-- Test et validation -->
        <div class="postbox" style="margin-top: 20px;">
            <h3 class="hndle" style="padding: 10px;">🧪 Test & Validation pour Shortcodes</h3>
            <div class="inside" style="padding: 15px;">
                <h4>Statut des composants :</h4>
                <ul>
                    <li>
                        <strong>Callbacks OAuth :</strong>
                        <?php if (file_exists(get_template_directory() . '/oauth-callbacks.php')): ?>
                            <span style="color: green;">✅ Fichier présent</span>
                        <?php else: ?>
                            <span style="color: red;">❌ Fichier manquant</span>
                        <?php endif; ?>
                    </li>
                    <li>
                        <strong>Formulaire de connexion :</strong>
                        <?php if (file_exists(get_template_directory() . '/login-form.php')): ?>
                            <span style="color: green;">✅ Shortcode disponible</span>
                        <?php else: ?>
                            <span style="color: orange;">⚠️ Fichier manquant</span>
                        <?php endif; ?>
                    </li>
                    <li>
                        <strong>Formulaire d'inscription :</strong>
                        <?php if (file_exists(get_template_directory() . '/register-form.php')): ?>
                            <span style="color: green;">✅ Shortcode disponible</span>
                        <?php else: ?>
                            <span style="color: orange;">⚠️ Fichier manquant</span>
                        <?php endif; ?>
                    </li>
                </ul>
                
                <h4>Utilisation dans Elementor :</h4>
                <ol>
                    <li>Ajoutez un widget <strong>"Shortcode"</strong> dans Elementor</li>
                    <li>Insérez <code>[newsaiige_login_form]</code> ou <code>[newsaiige_register_form]</code></li>
                    <li>Les callbacks OAuth se chargent automatiquement !</li>
                </ol>
                
                <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 4px; margin-top: 15px;">
                    <strong>⚠️ Important :</strong> Les connexions sociales ne fonctionnent qu'en HTTPS. 
                    Assurez-vous que votre site utilise SSL.
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .form-table th { width: 200px; }
    .postbox h3.hndle { cursor: default; }
    .notice ul, .notice ol { margin: 10px 0 10px 20px; }
    </style>
    
    <?php
}

// ===== NOTIFICATION ADMIN =====

add_action('admin_notices', 'newsaiige_social_config_notice');

function newsaiige_social_config_notice() {
    if (!newsaiige_social_is_configured() && current_user_can('manage_options')) {
        $current_screen = get_current_screen();
        
        if ($current_screen && $current_screen->id !== 'settings_page_newsaiige-social-config') {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong>🔐 Connexions Sociales NewSaiige :</strong> 
                    Configuration incomplète pour vos shortcodes Elementor.
                    <a href="<?php echo admin_url('options-general.php?page=newsaiige-social-config'); ?>" class="button button-primary" style="margin-left: 10px;">
                        Configurer maintenant
                    </a>
                </p>
            </div>
            <?php
        }
    }
}

// ===== FONCTIONS UTILITAIRES =====

function newsaiige_social_is_configured() {
    $google_ok = !empty(get_option('newsaiige_google_client_id')) && !empty(get_option('newsaiige_google_client_secret'));
    $facebook_ok = !empty(get_option('newsaiige_facebook_app_id')) && !empty(get_option('newsaiige_facebook_app_secret'));
    
    return $google_ok || $facebook_ok;
}

function newsaiige_get_social_keys() {
    return array(
        'google_client_id' => get_option('newsaiige_google_client_id', ''),
        'google_client_secret' => get_option('newsaiige_google_client_secret', ''),
        'facebook_app_id' => get_option('newsaiige_facebook_app_id', ''),
        'facebook_app_secret' => get_option('newsaiige_facebook_app_secret', '')
    );
}

function newsaiige_google_is_configured() {
    $keys = newsaiige_get_social_keys();
    return !empty($keys['google_client_id']) && !empty($keys['google_client_secret']);
}

function newsaiige_facebook_is_configured() {
    $keys = newsaiige_get_social_keys();
    return !empty($keys['facebook_app_id']) && !empty($keys['facebook_app_secret']);
}

?>