# Guide d'utilisation - Featured Products Showcase

## 🌟 **Vue d'ensemble**

Ce snippet affiche les produits avec étoile (featured) dans un design inspiré de la maquette "Nos clients aiment" avec des cartes glassmorphism et une navigation élégante.

## 🎯 **Configuration actuelle**

### **Mode actif : Produits avec étoile (Featured)**
```php
// Requête pour les produits featured
'meta_query' => array(
    array(
        'key' => '_featured',
        'value' => 'yes',
        'compare' => '='
    )
)
```

### **Mode en commentaire : Meilleures ventes**
```php
/* ALTERNATIVE: Configuration pour les meilleures ventes
$args = array(
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => intval($atts['limit']),
    'meta_key' => 'total_sales',
    'orderby' => 'meta_value_num',
    'order' => 'DESC'
);
*/
```

## 🚀 **Utilisation du shortcode**

### **Syntaxe de base**
```php
[newsaiige_featured_showcase]
```

### **Avec paramètres personnalisés**
```php
[newsaiige_featured_showcase 
    title="Nos bestsellers" 
    subtitle="Les produits que vous préférez" 
    limit="6" 
    show_navigation="true" 
    background_overlay="true"]
```

## 📋 **Paramètres disponibles**

| Paramètre | Description | Défaut | Options |
|-----------|-------------|---------|---------|
| `title` | Titre principal | "Nos clients aiment" | Texte libre |
| `subtitle` | Sous-titre descriptif | "Vous les adorez !..." | Texte libre |
| `limit` | Nombre de produits à afficher | `3` | Nombre entier |
| `show_navigation` | Afficher les boutons de navigation | `true` | `true`/`false` |
| `background_overlay` | Afficher l'arrière-plan dégradé | `true` | `true`/`false` |

## 🎨 **Fonctionnalités visuelles**

### **Design glassmorphism :**
- ✅ **Cartes transparentes** avec backdrop-filter
- ✅ **Effet de flou** et saturation
- ✅ **Bordures lumineuses** subtiles
- ✅ **Ombres réalistes** multicouches

### **Badges et indicateurs :**
- 🏷️ **Badge catégorie** en haut à gauche
- ⭐ **Badge "Coup de cœur"** animé en haut à droite
- 🛒 **Icône panier** interactive en bas à droite
- 💰 **Prix** avec gestion des promotions

### **Animations avancées :**
- 📱 **Apparition séquentielle** des cartes
- 🎭 **Hover effects** avec élévation
- ⚡ **Pulse animation** sur le badge vedette
- 🔄 **Navigation fluide** avec transitions

## 🛠️ **Fonctionnalités techniques**

### **Navigation intelligente :**
- ◀️ **Boutons précédent/suivant** adaptatifs
- 📱 **Responsive** : 3 produits desktop, 1 mobile
- 🚫 **Désactivation automatique** des boutons aux limites
- 🎛️ **Position adaptative** selon l'écran

### **Gestion du panier :**
- 🛒 **Simulation d'ajout** avec feedback visuel
- ✅ **États visuels** : normal → ajouté
- 🔔 **Notifications** toast élégantes
- 🔄 **Animation de clic** avec scale

### **Système responsive :**
```css
/* Desktop */
@media (min-width: 769px) {
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
}

/* Mobile */
@media (max-width: 768px) {
    grid-template-columns: 1fr;
}
```

## 🔧 **Configuration des produits**

### **Pour utiliser les produits avec étoile :**
1. **Admin WordPress** → WooCommerce → Produits
2. **Éditer un produit** → Données du produit
3. **Cocher "Produit vedette"** ⭐
4. **Sauvegarder** le produit

### **Pour basculer vers les meilleures ventes :**
1. **Ouvrir** `featured-products-showcase.php`
2. **Commenter** la section featured :
```php
/* 
$args = array(
    'meta_query' => array(
        array(
            'key' => '_featured',
            'value' => 'yes'
        )
    )
);
*/
```
3. **Décommenter** la section meilleures ventes
4. **Sauvegarder** le fichier

## 🎭 **Exemples d'usage**

### **Page d'accueil - Section hero**
```php
[newsaiige_featured_showcase 
    title="Nos coups de cœur" 
    subtitle="Découvrez nos produits les plus appréciés" 
    limit="3"]
```

### **Page boutique - Mise en avant**
```php
[newsaiige_featured_showcase 
    title="Sélection premium" 
    subtitle="Notre équipe a sélectionné ces pépites pour vous" 
    limit="6" 
    background_overlay="false"]
```

### **Landing page - Social proof**
```php
[newsaiige_featured_showcase 
    title="Nos clients adorent" 
    subtitle="Les produits qui font l'unanimité" 
    limit="4" 
    show_navigation="false"]
```

## 📱 **Comportement responsive**

### **Desktop (> 768px) :**
- 🖥️ **3 colonnes** adaptatives
- 🎛️ **Navigation latérale** fixe
- 🎨 **Cartes 350px** minimum

### **Tablette (≤ 768px) :**
- 📱 **1 colonne** centrée
- 🎛️ **Navigation centrée** en bas
- 📐 **Hauteur réduite** (300px)

### **Mobile (≤ 480px) :**
- 📲 **Layout compact** optimisé
- 🎭 **Tailles de police** réduites
- 🎨 **Espacement minimaliste**

## ⚡ **Performance**

- 🚀 **CSS intégré** : Pas de fichiers externes
- 🎯 **JavaScript vanilla** : Pas de jQuery requis
- 🔄 **Lazy loading** des images background
- 📦 **Code optimisé** avec animations CSS natives

Le snippet est prêt à utiliser avec une configuration flexible et un design professionnel ! 🌿