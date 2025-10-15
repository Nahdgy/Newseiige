# 🚀 Guide d'Installation - NewSaiige Reviews System

## ❌ **Problèmes qui peuvent empêcher l'activation :**

### 1. **Erreurs d'exécution PHP**
- Code trop long pour un snippet
- Timeout d'exécution
- Mémoire insuffisante

### 2. **Conflits de fonctions**
- Fonctions déjà déclarées
- Noms de fonctions en conflit

### 3. **Base de données manquante**
- Table `wp_newsaiige_reviews` inexistante
- Droits insuffisants sur la base

### 4. **Snippets WordPress limités**
- Certains plugins de snippets ont des limitations
- Problèmes avec les fichiers trop volumineux

---

## ✅ **3 SOLUTIONS POUR ACTIVER LE SYSTÈME**

### **SOLUTION 1 : Via Functions.php (RECOMMANDÉE)**

1. **Allez dans votre thème WordPress :**
   - `Apparence > Éditeur de thème`
   - Sélectionnez `functions.php`

2. **Ajoutez ce code à la fin du fichier :**

```php
// NewSaiige Reviews System - DÉBUT
if (!function_exists('newsaiige_reviews_shortcode')) {
    // Copiez tout le contenu du fichier reviews.php ICI
    // SAUF la première ligne <?php et la dernière ligne ?>
}
// NewSaiige Reviews System - FIN
```

3. **Créez la table de base de données :**
   - Allez dans phpMyAdmin
   - Exécutez le script SQL fourni dans `create_reviews_table.sql`

---

### **SOLUTION 2 : Plugin personnalisé**

1. **Créez un dossier dans `/wp-content/plugins/` :**
   ```
   newsaiige-reviews/
   └── newsaiige-reviews.php
   ```

2. **Copiez le contenu du fichier `reviews.php` dans `newsaiige-reviews.php`**

3. **Activez le plugin dans WordPress :**
   - `Extensions > Extensions installées`
   - Activez "NewSaiige Reviews System"

---

### **SOLUTION 3 : Code Snippets simplifié**

Si vous utilisez un plugin comme "Code Snippets", voici une version réduite :

```php
// ÉTAPE 1 : Ajoutez ce snippet
function newsaiige_reviews_basic() {
    return '
    <div style="padding:40px; text-align:center; background:#f8f9fa; border-radius:15px;">
        <h2 style="color:#82897F;">Elles aiment NewSaiige !</h2>
        <div style="font-size:2rem; color:#FFD700;">★★★★★</div>
        <p><strong>5,0/5</strong> (242 avis)</p>
        <div style="background:white; padding:30px; margin:20px 0; border-radius:15px; box-shadow:0 10px 30px rgba(0,0,0,0.1);">
            <p style="font-style:italic; color:#333;">
                "J\'ai récemment testé l\'huile corps nacrée de Newsaiige, et je dois dire que c\'est une belle découverte ! Sa texture légère et non grasse s\'applique facilement et laisse la peau douce, hydratée et délicatement parfumée."
            </p>
            <small style="color:#82897F; font-weight:600;">- Marie L.</small>
        </div>
        <button style="background:#82897F; color:white; padding:15px 30px; border:none; border-radius:25px; font-size:16px; cursor:pointer;">
            Partager votre expérience
        </button>
    </div>';
}
add_shortcode('newsaiige_reviews_basic', 'newsaiige_reviews_basic');
```

---

## 🔧 **Diagnostic des problèmes**

### **Test 1 : Vérifiez si le shortcode fonctionne**
```php
// Ajoutez ce code de test dans Code Snippets
function test_newsaiige() {
    return '<div style="background:green; color:white; padding:20px;">✅ Le système NewSaiige fonctionne !</div>';
}
add_shortcode('test_newsaiige', 'test_newsaiige');
```

Puis utilisez `[test_newsaiige]` dans une page.

### **Test 2 : Vérifiez la base de données**
```php
// Test de la table
function check_newsaiige_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'newsaiige_reviews';
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    return $exists ? "✅ Table trouvée" : "❌ Table manquante";
}
add_shortcode('check_table', 'check_newsaiige_table');
```

---

## 🎯 **Instructions spécifiques par méthode**

### **Pour Code Snippets Pro :**
- Type : "PHP Snippet"
- Scope : "Global"
- Priority : "10"

### **Pour Functions.php :**
- Placez le code AVANT la balise de fermeture `?>`
- Sauvegardez et testez

### **Pour un plugin :**
- Assurez-vous que le dossier a les bonnes permissions
- Vérifiez les logs d'erreur WordPress

---

## 📞 **En cas de problème :**

1. **Vérifiez les logs d'erreur :**
   - `/wp-content/debug.log`
   - Panneau d'administration > Outils > Santé du site

2. **Désactivez temporairement :**
   - Autres plugins de cache
   - Plugins de sécurité
   - Minification CSS/JS

3. **Contactez votre hébergeur :**
   - Si problèmes de mémoire PHP
   - Si problèmes de base de données

---

## ✨ **Une fois installé, utilisez :**

```
[newsaiige_reviews]
```

Dans n'importe quelle page ou article WordPress !