# Justifications des permissions — COLObanes

Application marketplace (WebView + pont natif). Usage réel documenté dans `lib/main.dart` et `ios/Runner/Info.plist`.

## Apple App Store (chaînes Info.plist)

Voir `ios/Runner/Info.plist` — chaque clé inclut finalité + exemple (exigence 5.1.1).

## Google Play Console

### CAMERA

```
COLObanes permet de prendre une photo lorsque l'utilisateur appuie sur « Prendre une photo » pour son profil, une fiche produit vendeur ou une pièce jointe. Exemple : photographier un article mis en vente sur la marketplace.
```

### ACCESS_FINE_LOCATION / ACCESS_COARSE_LOCATION

```
La localisation n'est demandée que lorsque l'utilisateur active la fonction de position sur la carte pour confirmer une adresse de livraison. Exemple : afficher le point de livraison lors d'une commande au Sénégal. Pas de suivi en arrière-plan.
```

### POST_NOTIFICATIONS (Android 13+)

```
Notifications de statut de commande et messages liés au compte (ex. : commande expédiée). L'utilisateur peut refuser dans les paramètres système.
```

### READ/WRITE_EXTERNAL_STORAGE (selon version Android)

```
Accès aux images uniquement lorsque l'utilisateur choisit d'importer une photo depuis la galerie ou d'enregistrer une image téléchargée depuis la plateforme.
```

## Permissions non utilisées

- **Microphone** : non demandé (retiré des autorisations WebView et absent d'Info.plist iOS).
