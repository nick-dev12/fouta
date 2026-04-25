// Tests sans montage de la WebView (incompatible avec l'environnement de test).
import 'package:flutter_test/flutter_test.dart';

import 'package:colobanes_marketplace/main.dart' as app;

void main() {
  test('resolveRelativeMarketUrl gère URL absolue', () {
    expect(
      app.resolveRelativeMarketUrl('https://samapiece.it.com/panier/'),
      'https://samapiece.it.com/panier/',
    );
  });

  test('resolveRelativeMarketUrl gère chemin relatif', () {
    expect(
      app.resolveRelativeMarketUrl('/produits/'),
      'https://samapiece.it.com/produits/',
    );
  });

  test('constante base pointe vers la marketplace', () {
    expect(app.kMarketplaceBaseUrl, 'https://samapiece.it.com/');
  });
}
