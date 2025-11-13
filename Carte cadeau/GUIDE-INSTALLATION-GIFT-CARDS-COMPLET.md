# 🎁 NewSaiige Gift Cards - Guide d'Installation Complet

## 📋 Vue d'ensemble

Système complet de cartes cadeaux pour WordPress avec :
- ✅ Intégration WooCommerce pour les paiements
- ✅ Génération automatique de codes uniques
- ✅ Envoi automatique par email
- ✅ Interface d'administration complète
- ✅ Validation publique des codes
- ✅ Design responsive et moderne

## 🎯 Prérequis

### Obligatoires
- **WordPress** 5.0 ou supérieur
- **PHP** 7.4 ou supérieur
- **WooCommerce** 5.0 ou supérieur
- **MySQL** 5.7 ou supérieur

### Recommandés
- **SSL** activé (HTTPS)
- **Cron** WordPress fonctionnel
- **Email** SMTP configuré
- **PHP** memory_limit ≥ 256M

## 🚀 Installation

### Option 1 : Installation Plugin (Recommandée)

1. **Copier les fichiers** dans `/wp-content/plugins/newsaiige-gift-cards/` :
   ```
   newsaiige-gift-cards/
   ├── newsaiige-gift-cards-plugin.php (fichier principal)
   ├── newsaiige-gift-cards.php
   ├── gift-cards-admin.php
   ├── gift-card-validator.php
   └── create_gift_cards_table.sql
   ```

2. **Activer le plugin** :
   - Aller dans `Plugins > Plugins installés`
   - Trouver "NewSaiige Gift Cards"
   - Cliquer sur "Activer"

3. **Configuration automatique** :
   - La table de base sera créée automatiquement
   - Les pages par défaut seront générées
   - Les tâches cron seront programmées

### Option 2 : Intégration Functions.php

Ajouter dans `functions.php` de votre thème :

```php
// Inclure le système de cartes cadeaux depuis les plugins
require_once WP_PLUGIN_DIR . '/newsaiige-gift-cards/newsaiige-gift-cards.php';
require_once WP_PLUGIN_DIR . '/newsaiige-gift-cards/gift-cards-admin.php';
require_once WP_PLUGIN_DIR . '/newsaiige-gift-cards/gift-card-validator.php';

// Activer le système
add_action('after_setup_theme', function() {
    newsaiige_gift_cards_init();
});
```

### Option 3 : Installation Manuelle Base de Données

1. **Exécuter le SQL** dans phpMyAdmin :
   ```sql
   -- Contenu du fichier create_gift_cards_table.sql
   ```

2. **Inclure les fichiers** dans votre projet

3. **Tester la configuration**

## 🛠 Configuration Post-Installation

### 1. Vérifier WooCommerce

Aller dans `WooCommerce > Paramètres` :

- **Général** : Configurer devise et pays
- **Paiements** : Activer vos moyens de paiement
- **Emails** : Vérifier que les emails sont activés
- **Produits > Inventaire** : Autoriser les téléchargements

### 2. Créer les Pages

#### Page d'Achat (Automatique ou Manuelle)
```
Titre : "Cartes Cadeaux"
Contenu : [newsaiige_gift_cards title="Offrir une Carte Cadeau" subtitle="Faites plaisir à vos proches"]
```

#### Page de Validation (Automatique ou Manuelle)
```
Titre : "Vérifier ma Carte Cadeau"
Contenu : [newsaiige_gift_card_validator title="Vérifier votre Carte" subtitle="Entrez votre code"]
```

### 3. Configuration Email

Dans `wp-config.php` ou via plugin SMTP :

```php
// Configuration SMTP recommandée
define('SMTP_HOST', 'votre-smtp.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'votre-email@domaine.com');
define('SMTP_PASS', 'votre-mot-de-passe');
```

### 4. Tester le Système

1. **Test d'achat** :
   - Aller sur la page cartes cadeaux
   - Remplir le formulaire
   - Effectuer un paiement test
   - Vérifier l'email reçu

2. **Test de validation** :
   - Aller sur la page de validation
   - Entrer le code reçu
   - Vérifier les informations affichées

## 🎨 Personnalisation

### Shortcodes Disponibles

#### Formulaire d'Achat
```
[newsaiige_gift_cards title="Titre personnalisé" subtitle="Sous-titre" min_amount="10" max_amount="500"]
```

**Paramètres** :
- `title` : Titre du formulaire
- `subtitle` : Sous-titre
- `min_amount` : Montant minimum (défaut: 10)
- `max_amount` : Montant maximum (défaut: 1000)

#### Validation de Codes
```
[newsaiige_gift_card_validator title="Titre" subtitle="Sous-titre"]
```

### Personnalisation CSS

Ajouter dans votre thème ou Customizer :

```css
/* Style du formulaire */
.newsaiige-gift-card-form {
    max-width: 600px;
    margin: 0 auto;
    padding: 30px;
    background: #f9f9f9;
    border-radius: 15px;
}

/* Style des boutons */
.newsaiige-gift-card-button {
    background: linear-gradient(45deg, #YOUR_COLOR_1, #YOUR_COLOR_2);
    color: white;
    padding: 15px 30px;
    border-radius: 25px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.newsaiige-gift-card-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}

/* Style du validateur */
.newsaiige-validator-container {
    text-align: center;
    padding: 40px 20px;
}
```

### Personnalisation Emails

Les templates d'email sont dans le code PHP. Pour les modifier :

1. **Localiser** la fonction `newsaiige_send_gift_card_email()`
2. **Modifier** le contenu HTML
3. **Tester** l'envoi

## 🔧 Administration

### Interface Admin

Accès via `Cartes Cadeaux` dans le menu WordPress :

- **Liste** : Voir toutes les cartes
- **Statistiques** : Analytics détaillées
- **Paramètres** : Configuration
- **Aide** : Documentation

### Actions Disponibles

- ✅ Voir les détails d'une carte
- ✅ Renvoyer un email
- ✅ Marquer comme utilisée
- ✅ Exporter les données
- ✅ Rechercher et filtrer
- ✅ Actions en lot

### Gestion des Statuts

- **pending** : En attente de paiement
- **paid** : Payée, prête à être envoyée
- **sent** : Envoyée par email
- **used** : Utilisée par le client
- **expired** : Expirée

## 🛡 Sécurité

### Codes Sécurisés
- Génération cryptographiquement sécurisée
- Format : `NSGG-XXXX-XXXX`
- Vérification d'unicité
- Protection contre les collisions

### Protection des Données
- Validation des entrées utilisateur
- Échappement des sorties
- Requêtes préparées
- Vérification des permissions

### Prévention des Abus
- Limite de montant (10€ - 1000€)
- Limite de quantité (1-10)
- Protection CSRF
- Rate limiting recommandé

## 🔄 Maintenance

### Tâches Automatiques

Le système programme automatiquement :
- **Nettoyage quotidien** des cartes expirées
- **Suppression** des commandes en attente anciennes
- **Envoi différé** des emails

### Surveillance

Surveiller dans les logs WordPress :
- Erreurs de paiement
- Échecs d'envoi d'email
- Erreurs de base de données

### Sauvegarde

Inclure dans vos sauvegardes :
- Table `wp_newsaiige_gift_cards`
- Fichiers du plugin/thème
- Configuration WooCommerce

## 🐛 Dépannage

### Problèmes Courants

#### Les emails ne sont pas envoyés
1. Vérifier la configuration SMTP
2. Tester l'envoi d'email WordPress
3. Vérifier les logs d'erreur
4. Contrôler le cron WordPress

#### Les paiements ne fonctionnent pas
1. Vérifier WooCommerce
2. Tester les moyens de paiement
3. Contrôler les webhooks
4. Vérifier les logs de commande

#### Erreurs de base de données
1. Vérifier les permissions MySQL
2. Contrôler la création de table
3. Vérifier les contraintes
4. Réinstaller si nécessaire

### Debug

Activer le debug WordPress dans `wp-config.php` :

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Consulter `/wp-content/debug.log`

### Support

1. **Logs WordPress** : Première étape de diagnostic
2. **Test de composants** : Isoler le problème
3. **Documentation** : Vérifier la configuration
4. **Contact support** : Si problème persistant

## 📊 Analytics et Suivi

### Métriques Disponibles

Via l'interface admin :
- Nombre total de cartes
- Chiffre d'affaires généré
- Cartes actives/utilisées/expirées
- Montant moyen des cartes
- Évolution temporelle

### Intégration Google Analytics

Ajouter un suivi des conversions :

```javascript
// Après un achat réussi
gtag('event', 'purchase', {
    'transaction_id': 'GIFT_CARD_ID',
    'value': MONTANT,
    'currency': 'EUR',
    'items': [{
        'item_name': 'Carte Cadeau NewSaiige',
        'category': 'Gift Cards',
        'quantity': QUANTITE,
        'price': MONTANT
    }]
});
```

## 🚀 Optimisations

### Performance

- **Cache** : Compatible avec les plugins de cache
- **Base de données** : Index optimisés
- **Images** : Optimiser les visuels
- **CDN** : Utiliser un CDN pour les assets

### SEO

- Pages avec métadonnées appropriées
- URLs friendly pour les pages de cartes
- Sitemap XML incluant les pages
- Schema.org pour les produits

### Conversion

- A/B tester les titres et descriptions
- Optimiser le tunnel d'achat
- Simplifier le formulaire
- Améliorer les CTA

## 📈 Évolutions Futures

### Fonctionnalités Planifiées

- 🔄 Cartes rechargeables
- 📱 Application mobile
- 🎨 Templates d'email personnalisables
- 📊 Analytics avancées
- 🌍 Multi-langues
- 💳 Intégration portefeuilles digitaux

### Extensibilité

Le système est conçu pour être étendu :
- Hooks et filtres WordPress
- API REST potentielle
- Intégrations tierces
- Personnalisations avancées

---

## ✅ Checklist d'Installation

- [ ] WordPress ≥ 5.0 installé
- [ ] WooCommerce ≥ 5.0 activé
- [ ] PHP ≥ 7.4 configuré
- [ ] SSL activé (HTTPS)
- [ ] Plugin/fichiers installés
- [ ] Base de données créée
- [ ] Pages créées et testées
- [ ] Moyens de paiement configurés
- [ ] SMTP configuré
- [ ] Premier test d'achat effectué
- [ ] Email de carte cadeau reçu
- [ ] Validation de code testée
- [ ] Interface admin explorée
- [ ] Styles personnalisés (optionnel)
- [ ] Analytics configurées (optionnel)

## 📞 Support et Contact

Pour toute question ou assistance :
- 📧 **Email** : support@newsaiige.com
- 📚 **Documentation** : Interface admin > Aide
- 🐛 **Bugs** : Logs WordPress + description détaillée
- 💡 **Suggestions** : Contact support avec vos idées

---

**Version** : 1.0.0  
**Dernière mise à jour** : Décembre 2024  
**Compatibilité** : WordPress 5.0+, WooCommerce 5.0+, PHP 7.4+