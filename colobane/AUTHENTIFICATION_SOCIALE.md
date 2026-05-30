# Authentification Google / Apple — app COLObanes (Flutter)

## Google : debug OK, Play Store KO (erreur 10)

L’erreur **`ApiException: 10` / `DEVELOPER_ERROR`** en production vient presque toujours du **certificat de signature Google Play**, pas de votre keystore local.

| Mode | Certificat utilisé | SHA déjà dans Firebase ? |
|------|-------------------|--------------------------|
| `flutter run` (debug) | `~/.android/debug.keystore` | Oui (`F6:64:49:32:…`) |
| APK/AAB signé localement (release) | `aria-release-key.jks` | Oui (`40:D6:A0:F3:…`) |
| **Install depuis Google Play** | **Play App Signing** (clé Google) | **Non — à ajouter** |

### Correction (obligatoire pour le Play Store)

1. **Google Play Console** → votre app → **Intégrité de l’app** (App integrity)
2. Onglet **Signature de l’application** → section **Certificat de signature de l’application** (App signing key certificate)
3. Copiez le **SHA-1** et le **SHA-256** (pas la clé « upload », mais bien **App signing**)
4. **Firebase Console** → projet `gestion-scolaire-6945a` → app Android **`com.colobanes.app`**
5. **Ajouter une empreinte** → collez le SHA-1 Play App Signing (et idéalement SHA-256)
6. **Retéléchargez `google-services.json`** → remplacez :
   ```
   colobane/android/app/google-services.json
   ```
7. Vérifiez qu’un **3ᵉ client OAuth Android** apparaît dans le JSON (`certificate_hash` = SHA-1 Play sans les `:`)
8. Rebuild et publiez une **nouvelle version** sur le Play Store :
   ```bash
   cd colobane
   flutter clean
   flutter pub get
   flutter build appbundle --release
   ```

### Empreintes locales (keystore aria / debug)

```bash
cd colobane/android
./gradlew signingReport
```

- Debug SHA-1 : `F6:64:49:32:B4:05:0F:54:E3:0F:CB:B4:E6:60:0C:41:80:10:A3:D8`
- Release SHA-1 : `40:D6:A0:F3:E1:00:7E:70:BE:8C:04:F9:C1:2B:F8:5F:14:6A:DC:DA`

### Web Client ID

Déjà dans `lib/config/firebase_auth_config.dart` :
`983006440407-goai5vsnrtaur5fpk8vq8m6gdnv1eh90.apps.googleusercontent.com`

### App Links (assetlinks.json)

Ajoutez aussi le **SHA-256 Play App Signing** dans `.well-known/assetlinks.json` sur `https://colobanes.com` (en plus debug + release), puis redéployez le site.

---

## Apple Sign-In

### iOS — OK (natif)

- Capability **Sign in with Apple** : `ios/Runner/Runner.entitlements`
- Bundle ID : `com.colobanes.app`

### Android — flux web obligatoire

Sur Android, Apple exige `webAuthenticationOptions` (Services ID + URL de retour HTTPS).

**Configuration code** (`lib/config/firebase_auth_config.dart`) :

- `kAppleServicesClientId` : identifiant de service Apple (Firebase → Authentication → Apple)
- `kAppleAndroidRedirectUri` : `https://colobanes.com/auth/apple-callback`

**Apple Developer** (Services ID) :

1. Identifiers → **Services IDs** → créer ou éditer le Services ID
2. Activer **Sign In with Apple**
3. **Return URL** : `https://colobanes.com/auth/apple-callback`
4. Même URL dans Firebase → Authentication → Apple

**Serveur** : page `auth/apple-callback.php` (rewrite `.htaccess` → `/auth/apple-callback`)

**AndroidManifest** : intent-filter pour `https://colobanes.com/auth/apple-callback`

---

## Fonctionnement dans l’app

- La WebView appelle le code **natif Flutter** (`signInWithGoogle` / `signInWithApple`)
- Le token Firebase est renvoyé au site PHP (`/auth-firebase-callback.php`)

Après toute modification Firebase ou Play Console, **republiez** une nouvelle version sur le Play Store.
