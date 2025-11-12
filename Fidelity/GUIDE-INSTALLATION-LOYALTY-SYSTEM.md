# Système de Fidélité Newsaiige - Guide d'installation et d'utilisation

## Vue d'ensemble

Le système de fidélité Newsaiige est un plugin WordPress complet qui permet de gérer un programme de fidélité avec :

- **Points de fidélité** : Les clients gagnent des points à chaque achat
- **Paliers de fidélité** : Bronze, Argent, Or, Platine avec avantages progressifs
- **Bons d'achat** : Conversion des points en réductions utilisables
- **Offres anniversaire** : Bons d'achat spéciaux le jour de l'anniversaire
- **Interface utilisateur** : Page dédiée avec le style de votre thème
- **Administration complète** : Gestion des paliers, points, et statistiques

## Installation

### 1. Upload des fichiers

Copiez tous les fichiers dans votre dossier WordPress :

```
/wp-content/themes/votre-theme/
├── loyalty-system.php                 # Système principal
├── loyalty-admin.php                  # Interface d'administration  
├── loyalty-woocommerce.php           # Intégration WooCommerce
├── newsaiige-loyalty-plugin.php      # Plugin principal
└── assets/
    ├── loyalty.css                   # Styles frontend
    ├── loyalty.js                    # Scripts frontend
    ├── admin.css                     # Styles admin
    └── admin.js                      # Scripts admin
```

### 2. Activation dans functions.php

Ajoutez cette ligne dans le fichier `functions.php` de votre thème :

```php
require_once get_template_directory() . '/newsaiige-loyalty-plugin.php';
```

### 3. Vérification des prérequis

- WordPress 5.0+
- WooCommerce 5.0+
- PHP 7.4+
- MySQL 5.6+

## Configuration initiale

### 1. Accès à l'administration

Une fois activé, un nouveau menu "Fidélité" apparaît dans l'administration WordPress avec :

- **Tableau de bord** : Statistiques générales
- **Paliers** : Gestion des niveaux de fidélité  
- **Utilisateurs** : Suivi des membres du programme
- **Paramètres** : Configuration du système

### 2. Paramètres essentiels

Allez dans **Fidélité > Paramètres** et configurez :

#### Points
- **Points par euro** : 1 point = 1€ (partie entière seulement)
- **Durée de validité** : 365 jours par défaut
- **Minimum pour conversion** : 50 points minimum
- **Valeur de conversion** : 0,02€ par point (50 points = 1€)

#### Conditions d'éligibilité
- **Abonnement requis** : Coché par défaut
- **Catégorie d'abonnement** : "soins" (slug de la catégorie)

#### Notifications
- **Emails automatiques** : Activés pour les nouveaux paliers et anniversaires

### 3. Configuration des paliers

Les paliers par défaut sont créés automatiquement :

| Palier | Points requis | Bonus anniversaire | Bon d'achat offert |
|--------|---------------|-------------------|-------------------|
| Bronze | 0 | 5% | - |
| Argent | 100 | 10% | 5€ |
| Or | 300 | 15% | 15€ |
| Platine | 500 | 20% | 25€ |

Vous pouvez les modifier dans **Fidélité > Paliers**.

## Utilisation frontend

### 1. Page du programme de fidélité

Une page "Mon Programme de Fidélité" est créée automatiquement avec le shortcode :

```php
[newsaiige_loyalty]
```

### 2. Shortcodes disponibles

#### Affichage des points
```php
[newsaiige_loyalty_points show="both" style="inline"]
```
Options :
- `show` : "available", "total", "both"
- `style` : "inline", "block"

#### Page complète du programme
```php
[newsaiige_loyalty title="Mon Programme" subtitle="Gagnez des points..."]
```

### 3. Intégration dans l'espace client

Un onglet "Programme de Fidélité" est automatiquement ajouté à l'espace client WooCommerce.

### 4. Widget de fidélité

Un widget "Points de Fidélité Newsaiige" est disponible dans **Apparence > Widgets**.

## Fonctionnalités détaillées

### 1. Attribution des points

**Conditions** :
- Client connecté
- Abonnement actif (achat dans la catégorie "soins" dans les 60 derniers jours)
- Commande avec statut "terminée"

**Calcul** :
- 1 point par euro dépensé (partie entière)
- Points valables 365 jours
- Attribution automatique une seule fois par commande

### 2. Système de paliers

**Évolution automatique** :
- Calcul basé sur le total des points gagnés (pas les points disponibles)
- Email de félicitations automatique
- Bon d'achat offert selon le palier atteint

### 3. Bons d'achat

**Création** :
- Conversion manuelle par le client (minimum 50 points)
- Offerts automatiquement lors de l'atteinte d'un nouveau palier
- Bons d'anniversaire selon le palier actuel

**Utilisation** :
- Interface dédiée sur la page de checkout
- Application automatique de la réduction
- Marquage comme utilisé après paiement

### 4. Offres anniversaire

**Fonctionnement** :
- Vérification quotidienne automatique (cron)
- Champ anniversaire dans le profil utilisateur
- Email automatique avec code promo
- Pourcentage selon le palier actuel

## Administration

### 1. Tableau de bord

Statistiques en temps réel :
- Points totaux gagnés
- Points utilisés  
- Bons d'achat actifs
- Utilisateurs participants
- Activité mensuelle
- Top 10 des utilisateurs

### 2. Gestion des paliers

**Actions disponibles** :
- Créer/modifier/désactiver des paliers
- Définir points requis et avantages
- Configurer les bonus anniversaire
- Personnaliser les messages

### 3. Gestion des utilisateurs

**Fonctionnalités** :
- Recherche par nom/email
- Ajout manuel de points
- Consultation des statistiques individuelles
- Historique des transactions

### 4. Maintenance

**Nettoyage automatique** :
- Points expirés (quotidien)
- Bons d'achat expirés
- Optimisation de la base de données

## Base de données

### Tables créées automatiquement

1. **`wp_newsaiige_loyalty_points`** : Transactions de points
2. **`wp_newsaiige_loyalty_tiers`** : Définition des paliers
3. **`wp_newsaiige_loyalty_vouchers`** : Bons d'achat
4. **`wp_newsaiige_loyalty_user_tiers`** : Paliers utilisateurs
5. **`wp_newsaiige_loyalty_settings`** : Configuration

## Personnalisation

### 1. Styles CSS

Les styles sont dans `/assets/loyalty.css` et peuvent être personnalisés :

```css
/* Couleurs principales */
.loyalty-card {
    border-color: #82897F; /* Couleur principale */
}

/* Paliers personnalisés */
.tier-gold {
    background: linear-gradient(135deg, #ffd700, #ffb347);
}
```

### 2. Hooks WordPress disponibles

```php
// Après attribution de points
add_action('newsaiige_points_awarded', function($user_id, $points, $order_id) {
    // Action personnalisée
}, 10, 3);

// Nouveau palier atteint
add_action('newsaiige_tier_achieved', function($user_id, $tier) {
    // Action personnalisée
}, 10, 2);
```

### 3. Fonctions utilitaires

```php
// Obtenir les points d'un utilisateur
$points = newsaiige_get_user_loyalty_points($user_id);

// Obtenir le palier d'un utilisateur
$tier = newsaiige_get_user_loyalty_tier($user_id);
```

## Dépannage

### Problèmes courants

1. **Points non attribués** :
   - Vérifier que l'utilisateur a un abonnement actif
   - Contrôler le statut de la commande (doit être "terminée")
   - Vérifier les logs d'erreur WordPress

2. **Emails non envoyés** :
   - Vérifier la configuration SMTP de WordPress
   - Contrôler les paramètres dans Fidélité > Paramètres

3. **Bons d'achat non appliqués** :
   - Vérifier la date d'expiration
   - S'assurer que le code est correct
   - Contrôler que le bon n'a pas déjà été utilisé

### Logs et debugging

Activer le debug WordPress dans `wp-config.php` :

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Les logs du système de fidélité sont préfixés par `[LOYALTY]`.

## Support et mises à jour

### Sauvegarde recommandée

Avant toute modification :
1. Sauvegarde de la base de données
2. Sauvegarde des fichiers du thème
3. Test sur un environnement de développement

### Maintenance préventive

- Vérification mensuelle des points expirés
- Surveillance des performances de la base de données
- Mise à jour des paliers selon l'évolution du business

---

## Raccourcis d'administration

- **Tableau de bord** : `/wp-admin/admin.php?page=newsaiige-loyalty`
- **Paliers** : `/wp-admin/admin.php?page=newsaiige-loyalty-tiers`
- **Utilisateurs** : `/wp-admin/admin.php?page=newsaiige-loyalty-users`
- **Paramètres** : `/wp-admin/admin.php?page=newsaiige-loyalty-settings`

Le système est maintenant prêt à utiliser ! 🎉