/**
 * Carte interactive — boutiques proches (Leaflet).
 */
(function (win, doc) {
    'use strict';

    var map = null;
    var markersLayer = null;
    var userMarker = null;
    var markerById = {};
    var selectedId = null;
    var state = { user: null, boutiques: [] };
    var sidePanel = null;
    var listPanel = null;
    var listToggle = null;

    function readPayload() {
        var el = doc.getElementById('mpBtMapData');
        if (!el) {
            return;
        }
        try {
            var data = JSON.parse(el.textContent || '{}');
            state.user = data.user || null;
            state.boutiques = Array.isArray(data.boutiques) ? data.boutiques : [];
        } catch (e) {
            state.boutiques = [];
        }
    }

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/"/g, '&quot;');
    }

    function formatDistance(km) {
        if (km == null || isNaN(km)) {
            return '';
        }
        var n = Number(km);
        if (n < 1) {
            return Math.round(n * 1000) + ' m';
        }
        return n.toFixed(n < 10 ? 1 : 0).replace('.', ',') + ' km';
    }

    function buildMarkerIcon(item) {
        var color = item.theme_main || '#3564a6';
        var logo = item.logo
            ? '<img src="' + esc(item.logo) + '" alt="" onerror="this.remove()">'
            : '<i class="fas fa-store"></i>';
        var label = esc(item.nom || 'Boutique');
        return L.divIcon({
            className: 'mp-bt-map-marker-wrap',
            html: ''
                + '<div class="mp-bt-map-marker" style="--mk-color:' + esc(color) + '">'
                + '<div class="mp-bt-map-marker__pin">' + logo + '</div>'
                + '<span class="mp-bt-map-marker__label">' + label + '</span>'
                + '</div>',
            iconSize: [120, 72],
            iconAnchor: [60, 42]
        });
    }

    function openModal() {
        var modal = doc.getElementById('mpBtMapModal');
        if (!modal) {
            return;
        }
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        doc.body.classList.add('mp-bt-map-open');
        win.setTimeout(initMap, 60);
    }

    function closeModal() {
        var modal = doc.getElementById('mpBtMapModal');
        if (!modal) {
            return;
        }
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        doc.body.classList.remove('mp-bt-map-open');
        hideDetailPanel();
        closeListPanel();
    }

    function hideDetailPanel() {
        selectedId = null;
        if (!sidePanel) {
            return;
        }
        sidePanel.hidden = true;
        sidePanel.classList.remove('is-open');
        doc.querySelectorAll('.mp-bt-map-list__item.is-active').forEach(function (el) {
            el.classList.remove('is-active');
        });
    }

    function showDetailPanel() {
        if (!sidePanel) {
            return;
        }
        sidePanel.hidden = false;
        win.requestAnimationFrame(function () {
            sidePanel.classList.add('is-open');
        });
    }

    function openListPanel() {
        if (!listPanel || !listToggle) {
            return;
        }
        listPanel.classList.add('is-open');
        listToggle.setAttribute('aria-expanded', 'true');
    }

    function closeListPanel() {
        if (!listPanel || !listToggle) {
            return;
        }
        listPanel.classList.remove('is-open');
        listToggle.setAttribute('aria-expanded', 'false');
    }

    function toggleListPanel() {
        if (!listPanel) {
            return;
        }
        if (listPanel.classList.contains('is-open')) {
            closeListPanel();
        } else {
            openListPanel();
        }
    }

    function highlightListItem(id) {
        doc.querySelectorAll('.mp-bt-map-list__item').forEach(function (el) {
            el.classList.toggle('is-active', String(el.getAttribute('data-id')) === String(id));
        });
    }

    function selectBoutique(item, options) {
        options = options || {};
        if (!item) {
            return;
        }
        selectedId = item.id;
        highlightListItem(item.id);
        showDetailPanel();

        var logoWrap = doc.getElementById('mpBtMapSideLogo');
        if (logoWrap) {
            logoWrap.innerHTML = item.logo
                ? '<img src="' + esc(item.logo) + '" alt="">'
                : '<i class="fas fa-store"></i>';
        }

        var nameEl = doc.getElementById('mpBtMapSideName');
        if (nameEl) {
            nameEl.textContent = item.nom || 'Boutique';
        }

        var distEl = doc.getElementById('mpBtMapSideDist');
        if (distEl) {
            var distLabel = formatDistance(item.distance_km);
            distEl.textContent = distLabel;
            distEl.hidden = distLabel === '';
        }

        var addrEl = doc.getElementById('mpBtMapSideAddr');
        if (addrEl) {
            addrEl.textContent = item.adresse || item.region || '';
            addrEl.hidden = !(item.adresse || item.region);
        }

        var visit = doc.getElementById('mpBtMapSideVisit');
        if (visit) {
            visit.href = item.vitrine_href || '#';
        }
        var maps = doc.getElementById('mpBtMapSideMaps');
        if (maps) {
            maps.href = item.maps_url || '#';
            maps.hidden = !item.maps_url;
        }

        var shareShop = doc.getElementById('mpBtMapShareShop');
        if (shareShop) {
            shareShop.onclick = function () {
                if (win.openPlatformShareModal && item.share_url) {
                    win.openPlatformShareModal({
                        modalTitle: 'Partager cette boutique',
                        title: item.share_title || item.nom,
                        url: item.share_url,
                        message: item.share_text || item.share_title,
                        hint: 'Partagez le lien de la vitrine avec vos proches.'
                    });
                }
            };
        }

        var shareGeo = doc.getElementById('mpBtMapShareGeo');
        if (shareGeo) {
            shareGeo.onclick = function () {
                if (!win.openPlatformShareModal) {
                    return;
                }
                var geoUrl = item.geo_share_url || item.maps_url || '';
                if (!geoUrl) {
                    return;
                }
                win.openPlatformShareModal({
                    modalTitle: 'Partager la localisation',
                    title: item.geo_share_title || ('Point de retrait — ' + item.nom),
                    url: geoUrl,
                    message: item.geo_share_title || item.nom,
                    hint: 'Partagez le point de retrait de la boutique.'
                });
            };
            shareGeo.hidden = !(item.geo_share_url || item.maps_url);
        }

        if (options.pan !== false && map && item.lat && item.lng) {
            map.panTo([item.lat, item.lng], { animate: true });
        }
    }

    function buildList() {
        var listEl = doc.getElementById('mpBtMapListItems');
        var countEl = doc.getElementById('mpBtMapListCount');
        if (!listEl) {
            return;
        }

        listEl.innerHTML = '';
        var sorted = state.boutiques.slice().sort(function (a, b) {
            var da = a.distance_km != null ? Number(a.distance_km) : 9999;
            var db = b.distance_km != null ? Number(b.distance_km) : 9999;
            return da - db;
        });

        sorted.forEach(function (item) {
            var li = doc.createElement('li');
            li.className = 'mp-bt-map-list__item';
            li.setAttribute('data-id', String(item.id));
            li.setAttribute('role', 'listitem');

            var logoHtml = item.logo
                ? '<img src="' + esc(item.logo) + '" alt="" onerror="this.remove()">'
                : '<i class="fas fa-store"></i>';
            var dist = formatDistance(item.distance_km);

            li.innerHTML = ''
                + '<button type="button" class="mp-bt-map-list__btn">'
                + '<span class="mp-bt-map-list__logo">' + logoHtml + '</span>'
                + '<span class="mp-bt-map-list__meta">'
                + '<strong>' + esc(item.nom || 'Boutique') + '</strong>'
                + (dist ? '<span class="mp-bt-map-list__dist">' + esc(dist) + '</span>' : '')
                + '</span>'
                + '<i class="fas fa-chevron-right mp-bt-map-list__arrow" aria-hidden="true"></i>'
                + '</button>';

            li.querySelector('.mp-bt-map-list__btn').addEventListener('click', function () {
                selectBoutique(item);
                if (win.matchMedia('(max-width: 1023px)').matches) {
                    closeListPanel();
                }
            });

            listEl.appendChild(li);
        });

        if (countEl) {
            countEl.textContent = String(sorted.length);
        }
    }

    function initMap() {
        if (!win.L) {
            return;
        }
        var canvas = doc.getElementById('mpBtMapCanvas');
        if (!canvas || !state.boutiques.length) {
            return;
        }

        if (map) {
            map.invalidateSize();
            buildList();
            return;
        }

        var centerLat = state.user ? state.user.lat : state.boutiques[0].lat;
        var centerLng = state.user ? state.user.lng : state.boutiques[0].lng;

        map = L.map(canvas, { zoomControl: true }).setView([centerLat, centerLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        markersLayer = L.layerGroup().addTo(map);
        markerById = {};
        var bounds = [];

        if (state.user && state.user.lat && state.user.lng) {
            userMarker = L.circleMarker([state.user.lat, state.user.lng], {
                radius: 8,
                color: '#3564a6',
                fillColor: '#3564a6',
                fillOpacity: 0.85,
                weight: 2
            }).addTo(map);
            userMarker.bindTooltip('Vous', { permanent: false, direction: 'top' });
            bounds.push([state.user.lat, state.user.lng]);
        }

        state.boutiques.forEach(function (item) {
            if (!item.lat || !item.lng) {
                return;
            }
            var marker = L.marker([item.lat, item.lng], { icon: buildMarkerIcon(item) });
            marker.on('click', function (e) {
                if (e && e.originalEvent) {
                    e.originalEvent.stopPropagation();
                }
                selectBoutique(item);
            });
            marker.addTo(markersLayer);
            markerById[item.id] = marker;
            bounds.push([item.lat, item.lng]);
        });

        map.on('click', function () {
            hideDetailPanel();
        });

        if (bounds.length > 1) {
            map.fitBounds(bounds, { padding: [48, 48], maxZoom: 15 });
        }

        buildList();
        hideDetailPanel();

        if (win.matchMedia('(min-width: 1024px)').matches && listPanel) {
            listPanel.classList.add('is-open');
            if (listToggle) {
                listToggle.setAttribute('aria-expanded', 'true');
            }
        }

        win.setTimeout(function () {
            map.invalidateSize();
        }, 120);
    }

    function bindUi() {
        sidePanel = doc.getElementById('mpBtMapSide');
        listPanel = doc.getElementById('mpBtMapList');
        listToggle = doc.getElementById('mpBtMapListToggle');

        var openBtn = doc.getElementById('mpBtOpenMap');
        if (openBtn) {
            openBtn.addEventListener('click', openModal);
        }

        if (listToggle) {
            listToggle.addEventListener('click', toggleListPanel);
        }

        var listClose = doc.getElementById('mpBtMapListClose');
        if (listClose) {
            listClose.addEventListener('click', closeListPanel);
        }

        if (listPanel) {
            listPanel.addEventListener('click', function (e) {
                e.stopPropagation();
            });
        }

        if (sidePanel) {
            sidePanel.addEventListener('click', function (e) {
                e.stopPropagation();
            });
        }

        doc.querySelectorAll('[data-map-detail-close]').forEach(function (el) {
            el.addEventListener('click', hideDetailPanel);
        });

        doc.querySelectorAll('[data-map-close]').forEach(function (el) {
            el.addEventListener('click', closeModal);
        });

        doc.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                if (sidePanel && sidePanel.classList.contains('is-open')) {
                    hideDetailPanel();
                    return;
                }
                if (listPanel && listPanel.classList.contains('is-open')
                    && win.matchMedia('(max-width: 1023px)').matches) {
                    closeListPanel();
                    return;
                }
                closeModal();
            }
        });
    }

    readPayload();
    win.openBoutiquesMapModal = openModal;

    if (doc.readyState === 'loading') {
        doc.addEventListener('DOMContentLoaded', bindUi);
    } else {
        bindUi();
    }
})(window, document);
