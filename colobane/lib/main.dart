import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_inappwebview/flutter_inappwebview.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:image_picker/image_picker.dart';
import 'package:geolocator/geolocator.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'dart:convert';
import 'dart:collection';
import 'services/fcm_service.dart';
import 'services/social_auth_service.dart';
import 'dart:async';

/// URL de la marketplace chargée dans la WebView (production)
const String kMarketplaceBaseUrl = 'https://colobanes.com/';
const Color kBleuPrincipal = Color(0xFF3564A6);
const Color kBleuPrincipalFonce = Color(0xFF2D5690);
const Color kOrangePromo = Color(0xFFFF6B35);
/// Bleu marine proche du logo « banes »
const Color kBleuLogoMarine = Color(0xFF1A3A5C);

bool _isMarketplaceHost(String host) {
  final h = host.toLowerCase();
  return h == 'colobanes.com' || h == 'www.colobanes.com';
}

String resolveRelativeMarketUrl(String href) {
  if (href.isEmpty) {
    return kMarketplaceBaseUrl;
  }
  if (href.startsWith('http://') || href.startsWith('https://')) {
    return href;
  }
  final base = kMarketplaceBaseUrl.replaceAll(RegExp(r'/$'), '');
  final path = href.startsWith('/') ? href : '/$href';
  return '$base$path';
}

// Handler pour les notifications en arrière-plan
@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
  print(
    '📬 Notification reçue en arrière-plan: ${message.notification?.title}',
  );
}

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  print('🔥 Démarrage de l\'application...');

  try {
    // Initialiser Firebase
    print('🔥 Initialisation de Firebase...');
    await Firebase.initializeApp();
    print('✅ Firebase initialisé avec succès');
  } catch (e) {
    print('❌ ERREUR lors de l\'initialisation Firebase: $e');
  }

  try {
    // Configurer le handler pour les notifications en arrière-plan
    print('🔥 Configuration du handler en arrière-plan...');
    FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);
    print('✅ Handler en arrière-plan configuré');
  } catch (e) {
    print('❌ ERREUR lors de la configuration du handler: $e');
  }

  runApp(const ColobanesApp());
}

class ColobanesApp extends StatelessWidget {
  const ColobanesApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'COLObanes',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(
          seedColor: kBleuPrincipal,
          primary: kBleuPrincipal,
          secondary: kOrangePromo,
          surface: Colors.white,
        ),
        useMaterial3: true,
      ),
      home: const WebViewScreen(),
    );
  }
}

class WebViewScreen extends StatefulWidget {
  const WebViewScreen({super.key});

  @override
  State<WebViewScreen> createState() => _WebViewScreenState();
}

class _WebViewScreenState extends State<WebViewScreen>
    with WidgetsBindingObserver {
  InAppWebViewController? webViewController;
  bool isLoading = true;
  bool isInitialLoad = true; // Pour distinguer le premier chargement
  double progress = 0;
  String? _currentUrl; // Sauvegarder l'URL actuelle

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _initializeFCM();
    _loadSavedUrl();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  // Observer le cycle de vie de l'application
  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    super.didChangeAppLifecycleState(state);
    if (state == AppLifecycleState.paused) {
      // Application en arrière-plan - sauvegarder l'URL
      _saveCurrentUrl();
    } else if (state == AppLifecycleState.resumed) {
      // Application revenue au premier plan - restaurer l'URL si nécessaire
      _restoreUrlIfNeeded();
    }
  }

  // Sauvegarder l'URL actuelle
  Future<void> _saveCurrentUrl() async {
    if (webViewController != null) {
      try {
        final currentUrl = await webViewController!.getUrl();
        if (currentUrl != null) {
          _currentUrl = currentUrl.toString();
          final prefs = await SharedPreferences.getInstance();
          await prefs.setString('last_webview_url', _currentUrl!);
        }
      } catch (e) {
        print('Erreur lors de la sauvegarde de l\'URL: $e');
      }
    }
  }

  // Charger l'URL sauvegardée (invalide l'ancien domaine Aria)
  Future<void> _loadSavedUrl() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final savedUrl = prefs.getString('last_webview_url');
      if (savedUrl != null && savedUrl.isNotEmpty) {
        if (savedUrl.contains('aria-edu.com') ||
            savedUrl.contains('samapiece.it.com')) {
          await prefs.remove('last_webview_url');
          _currentUrl = null;
        } else {
          try {
            final u = Uri.parse(savedUrl);
            if (u.hasAuthority && _isMarketplaceHost(u.host)) {
              _currentUrl = savedUrl;
            } else {
              await prefs.remove('last_webview_url');
              _currentUrl = null;
            }
          } catch (_) {
            await prefs.remove('last_webview_url');
            _currentUrl = null;
          }
        }
      }
    } catch (e) {
      print('Erreur lors du chargement de l\'URL sauvegardée: $e');
    }
  }

  // Restaurer l'URL si nécessaire (seulement si différente de l'URL actuelle)
  Future<void> _restoreUrlIfNeeded() async {
    if (webViewController != null && _currentUrl != null) {
      try {
        final currentUrl = await webViewController!.getUrl();
        // Si la WebView est toujours sur la bonne page, ne rien faire
        if (currentUrl != null && currentUrl.toString() == _currentUrl) {
          // La page est déjà chargée, pas besoin de recharger
          return;
        }
        // Sinon, recharger l'URL sauvegardée (utilise le cache si disponible)
        await webViewController!.loadUrl(
          urlRequest: URLRequest(url: WebUri(_currentUrl!)),
        );
      } catch (e) {
        print('Erreur lors de la restauration de l\'URL: $e');
      }
    }
  }

  // Initialiser Firebase Cloud Messaging
  Future<void> _initializeFCM() async {
    print('🔥 Début de l\'initialisation FCM...');
    try {
      // Définir le callback pour la navigation depuis les notifications
      print('🔥 Configuration du callback de navigation...');
      FCMService.setNotificationTapCallback((url) {
        if (webViewController != null && url.isNotEmpty) {
          final fullUrl = resolveRelativeMarketUrl(url);
          webViewController?.loadUrl(
            urlRequest: URLRequest(url: WebUri(fullUrl)),
          );
        }
      });
      print('✅ Callback de navigation configuré');

      print('🔥 Initialisation de FCMService avec URL: $kMarketplaceBaseUrl');
      final token = await FCMService.initialize(kMarketplaceBaseUrl);

      if (token != null) {
        print(
          '✅ FCM initialisé avec succès, token: ${token.substring(0, 20)}...',
        );
      } else {
        print('⚠️ FCM initialisé mais token non obtenu');
      }

      // Configurer le rafraîchissement automatique du token
      print('🔥 Configuration du rafraîchissement automatique du token...');
      FCMService.setupTokenRefresh();
      print('✅ Rafraîchissement automatique configuré');

      print('✅ FCM complètement initialisé avec succès');
    } catch (e, stackTrace) {
      print('❌ ERREUR lors de l\'initialisation FCM: $e');
      print('❌ Stack trace: $stackTrace');
    }
  }

  // Gérer les messages JavaScript depuis la WebView
  void _setupJavaScriptHandlers() {
    webViewController?.addJavaScriptHandler(
      handlerName: 'requestCamera',
      callback: (args) async {
        return await _handleCameraRequest();
      },
    );

    webViewController?.addJavaScriptHandler(
      handlerName: 'requestLocation',
      callback: (args) async {
        return await _handleLocationRequest();
      },
    );

    webViewController?.addJavaScriptHandler(
      handlerName: 'saveData',
      callback: (args) async {
        if (args.isNotEmpty) {
          final data = args[0];
          return await _handleSaveData(data);
        }
        return {'success': false, 'error': 'No data provided'};
      },
    );

    webViewController?.addJavaScriptHandler(
      handlerName: 'getData',
      callback: (args) async {
        if (args.isNotEmpty) {
          final key = args[0] as String;
          return await _handleGetData(key);
        }
        return {'success': false, 'error': 'No key provided'};
      },
    );

    webViewController?.addJavaScriptHandler(
      handlerName: 'showNotification',
      callback: (args) async {
        if (args.length >= 2) {
          final title = args[0] as String;
          final body = args[1] as String;
          return await _handleShowNotification(title, body);
        }
        return {'success': false, 'error': 'Invalid parameters'};
      },
    );

    webViewController?.addJavaScriptHandler(
      handlerName: 'signInWithGoogle',
      callback: (args) async {
        return SocialAuthService.signInWithGoogle();
      },
    );
  }

  // Gérer la demande de caméra
  Future<Map<String, dynamic>> _handleCameraRequest() async {
    try {
      // Vérifier d'abord le statut de la permission de caméra
      PermissionStatus cameraStatus = await Permission.camera.status;

      // Si la permission n'est pas accordée, la demander
      if (!cameraStatus.isGranted) {
        cameraStatus = await Permission.camera.request();

        // Si la permission est refusée de manière permanente
        if (cameraStatus.isPermanentlyDenied) {
          return {
            'success': false,
            'error':
                'L\'accès à la caméra est refusé de manière permanente. Veuillez l\'activer dans les paramètres de l\'application.',
          };
        }

        // Si la permission est refusée
        if (!cameraStatus.isGranted) {
          return {
            'success': false,
            'error': 'L\'accès à la caméra a été refusé.',
          };
        }
      }

      // Maintenant que la permission est accordée, accéder à la caméra
      final ImagePicker picker = ImagePicker();
      final XFile? image = await picker.pickImage(
        source: ImageSource.camera,
        imageQuality: 85,
      );

      if (image != null) {
        final bytes = await image.readAsBytes();
        final base64Image = base64Encode(bytes);
        return {
          'success': true,
          'image': 'data:image/jpeg;base64,$base64Image',
          'path': image.path,
        };
      }
      return {'success': false, 'error': 'Aucune image sélectionnée'};
    } catch (e) {
      return {'success': false, 'error': e.toString()};
    }
  }

  // Gérer la demande de localisation
  Future<Map<String, dynamic>> _handleLocationRequest() async {
    try {
      bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        return {'success': false, 'error': 'Location services are disabled'};
      }

      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
        if (permission == LocationPermission.denied) {
          return {'success': false, 'error': 'Location permissions are denied'};
        }
      }

      if (permission == LocationPermission.deniedForever) {
        return {
          'success': false,
          'error': 'Location permissions are permanently denied',
        };
      }

      Position position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );

      return {
        'success': true,
        'latitude': position.latitude,
        'longitude': position.longitude,
        'accuracy': position.accuracy,
        'altitude': position.altitude,
        'speed': position.speed,
        'timestamp': position.timestamp.toIso8601String(),
      };
    } catch (e) {
      return {'success': false, 'error': e.toString()};
    }
  }

  // Sauvegarder des données localement
  Future<Map<String, dynamic>> _handleSaveData(dynamic data) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      if (data is Map) {
        for (var entry in data.entries) {
          final key = entry.key.toString();
          final value = entry.value;
          if (value is String) {
            await prefs.setString(key, value);
          } else if (value is int) {
            await prefs.setInt(key, value);
          } else if (value is double) {
            await prefs.setDouble(key, value);
          } else if (value is bool) {
            await prefs.setBool(key, value);
          } else {
            await prefs.setString(key, jsonEncode(value));
          }
        }
        return {'success': true};
      }
      return {'success': false, 'error': 'Invalid data format'};
    } catch (e) {
      return {'success': false, 'error': e.toString()};
    }
  }

  // Récupérer des données localement
  Future<Map<String, dynamic>> _handleGetData(String key) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final value = prefs.get(key);
      if (value != null) {
        return {'success': true, 'data': value};
      }
      return {'success': false, 'error': 'Key not found'};
    } catch (e) {
      return {'success': false, 'error': e.toString()};
    }
  }

  // Afficher une notification
  Future<Map<String, dynamic>> _handleShowNotification(
    String title,
    String body,
  ) async {
    try {
      // Ici vous pouvez intégrer flutter_local_notifications
      // Pour l'instant, on retourne un succès
      // Vous pouvez aussi afficher un snackbar ou dialog
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('$title: $body'),
            duration: const Duration(seconds: 3),
          ),
        );
      }
      return {'success': true};
    } catch (e) {
      return {'success': false, 'error': e.toString()};
    }
  }

  // Injecter le code JavaScript pour la communication
  Future<void> _injectJavaScript() async {
    const jsCode = '''
      (function() {
        window.__COLOBANES_NATIVE_APP = true;
        window.ColobanesNative = {
          // Demander l'accès à la caméra
          requestCamera: function() {
            return new Promise((resolve, reject) => {
              window.flutter_inappwebview.callHandler('requestCamera')
                .then(result => {
                  if (result.success) {
                    resolve(result);
                  } else {
                    reject(new Error(result.error || 'Camera request failed'));
                  }
                })
                .catch(error => reject(error));
            });
          },
          
          // Demander la localisation
          requestLocation: function() {
            return new Promise((resolve, reject) => {
              window.flutter_inappwebview.callHandler('requestLocation')
                .then(result => {
                  if (result.success) {
                    resolve(result);
                  } else {
                    reject(new Error(result.error || 'Location request failed'));
                  }
                })
                .catch(error => reject(error));
            });
          },
          
          // Sauvegarder des données
          saveData: function(data) {
            return new Promise((resolve, reject) => {
              window.flutter_inappwebview.callHandler('saveData', data)
                .then(result => {
                  if (result.success) {
                    resolve(result);
                  } else {
                    reject(new Error(result.error || 'Save failed'));
                  }
                })
                .catch(error => reject(error));
            });
          },
          
          // Récupérer des données
          getData: function(key) {
            return new Promise((resolve, reject) => {
              window.flutter_inappwebview.callHandler('getData', key)
                .then(result => {
                  if (result.success) {
                    resolve(result.data);
                  } else {
                    reject(new Error(result.error || 'Get failed'));
                  }
                })
                .catch(error => reject(error));
            });
          },
          
          // Afficher une notification
          showNotification: function(title, body) {
            return new Promise((resolve, reject) => {
              window.flutter_inappwebview.callHandler('showNotification', title, body)
                .then(result => {
                  if (result.success) {
                    resolve(result);
                  } else {
                    reject(new Error(result.error || 'Notification failed'));
                  }
                })
                .catch(error => reject(error));
            });
          },

          // Connexion Google native (contourne le blocage WebView 403)
          signInWithGoogle: function() {
            return new Promise((resolve, reject) => {
              window.flutter_inappwebview.callHandler('signInWithGoogle')
                .then(result => {
                  if (result && result.success && result.idToken) {
                    resolve(result);
                  } else {
                    reject(new Error((result && result.error) ? result.error : 'Connexion Google impossible'));
                  }
                })
                .catch(error => reject(error));
            });
          },

          isNativeApp: function() {
            return true;
          }
        };
        window.AriaNative = window.ColobanesNative;

        // Intercepter Google AVANT le JS du site (évite signInWithPopup bloqué en WebView)
        if (document.documentElement.getAttribute('data-colobanes-google-hook') !== '1') {
          document.documentElement.setAttribute('data-colobanes-google-hook', '1');
          document.addEventListener('click', function(event) {
            var googleBtn = event.target.closest('.google-auth-btn');
            if (!googleBtn || !window.flutter_inappwebview) return;

            event.preventDefault();
            event.stopImmediatePropagation();

            var wrap = googleBtn.closest('.social-auth');
            var msgEl = wrap ? wrap.querySelector('.social-auth-message') : null;
            var accountType = googleBtn.getAttribute('data-social-auth-type')
              || googleBtn.getAttribute('data-google-auth-type') || 'auto';
            var redirect = googleBtn.getAttribute('data-social-auth-redirect')
              || googleBtn.getAttribute('data-google-auth-redirect') || '';
            var originalHtml = googleBtn.innerHTML;

            function setMsg(text, isError) {
              if (!msgEl) return;
              msgEl.textContent = text || '';
              msgEl.classList.toggle('is-error', !!isError);
            }

            if (wrap) {
              wrap.querySelectorAll('.google-auth-btn, .apple-auth-btn').forEach(function(btn) {
                btn.disabled = true;
              });
            }
            setMsg('', false);

            window.flutter_inappwebview.callHandler('signInWithGoogle')
              .then(function(result) {
                if (!result || !result.success || !result.idToken) {
                  throw new Error((result && result.error) ? result.error : 'Connexion Google impossible.');
                }
                return fetch('/auth-firebase-callback.php', {
                  method: 'POST',
                  headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                  },
                  credentials: 'same-origin',
                  body: JSON.stringify({
                    idToken: result.idToken,
                    accountType: accountType,
                    redirect: redirect,
                    provider: 'google'
                  })
                });
              })
              .then(function(response) {
                return response.json().catch(function() {
                  throw new Error('Réponse serveur invalide.');
                });
              })
              .then(function(data) {
                if (!data || !data.success || !data.redirect) {
                  throw new Error((data && data.message) ? data.message : 'Connexion refusée.');
                }
                window.location.href = data.redirect;
              })
              .catch(function(error) {
                setMsg(error && error.message ? error.message : 'Connexion annulée ou impossible.', true);
                if (wrap) {
                  wrap.querySelectorAll('.google-auth-btn, .apple-auth-btn').forEach(function(btn) {
                    btn.disabled = false;
                  });
                }
                googleBtn.innerHTML = originalHtml;
              });
          }, true);
          console.log('ColobanesNative Google auth hook active');
        }
        
        console.log('ColobanesNative API initialized');
      })();
    ''';

    await webViewController?.evaluateJavascript(source: jsCode);
  }

  // Enregistrer le token FCM dans la WebView (utilise la session authentifiée)
  Future<void> _registerFCMTokenInWebView() async {
    try {
      final fcmToken = FCMService.getToken();
      if (fcmToken != null) {
        // Injecter le script pour enregistrer le token via la WebView
        final script = FCMService.getTokenRegistrationScript(fcmToken);
        await webViewController?.evaluateJavascript(source: script);
        print('📤 Token FCM envoyé via WebView');
      }
    } catch (e) {
      print('❌ Erreur lors de l\'enregistrement du token FCM: $e');
    }
  }

  // Gérer le bouton retour Android
  Future<bool> _handleBackButton() async {
    if (webViewController != null) {
      // Vérifier si la WebView peut revenir en arrière
      final canGoBack = await webViewController!.canGoBack();
      if (canGoBack) {
        // Revenir à la page précédente dans l'historique
        await webViewController!.goBack();
        return false; // Empêcher la fermeture de l'app
      }
    }
    // Si on ne peut pas revenir en arrière, fermer l'application
    return true; // Permettre la fermeture de l'app
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false, // Empêcher la fermeture automatique
      onPopInvoked: (didPop) async {
        if (!didPop) {
          final shouldPop = await _handleBackButton();
          if (shouldPop && context.mounted) {
            SystemNavigator.pop(); // Fermer l'application
          }
        }
      },
      child: Scaffold(
        // Désactiver la résize automatique pour améliorer les performances du clavier
        resizeToAvoidBottomInset: false,
        body: Stack(
          children: [
            SafeArea(
              child: InAppWebView(
                initialUrlRequest: URLRequest(
                  url: WebUri(_currentUrl ?? kMarketplaceBaseUrl),
                ),
                initialUserScripts: UnmodifiableListView<UserScript>([
                  UserScript(
                    source: 'window.__COLOBANES_NATIVE_APP = true;',
                    injectionTime: UserScriptInjectionTime.AT_DOCUMENT_START,
                  ),
                ]),
                initialSettings: InAppWebViewSettings(
                  applicationNameForUserAgent: 'ColobanesApp',
                  javaScriptEnabled: true,
                  domStorageEnabled: true,
                  databaseEnabled: true,
                  useShouldOverrideUrlLoading: true,
                  mediaPlaybackRequiresUserGesture: false,
                  allowsInlineMediaPlayback: true,
                  iframeAllow: "camera",
                  iframeAllowFullscreen: true,
                  // Désactivé pour améliorer les performances
                  useOnLoadResource: false,
                  useOnDownloadStart: false,
                  useShouldInterceptRequest: false,
                  thirdPartyCookiesEnabled: true,
                  cacheEnabled: true,
                  clearCache: false,
                  transparentBackground: false,
                  supportZoom: true,
                  builtInZoomControls: false,
                  displayZoomControls: false,
                  // Optimisations pour le clavier
                  verticalScrollBarEnabled: true,
                  horizontalScrollBarEnabled: true,
                  // Désactiver les animations inutiles
                  disableVerticalScroll: false,
                  disableHorizontalScroll: false,
                ),
                onWebViewCreated: (controller) {
                  webViewController = controller;
                  _setupJavaScriptHandlers();
                },
                onLoadStart: (controller, url) {
                  setState(() {
                    isLoading = true;
                  });
                },
                onLoadStop: (controller, url) async {
                  setState(() {
                    isLoading = false;
                    isInitialLoad =
                        false; // Après le premier chargement, ce n'est plus le chargement initial
                  });
                  // Sauvegarder l'URL actuelle
                  if (url != null) {
                    _currentUrl = url.toString();
                    await _saveCurrentUrl();
                  }
                  await _injectJavaScript();
                  // Envoyer le token FCM après le chargement de la page
                  await _registerFCMTokenInWebView();
                },
                onProgressChanged: (controller, progress) {
                  // Ne mettre à jour que si la différence est significative pour éviter trop de rebuilds
                  final newProgress = progress / 100;
                  if ((newProgress - this.progress).abs() > 0.01) {
                    setState(() {
                      this.progress = newProgress;
                    });
                  }
                },
                onPermissionRequest: (controller, request) async {
                  // Caméra uniquement si le site la demande ; pas de micro (non utilisé par COLObanes)
                  final allowed = request.resources.where((r) {
                    final name = r.toString().toLowerCase();
                    return name.contains('camera');
                  }).toList();
                  if (allowed.isEmpty) {
                    return PermissionResponse(
                      resources: request.resources,
                      action: PermissionResponseAction.DENY,
                    );
                  }
                  return PermissionResponse(
                    resources: allowed,
                    action: PermissionResponseAction.GRANT,
                  );
                },
                onReceivedError: (controller, request, error) {
                  print('WebView Error: ${error.description}');
                },
                shouldOverrideUrlLoading: (controller, navigationAction) async {
                  return NavigationActionPolicy.ALLOW;
                },
              ),
            ),
            // Loader plein écran uniquement au lancement
            if (isLoading && isInitialLoad)
              _MarketplaceLoader(progress: progress),
            // Barre de progression discrète en haut pour les navigations
            if (isLoading && !isInitialLoad)
              _TopProgressBar(progress: progress),
          ],
        ),
      ),
    );
  }
}

// Widget personnalisé pour le loader avec logo et barre de progression
class _MarketplaceLoader extends StatefulWidget {
  final double progress;

  const _MarketplaceLoader({required this.progress});

  @override
  State<_MarketplaceLoader> createState() => _MarketplaceLoaderState();
}

class _MarketplaceLoaderState extends State<_MarketplaceLoader>
    with TickerProviderStateMixin {
  static const Color _senegalRouge = Color(0xFFE31B23);
  static const Color _senegalJaune = Color(0xFFFCD116);
  static const Color _senegalVert = Color(0xFF00853F);

  late AnimationController _pulseController;
  late Animation<double> _scaleAnimation;
  late AnimationController _ribbonController;

  @override
  void initState() {
    super.initState();
    _pulseController = AnimationController(
      duration: const Duration(milliseconds: 1400),
      vsync: this,
    )..repeat(reverse: true);

    _scaleAnimation = Tween<double>(begin: 0.96, end: 1.04).animate(
      CurvedAnimation(parent: _pulseController, curve: Curves.easeInOut),
    );

    _ribbonController = AnimationController(
      duration: const Duration(milliseconds: 2400),
      vsync: this,
    )..repeat();
  }

  @override
  void dispose() {
    _pulseController.dispose();
    _ribbonController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final p = widget.progress.clamp(0.0, 1.0);

    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            Color.lerp(kOrangePromo, Colors.white, 0.06)!,
            kBleuLogoMarine,
            const Color(0xFF0D2238),
          ],
          stops: const [0.0, 0.52, 1.0],
        ),
      ),
      child: SafeArea(
        child: Center(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 28),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                AnimatedBuilder(
                  animation: _ribbonController,
                  builder: (context, _) {
                    final slide =
                        (_ribbonController.value * 200 - 60).clamp(-60.0, 140.0);
                    return ClipRRect(
                      borderRadius: BorderRadius.circular(4),
                      child: SizedBox(
                        height: 5,
                        width: 168,
                        child: Stack(
                          clipBehavior: Clip.hardEdge,
                          children: [
                            const Row(
                              children: [
                                Expanded(child: ColoredBox(color: _senegalRouge)),
                                Expanded(child: ColoredBox(color: _senegalJaune)),
                                Expanded(child: ColoredBox(color: _senegalVert)),
                              ],
                            ),
                            Positioned.fill(
                              child: Transform.translate(
                                offset: Offset(slide, 0),
                                child: Container(
                                  width: 56,
                                  decoration: BoxDecoration(
                                    gradient: LinearGradient(
                                      colors: [
                                        Colors.white.withValues(alpha: 0),
                                        Colors.white.withValues(alpha: 0.38),
                                        Colors.white.withValues(alpha: 0),
                                      ],
                                    ),
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
                const SizedBox(height: 28),
                AnimatedBuilder(
                  animation: _pulseController,
                  builder: (context, child) {
                    return Transform.scale(
                      scale: _scaleAnimation.value,
                      child: child,
                    );
                  },
                  child: Container(
                    width: 136,
                    height: 136,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(30),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.22),
                          blurRadius: 28,
                          offset: const Offset(0, 14),
                          spreadRadius: -4,
                        ),
                        BoxShadow(
                          color: kOrangePromo.withValues(alpha: 0.35),
                          blurRadius: 24,
                          spreadRadius: -8,
                        ),
                      ],
                    ),
                    padding: const EdgeInsets.all(14),
                    child: Image.asset(
                      'assets/images/app_icon.png',
                      fit: BoxFit.contain,
                      errorBuilder: (context, error, stackTrace) {
                        return const Icon(
                          Icons.shopping_cart_rounded,
                          size: 64,
                          color: kBleuLogoMarine,
                        );
                      },
                    ),
                  ),
                ),
                const SizedBox(height: 28),
                Text.rich(
                  TextSpan(
                    children: [
                      TextSpan(
                        text: 'COLO',
                        style: TextStyle(
                          fontSize: 30,
                          fontWeight: FontWeight.w800,
                          color: kOrangePromo,
                          letterSpacing: 0.5,
                          shadows: [
                            Shadow(
                              color: Colors.black.withValues(alpha: 0.25),
                              blurRadius: 8,
                              offset: Offset(0, 2),
                            ),
                          ],
                        ),
                      ),
                      TextSpan(
                        text: 'banes',
                        style: TextStyle(
                          fontSize: 30,
                          fontWeight: FontWeight.w800,
                          color: Colors.white,
                          letterSpacing: 0.5,
                          shadows: [
                            Shadow(
                              color: Colors.black.withValues(alpha: 0.35),
                              blurRadius: 10,
                              offset: Offset(0, 2),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 10),
                Text(
                  'MARCHÉ EN LIGNE • GLOBAL MARKETPLACE',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 10.5,
                    letterSpacing: 1.1,
                    height: 1.35,
                    color: Colors.white.withValues(alpha: 0.76),
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 14),
                Text(
                  'Chargement du marché…',
                  style: TextStyle(
                    fontSize: 15,
                    color: Colors.white.withValues(alpha: 0.9),
                    fontWeight: FontWeight.w400,
                  ),
                ),
                const SizedBox(height: 36),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 32),
                  child: Column(
                    children: [
                      ClipRRect(
                        borderRadius: BorderRadius.circular(10),
                        child: SizedBox(
                          height: 8,
                          width: double.infinity,
                          child: Stack(
                            fit: StackFit.expand,
                            children: [
                              ColoredBox(
                                color: Colors.white.withValues(alpha: 0.18),
                              ),
                              FractionallySizedBox(
                                alignment: Alignment.centerLeft,
                                widthFactor: p,
                                heightFactor: 1,
                                child: DecoratedBox(
                                  decoration: BoxDecoration(
                                    borderRadius: BorderRadius.circular(10),
                                    gradient: LinearGradient(
                                      colors: [
                                        Colors.white.withValues(alpha: 0.95),
                                        Color.lerp(
                                          Colors.white,
                                          kOrangePromo,
                                          0.55,
                                        )!,
                                      ],
                                    ),
                                    boxShadow: [
                                      BoxShadow(
                                        color: Colors.white.withValues(
                                          alpha: 0.45,
                                        ),
                                        blurRadius: 10,
                                        spreadRadius: 0,
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 12),
                      Text(
                        '${(p * 100).toInt()}%',
                        style: TextStyle(
                          fontSize: 14,
                          color: Colors.white.withValues(alpha: 0.82),
                          fontWeight: FontWeight.w600,
                          fontFeatures: const [FontFeature.tabularFigures()],
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 36),
                SizedBox(
                  width: 30,
                  height: 30,
                  child: CircularProgressIndicator(
                    valueColor: AlwaysStoppedAnimation<Color>(
                      Colors.white.withValues(alpha: 0.85),
                    ),
                    strokeWidth: 3,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

// Barre de progression discrète en haut de l'écran pour les navigations
class _TopProgressBar extends StatelessWidget {
  final double progress;

  const _TopProgressBar({required this.progress});

  @override
  Widget build(BuildContext context) {
    return Positioned(
      top: 0,
      left: 0,
      right: 0,
      child: Container(
        height: 3,
        child: Stack(
          children: [
            // Barre de progression
            FractionallySizedBox(
              widthFactor: progress.clamp(0.0, 1.0),
              child: Container(
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [kBleuPrincipal, kOrangePromo],
                  ),
                  boxShadow: [
                    BoxShadow(
                      color: kBleuPrincipal.withValues(alpha: 0.45),
                      blurRadius: 6,
                      spreadRadius: 1,
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
