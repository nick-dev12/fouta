/**
 * Modal partage unifiée — produits + boutique vendeur.
 * WebView Flutter : schémas natifs (whatsapp://, tg://…) au lieu de target="_blank" (page blanche).
 */
(function () {
    'use strict';

    var modal = null;
    var titleEl = null;
    var urlEl = null;
    var hintEl = null;
    var btnCopy = null;
    var lastFocus = null;
    var state = { url: '', title: '', message: '', hint: '' };

    function enc(v) {
        return encodeURIComponent(v == null ? '' : String(v));
    }

    function isInNativeApp() {
        return !!(window.__COLOBANES_NATIVE_APP || window.flutter_inappwebview
            || /ColobanesApp/i.test(navigator.userAgent || ''));
    }

    function isMobileUa() {
        return /Android|iPhone|iPad|iPod/i.test(navigator.userAgent || '');
    }

    function preferNativeSchemes() {
        return isInNativeApp() || isMobileUa();
    }

    function channelOpenUrl(channel) {
        var url = state.url;
        var title = state.title;
        var message = state.message || (title + ' : ' + url);
        var native = preferNativeSchemes();

        switch (channel) {
            case 'wa':
                return native
                    ? 'whatsapp://send?text=' + enc(message)
                    : 'https://wa.me/?text=' + enc(message);
            case 'gmail':
                return native
                    ? 'googlegmail://co?subject=' + enc(title) + '&body=' + enc(message)
                    : 'https://mail.google.com/mail/?view=cm&fs=1&su=' + enc(title) + '&body=' + enc(message);
            case 'fb':
                if (native && /Android/i.test(navigator.userAgent || '')) {
                    var fbWeb = 'https://www.facebook.com/sharer/sharer.php?u=' + enc(url);
                    return 'intent://share/#Intent;package=com.facebook.katana;scheme=https;S.browser_fallback_url='
                        + enc(fbWeb) + ';end';
                }
                if (native) {
                    return 'fb://facewebmodal/f?href=' + enc(url);
                }
                return 'https://www.facebook.com/sharer/sharer.php?u=' + enc(url);
            case 'tg':
                return native
                    ? 'tg://msg?text=' + enc(message)
                    : 'https://t.me/share/url?url=' + enc(url) + '&text=' + enc(title);
            default:
                return url;
        }
    }

    function openExternal(href) {
        if (!href) {
            return;
        }
        if (preferNativeSchemes()) {
            window.location.href = href;
            return;
        }
        var w = window.open(href, '_blank', 'noopener,noreferrer');
        if (!w) {
            window.location.href = href;
        }
    }

    function copyText(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve, reject) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
                resolve();
            } catch (e) {
                reject(e);
            }
            document.body.removeChild(ta);
        });
    }

    function flashCopyButton() {
        if (!btnCopy) {
            return;
        }
        var icon = btnCopy.querySelector('i');
        var label = btnCopy.querySelector('.prm-share-modal__url-copy-label');
        var oldIconClass = icon ? icon.className : '';
        var oldLabel = label ? label.textContent : '';
        btnCopy.classList.add('is-copied');
        if (icon) {
            icon.className = 'fas fa-check';
        }
        if (label) {
            label.textContent = 'Copié';
        }
        window.setTimeout(function () {
            btnCopy.classList.remove('is-copied');
            if (icon) {
                icon.className = oldIconClass || 'fas fa-link';
            }
            if (label) {
                label.textContent = oldLabel || 'Copier';
            }
        }, 1800);
    }

    function copyShareUrl() {
        if (!state.url) {
            return;
        }
        copyText(state.url).then(flashCopyButton).catch(function () {
            if (urlEl) {
                var range = document.createRange();
                range.selectNodeContents(urlEl);
                var sel = window.getSelection();
                if (sel) {
                    sel.removeAllRanges();
                    sel.addRange(range);
                }
            }
        });
    }

    function closeModal() {
        if (!modal) {
            return;
        }
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        modal.hidden = true;
        document.body.style.overflow = '';
        if (lastFocus && typeof lastFocus.focus === 'function') {
            lastFocus.focus();
        }
    }

    function openModal(opts) {
        opts = opts || {};
        if (!modal) {
            return;
        }
        state.url = opts.url || '';
        state.title = opts.title || 'Partager';
        state.message = opts.message || (state.title + ' : ' + state.url);
        state.hint = opts.hint || '';

        if (titleEl) {
            titleEl.textContent = opts.modalTitle || state.title || 'Partager';
        }
        if (urlEl) {
            urlEl.textContent = state.url;
        }
        if (hintEl) {
            if (state.hint) {
                hintEl.textContent = state.hint;
                hintEl.hidden = false;
            } else {
                hintEl.textContent = '';
                hintEl.hidden = true;
            }
        }

        lastFocus = document.activeElement;
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        var closeBtn = modal.querySelector('.prm-share-modal__close');
        if (closeBtn) {
            closeBtn.focus();
        }
    }

    function escAttr(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;');
    }

    window.openPlatformShareModal = openModal;
    window.platformShareCopyUrl = copyShareUrl;

    window.buildProductShareHtml = function (opts) {
        opts = opts || {};
        var url = opts.url || '';
        var text = opts.text || '';
        var title = opts.title || '';
        if (!url) {
            return '';
        }
        return '<div class="pshare" data-pshare>'
            + '<button type="button" class="pshare__toggle" aria-label="Partager ' + escAttr(title) + '"'
            + ' data-share-url="' + escAttr(url) + '" data-share-text="' + escAttr(text) + '" data-share-title="' + escAttr(title) + '">'
            + '<i class="fa-solid fa-share-nodes" aria-hidden="true"></i></button></div>';
    };

    function init() {
        modal = document.getElementById('platformShareModal');
        if (!modal) {
            return;
        }
        titleEl = document.getElementById('platformShareModalTitle');
        urlEl = document.getElementById('platformShareModalUrl');
        hintEl = document.getElementById('platformShareModalHint');
        btnCopy = document.getElementById('platformShareModalUrlCopy');

        modal.querySelectorAll('[data-share-close]').forEach(function (el) {
            el.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.classList.contains('is-open')) {
                closeModal();
            }
        });

        if (btnCopy) {
            btnCopy.addEventListener('click', function (e) {
                e.preventDefault();
                copyShareUrl();
            });
        }

        modal.querySelectorAll('[data-share-channel]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var ch = btn.getAttribute('data-share-channel') || '';
                openExternal(channelOpenUrl(ch));
            });
        });

        document.addEventListener('click', function (e) {
            var toggle = e.target.closest('.pshare__toggle');
            if (!toggle) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            var url = toggle.getAttribute('data-share-url') || '';
            var text = toggle.getAttribute('data-share-text') || '';
            var title = toggle.getAttribute('data-share-title') || 'Produit';
            if (!url) {
                return;
            }
            openModal({
                modalTitle: 'Partager ce produit',
                title: title,
                url: url,
                message: text || ('Découvrez « ' + title + ' » sur COLObanes : ' + url),
                hint: 'Partagez ce lien pour que vos contacts voient la fiche produit.'
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
