# 🎂 Système d'Anniversaire - Programme de Fidélité NewSaiige

## 📋 Vue d'ensemble

Le système d'anniversaire envoie automatiquement un bon d'achat personnalisé aux clients selon leur palier de fidélité le jour de leur anniversaire.

---

## 🎁 Réductions par palier

| Palier | Réduction | Validité | Conditions |
|--------|-----------|----------|------------|
| **Bronze** | 0% | - | Email de voeux uniquement |
| **Argent** | 15% | 7 jours | Utilisable 1 fois |
| **Or** | 15% | 7 jours | Utilisable 1 fois |
| **Platine** | 30% | 7 jours | Utilisable 1 fois |

---

## 🔧 Installation

### **Étape 1 : Activer le système**

Le système est automatiquement activé lorsque le plugin de fidélité est installé. Il charge le fichier `includes/birthday-system.php`.

### **Étape 2 : Vérifier le cron**

Le système utilise un cron WordPress qui s'exécute **quotidiennement** à 00h00.

```php
// Vérifier si le cron est actif
wp_next_scheduled('newsaiige_daily_birthday_check');
```

### **Étape 3 : Ajouter le champ dans le formulaire d'inscription**

Le champ `birthday` a été ajouté dans :
- **Formulaire d'inscription** (register-form.php)
- **Page Mon Compte** (account-form.php)

---

## 📱 Utilisation côté client

### **1. Lors de l'inscription**
Les utilisateurs peuvent renseigner leur date d'anniversaire dans le formulaire d'inscription.

### **2. Modification du profil**
Dans **Mon Compte**, section "Informations personnelles", les utilisateurs peuvent :
- Ajouter leur date d'anniversaire
- Modifier leur date d'anniversaire
- Voir l'info : "📧 Recevez un bon d'achat à votre anniversaire"

---

## 🤖 Fonctionnement automatique

### **Vérification quotidienne**

Chaque jour à 00h00, le système :
1. ✅ Récupère tous les utilisateurs avec une date d'anniversaire
2. ✅ Compare avec la date du jour (mois-jour uniquement)
3. ✅ Vérifie qu'un bon n'a pas déjà été envoyé cette année
4. ✅ Récupère le palier de fidélité actuel
5. ✅ Crée un coupon WooCommerce personnalisé
6. ✅ Envoie l'email avec le code promo
7. ✅ Marque l'année d'envoi pour éviter les doublons

### **Caractéristiques du coupon**

```php
Code: BIRTHDAY2025_123_abc123
Type: Pourcentage
Montant: 15% ou 30% selon palier
Usage: 1 fois uniquement
Destinataire: Email de l'utilisateur
Expiration: 7 jours après création
Individuel: Oui (ne peut être combiné)
```

---

## 📧 Templates d'emails

### **Email avec bon d'achat (Argent, Or, Platine)**

Contenu :
- 🎂 Header festif avec gradient
- 👤 Personnalisation avec prénom
- 🏆 Badge du palier actuel
- 🎁 Box avec le pourcentage de réduction
- 🔢 Code promo en grand
- ⏰ Date d'expiration claire
- 🔘 Bouton CTA "Profiter de mon cadeau"
- 📝 Conditions d'utilisation

### **Email sans bon (Bronze)**

Contenu :
- 🎂 Voeux d'anniversaire
- 💡 Incitation à progresser dans le programme
- 🔘 Lien vers le compte fidélité

---

## 🛠️ Fonctions principales

### **check_birthdays()**
Vérifie tous les anniversaires du jour et envoie les bons.

### **send_birthday_coupon($user_id)**
Crée et envoie le bon d'anniversaire selon le palier.

### **create_birthday_coupon($user_id, $discount)**
Crée le coupon WooCommerce avec les bonnes métadonnées.

### **get_user_tier($user_id)**
Récupère le palier actuel depuis `wp_newsaiige_user_tiers`.

---

## 🧪 Tests

### **Test manuel du système**

```php
// Dans WordPress Admin > Outils > Code Snippets
// Ou via SSH/Terminal

// 1. Forcer l'exécution du cron d'anniversaire
do_action('newsaiige_daily_birthday_check');

// 2. Tester pour un utilisateur spécifique
$birthday_system = new NewsaiigeBirthdaySystem();
$birthday_system->send_birthday_coupon(USER_ID);

// 3. Changer temporairement une date d'anniversaire pour aujourd'hui
update_user_meta(USER_ID, 'birthday', date('Y-m-d'));
do_action('newsaiige_daily_birthday_check');
```

### **Vérifier les logs**

Les envois sont enregistrés dans le log WordPress :

```php
// Dans wp-config.php, activer le debug
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Voir le fichier : wp-content/debug.log
// Rechercher : "Birthday coupon sent to user"
```

### **Requêtes SQL de vérification**

```sql
-- Anniversaires d'aujourd'hui
SELECT u.user_email, um.meta_value as birthday
FROM wp_users u
JOIN wp_usermeta um ON u.ID = um.user_id
WHERE um.meta_key = 'birthday'
AND DATE_FORMAT(STR_TO_DATE(um.meta_value, '%Y-%m-%d'), '%m-%d') = DATE_FORMAT(NOW(), '%m-%d');

-- Coupons d'anniversaire créés
SELECT * FROM wp_posts 
WHERE post_type = 'shop_coupon' 
AND post_title LIKE 'BIRTHDAY%'
ORDER BY post_date DESC;

-- Derniers envois par utilisateur
SELECT user_id, meta_value as last_sent_year
FROM wp_usermeta
WHERE meta_key = 'birthday_coupon_last_sent'
ORDER BY meta_value DESC;
```

---

## 🔒 Sécurité

### **Protection contre les abus**

✅ **Un seul bon par an** : Vérifie `birthday_coupon_last_sent`  
✅ **Usage limité** : `usage_limit = 1`  
✅ **Email unique** : `customer_email` défini dans le coupon  
✅ **Pas de cumul** : `individual_use = yes`  
✅ **Expiration** : 7 jours automatiquement  

### **Validation des données**

```php
// Format de date strict : YYYY-MM-DD
DateTime::createFromFormat('Y-m-d', $date);

// Sanitization
sanitize_text_field($_POST['birthday']);

// Age minimum (optionnel)
if (date('Y') - date('Y', strtotime($birthday)) < 18) {
    // Alerte mineur
}
```

---

## 📊 Statistiques

### **Requêtes utiles**

```sql
-- Nombre d'utilisateurs avec anniversaire renseigné
SELECT COUNT(DISTINCT user_id) 
FROM wp_usermeta 
WHERE meta_key = 'birthday' AND meta_value != '';

-- Anniversaires par mois
SELECT 
    DATE_FORMAT(STR_TO_DATE(meta_value, '%Y-%m-%d'), '%M') as mois,
    COUNT(*) as nombre
FROM wp_usermeta 
WHERE meta_key = 'birthday' AND meta_value != ''
GROUP BY mois
ORDER BY MONTH(STR_TO_DATE(meta_value, '%Y-%m-%d'));

-- Taux de conversion des bons d'anniversaire
SELECT 
    COUNT(DISTINCT p.ID) as total_coupons,
    COUNT(DISTINCT CASE WHEN pm.meta_value > 0 THEN p.ID END) as used_coupons
FROM wp_posts p
LEFT JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = 'usage_count'
WHERE p.post_type = 'shop_coupon'
AND p.post_title LIKE 'BIRTHDAY%';
```

---

## 🎨 Personnalisation

### **Modifier les réductions**

Dans `birthday-system.php` :

```php
$discount_by_tier = array(
    'bronze' => 0,      // Modifier ici
    'argent' => 15,     // Modifier ici
    'or' => 15,         // Modifier ici
    'platine' => 30     // Modifier ici
);
```

### **Modifier la durée de validité**

```php
// Actuellement : 7 jours
$expiry_date = date('Y-m-d', strtotime('+7 days'));

// Changer pour 14 jours
$expiry_date = date('Y-m-d', strtotime('+14 days'));
```

### **Personnaliser l'email**

Les templates sont dans les fonctions :
- `send_birthday_email_with_coupon()` : Email avec bon
- `send_birthday_email_no_coupon()` : Email sans bon (Bronze)

Modifier le HTML/CSS directement dans ces fonctions.

---

## ⚠️ Dépannage

### **Les emails ne sont pas envoyés**

1. Vérifier que le cron WordPress fonctionne :
```php
// Ajouter dans functions.php temporairement
add_action('init', function() {
    error_log('Cron test: ' . wp_next_scheduled('newsaiige_daily_birthday_check'));
});
```

2. Tester l'envoi d'email manuellement :
```php
wp_mail('test@example.com', 'Test', 'Message de test');
```

3. Vérifier les logs SMTP si configuré

### **Le cron ne s'exécute pas**

```php
// Désinstaller et réinstaller le cron
wp_clear_scheduled_hook('newsaiige_daily_birthday_check');
wp_schedule_event(time(), 'daily', 'newsaiige_daily_birthday_check');
```

### **Un utilisateur ne reçoit pas son bon**

Vérifier :
1. Date d'anniversaire enregistrée : `SELECT * FROM wp_usermeta WHERE user_id = X AND meta_key = 'birthday'`
2. Déjà envoyé cette année : `SELECT * FROM wp_usermeta WHERE user_id = X AND meta_key = 'birthday_coupon_last_sent'`
3. Palier actuel : `SELECT * FROM wp_newsaiige_user_tiers WHERE user_id = X ORDER BY achieved_at DESC LIMIT 1`

---

## 📅 Planning d'exécution

Le cron s'exécute **tous les jours à 00h00** (heure du serveur).

Pour modifier l'heure :
```php
// Dans birthday-system.php
wp_schedule_event(strtotime('08:00:00'), 'daily', 'newsaiige_daily_birthday_check');
```

---

## ✅ Checklist de déploiement

- [ ] Plugin de fidélité activé
- [ ] Fichier `birthday-system.php` chargé
- [ ] Cron `newsaiige_daily_birthday_check` actif
- [ ] Champ birthday ajouté dans account-form.php
- [ ] Champ birthday ajouté dans register-form.php
- [ ] Test d'envoi d'email fonctionnel
- [ ] Vérification des paliers dans la BDD
- [ ] Templates d'emails personnalisés
- [ ] Documentation fournie au client

---

## 🚀 Améliorations futures

- [ ] Dashboard admin pour voir les anniversaires à venir
- [ ] Statistiques d'utilisation des bons d'anniversaire
- [ ] Rappel 3 jours avant expiration du bon
- [ ] Personnalisation des emails par palier
- [ ] Possibilité de désactiver les emails d'anniversaire
- [ ] Support multi-langues

---

Système prêt à l'emploi ! 🎉
