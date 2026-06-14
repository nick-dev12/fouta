/**
 * Partage produit — Web Share API + menu WhatsApp / réseaux / copie lien.
 */
(function () {
    'use strict';

    function closeAllMenus(except) {
        document.querySelectorAll('[data-pshare]').forEach(function (wrap) {
            if (except && wrap === except) {
                return;
            }
            var btn = wrap.querySelector('.pshare__toggle');
            var menu = wrap.querySelector('.pshare__menu');
            if (btn) {
                btn.setAttribute('aria-expanded', 'false');
            }
            if (menu) {
                menu.hidden = true;
            }
        });
    }

    function openMenu(wrap) {
        var btn = wrap.querySelector('.pshare__toggle');
        var menu = wrap.querySelector('.pshare__menu');
        if (!btn || !menu) {
            return;
        }
        closeAllMenus(wrap);
        menu.hidden = false;
        btn.setAttribute('aria-expanded', 'true');
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

    document.addEventListener('click', function (e) {
        var toggle = e.target.closest('.pshare__toggle');
        if (toggle) {
            e.preventDefault();
            e.stopPropagation();
            var wrap = toggle.closest('[data-pshare]');
            if (!wrap) {
                return;
            }
            var menu = wrap.querySelector('.pshare__menu');
            var isOpen = menu && !menu.hidden;
            if (isOpen) {
                closeAllMenus();
                return;
            }
            var url = toggle.getAttribute('data-share-url') || '';
            var text = toggle.getAttribute('data-share-text') || '';
            var title = toggle.getAttribute('data-share-title') || '';
            if (navigator.share && url) {
                navigator.share({ title: title, text: text, url: url }).catch(function () {
                    openMenu(wrap);
                });
                return;
            }
            openMenu(wrap);
            return;
        }

        var copyBtn = e.target.closest('[data-share-copy]');
        if (copyBtn) {
            e.preventDefault();
            e.stopPropagation();
            var root = copyBtn.closest('[data-pshare]');
            var tgl = root ? root.querySelector('.pshare__toggle') : null;
            var link = tgl ? (tgl.getAttribute('data-share-url') || '') : '';
            if (!link) {
                return;
            }
            copyText(link).then(function () {
                var old = copyBtn.innerHTML;
                copyBtn.classList.add('is-copied');
                copyBtn.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> Lien copié';
                window.setTimeout(function () {
                    copyBtn.classList.remove('is-copied');
                    copyBtn.innerHTML = old;
                }, 1800);
            });
            return;
        }

        if (!e.target.closest('[data-pshare]')) {
            closeAllMenus();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeAllMenus();
        }
    });

    function escAttr(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;');
    }

    window.buildProductShareHtml = function (opts) {
        opts = opts || {};
        var url = opts.url || '';
        var text = opts.text || '';
        var title = opts.title || '';
        if (!url) {
            return '';
        }
        var wa = 'https://wa.me/?text=' + encodeURIComponent(text || url);
        var fb = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url);
        var twText = title || text || '';
        var tw = 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(twText) + '&url=' + encodeURIComponent(url);
        return '<div class="pshare" data-pshare>'
            + '<button type="button" class="pshare__toggle" aria-label="Partager ' + escAttr(title) + '" aria-expanded="false" aria-haspopup="true"'
            + ' data-share-url="' + escAttr(url) + '" data-share-text="' + escAttr(text) + '" data-share-title="' + escAttr(title) + '">'
            + '<i class="fa-solid fa-share-nodes" aria-hidden="true"></i></button>'
            + '<div class="pshare__menu" hidden role="menu" aria-label="Partager ce produit">'
            + '<a class="pshare__item pshare__item--wa" href="' + escAttr(wa) + '" target="_blank" rel="noopener noreferrer" role="menuitem">'
            + '<i class="fa-brands fa-whatsapp" aria-hidden="true"></i> WhatsApp</a>'
            + '<a class="pshare__item" href="' + escAttr(fb) + '" target="_blank" rel="noopener noreferrer" role="menuitem">'
            + '<i class="fa-brands fa-facebook-f" aria-hidden="true"></i> Facebook</a>'
            + '<a class="pshare__item" href="' + escAttr(tw) + '" target="_blank" rel="noopener noreferrer" role="menuitem">'
            + '<i class="fa-brands fa-x-twitter" aria-hidden="true"></i> X</a>'
            + '<button type="button" class="pshare__item pshare__item--copy" data-share-copy role="menuitem">'
            + '<i class="fa-solid fa-link" aria-hidden="true"></i> Copier le lien</button>'
            + '</div></div>';
    };
})();
