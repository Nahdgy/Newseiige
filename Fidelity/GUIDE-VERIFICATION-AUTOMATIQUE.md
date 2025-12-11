# 🔄 SYSTÈME DE VÉRIFICATION AUTOMATIQUE DES PAIEMENTS

## Date : 2 décembre 2025

---

## 🎯 OBJECTIF

Attribuer automatiquement les points aux utilisateurs le lendemain de leur paiement d'abonnement.

**Exemple** : 
- 1er décembre : Véronique paie son abonnement de 59€
- 2 décembre à 02h00 : Le système attribue automatiquement 59 points

---

## ✅ CORRECTIONS APPLIQUÉES

### 1. **Erreur SQL dans admin.php (ligne 2522)**

**AVANT** :
```sql
-- Sous-requête inutile qui causait une erreur
(SELECT COUNT(*) FROM wp_newsaiige_loyalty_points p 
 WHERE p.order_id = o.id AND p.action_type = 'order') as has_points
```

**APRÈS** :
```sql
-- Supprimé - la colonne n'était pas nécessaire
```

### 2. **Nouvelle fonction dans system.php (lignes 533-633)**

Ajout de `daily_subscription_points_check()` :
- Cherche les paiements des dernières 48h sans points
- Attribue automatiquement les points manquants
- Logs détaillés de chaque opération

### 3. **Nouveau fichier cron.php**

Gestion complète des tâches automatiques :
- Configuration des tâches quotidiennes
- Page admin pour voir l'état des tâches
- Bouton pour exécuter manuellement

---

## 📥 INSTALLATION

### Étape 1 : Upload des fichiers (3 fichiers)

```
1. system.php → /wp-content/plugins/newsaiige-loyalty/includes/
2. admin.php → /wp-content/plugins/newsaiige-loyalty/includes/
3. cron.php → /wp-content/plugins/newsaiige-loyalty/includes/ (NOUVEAU)
```

### Étape 2 : Charger cron.php dans le plugin principal

Éditer le fichier principal du plugin (ex: `newsaiige-loyalty.php`) et ajouter :

```php
// Charger le système de tâches automatiques
require_once plugin_dir_path(__FILE__) . 'includes/cron.php';
```

**OU** si vous avez déjà un système de chargement de fichiers :

```php
$includes = array(
    'includes/system.php',
    'includes/admin.php',
    'includes/woocommerce.php',
    'includes/cron.php',  // ← AJOUTER CETTE LIGNE
);

foreach ($includes as $file) {
    require_once plugin_dir_path(__FILE__) . $file;
}
```

### Étape 3 : Activer WP_DEBUG_LOG

Dans `wp-config.php` :

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Étape 4 : Vérifier l'activation des tâches

1. Aller dans **Admin → Fidélité → 🕐 Tâches Auto**
2. Vous devriez voir 3 tâches actives :
   - ✓ Vérification des paiements (02h00)
   - ✓ Nettoyage des points (03h00)
   - ✓ Anniversaires (08h00)

---

## 🧪 TEST MANUEL

### Option 1 : Via l'interface admin

1. **Admin → Fidélité → 🕐 Tâches Auto**
2. Cliquer sur **"▶️ Exécuter la vérification maintenant"**
3. Vérifier les logs dans `/wp-content/debug.log`

### Option 2 : Via PHP dans le terminal

```php
<?php
require_once('./wp-load.php');
global $newsaiige_loyalty;
$newsaiige_loyalty->daily_subscription_points_check();
?>
```

### Option 3 : Via WP-CLI (si disponible)

```bash
wp eval 'global $newsaiige_loyalty; $newsaiige_loyalty->daily_subscription_points_check();'
```

---

## 📊 VÉRIFICATION

### Étape 1 : Vérifier les logs

```bash
tail -f /wp-content/debug.log | grep "daily_subscription_points_check"
```

Vous devriez voir :
```
[02-Dec-2025 02:00:01] daily_subscription_points_check: Démarrage de la vérification quotidienne
[02-Dec-2025 02:00:02] daily_subscription_points_check: Traitement paiement abonnement #1033
[02-Dec-2025 02:00:03] process_order_points: ✓✓✓ 59 points ATTRIBUÉS à user 123 pour commande #1033
[02-Dec-2025 02:00:03] daily_subscription_points_check: ✓ Points attribués pour abonnement #1033
[02-Dec-2025 02:00:04] daily_subscription_points_check: Terminé - 1 commandes traitées, 0 erreurs
```

### Étape 2 : Vérifier dans la base de données

```sql
-- Vérifier les points de Véronique
SELECT 
    u.user_email,
    SUM(p.points_earned) as total_points,
    SUM(p.points_available) as points_disponibles,
    COUNT(p.id) as nombre_transactions
FROM wp_users u
LEFT JOIN wp_newsaiige_loyalty_points p ON u.ID = p.user_id
WHERE u.user_email LIKE '%veronique%'
GROUP BY u.ID;

-- Voir l'historique complet des points
SELECT 
    p.id,
    p.points_earned,
    p.order_id,
    p.description,
    p.created_at,
    o.type as order_type,
    o.total as order_total
FROM wp_newsaiige_loyalty_points p
LEFT JOIN wp_wc_orders o ON p.order_id = o.id
WHERE p.user_id = [ID_VERONIQUE]
ORDER BY p.created_at DESC;
```

### Étape 3 : Vérifier dans l'admin WordPress

1. **Admin → Fidélité → Gérer Utilisateurs**
2. Chercher Véronique
3. Elle devrait avoir **59 points**

---

## ⏰ PLANNING DES TÂCHES AUTOMATIQUES

| Tâche | Heure | Fréquence | Description |
|-------|-------|-----------|-------------|
| **Vérification paiements** | 02h00 | Quotidienne | Attribue les points des paiements des 48 dernières heures |
| **Nettoyage points** | 03h00 | Quotidienne | Désactive les points expirés (>6 mois) |
| **Anniversaires** | 08h00 | Quotidienne | Attribue des points bonus pour les anniversaires |

---

## 🔧 FONCTIONNEMENT DÉTAILLÉ

### Algorithme de vérification quotidienne

```
1. Récupérer les commandes des 48 dernières heures
   - Types : wps_subscription, wps_subscriptions, shop_order
   - Statuts : wc-completed, wc-processing, wc-active
   - Montant > 0
   
2. Pour chaque commande :
   - Vérifier si des points existent déjà (table wp_newsaiige_loyalty_points)
   - SI NON :
     * Si type = wps_subscription OU wps_subscriptions → Attribuer points automatiquement
     * Si type = shop_order → NE PAS attribuer de points (seuls les abonnements donnent des points)
       
3. Logger chaque opération
4. Envoyer notification si points attribués
```

### Critères d'attribution

**Points attribués SI** :
- ✅ Type de commande = `wps_subscription` ou `wps_subscriptions`
- ✅ Statut = `wc-completed`, `wc-processing` ou `wc-active`
- ✅ Montant > 0€
- ✅ Pas de points déjà attribués pour cette commande
- ✅ Client connecté (customer_id > 0)

**Points refusés SI** :
- ❌ Points déjà attribués (évite les doublons)
- ❌ Commande sans utilisateur (customer_id = 0)
- ❌ Montant = 0€
- ❌ Type de commande non supporté

---

## 🐛 DÉPANNAGE

### Problème 1 : Les tâches ne s'exécutent jamais

**Cause** : WP-Cron désactivé ou site sans trafic

**Solution** :
```php
// Dans wp-config.php, vérifier que cette ligne N'existe PAS :
define('DISABLE_WP_CRON', true);

// Si vous avez peu de trafic, configurer un vrai cron :
// Ajouter dans le crontab du serveur :
*/15 * * * * wget -q -O - https://votresite.com/wp-cron.php?doing_wp_cron
```

### Problème 2 : Les points ne sont pas attribués

**Diagnostic** :
```bash
# 1. Vérifier les logs
tail -50 /wp-content/debug.log

# 2. Exécuter manuellement
# Admin → Fidélité → Tâches Auto → Exécuter maintenant

# 3. Vérifier la requête SQL
# Exécuter DIAGNOSTIC-ABONNEMENTS-POINTS.sql dans phpMyAdmin
```

**Causes possibles** :
- La commande a déjà des points (vérifier dans wp_newsaiige_loyalty_points)
- Le type de commande n'est pas reconnu (vérifier les logs)
- L'utilisateur n'a pas d'abonnement actif (pour shop_order)

### Problème 3 : Erreurs SQL

**Erreur** : `Unknown column 'o.total' in 'SELECT'`

**Solution** : Vérifier que HPOS est activé et que la table wp_wc_orders existe

```sql
-- Vérifier l'existence de la table
SHOW TABLES LIKE 'wp_wc_orders';

-- Si elle n'existe pas, activer HPOS dans :
-- WooCommerce → Réglages → Avancé → Fonctionnalités
-- ☑ High-Performance Order Storage
```

---

## 📈 MONITORING

### Logs à surveiller quotidiennement

```bash
# Voir les vérifications quotidiennes
grep "daily_subscription_points_check" /wp-content/debug.log | tail -20

# Voir les points attribués
grep "points ATTRIBUÉS" /wp-content/debug.log | tail -20

# Voir les erreurs
grep "ÉCHEC\|ERROR\|ERREUR" /wp-content/debug.log | tail -20
```

### Statistiques à vérifier

```sql
-- Nombre de points attribués par jour (derniers 7 jours)
SELECT 
    DATE(created_at) as date,
    COUNT(*) as nb_attributions,
    SUM(points_earned) as total_points
FROM wp_newsaiige_loyalty_points
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;

-- Commandes sans points (derniers 7 jours)
SELECT COUNT(*) as commandes_sans_points
FROM wp_wc_orders o
WHERE o.type IN ('wps_subscription', 'wps_subscriptions')
AND o.status IN ('wc-completed', 'wc-active')
AND o.date_created >= DATE_SUB(NOW(), INTERVAL 7 DAY)
AND NOT EXISTS (
    SELECT 1 FROM wp_newsaiige_loyalty_points p WHERE p.order_id = o.id
);
```

---

## 🎉 RÉSULTAT ATTENDU

### Scénario : Véronique paie son abonnement

**1er décembre 23h30** :
- Véronique effectue un paiement de 59€ pour son abonnement
- Commande #1033 créée avec type = `wps_subscription`
- Statut = `wc-active`

**2 décembre 02h00** :
- La tâche automatique s'exécute
- Détecte la commande #1033 sans points
- Log : "Traitement paiement abonnement #1033"
- Attribue 59 points à Véronique
- Log : "✓✓✓ 59 points ATTRIBUÉS à user [ID] pour commande #1033"

**2 décembre 09h00** :
- Véronique se connecte
- Voit 59 points disponibles dans son compte
- Peut les utiliser pour obtenir des réductions

---

## 📞 SUPPORT

Si après ces corrections le système ne fonctionne toujours pas :

1. ✅ Vérifier que les 3 fichiers sont uploadés
2. ✅ Vérifier que cron.php est chargé dans le plugin principal
3. ✅ Vérifier que WP_DEBUG_LOG est activé
4. ✅ Exécuter manuellement la vérification via l'admin
5. ✅ Envoyer les logs de /wp-content/debug.log

---

**Validation syntaxe** :
- ✅ system.php : No syntax errors
- ✅ admin.php : No syntax errors  
- ✅ cron.php : No syntax errors
