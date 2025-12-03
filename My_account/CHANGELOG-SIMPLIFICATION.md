# 🔄 Changement d'abonnement - Version Simplifiée

## 📋 Modifications effectuées

Le système a été simplifié pour que **les différences de prix soient appliquées au prochain prélèvement** au lieu de créer des paiements immédiats ou des coupons.

---

## ✅ Nouveau fonctionnement

### Quand un client change son abonnement :

#### 1️⃣ **Upgrade** (passage à un abonnement plus cher)
- L'abonnement est mis à jour immédiatement
- La différence de prix est **ajoutée au prochain prélèvement**
- Email de confirmation envoyé avec l'explication
- Pas de paiement immédiat requis

**Exemple :**
- Ancien : 1 soin/mois - 50€
- Nouveau : 3 soins/mois - 120€
- → Prochain prélèvement : 120€ + 70€ (différence) = **190€**

#### 2️⃣ **Downgrade** (passage à un abonnement moins cher)
- L'abonnement est mis à jour immédiatement
- La différence de prix est **déduite du prochain prélèvement**
- Email de confirmation envoyé avec l'explication
- Pas de coupon créé

**Exemple :**
- Ancien : 3 soins/mois - 120€
- Nouveau : 1 soin/mois - 50€
- → Prochain prélèvement : 50€ - 70€ (différence) = **Crédit de 20€** (si reste positif) ou 0€

#### 3️⃣ **Prix identique**
- Simple changement d'abonnement
- Aucun impact financier
- Email de confirmation

---

## 🗑️ Fonctions supprimées

Les fonctions suivantes ont été **retirées** car elles ne sont plus nécessaires :

### ❌ `newsaiige_create_supplement_order()`
**Avant :** Créait une commande complémentaire pour les upgrades  
**Maintenant :** La différence est stockée dans les métadonnées de la commande

### ❌ `newsaiige_add_customer_credit()`
**Avant :** Créait un coupon personnalisé pour les downgrades  
**Maintenant :** La différence est stockée pour déduction au prochain prélèvement

---

## 📊 Données stockées

### Métadonnées de commande

```php
_subscription_last_change      // Date de la dernière modification (MySQL datetime)
_subscription_price_change     // Montant de la différence (float, positif ou négatif)
```

### Notes de commande

Chaque changement ajoute une note :
```
Abonnement modifié par le client : 
Abonnement 1 soin/mois → Abonnement 3 soins/mois 
(Différence de prix : 70,00€ - sera appliquée au prochain prélèvement)
```

---

## 📧 Email de confirmation

L'email envoyé au client contient :

✅ Ancien et nouvel abonnement  
✅ Différence de prix (si applicable)  
✅ **Encadré informatif bleu** expliquant l'impact sur le prochain prélèvement :

- **Upgrade** : "La différence de X€ sera ajoutée à votre prochain prélèvement automatique"
- **Downgrade** : "La différence de X€ sera déduite de votre prochain prélèvement automatique"

---

## 🎨 Interface utilisateur

### Modal de changement

**Modifications apportées :**

#### Avant :
- Badge orange pour upgrade ("Différence à payer")
- Badge vert pour downgrade ("Crédit pour prochaine commande")
- Bouton de paiement immédiat
- Information sur le coupon

#### Maintenant :
- **Badge bleu unique** pour tous les changements
- Message clair : "Cette différence sera ajoutée/déduite de votre prochain prélèvement"
- Pas de bouton de paiement
- Confirmation simple

---

## 💳 Gestion du prochain prélèvement

### Pour intégrer cette fonctionnalité avec votre système de paiement récurrent :

Lors de la création du prochain prélèvement, récupérez la différence stockée :

```php
// Lors de la création d'un nouveau prélèvement
$order_id = 1234; // ID de la commande d'abonnement
$base_amount = 120.00; // Montant de base du nouvel abonnement

// Récupérer la différence à appliquer
$price_change = get_post_meta($order_id, '_subscription_price_change', true);
$price_change = floatval($price_change);

// Calculer le montant du prochain prélèvement
$next_payment_amount = $base_amount + $price_change;

// Réinitialiser la différence après application
if ($price_change != 0) {
    update_post_meta($order_id, '_subscription_price_change', 0);
    $order = wc_get_order($order_id);
    $order->add_order_note(sprintf(
        'Différence de prix appliquée au prélèvement : %s',
        wc_price($price_change)
    ));
}
```

### Exemple d'intégration avec un plugin de subscription :

```php
// Hook pour WooCommerce Subscriptions (si vous l'utilisez)
add_filter('wcs_renewal_order_amount', function($amount, $order) {
    $parent_order_id = $order->get_parent_id();
    
    if ($parent_order_id) {
        $price_change = get_post_meta($parent_order_id, '_subscription_price_change', true);
        $price_change = floatval($price_change);
        
        if ($price_change != 0) {
            $amount += $price_change;
            
            // Réinitialiser après application
            update_post_meta($parent_order_id, '_subscription_price_change', 0);
            
            // Ajouter une note
            $order->add_order_note(sprintf(
                'Ajustement suite au changement d\'abonnement : %s',
                wc_price($price_change)
            ));
        }
    }
    
    return $amount;
}, 10, 2);
```

---

## 🔄 Flux complet

1. **Client** clique sur "Modifier l'abonnement"
2. **Modal** s'ouvre avec les variations disponibles
3. **Client** sélectionne une nouvelle variation
4. **Système** calcule la différence de prix
5. **Client** confirme le changement
6. **Backend** met à jour :
   - L'item de commande (nouvelle variation)
   - Les totaux de la commande
   - Les métadonnées (`_subscription_price_change`)
   - Ajoute une note à la commande
7. **Email** de confirmation envoyé au client
8. **Prochain prélèvement** : montant ajusté selon la différence

---

## 🧪 Tests à effectuer

### Test 1 : Upgrade
1. Client avec abonnement 1 soin/mois (50€)
2. Change pour 3 soins/mois (120€)
3. ✅ Vérifier : métadonnée `_subscription_price_change` = 70
4. ✅ Vérifier : email reçu avec info "ajoutée au prochain prélèvement"
5. ✅ Vérifier : note dans la commande

### Test 2 : Downgrade
1. Client avec abonnement 3 soins/mois (120€)
2. Change pour 1 soin/mois (50€)
3. ✅ Vérifier : métadonnée `_subscription_price_change` = -70
4. ✅ Vérifier : email reçu avec info "déduite du prochain prélèvement"
5. ✅ Vérifier : note dans la commande

### Test 3 : Prix identique
1. Client avec abonnement formule A (100€)
2. Change pour formule B (100€)
3. ✅ Vérifier : métadonnée `_subscription_price_change` = 0
4. ✅ Vérifier : email reçu avec info "aucun impact"
5. ✅ Vérifier : changement effectif

---

## 📋 Checklist de déploiement

- [ ] Uploader `subscription-change-handler.php` (version simplifiée)
- [ ] Uploader `subscription-history.php` (avec nouveau design du modal)
- [ ] Ajouter la ligne dans `functions.php`
- [ ] Tester un upgrade
- [ ] Tester un downgrade
- [ ] Vérifier les emails reçus
- [ ] Vérifier les métadonnées stockées
- [ ] Implémenter la logique de prélèvement avec ajustement
- [ ] Documenter pour l'équipe

---

## 🎯 Avantages de cette approche

✅ **Plus simple** : Pas de commandes supplémentaires à gérer  
✅ **Plus clair** : Le client comprend que ça s'applique au prochain prélèvement  
✅ **Moins de frais** : Pas de transaction immédiate  
✅ **Flexible** : Fonctionne pour upgrade et downgrade  
✅ **Transparent** : Historique complet dans les notes de commande  

---

**Version :** 2.0 (Simplifiée)  
**Date :** 3 décembre 2025  
**Migration depuis :** Version 1.0 (avec paiements immédiats)
