# 🎯 Guide de contrôle de position des cartes

## 📐 Variables CSS personnalisées disponibles :

### **Hauteur du grid :**
```css
--grid-height: 100vh;        /* Hauteur principale du grid (100vh = pleine hauteur écran) */
```

### **Position verticale carte GAUCHE :**
```css
--card-left-row: 4 / 7;      /* Position dans la grille (lignes 4 à 7 sur 10) */
--card-left-align: center;   /* Alignement: start (haut), center (milieu), end (bas) */
```

### **Position verticale carte DROITE :**
```css
--card-right-row: 4 / 7;     /* Position dans la grille (lignes 4 à 7 sur 10) */
--card-right-align: center;  /* Alignement: start (haut), center (milieu), end (bas) */
```

## 🛠️ Exemples d'utilisation :

### **Carte gauche en haut, droite centrée :**
```html
<div class="content-grid" 
     style="--card-left-row: 1 / 4; --card-left-align: start;
            --card-right-row: 4 / 7; --card-right-align: center;">
```

### **Carte gauche centrée, droite en bas :**
```html
<div class="content-grid" 
     style="--card-left-row: 4 / 7; --card-left-align: center;
            --card-right-row: 7 / 10; --card-right-align: end;">
```

### **Les deux cartes en haut :**
```html
<div class="content-grid" 
     style="--card-left-row: 2 / 5; --card-right-row: 2 / 5;">
```

### **Les deux cartes en bas :**
```html
<div class="content-grid" 
     style="--card-left-row: 6 / 9; --card-right-row: 6 / 9;">
```

### **Positions asymétriques :**
```html
<div class="content-grid" 
     style="--card-left-row: 1 / 3; --card-left-align: start;
            --card-right-row: 8 / 10; --card-right-align: end;">
```

## 🎨 Configurations recommandées :

### **Image avec logo en haut :**
```css
--grid-height: 100vh;
--grid-padding-top: 15vh;
```

### **Image avec éléments déco en bas :**
```css
--grid-height: 100vh;
--grid-padding-bottom: 10vh;
```

### **Image très haute :**
```css
--grid-height: 120vh;
--grid-min-height: 120vh;
```

## 💡 Conseils d'optimisation :

1. **Testez visuellement** : Ajustez progressivement pour aligner avec votre image
2. **Unités vh** : Utilisez vh pour s'adapter à la taille d'écran
3. **Combinaisons** : Mélangez padding et margin pour un contrôle précis
4. **Responsive** : Les media queries gardent la priorité pour mobile

---
**Modification facile** : Changez simplement les valeurs dans l'attribut `style` du content-grid !