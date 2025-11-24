# 📋 Guide - Gestion des Abonnements Actifs dans le Programme de Fidélité

## 🎯 Objectif
Tous les clients ayant un **abonnement actif** (produits de la catégorie "soins") sont automatiquement éligibles au programme de fidélité, même s'ils n'ont pas encore de points.

---

## ✨ Fonctionnalités Implémentées

### 1. Détection Intelligente des Abonnements Actifs

La fonction `has_active_subscription()` dans `includes/system.php` vérifie maintenant :

- ✅ **Toutes les commandes complétées, en cours et en attente**
- ✅ **Produits de la catégorie "soins"** (variables et variations)
- ✅ **Durée de l'abonnement** extraite automatiquement :
  - Depuis les attributs de variation (`1-mois`, `3-mois`, `6-mois`, etc.)
  - Depuis le nom du produit (ex: "Soin visage 3 mois")
  - Durée par défaut : 30 jours si non spécifiée

- ✅ **Vérification de l'expiration** : compare la date de commande + durée vs date actuelle
- ✅ **Logs détaillés** pour le débogage

#### Exemple de détection

```
Utilisateur achète "Soin visage 3 mois" le 01/11/2025
→ Abonnement actif jusqu'au 01/02/2026
→ Le 24/11/2025 : ✓ Abonnement actif
→ Le 05/02/2026 : ✗ Abonnement expiré
```

---

### 2. Liste Administrative Complète

Dans **Fidélité → Utilisateurs** (`admin.php`), vous voyez maintenant :

#### Avant
- Uniquement les utilisateurs avec points accumulés

#### Après
- ✅ **Tous les utilisateurs avec points**
- ✅ **+ Tous les utilisateurs avec abonnement actif** (même sans points)
- ✅ **Badge visuel "✓ Abonné"** pour identifier facilement les abonnés

#### Affichage

```
┌─────────────────────────────────────────────────────────┐
│ Utilisateur          │ Email            │ Palier        │
├─────────────────────────────────────────────────────────┤
│ Marie Dupont         │ marie@...        │ Silver        │
│ ✓ Abonné            │                  │               │
├─────────────────────────────────────────────────────────┤
│ Jean Martin          │ jean@...         │ Bronze        │
│                      │                  │               │
└─────────────────────────────────────────────────────────┘
```

---

## 🔧 Détails Techniques

### Modification de `has_active_subscription()`

**Localisation** : `includes/system.php`, ligne ~242

#### Améliorations clés

1. **Récupération élargie des commandes**
```php
'status' => array('completed', 'processing', 'on-hold')
```

2. **Support des variations**
```php
if ($product->is_type('variation')) {
    $product_id = $product->get_parent_id();
}
```

3. **Extraction automatique de la durée**
```php
// Depuis les attributs
if (stripos($key, 'duree') !== false || stripos($key, 'mois') !== false)

// Depuis le nom du produit
if (preg_match('/(\d+)\s*mois/', $product_name, $matches))
```

4. **Calcul de l'expiration**
```php
$expiration_timestamp = $order_timestamp + ($subscription_duration_days * 24 * 60 * 60);
if ($current_timestamp <= $expiration_timestamp) {
    return true; // Abonnement actif
}
```

---

### Modification de `newsaiige_loyalty_users_page()`

**Localisation** : `includes/admin.php`, ligne ~590

#### Ajout des utilisateurs avec abonnement

```php
// Récupérer tous les utilisateurs
$all_users = get_users(array('number' => 500));

// Pour chaque utilisateur
foreach ($all_users as $user) {
    // Si pas déjà dans la liste et a un abonnement actif
    if (!$already_included && $newsaiige_loyalty->has_active_subscription($user->ID)) {
        // Ajouter à la liste avec points = 0
        $users_with_subscription[] = $user_obj;
    }
}

// Fusionner les deux listes
$users_data = array_merge($users_data, $users_with_subscription);
```

#### Badge visuel

```php
<?php if ($has_subscription): ?>
    <span class="subscription-active-badge" title="Abonnement actif">
        ✓ Abonné
    </span>
<?php endif; ?>
```

**Style CSS** (ligne ~755) :
```css
.subscription-active-badge {
    display: inline-block;
    margin-left: 8px;
    padding: 3px 8px;
    background: rgba(76, 175, 80, 0.1);
    color: #2e7d32;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
}
```

---

## 📊 Cas d'Usage

### Scénario 1 : Nouvel abonné sans points
```
Utilisateur : Sophie Laurent
Commande : Soin visage 6 mois (15/11/2025)
Points actuels : 0
Statut : ✓ Abonnement actif jusqu'au 15/05/2026

→ Apparaît dans la liste avec badge "✓ Abonné"
→ Peut gagner des points sur ses prochains achats
→ Reçoit les emails d'anniversaire (si configuré)
```

### Scénario 2 : Client avec points mais abonnement expiré
```
Utilisateur : Marc Dubois
Dernière commande soins : 01/08/2025 (3 mois)
Points actuels : 850
Statut : ✗ Abonnement expiré depuis le 01/11/2025

→ Apparaît dans la liste (a des points)
→ Pas de badge "✓ Abonné"
→ Ne peut plus gagner de nouveaux points (si subscription_required = 1)
```

### Scénario 3 : Multi-abonnements
```
Utilisateur : Julie Petit
Commandes :
- Soin visage 3 mois (01/09/2025) → expiré
- Soin corps 6 mois (01/11/2025) → actif jusqu'au 01/05/2026

→ Badge "✓ Abonné" affiché (dernier abonnement actif)
→ Éligible au programme de fidélité
```

---

## 🔍 Vérification et Débogage

### Logs WordPress

Les logs sont écrits dans `wp-content/debug.log` :

```
has_active_subscription: User 42 a un abonnement actif 
  (commande #1234, expire le 2026-05-15)

has_active_subscription: User 89 n'a pas d'abonnement actif
```

### Activer les logs

Dans `wp-config.php` :
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Vérification manuelle

1. **Admin WordPress** → Fidélité → Utilisateurs
2. Chercher un utilisateur avec abonnement
3. Vérifier la présence du badge **"✓ Abonné"**
4. Consulter les logs pour confirmer la détection

---

## ⚙️ Configuration

### Paramètres importants

**Fidélité → Paramètres** :

1. **Abonnement requis** : 
   - ✓ Activé : Seuls les abonnés actifs gagnent des points
   - ✗ Désactivé : Tous les clients gagnent des points

2. **Catégorie d'abonnement** : `soins` (slug de la catégorie)

3. **Durée par défaut** : 30 jours (si non détectée automatiquement)

---

## 🎨 Personnalisation

### Modifier la durée par défaut

Dans `system.php`, ligne ~279 :
```php
$subscription_duration_days = 30; // Changer ici
```

### Modifier le style du badge

Dans `admin.php`, ligne ~755 :
```css
.subscription-active-badge {
    background: rgba(76, 175, 80, 0.1); /* Vert clair */
    color: #2e7d32; /* Vert foncé */
}
```

### Changer le texte du badge

Dans `admin.php`, ligne ~718 :
```php
<span class="subscription-active-badge">
    ✓ Abonné <!-- Changer ici -->
</span>
```

---

## 🚀 Avantages

### Pour les administrateurs
- ✅ Vision complète des abonnés actifs
- ✅ Identification rapide avec badge visuel
- ✅ Logs détaillés pour le support client
- ✅ Pas besoin de vérifier manuellement les commandes

### Pour les clients
- ✅ Éligibilité automatique au programme
- ✅ Pas de perte de points pendant l'abonnement
- ✅ Accumulation continue de points
- ✅ Emails d'anniversaire garantis

### Pour le business
- ✅ Encourage les renouvellements d'abonnement
- ✅ Fidélisation accrue des abonnés
- ✅ Suivi précis du statut d'abonnement
- ✅ Moins de tickets support

---

## 📝 Notes Importantes

1. **Performance** : La vérification des abonnements se fait à la demande, pas en temps réel sur toutes les pages

2. **Cache** : Si vous modifiez la durée d'un abonnement, videz le cache WordPress

3. **Compatibilité** : Testé avec WooCommerce 8.x et WordPress 6.x

4. **Variations** : Supporte tous les types de variations d'attributs

5. **Formats acceptés** : 
   - Attributs : `1-mois`, `3-mois`, `6-mois`, `12-mois`
   - Noms : "Soin 1 mois", "Abonnement 3 mois", etc.

---

## 🔄 Mise à Jour Future

Pour ajouter d'autres critères de détection, modifier la section d'extraction de durée dans `has_active_subscription()` :

```php
// Exemple : ajouter support pour "trimestre"
if (stripos($product_name, 'trimestre') !== false) {
    $subscription_duration_days = 90; // 3 mois
}

// Exemple : ajouter support pour "annuel"
if (stripos($product_name, 'annuel') !== false || 
    stripos($product_name, 'an') !== false) {
    $subscription_duration_days = 365; // 1 an
}
```

---

## ✅ Résumé

| Fonctionnalité | Statut | Description |
|---|---|---|
| Détection automatique | ✅ | Abonnements actifs détectés automatiquement |
| Support variations | ✅ | Produits variables et variations supportés |
| Extraction durée | ✅ | Depuis attributs et nom du produit |
| Liste étendue | ✅ | Abonnés visibles même sans points |
| Badge visuel | ✅ | Identification rapide des abonnés |
| Logs détaillés | ✅ | Débogage et support facilités |

---

**Date de création** : 24 novembre 2025  
**Version** : 1.0  
**Auteur** : GitHub Copilot
