# 🚀 Installation rapide - Changement d'abonnement

## ⚡ En 3 étapes

### 1️⃣ Uploader les fichiers (FTP/SFTP)

```
/wp-content/themes/votre-theme/My_account/
├── subscription-history.php (REMPLACER)
└── subscription-change-handler.php (NOUVEAU)
```

### 2️⃣ Modifier functions.php

Ajouter à la fin du fichier `functions.php` :

```php
// Système de changement d'abonnement
require_once get_template_directory() . '/My_account/subscription-change-handler.php';
```

### 3️⃣ Tester

1. Se connecter en tant que client ayant un abonnement
2. Aller sur "Mes abonnements"
3. Cliquer sur **"Modifier l'abonnement"**
4. Choisir une nouvelle option
5. Confirmer

## ✅ Fonctionnalités

| Scénario | Action automatique |
|----------|-------------------|
| **Upgrade** (prix plus cher) | La différence sera ajoutée au prochain prélèvement |
| **Downgrade** (prix moins cher) | La différence sera déduite du prochain prélèvement |
| **Prix identique** | Changement gratuit immédiat |

## 📧 Emails automatiques

- ✅ Confirmation de changement envoyée au client
- ✅ Détails complets (ancien → nouveau)
- ✅ Information sur l'impact du prochain prélèvement
- ✅ Montant de la différence (ajout ou déduction)

## 🎨 Interface

- Modal élégant et responsive
- Toutes les variations affichées avec prix
- Badge "ACTUEL" sur l'abonnement en cours
- Calcul automatique de la différence de prix
- Design cohérent avec votre charte Montserrat/#82897F

## 📊 Données enregistrées

- Notes ajoutées à la commande originale
- Historique complet conservé
- Date de dernière modification
- Différence de prix à appliquer au prochain prélèvement

## 🔒 Sécurité

- Nonce AJAX vérifié
- Utilisateur authentifié requis
- Vérification de propriété de commande
- Vérification du stock

## 📱 Compatible

- ✅ Desktop
- ✅ Tablette
- ✅ Mobile

## 📖 Documentation complète

Voir `GUIDE-CHANGEMENT-ABONNEMENT.md` pour :
- Architecture détaillée
- Personnalisation
- Dépannage
- Améliorations futures

---

**C'est prêt !** 🎉
