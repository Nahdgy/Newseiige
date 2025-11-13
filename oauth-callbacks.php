<?php
/**
 * Callbacks OAuth pour les connexions sociales NewSaiige
 * À ajouter à la fin de register-form.php ou login-form.php
 */

// === CALLBACKS GOOGLE ===

// Callback pour la connexion Google
add_action('wp_ajax_nopriv_google_login_callback', 'newsaiige_google_login_callback');
add_action('wp_ajax_google_login_callback', 'newsaiige_google_login_callback');

function newsaiige_google_login_callback() {
    // Vérifier le state pour la sécurité
    if (!wp_verify_nonce($_GET['state'], 'google_oauth_state')) {
        wp_redirect(add_query_arg('social_error', '1', wp_login_url()));
        exit;
    }
    
    if (isset($_GET['error']) || !isset($_GET['code'])) {
        wp_redirect(add_query_arg('social_error', '1', wp_login_url()));
        exit;
    }
    
    $code = sanitize_text_field($_GET['code']);
    $google_client_id = get_option('newsaiige_google_client_id', '');
    $google_client_secret = get_option('newsaiige_google_client_secret', '');
    
    if (empty($google_client_id) || empty($google_client_secret)) {
        wp_redirect(add_query_arg('social_error', '1', wp_login_url()));
        exit;
    }
    
    // Échanger le code contre un token d'accès
    $token_url = 'https://oauth2.googleapis.com/token';
    $token_data = array(
        'client_id' => $google_client_id,
        'client_secret' => $google_client_secret,
        'code' => $code,
        'grant_type' => 'authorization_code',
        'redirect_uri' => admin_url('admin-ajax.php?action=google_login_callback')
    );
    
    $token_response = wp_remote_post($token_url, array(
        'body' => $token_data,
        'timeout' => 30
    ));
    
    if (is_wp_error($token_response)) {
        wp_redirect(add_query_arg('social_error', '1', wp_login_url()));
        exit;
    }
    
    $token_body = json_decode(wp_remote_retrieve_body($token_response), true);
    
    if (!isset($token_body['access_token'])) {
        wp_redirect(add_query_arg('social_error', '1', wp_login_url()));
        exit;
    }
    
    // Récupérer les informations utilisateur
    $user_info_url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $token_body['access_token'];
    $user_response = wp_remote_get($user_info_url);
    
    if (is_wp_error($user_response)) {
        wp_redirect(add_query_arg('social_error', '1', wp_login_url()));
        exit;
    }
    
    $user_data = json_decode(wp_remote_retrieve_body($user_response), true);
    
    if (!isset($user_data['email'])) {
        wp_redirect(add_query_arg('social_error', '1', wp_login_url()));
        exit;
    }
    
    // Vérifier si l'utilisateur existe
    $existing_user = get_user_by('email', $user_data['email']);
    
    if ($existing_user) {
        // Connexion de l'utilisateur existant
        wp_set_current_user($existing_user->ID);
        wp_set_auth_cookie($existing_user->ID);
        wp_redirect(home_url());
    } else {
        // Rediriger vers inscription avec données pré-remplies
        $register_url = add_query_arg(array(
            'social_register' => 'google',
            'email' => urlencode($user_data['email']),
            'name' => urlencode($user_data['name'] ?? ''),
            'social_error' => 'no_account'
        ), wp_registration_url());
        wp_redirect($register_url);
    }
    exit;
}

// Callback pour l'inscription Google
add_action('wp_ajax_nopriv_google_register_callback', 'newsaiige_google_register_callback');
add_action('wp_ajax_google_register_callback', 'newsaiige_google_register_callback');

function newsaiige_google_register_callback() {
    // Vérifier le state pour la sécurité
    if (!wp_verify_nonce($_GET['state'], 'google_register_oauth_state')) {
        wp_redirect(add_query_arg('social_error', '1', wp_registration_url()));
        exit;
    }
    
    if (isset($_GET['error']) || !isset($_GET['code'])) {
        wp_redirect(add_query_arg('social_error', '1', wp_registration_url()));
        exit;
    }
    
    $code = sanitize_text_field($_GET['code']);
    $google_client_id = get_option('newsaiige_google_client_id', '');
    $google_client_secret = get_option('newsaiige_google_client_secret', '');
    
    if (empty($google_client_id) || empty($google_client_secret)) {
        wp_redirect(add_query_arg('social_error', '1', wp_registration_url()));
        exit;
    }
    
    // Échanger le code contre un token d'accès
    $token_url = 'https://oauth2.googleapis.com/token';
    $token_data = array(
        'client_id' => $google_client_id,
        'client_secret' => $google_client_secret,
        'code' => $code,
        'grant_type' => 'authorization_code',
        'redirect_uri' => admin_url('admin-ajax.php?action=google_register_callback')
    );
    
    $token_response = wp_remote_post($token_url, array(
        'body' => $token_data,
        'timeout' => 30
    ));
    
    if (is_wp_error($token_response)) {
        wp_redirect(add_query_arg('social_error', '1', wp_registration_url()));
        exit;
    }
    
    $token_body = json_decode(wp_remote_retrieve_body($token_response), true);
    
    if (!isset($token_body['access_token'])) {
        wp_redirect(add_query_arg('social_error', '1', wp_registration_url()));
        exit;
    }
    
    // Récupérer les informations utilisateur
    $user_info_url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $token_body['access_token'];
    $user_response = wp_remote_get($user_info_url);
    
    if (is_wp_error($user_response)) {
        wp_redirect(add_query_arg('social_error', '1', wp_registration_url()));
        exit;
    }
    
    $user_data = json_decode(wp_remote_retrieve_body($user_response), true);
    
    if (!isset($user_data['email'])) {
        wp_redirect(add_query_arg('social_error', '1', wp_registration_url()));
        exit;
    }
    
    // Vérifier si l'utilisateur existe déjà
    if (email_exists($user_data['email'])) {
        wp_redirect(add_query_arg('social_error', 'email_exists', wp_registration_url()));
        exit;
    }
    
    // Créer l'utilisateur
    $username = sanitize_user(strtolower(str_replace(' ', '', $user_data['name'])) . '_' . rand(1000, 9999));
    
    // Assurer l'unicité du nom d'utilisateur
    $base_username = $username;
    $counter = 1;
    while (username_exists($username)) {
        $username = $base_username . '_' . $counter;
        $counter++;
    }
    
    $user_id = wp_create_user(
        $username,
        wp_generate_password(),
        $user_data['email']
    );
    
    if (is_wp_error($user_id)) {
        wp_redirect(add_query_arg('social_error', '1', wp_registration_url()));
        exit;
    }
    
    // Mettre à jour les informations utilisateur
    wp_update_user(array(
        'ID' => $user_id,
        'display_name' => $user_data['name'],
        'first_name' => $user_data['given_name'] ?? '',
        'last_name' => $user_data['family_name'] ?? '',
        'role' => 'customer'
    ));
    
    // Marquer comme utilisateur Google
    update_user_meta($user_id, 'social_login_google', true);
    update_user_meta($user_id, 'google_id', $user_data['id']);
    
    // Connexion automatique
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);
    
    wp_redirect(home_url());
    exit;
}

// === CALLBACKS FACEBOOK ===

// Callback pour la connexion Facebook
add_action('wp_ajax_nopriv_facebook_login_callback', 'newsaiige_facebook_login_callback');
add_action('wp_ajax_facebook_login_callback', 'newsaiige_facebook_login_callback');

function newsaiige_facebook_login_callback() {
    // Vérifier le state pour la sécurité
    if (!wp_verify_nonce($_GET['state'], 'facebook_oauth_state')) {
        wp_redirect(add_query_arg('social_error', '1', wp_login_url()));
        exit;
    }
    
    if (isset($_GET['error']) || !isset($_GET['code'])) {
        wp_redirect(add_query_arg('social_error', '1', wp_login_url()));
        exit;
    }
    
    $code = sanitize_text_field($_GET['code']);
    $facebook_app_id = get_option('newsaiige_facebook_app_id', '');
    $facebook_app_secret = get_option('newsaiige_facebook_app_secret', '');
    
    if (empty($facebook_app_id) || empty($facebook_app_secret)) {
        wp_redirect(add_query_arg('social_error', '1', wp_login_url()));
        exit;
    }
    
    // Échanger le code contre un token d'accès
    $token_url = 'https://graph.facebook.com/v18.0/oauth/access_token';
    $token_data = array(
        'client_id' => $facebook_app_id,
        'client_secret' => $facebook_app_secret,
        'code' => $code,
        'redirect_uri' => admin_url('admin-ajax.php?action=facebook_login_callback')
    );
    
    $token_response = wp_remote_post($token_url, array(
        'body' => $token_data,
        'timeout' => 30
    ));
    
    if (is_wp_error($token_response)) {
        wp_redirect(add_query_arg('social_error', '1', wp_login_url()));
        exit;
    }
    
    $token_body = json_decode(wp_remote_retrieve_body($token_response), true);
    
    if (!isset($token_body['access_token'])) {
        wp_redirect(add_query_arg('social_error', '1', wp_login_url()));
        exit;
    }
    
    // Récupérer les informations utilisateur
    $user_info_url = 'https://graph.facebook.com/me?fields=id,name,email&access_token=' . $token_body['access_token'];
    $user_response = wp_remote_get($user_info_url);
    
    if (is_wp_error($user_response)) {
        wp_redirect(add_query_arg('social_error', '1', wp_login_url()));
        exit;
    }
    
    $user_data = json_decode(wp_remote_retrieve_body($user_response), true);
    
    if (!isset($user_data['email'])) {
        wp_redirect(add_query_arg('social_error', 'no_email', wp_login_url()));
        exit;
    }
    
    // Vérifier si l'utilisateur existe
    $existing_user = get_user_by('email', $user_data['email']);
    
    if ($existing_user) {
        // Connexion de l'utilisateur existant
        wp_set_current_user($existing_user->ID);
        wp_set_auth_cookie($existing_user->ID);
        wp_redirect(home_url());
    } else {
        // Rediriger vers inscription
        $register_url = add_query_arg(array(
            'social_register' => 'facebook',
            'email' => urlencode($user_data['email']),
            'name' => urlencode($user_data['name'] ?? ''),
            'social_error' => 'no_account'
        ), wp_registration_url());
        wp_redirect($register_url);
    }
    exit;
}

// Callback pour l'inscription Facebook
add_action('wp_ajax_nopriv_facebook_register_callback', 'newsaiige_facebook_register_callback');
add_action('wp_ajax_facebook_register_callback', 'newsaiige_facebook_register_callback');

function newsaiige_facebook_register_callback() {
    // Code similaire à Google mais pour Facebook
    // Implémentation similaire à google_register_callback
    // mais avec les URLs Facebook Graph API
    
    // Vérifier le state
    if (!wp_verify_nonce($_GET['state'], 'facebook_register_oauth_state')) {
        wp_redirect(add_query_arg('social_error', '1', wp_registration_url()));
        exit;
    }
    
    // Le reste de l'implémentation suit la même logique que Google
    // mais avec les APIs Facebook
    
    wp_redirect(add_query_arg('social_error', 'not_implemented', wp_registration_url()));
    exit;
}
?>