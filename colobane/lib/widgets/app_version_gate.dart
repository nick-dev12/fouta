import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_native_splash/flutter_native_splash.dart';
import 'package:url_launcher/url_launcher.dart';

import '../main.dart' show kBleuPrincipal, kOrangePromo, kSplashLogoAsset;
import '../services/app_version_service.dart';

/// Vérifie la version au démarrage et bloque l'accès si une mise à jour est requise.
class AppVersionGate extends StatefulWidget {
  const AppVersionGate({super.key, required this.child});

  final Widget child;

  @override
  State<AppVersionGate> createState() => _AppVersionGateState();
}

class _AppVersionGateState extends State<AppVersionGate> {
  AppVersionCheckResult? _blockedResult;
  bool _checking = true;

  @override
  void initState() {
    super.initState();
    _runCheck();
  }

  Future<void> _runCheck() async {
    final result = await fetchAppVersionCheck();
    if (!mounted) {
      return;
    }
    if (result != null && result.updateRequired) {
      FlutterNativeSplash.remove();
      setState(() {
        _blockedResult = result;
        _checking = false;
      });
      return;
    }
    if (mounted) {
      setState(() => _checking = false);
    }
  }

  void _clearBlock() {
    setState(() => _blockedResult = null);
  }

  @override
  Widget build(BuildContext context) {
    if (_blockedResult != null) {
      return ForceUpdateScreen(
        result: _blockedResult!,
        onUnblocked: _clearBlock,
      );
    }
    if (_checking) {
      return const _VersionCheckSplash();
    }
    return widget.child;
  }
}

class _VersionCheckSplash extends StatelessWidget {
  const _VersionCheckSplash();

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      backgroundColor: Colors.white,
      body: Center(
        child: SizedBox(
          width: 120,
          height: 120,
          child: Image(
            image: AssetImage(kSplashLogoAsset),
            fit: BoxFit.contain,
          ),
        ),
      ),
    );
  }
}

class ForceUpdateScreen extends StatefulWidget {
  const ForceUpdateScreen({
    super.key,
    required this.result,
    required this.onUnblocked,
  });

  final AppVersionCheckResult result;
  final VoidCallback onUnblocked;

  @override
  State<ForceUpdateScreen> createState() => _ForceUpdateScreenState();
}

class _ForceUpdateScreenState extends State<ForceUpdateScreen> {
  bool _rechecking = false;

  Future<void> _openStore() async {
    final url = Uri.tryParse(widget.result.storeUrl);
    if (url == null) {
      return;
    }
    await launchUrl(url, mode: LaunchMode.externalApplication);
  }

  Future<void> _runRecheck() async {
    if (_rechecking) {
      return;
    }
    setState(() => _rechecking = true);
    final fresh = await fetchAppVersionCheck();
    if (!mounted) {
      return;
    }
    setState(() => _rechecking = false);
    if (fresh != null && !fresh.updateRequired) {
      widget.onUnblocked();
    }
  }

  @override
  Widget build(BuildContext context) {
    final result = widget.result;
    return PopScope(
      canPop: false,
      child: AnnotatedRegion<SystemUiOverlayStyle>(
        value: SystemUiOverlayStyle.dark,
        child: Scaffold(
          backgroundColor: Colors.white,
          body: SafeArea(
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 24),
              child: Column(
                children: [
                  const Spacer(flex: 2),
                  Image.asset(
                    kSplashLogoAsset,
                    width: 110,
                    height: 110,
                    fit: BoxFit.contain,
                  ),
                  const SizedBox(height: 28),
                  Icon(
                    Icons.system_update_alt_rounded,
                    size: 56,
                    color: kOrangePromo,
                  ),
                  const SizedBox(height: 20),
                  Text(
                    result.title.isNotEmpty
                        ? result.title
                        : 'Mise à jour requise',
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.w700,
                      color: Color(0xFF0D0D0D),
                    ),
                  ),
                  const SizedBox(height: 14),
                  Text(
                    result.message.isNotEmpty
                        ? result.message
                        : 'Veuillez mettre à jour l\'application pour continuer.',
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      fontSize: 15,
                      height: 1.45,
                      color: Color(0xFF4A4A4A),
                    ),
                  ),
                  if (result.minBuild > 0) ...[
                    const SizedBox(height: 12),
                    Text(
                      'Version installée : build ${result.installedBuild} — minimum requis : ${result.minBuild}',
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        fontSize: 13,
                        color: Color(0xFF737373),
                      ),
                    ),
                  ],
                  const Spacer(flex: 3),
                  SizedBox(
                    width: double.infinity,
                    child: FilledButton(
                      onPressed: _openStore,
                      style: FilledButton.styleFrom(
                        backgroundColor: kBleuPrincipal,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      child: const Text(
                        'Mettre à jour',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  TextButton(
                    onPressed: _rechecking ? null : _runRecheck,
                    child: Text(_rechecking ? 'Vérification…' : 'Réessayer'),
                  ),
                  const SizedBox(height: 8),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
