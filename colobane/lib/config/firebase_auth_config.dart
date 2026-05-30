/// Configuration Google Sign-In pour Firebase Auth (app native).
///
/// Firebase Console → Paramètres du projet → Vos applications → App Web
/// → « ID client OAuth 2.0 » (type Web, se termine par .apps.googleusercontent.com)
///
/// Android : ajoutez aussi les empreintes SHA-1 dans Firebase pour com.colobanes.app :
/// - debug (flutter run)
/// - release upload (keystore aria)
/// - **Play App Signing** (Play Console → Intégrité de l'app → Certificat de signature)
///   → obligatoire pour les installs depuis Google Play
const String kFirebaseWebClientId =
    '983006440407-goai5vsnrtaur5fpk8vq8m6gdnv1eh90.apps.googleusercontent.com';

/// Apple Sign-In sur Android (flux web) — Services ID Apple.
/// Firebase Console → Authentication → Apple → « Identifiant de service »
/// Apple Developer → Identifiers → Services IDs
const String kAppleServicesClientId =
    'com.colobanes.app.signin';

/// URL de retour Apple enregistrée dans le Services ID (Return URL).
/// Doit correspondre à un lien https://colobanes.com/… intercepté par l'app.
const String kAppleAndroidRedirectUri =
    'https://colobanes.com/auth/apple-callback';
