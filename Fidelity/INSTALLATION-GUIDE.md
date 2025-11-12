# Installation du Système de Fidélité Newsaiige

## Étape 1 : Import de la Base de Données

### Option A : Via phpMyAdmin (Recommandé)

1. **Connectez-vous à phpMyAdmin**
   - Allez dans votre interface d'hébergement (cPanel, Plesk, etc.)
   - Cliquez sur phpMyAdmin

2. **Sélectionnez votre base de données WordPress**
   - Cliquez sur le nom de votre base de données WordPress dans la liste de gauche

3. **Importez le fichier SQL**
   - Cliquez sur l'onglet "Importer"
   - Cliquez sur "Choisir un fichier" et sélectionnez `loyalty-database.sql`
   - Cliquez sur "Exécuter"

4. **Vérification**
   - Vous devriez voir 5 nouvelles tables créées :
     - `wp_newsaiige_loyalty_points`
     - `wp_newsaiige_loyalty_tiers` 
     - `wp_newsaiige_loyalty_vouchers`
     - `wp_newsaiige_loyalty_user_tiers`
     - `wp_newsaiige_loyalty_settings`

### Option B : Via ligne de commande

```bash
mysql -u votre_utilisateur -p votre_base_wordpress < loyalty-database.sql
```

### Option C : Via un client MySQL

Utilisez MySQL Workbench, HeidiSQL, ou tout autre client MySQL pour exécuter le contenu du fichier `loyalty-database.sql`.

## Étape 2 : Installation des Fichiers PHP

1. **Copiez les fichiers dans votre thème**
   ```
   /wp-content/themes/votre-theme/Fidelity/
   ├── loyalty-system.php
   ├── loyalty-admin.php
   ├── loyalty-woocommerce.php
   ├── newsaiige-loyalty-plugin.php
   └── assets/
       ├── loyalty.css
       ├── loyalty.js
       ├── admin.css
       └── admin.js
   ```

2. **Incluez le système dans functions.php**
   ```php
   // Système de fidélité Newsaiige
   require_once get_template_directory() . '/Fidelity/loyalty-system.php';
   require_once get_template_directory() . '/Fidelity/loyalty-admin.php';
   require_once get_template_directory() . '/Fidelity/loyalty-woocommerce.php';
   require_once get_template_directory() . '/Fidelity/newsaiige-loyalty-plugin.php';
   ```

## Étape 3 : Configuration

### Dans l'Admin WordPress

1. **Accédez au menu de fidélité**
   - Allez dans WordPress Admin → Fidélité

2. **Configurez les paramètres**
   - Points par euro : 1 (par défaut)
   - Durée de validité des points : 365 jours
   - Durée de validité des bons : 90 jours
   - Minimum pour conversion : 50 points
   - Valeur par point : 0.02€

3. **Vérifiez les paliers**
   - Bronze : 0 points (5% anniversaire)
   - Argent : 100 points (10% anniversaire) 
   - Or : 300 points (15% anniversaire)
   - Platine : 500 points (20% anniversaire)

### Configuration WooCommerce

1. **Catégorie d'abonnement**
   - Créez une catégorie "soins" pour les produits qui donnent droit aux points
   - Ou modifiez le paramètre `subscription_category_slug` dans les réglages

2. **Champ anniversaire**
   - Le système utilise le champ `billing_birthday` dans le profil utilisateur
   - Vous pouvez l'ajouter manuellement ou utiliser un plugin comme "WooCommerce Checkout Field Editor"

## Étape 4 : Utilisation

### Shortcodes Disponibles

```php
// Interface complète de fidélité
[newsaiige_loyalty title="Mon Programme de Fidélité" subtitle="Gagnez des points à chaque achat"]

// Widget points dans le compte utilisateur  
[loyalty_widget]

// Historique des points
[loyalty_history]
```

### Pages suggérées

1. **Page Programme de Fidélité**
   - Créez une nouvelle page
   - Ajoutez le shortcode `[newsaiige_loyalty]`

2. **Intégration compte utilisateur**
   - Ajoutez un onglet "Fidélité" dans "Mon compte" WooCommerce
   - Le système se charge automatiquement de l'intégration

### Fonctionnalités Automatiques

✅ **Attribution automatique des points** lors des commandes complétées  
✅ **Calcul automatique des paliers** et notifications email  
✅ **Vérification quotidienne des anniversaires** et envoi des bons  
✅ **Nettoyage automatique** des points et bons expirés  
✅ **Intégration WooCommerce** pour l'utilisation des bons au checkout  

## Étape 5 : Vérifications

### Vérifiez que tout fonctionne

1. **Tables créées** : 5 tables doivent être présentes dans votre base
2. **Admin accessible** : Menu "Fidélité" dans l'admin WordPress
3. **Shortcode fonctionnel** : Test sur une page avec `[newsaiige_loyalty]`
4. **Attribution des points** : Testez une commande complétée
5. **Emails envoyés** : Vérifiez les notifications de paliers

### Logs et Debug

- Activez `WP_DEBUG` dans wp-config.php pour voir les erreurs éventuelles
- Consultez les logs d'erreur de votre serveur
- Vérifiez la console du navigateur pour les erreurs JavaScript

## Dépannage

### Problèmes courants

**❌ Tables non créées**
- Vérifiez que le fichier SQL a été correctement importé
- Vérifiez les droits de votre utilisateur MySQL

**❌ Points non attribués**
- Vérifiez que l'utilisateur a un abonnement actif (catégorie "soins")
- Vérifiez que `subscription_required` est configuré correctement

**❌ Emails non envoyés**  
- Testez l'envoi d'email WordPress avec un plugin comme "WP Mail SMTP"
- Vérifiez que `email_notifications_enabled` est activé

**❌ Interface non stylée**
- Vérifiez que les fichiers CSS sont bien chargés
- Vérifiez le chemin vers les assets dans votre thème

### Support

Pour toute question ou problème :
1. Vérifiez d'abord ce guide
2. Consultez les logs d'erreur  
3. Testez sur un environnement de développement d'abord

## Maintenance

### Tâches régulières
- Le système se maintient automatiquement via les tâches cron WordPress
- Nettoyage quotidien des données expirées
- Vérification des anniversaires chaque jour

### Sauvegarde
- Sauvegardez régulièrement les 5 tables de fidélité
- Incluez-les dans vos sauvegardes WordPress habituelles

---

**Version du système :** 1.0  
**Compatibilité :** WordPress 5.0+, WooCommerce 4.0+  
**Date :** Novembre 2025