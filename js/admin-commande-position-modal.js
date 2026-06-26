/**
 * Modal position client + ouverture apps livraison (admin commandes).
 */
(function () {
    'use strict';

    var map = null;
    var marker = null;
    var currentLat = null;
    var currentLng = null;
    var currentLabel = 'Position client';

    function qs(id) { return document.getElementById(id); }

    function openModal() {
        var modal = qs('cmd-pos-modal');
        if (modal) {
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal() {
        var modal = qs('cmd-pos-modal');
        if (modal) {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }
        closeAppsPanel();
    }

    function closeAppsPanel() {
        var panel = qs('cmd-pos-apps-panel');
        if (panel) panel.classList.remove('is-open');
    }

    function openAppsPanel() {
        if (currentLat === null || currentLng === null) return;
        var panel = qs('cmd-pos-apps-panel');
        if (!panel) return;

        var apps = typeof window.geoBuildNavApps === 'function'
            ? window.geoBuildNavApps(currentLat, currentLng, currentLabel)
            : [];

        var list = qs('cmd-pos-apps-list');
        if (!list) return;
        list.innerHTML = '';
        apps.forEach(function (app) {
            var a = document.createElement('a');
            a.className = 'cmd-pos-app-link cmd-pos-app-link--' + app.cls;
            a.href = app.url;
            a.target = '_blank';
            a.rel = 'noopener noreferrer';
            a.innerHTML = '<i class="' + app.icon + '"></i><span>' + app.name + '</span><i class="fas fa-external-link-alt cmd-pos-app-ext"></i>';
            list.appendChild(a);
        });

        panel.classList.add('is-open');
    }

    function ensureMap(lat, lng) {
        var container = qs('cmd-pos-map');
        if (!container || typeof window.L === 'undefined') return;

        if (!map) {
            map = L.map(container, { zoomControl: true }).setView([lat, lng], 16);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);
            marker = L.marker([lat, lng]).addTo(map);
        } else {
            marker.setLatLng([lat, lng]);
            map.setView([lat, lng], 16);
        }
        setTimeout(function () { map.invalidateSize(); }, 200);
    }

    function geocodeAddress(address) {
        var url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q='
            + encodeURIComponent(address) + '&accept-language=fr';
        return fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : []; })
            .then(function (data) {
                if (data && data[0] && data[0].lat && data[0].lon) {
                    return { lat: parseFloat(data[0].lat), lng: parseFloat(data[0].lon) };
                }
                return null;
            });
    }

    function showPosition(lat, lng, meta) {
        currentLat = lat;
        currentLng = lng;
        currentLabel = meta.numero ? ('Commande #' + meta.numero) : 'Position client';

        var loading = qs('cmd-pos-loading');
        if (loading) loading.style.display = 'none';

        ensureMap(lat, lng);

        var title = qs('cmd-pos-modal-title');
        if (title && meta.numero) {
            title.textContent = 'Position client — #' + meta.numero;
        }
        var addrEl = qs('cmd-pos-modal-addr');
        if (addrEl) {
            addrEl.textContent = meta.adresse || '';
            addrEl.style.display = meta.adresse ? '' : 'none';
        }

        var waBtn = qs('cmd-pos-btn-whatsapp');
        if (waBtn && typeof window.geoBuildNavApps === 'function') {
            var apps = window.geoBuildNavApps(lat, lng, currentLabel);
            var waApp = apps.filter(function (a) { return a.cls === 'whatsapp'; })[0];
            if (waApp && waApp.url) {
                waBtn.href = waApp.url;
                waBtn.hidden = false;
            } else {
                waBtn.hidden = true;
            }
        }

        var noGeo = qs('cmd-pos-no-geo');
        var body = qs('cmd-pos-modal-body');
        if (noGeo) noGeo.style.display = 'none';
        if (body) body.style.display = '';

        openModal();
    }

    function showNoPosition(message) {
        currentLat = null;
        currentLng = null;
        var loading = qs('cmd-pos-loading');
        if (loading) loading.style.display = 'none';
        var noGeo = qs('cmd-pos-no-geo');
        var body = qs('cmd-pos-modal-body');
        if (noGeo) {
            noGeo.style.display = '';
            var p = noGeo.querySelector('p');
            if (p) p.textContent = message;
        }
        if (body) body.style.display = 'none';
        openModal();
    }

    function onVoirPositionClick(btn) {
        var lat = parseFloat(btn.getAttribute('data-lat') || '');
        var lng = parseFloat(btn.getAttribute('data-lng') || '');
        var adresse = btn.getAttribute('data-adresse') || '';
        var numero = btn.getAttribute('data-numero') || '';

        if (!isNaN(lat) && !isNaN(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
            showPosition(lat, lng, { adresse: adresse, numero: numero });
            return;
        }

        if (adresse.trim() !== '') {
            var loading = qs('cmd-pos-loading');
            if (loading) loading.style.display = 'flex';
            openModal();
            geocodeAddress(adresse).then(function (pos) {
                if (loading) loading.style.display = 'none';
                if (pos) {
                    showPosition(pos.lat, pos.lng, { adresse: adresse, numero: numero });
                } else {
                    showNoPosition('Position GPS non disponible et adresse introuvable sur la carte.');
                }
            }).catch(function () {
                if (loading) loading.style.display = 'none';
                showNoPosition('Impossible de localiser cette adresse.');
            });
            return;
        }

        showNoPosition('Aucune position GPS enregistrée pour cette commande.');
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.js-cmd-voir-position').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                onVoirPositionClick(btn);
            });
        });

        var closeBtn = qs('cmd-pos-modal-close');
        var backdrop = qs('cmd-pos-modal-backdrop');
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (backdrop) backdrop.addEventListener('click', closeModal);

        var btnLivreur = qs('cmd-pos-btn-livreur');
        if (btnLivreur) {
            btnLivreur.addEventListener('click', function () {
                if (currentLat !== null) openAppsPanel();
            });
        }

        var appsClose = qs('cmd-pos-apps-close');
        var appsBackdrop = qs('cmd-pos-apps-backdrop');
        if (appsClose) appsClose.addEventListener('click', closeAppsPanel);
        if (appsBackdrop) appsBackdrop.addEventListener('click', closeAppsPanel);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeAppsPanel();
                closeModal();
            }
        });
    });
})();
