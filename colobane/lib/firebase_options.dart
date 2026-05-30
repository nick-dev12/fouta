// Configuration Firebase — projet gestion-scolaire-6945a (aligné site web).
// Si android/app/google-services.json est présent, Firebase.initializeApp()
// sans options utilise aussi les ressources Gradle générées.

import 'package:firebase_core/firebase_core.dart' show FirebaseOptions;
import 'package:flutter/foundation.dart'
    show defaultTargetPlatform, kIsWeb, TargetPlatform;

class DefaultFirebaseOptions {
  static const String projectId = 'gestion-scolaire-6945a';
  static const String messagingSenderId = '983006440407';

  static FirebaseOptions get currentPlatform {
    if (kIsWeb) {
      return web;
    }
    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
        return android;
      case TargetPlatform.iOS:
        return ios;
      default:
        return web;
    }
  }

  static const FirebaseOptions web = FirebaseOptions(
    apiKey: 'AIzaSyC9sEoKn89rBaYvNAULOKtXZIIFYTyL4f4',
    appId: '1:983006440407:web:1c18c83f8942e8577e7992',
    messagingSenderId: messagingSenderId,
    projectId: projectId,
    authDomain: 'gestion-scolaire-6945a.firebaseapp.com',
    storageBucket: 'gestion-scolaire-6945a.firebasestorage.app',
  );

  /// Secours si google-services.json absent (aligné sur com.colobanes.app).
  static const FirebaseOptions android = FirebaseOptions(
    apiKey: 'AIzaSyBKb1r-Jl6lG1bT3c95Cqtlq1pWgXzZ4zw',
    appId: '1:983006440407:android:b86121bc26ffaa8d7e7992',
    messagingSenderId: messagingSenderId,
    projectId: projectId,
    storageBucket: 'gestion-scolaire-6945a.firebasestorage.app',
  );

  static const FirebaseOptions ios = FirebaseOptions(
    apiKey: 'AIzaSyBU95l5LJdJZ1HMy-TCZMBVsIfMGjy5jng',
    appId: '1:983006440407:ios:24b1ab5b70ab7b327e7992',
    messagingSenderId: messagingSenderId,
    projectId: projectId,
    storageBucket: 'gestion-scolaire-6945a.firebasestorage.app',
    iosBundleId: 'com.colobanes.app',
    iosClientId:
        '983006440407-0lj1ljivl26pt6emhu4tlvgl2p047rgp.apps.googleusercontent.com',
  );
}
