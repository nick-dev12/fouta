/// Exemple — généré depuis config/firebase_config.php (section auth).
/// Copiez en firebase_auth_config.dart ou exécutez :
///   php scripts/sync_colobane_auth_config.php
const String kFirebaseWebClientId =
    '983006440407-goai5vsnrtaur5fpk8vq8m6gdnv1eh90.apps.googleusercontent.com';

const String kFirebaseIosClientId =
    '983006440407-0lj1ljivl26pt6emhu4tlvgl2p047rgp.apps.googleusercontent.com';

/// Services ID Apple (Firebase → Auth → Apple = Apple Developer → Services IDs)
const String kAppleServicesClientId = 'com.colobanes.web';

/// Même Return URL que le site web (authDomain + /__/auth/handler)
const String kAppleAndroidRedirectUri =
    'https://gestion-scolaire-6945a.firebaseapp.com/__/auth/handler';

const String kFirebaseAuthDomain = 'gestion-scolaire-6945a.firebaseapp.com';
