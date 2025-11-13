# GUIDE COMPLET - Connexions Sociales NewSaiige

## 📋 RÉSUMÉ DE CE QU'IL FAUT FAIRE

Pour faire fonctionner les connexions sociales, vous avez besoin de :

### 1. CONFIGURATION DES APPLICATIONS
- **Google Cloud Console** : Créer une application OAuth 2.0
- **Facebook Developers** : Créer une application Facebook Login
- **WordPress** : Configurer les clés dans l'admin

### 2. INTÉGRATION DU CODE
- ✅ UI des boutons sociaux (déjà fait)
- ✅ Fonctions de redirection (déjà fait)  
- ✅ Callbacks OAuth (créé dans oauth-callbacks.php)
- ❌ **À FAIRE** : Inclure les callbacks dans vos pages

---

## 🔧 ÉTAPE 1 : CONFIGURATION GOOGLE

### 1.1 Créer l'application Google
1. Allez sur [Google Cloud Console](https://console.cloud.google.com/)
2. Créez un nouveau projet ou sélectionnez-en un
3. Activez l'API "Google+ API"
4. Allez dans "Identifiants" > "Créer des identifiants" > "ID client OAuth 2.0"
5. Type d'application : "Application Web"
6. **URIs de redirection autorisées** :
   ```
   https://votre-site.com/wp-admin/admin-ajax.php?action=google_login_callback
   https://votre-site.com/wp-admin/admin-ajax.php?action=google_register_callback
   ```

### 1.2 Récupérer les clés
- **Client ID** : Ressemble à `123456789-abc123.apps.googleusercontent.com` (749411359639-vv1ccrjpr27kd1jl2q3cubk7m7v4a1og.apps.googleusercontent.com)
- **Client Secret** : Une chaîne aléatoire (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)

---

## 🔧 ÉTAPE 2 : CONFIGURATION FACEBOOK

### 2.1 Créer l'application Facebook
1. Allez sur [Facebook Developers](https://developers.facebook.com/)
2. Créez une nouvelle application
3. Ajoutez le produit "Facebook Login"
4. **URIs de redirection OAuth valides** :
   ```
   https://votre-site.com/wp-admin/admin-ajax.php?action=facebook_login_callback
   https://votre-site.com/wp-admin/admin-ajax.php?action=facebook_register_callback
   ```

### 2.2 Récupérer les clés
- **App ID** : ID numérique de l'application
- **App Secret** : Clé secrète

---

## 🔧 ÉTAPE 3 : CONFIGURATION WORDPRESS

### 3.1 Ajouter les options dans l'admin
Ajoutez ce code dans `functions.php` ou créez une page d'admin :

```php
// Ajouter les options de configuration
add_action('admin_menu', 'newsaiige_social_admin_menu');

function newsaiige_social_admin_menu() {
    add_options_page(
        'Connexions Sociales',
        'Connexions Sociales', 
        'manage_options',
        'newsaiige-social-config',
        'newsaiige_social_config_page'
    );
}

function newsaiige_social_config_page() {
    if (isset($_POST['submit'])) {
        update_option('newsaiige_google_client_id', sanitize_text_field($_POST['google_client_id']));
        update_option('newsaiige_google_client_secret', sanitize_text_field($_POST['google_client_secret']));
        update_option('newsaiige_facebook_app_id', sanitize_text_field($_POST['facebook_app_id']));
        update_option('newsaiige_facebook_app_secret', sanitize_text_field($_POST['facebook_app_secret']));
        echo '<div class="notice notice-success"><p>Configuration sauvegardée !</p></div>';
    }
    
    $google_client_id = get_option('newsaiige_google_client_id', '');
    $google_client_secret = get_option('newsaiige_google_client_secret', '');
    $facebook_app_id = get_option('newsaiige_facebook_app_id', '');
    $facebook_app_secret = get_option('newsaiige_facebook_app_secret', '');
    ?>
    <div class="wrap">
        <h1>Configuration des Connexions Sociales</h1>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row">Google Client ID</th>
                    <td><input type="text" name="google_client_id" value="<?php echo esc_attr($google_client_id); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Google Client Secret</th>
                    <td><input type="text" name="google_client_secret" value="<?php echo esc_attr($google_client_secret); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Facebook App ID</th>
                    <td><input type="text" name="facebook_app_id" value="<?php echo esc_attr($facebook_app_id); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Facebook App Secret</th>
                    <td><input type="text" name="facebook_app_secret" value="<?php echo esc_attr($facebook_app_secret); ?>" class="regular-text" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
```

---

## 🔧 ÉTAPE 4 : INTÉGRER LES CALLBACKS

### 4.1 Inclure les callbacks
Ajoutez cette ligne en haut de `register-form.php` et `login-form.php` :

```php
<?php
require_once 'oauth-callbacks.php';
// ... reste du code
```

### 4.2 Ou bien inclure dans functions.php
```php
// Dans functions.php
require_once get_template_directory() . '/oauth-callbacks.php';
```

---

## 🔧 ÉTAPE 5 : TESTER

### 5.1 Vérifications
1. ✅ Les clés sont bien configurées dans WordPress
2. ✅ Les URLs de redirection sont correctes
3. ✅ Les callbacks sont inclus
4. ✅ HTTPS est activé (obligatoire pour OAuth)

### 5.2 Test de connexion
1. Cliquez sur "Connexion avec Google"
2. Vérifiez que ça redirige vers Google
3. Après autorisation, vérifiez que ça revient sur votre site
4. L'utilisateur doit être connecté ou redirigé vers l'inscription

---

## 🚨 POINTS CRITIQUES

### URLs de redirection
⚠️ **TRÈS IMPORTANT** : Les URLs de redirection doivent être EXACTEMENT :
- Google : `https://votre-site.com/wp-admin/admin-ajax.php?action=google_login_callback`
- Facebook : `https://votre-site.com/wp-admin/admin-ajax.php?action=facebook_login_callback`

### HTTPS obligatoire
🔒 OAuth ne fonctionne qu'en **HTTPS**. En local, utilisez :
- `https://localhost`
- Ou configurez un certificat SSL local

### Permissions Facebook
📧 Demandez la permission `email` dans votre app Facebook.

---

## 🔧 FICHIERS À MODIFIER

### 1. Inclure les callbacks
**register-form.php** (ligne 1) :
```php
<?php require_once 'oauth-callbacks.php'; ?>
```

**login-form.php** (ligne 1) :
```php
<?php require_once 'oauth-callbacks.php'; ?>
```

### 2. Ajouter l'admin (optionnel)
**functions.php** : Ajouter le code d'administration des clés

---

## 🎯 RÉSUMÉ FINAL

**CE QU'IL VOUS FAUT FAIRE MAINTENANT :**

1. ✅ Créer les apps Google et Facebook
2. ✅ Récupérer les 4 clés (2 Google + 2 Facebook)  
3. ✅ Les configurer dans WordPress
4. ✅ Inclure `oauth-callbacks.php` dans vos formulaires
5. ✅ Tester en HTTPS

**Durée estimée :** 30-45 minutes

Les boutons et le code sont déjà prêts, il ne manque que la configuration des applications externes ! 🚀