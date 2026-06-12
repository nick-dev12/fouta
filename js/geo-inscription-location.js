/**
 * Capture GPS à l'inscription (client / vendeur) — UI uniquement.
 * App native : GeoNativeBridge (ColobanesNative + geolocator).
 */
(function () {
    'use strict';

    function qs(id) { return document.getElementById(id); }

    function reverseGeocodeConcise(lat, lng, cb) {
        if (window.GeoNativeBridge && typeof window.GeoNativeBridge.reverseGeocode === 'function') {
            window.GeoNativeBridge.reverseGeocode(lat, lng).then(function (label) {
                cb(label || '');
            }).catch(function () { cb(''); });
            return;
        }
        var url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&zoom=17&lat='
            + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng);
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (!data) { cb(''); return; }
                if (window.GeoAddressFormat && typeof window.GeoAddressFormat.fromNominatim === 'function') {
                    cb(window.GeoAddressFormat.fromNominatim(data));
                    return;
                }
                cb(data.display_name || '');
            })
            .catch(function () { cb(''); });
    }

    function fillCoords(lat, lng, accuracy) {
        var latEl = qs('insc_geo_lat');
        var lngEl = qs('insc_geo_lng');
        var precEl = qs('insc_geo_precision');
        if (latEl) latEl.value = lat.toFixed(8);
        if (lngEl) lngEl.value = lng.toFixed(8);
        if (precEl && accuracy != null) precEl.value = Math.round(accuracy);
    }

    function capturePosition(onSuccess, onError, options) {
        options = options || {};
        if (window.GeoNativeBridge && typeof window.GeoNativeBridge.getCurrentPosition === 'function') {
            window.GeoNativeBridge.getCurrentPosition(options).then(onSuccess).catch(onError);
            return;
        }
        if (!('geolocation' in navigator)) {
            onError();
            return;
        }
        navigator.geolocation.getCurrentPosition(onSuccess, onError, {
            enableHighAccuracy: true,
            timeout: options.timeout || 12000,
            maximumAge: options.maximumAge != null ? options.maximumAge : 300000
        });
    }

    function autoCapture() {
        var latEl = qs('insc_geo_lat');
        if (!latEl || latEl.value) return;
        capturePosition(
            function (pos) {
                fillCoords(pos.coords.latitude, pos.coords.longitude, pos.coords.accuracy);
            },
            function () { /* silencieux */ },
            { maximumAge: 300000 }
        );
    }

    function bindLocalizeBoutique() {
        var btn = qs('btn-localiser-boutique');
        var addr = qs('boutique_adresse');
        var status = qs('insc-geo-status');
        if (!btn || !addr) return;

        btn.addEventListener('click', function () {
            btn.disabled = true;
            if (status) status.textContent = 'Localisation en cours…';
            capturePosition(
                function (pos) {
                    var lat = pos.coords.latitude;
                    var lng = pos.coords.longitude;
                    fillCoords(lat, lng, pos.coords.accuracy);
                    var flag = qs('insc_geo_manual');
                    if (flag) flag.value = '1';
                    reverseGeocodeConcise(lat, lng, function (label) {
                        if (label) addr.value = label;
                        if (status) status.textContent = label ? 'Position détectée — vérifiez l\'adresse puis validez.' : 'Coordonnées enregistrées — complétez l\'adresse si besoin.';
                        btn.disabled = false;
                    });
                },
                function () {
                    if (status) status.textContent = 'Impossible d\'obtenir la position. Saisissez l\'adresse manuellement.';
                    btn.disabled = false;
                },
                { maximumAge: 0 }
            );
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        autoCapture();
        bindLocalizeBoutique();
    });
})();
