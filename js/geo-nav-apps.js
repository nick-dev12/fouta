/**
 * Liens navigation livraison (partagés avec geo_location_service.php côté PHP).
 */
(function () {
    'use strict';

    window.geoBuildNavApps = function (lat, lng, label) {
        label = label || 'Position client';
        var enc = encodeURIComponent;
        var gmaps = 'https://www.google.com/maps/dir/?api=1&destination=' + lat + ',' + lng + '&travelmode=driving';
        var yango = 'https://3.redirect.appmetrica.yandex.com/route?end-lat=' + lat + '&end-lon=' + lng;
        var yassir = 'yassir://book-ride?destinationLat=' + enc(lat) + '&destinationLng=' + enc(lng);
        var wa = 'https://wa.me/?text=' + enc(label + ' : https://maps.google.com/?q=' + lat + ',' + lng);

        return [
            { name: 'Google Maps', icon: 'fab fa-google', cls: 'gmaps', url: gmaps },
            { name: 'Yango', icon: 'fas fa-car', cls: 'yango', url: yango },
            { name: 'Partager sur WhatsApp', icon: 'fab fa-whatsapp', cls: 'whatsapp', url: wa }
        ];
    };
})();
