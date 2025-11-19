# 🎯 Guide d'Installation - Reviews par Prestation

## 📋 Vue d'ensemble

Ce système permet d'afficher et de gérer des avis clients liés à des prestations spécifiques (produits/services). Il complète le système d'avis général existant.

---

## 🔧 Installation

### **Étape 1 : Mettre à jour la base de données**

Exécutez le script SQL `update-reviews-table.sql` dans phpMyAdmin :

1. Connectez-vous à phpMyAdmin
2. Sélectionnez votre base de données WordPress
3. Allez dans l'onglet "SQL"
4. Copiez et exécutez le contenu du fichier `update-reviews-table.sql`

Cela ajoutera les colonnes `service_id` et `service_name` à la table existante.

### **Étape 2 : Mettre à jour functions-newsaiige-reviews.php**

Le fichier `functions-newsaiige-reviews.php` a déjà été mis à jour pour supporter les prestations. Les modifications incluent :

- ✅ Capture du `service_id` et `service_name` lors de la soumission
- ✅ Enregistrement dans la base de données
- ✅ Affichage dans l'interface admin avec badge de prestation
- ✅ Comptage des avis par prestation

### **Étape 3 : Ajouter le fichier reviews-by-service.php**

Copiez le fichier `reviews-by-service.php` dans votre thème WordPress :
```
/wp-content/themes/votre-theme/reviews-by-service.php
```

---

## 📱 Utilisation des shortcodes

### **Version 1 : Avis généraux (existant)**
```php
[newsaiige_reviews limit="10"]
```
Affiche tous les avis dans le carrousel d'origine.

### **Version 2 : Avis par prestation (nouveau)**

#### **Afficher les avis d'une prestation spécifique**
```php
[newsaiige_service_reviews service_id="123" service_name="Huile Corps Nacrée" limit="10"]
```

#### **Afficher tous les avis avec formulaire général**
```php
[newsaiige_service_reviews show_all_reviews="true" limit="20"]
```
Les utilisateurs peuvent choisir la prestation dans le formulaire.

#### **Afficher les avis d'une prestation sans formulaire**
```php
[newsaiige_service_reviews service_id="123" service_name="Massage Relaxant" show_form="false"]
```
Affiche uniquement les avis, sans possibilité d'en ajouter.

---

## ⚙️ Paramètres disponibles

| Paramètre | Type | Défaut | Description |
|-----------|------|--------|-------------|
| `service_id` | Entier | `0` | ID du produit/service WooCommerce |
| `service_name` | Texte | `''` | Nom de la prestation (affiché dans le badge) |
| `limit` | Entier | `10` | Nombre maximum d'avis à afficher |
| `show_form` | Booléen | `true` | Afficher le formulaire d'ajout d'avis |
| `show_all_reviews` | Booléen | `false` | Afficher tous les avis ou seulement ceux de la prestation |

---

## 🎨 Différences visuelles avec la version 1

### **Version 1 (Carrousel)**
- Affichage en carrousel avec navigation
- Design avec cards flottantes et glassmorphism
- Auto-scroll activé
- Pagination par groupes de 3
- Parfait pour la page d'accueil

### **Version 2 (Grille par prestation)**
- Affichage en grille responsive
- Design plus épuré et professionnel
- Cards avec hover effect
- Badge de prestation affiché
- Parfait pour les pages produit/service

---

## 💡 Exemples d'utilisation

### **Sur une page produit WooCommerce**
```php
// Dans votre template single-product.php
global $product;
$product_id = $product->get_id();
$product_name = $product->get_name();

echo do_shortcode('[newsaiige_service_reviews service_id="' . $product_id . '" service_name="' . $product_name . '" limit="15"]');
```

### **Sur une page de service personnalisée**
```php
[newsaiige_service_reviews service_id="789" service_name="Soin Visage Premium" limit="12"]
```

### **Page "Tous nos avis"**
```php
[newsaiige_service_reviews show_all_reviews="true" limit="50"]
```

---

## 🔍 Récupération du service_id

Pour les produits WooCommerce, le `service_id` correspond à l'ID du produit. Pour le trouver :

1. **Méthode 1 : Dans l'URL du produit**
   ```
   https://votresite.com/wp-admin/post.php?post=123&action=edit
   ```
   Le `123` est votre service_id

2. **Méthode 2 : Via code PHP**
   ```php
   $product_id = get_the_ID(); // Dans une boucle produit
   ```

3. **Méthode 3 : Liste des produits dans l'admin**
   - WooCommerce > Produits
   - Survolez un produit, l'ID s'affiche dans l'URL

---

## 📊 Interface Admin

L'interface admin a été mise à jour avec :

- **Nouvelle colonne "Prestation"** affichant le nom du service
- **Badge coloré** pour identifier rapidement la prestation
- **Filtrage maintenu** par statut (en attente, approuvé, rejeté)
- **Statistiques globales** + statistiques par prestation

---

## 🎯 Fonctionnalités clés

### **Pour le formulaire d'avis**
- ✅ Sélection automatique de la prestation (si service_id fourni)
- ✅ Liste déroulante des prestations (si service_id = 0)
- ✅ Exclusion automatique de la catégorie "E-Carte Cadeau"
- ✅ Validation en temps réel
- ✅ Messages de succès/erreur animés

### **Pour l'affichage**
- ✅ Grille responsive (3 colonnes desktop, 1 colonne mobile)
- ✅ Statistiques par prestation (note moyenne, nombre d'avis)
- ✅ Date formatée en français
- ✅ Badge de prestation (si show_all_reviews=true)
- ✅ Message si aucun avis

### **Pour l'admin**
- ✅ Modération avec aperçu de la prestation
- ✅ Comptage des avis par prestation
- ✅ Export possible via SQL
- ✅ Statistiques détaillées

---

## 🚀 Performances

Le système est optimisé avec :

- **Indexes SQL** sur service_id et status
- **Requêtes préparées** pour la sécurité
- **Lazy loading** des images (si ajouté)
- **Cache-friendly** (compatible avec WP Rocket, W3 Total Cache, etc.)

---

## 🔒 Sécurité

- ✅ Nonces WordPress pour tous les formulaires
- ✅ Sanitisation de toutes les entrées utilisateur
- ✅ Protection contre les doublons (24h par email)
- ✅ Modération obligatoire avant publication
- ✅ Enregistrement de l'IP et User-Agent

---

## 📞 Support & Debug

### **Test 1 : Vérifier la table**
```sql
DESCRIBE wp_newsaiige_reviews;
```
Vous devez voir les colonnes `service_id` et `service_name`.

### **Test 2 : Vérifier les avis**
```sql
SELECT id, customer_name, service_name, rating, status 
FROM wp_newsaiige_reviews 
ORDER BY created_at DESC 
LIMIT 10;
```

### **Test 3 : Statistiques par prestation**
```sql
SELECT 
    service_name, 
    COUNT(*) as total, 
    AVG(rating) as moyenne,
    status
FROM wp_newsaiige_reviews 
WHERE service_id > 0
GROUP BY service_id, service_name, status
ORDER BY total DESC;
```

---

## 🎨 Personnalisation CSS

Les classes CSS principales à personnaliser :

```css
/* Container principal */
.newsaiige-service-reviews { }

/* Badge de prestation */
.service-name-badge { }

/* Grille d'avis */
.service-reviews-grid { }

/* Card d'avis */
.service-review-card { }

/* Modale */
.service-modal-content { }
```

---

## 📝 Notes importantes

1. **Compatibilité** : Requiert WordPress 5.0+ et WooCommerce 3.0+
2. **Responsive** : Optimisé pour mobile, tablette et desktop
3. **Multilangue** : Prêt pour WPML/Polylang
4. **SEO-friendly** : Schema.org markup peut être ajouté
5. **RGPD** : Enregistrement de l'email optionnel

---

## ✅ Checklist d'installation

- [ ] Table de base de données mise à jour (colonnes ajoutées)
- [ ] Fichier `reviews-by-service.php` ajouté au thème
- [ ] Fichier `functions-newsaiige-reviews.php` mis à jour
- [ ] Test du shortcode sur une page
- [ ] Vérification de l'interface admin
- [ ] Test de soumission d'avis
- [ ] Test de modération
- [ ] Test responsive mobile

---

## 🎉 Exemples de requêtes SQL utiles

### **Avis les plus récents par prestation**
```sql
SELECT service_name, customer_name, rating, comment, created_at
FROM wp_newsaiige_reviews
WHERE status = 'approved' AND service_id > 0
ORDER BY created_at DESC
LIMIT 20;
```

### **Top 5 des prestations les mieux notées**
```sql
SELECT 
    service_name,
    COUNT(*) as total_avis,
    AVG(rating) as note_moyenne
FROM wp_newsaiige_reviews
WHERE status = 'approved' AND service_id > 0
GROUP BY service_id, service_name
HAVING COUNT(*) >= 3
ORDER BY note_moyenne DESC, total_avis DESC
LIMIT 5;
```

### **Prestations sans avis**
```sql
SELECT p.ID, p.post_title
FROM wp_posts p
LEFT JOIN wp_newsaiige_reviews r ON r.service_id = p.ID AND r.status = 'approved'
WHERE p.post_type = 'product' 
AND p.post_status = 'publish'
AND r.id IS NULL;
```

---

## 🆘 Problèmes courants

### **Les avis ne s'affichent pas**
- Vérifiez que des avis sont approuvés : `SELECT * FROM wp_newsaiige_reviews WHERE status='approved'`
- Vérifiez le service_id correspond bien à un produit existant

### **Le formulaire ne fonctionne pas**
- Vérifiez la console JavaScript (F12)
- Vérifiez que `functions-newsaiige-reviews.php` est bien chargé
- Vérifiez les nonces et AJAX

### **L'admin ne montre pas la colonne prestation**
- Videz le cache
- Rechargez la page avec CTRL+F5
- Vérifiez que les colonnes SQL ont été ajoutées

---

Vous êtes prêt ! 🚀
