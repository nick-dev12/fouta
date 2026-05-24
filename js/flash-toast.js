(function () {
    'use strict';

    var ICONS = {
        success: 'fa-circle-check',
        error:   'fa-circle-exclamation',
        info:    'fa-circle-info',
        warning: 'fa-triangle-exclamation'
    };
    var TITLES = {
        success: 'Succès',
        error:   'Erreur',
        info:    'Information',
        warning: 'Attention'
    };

    function escHtml(t) {
        var d = document.createElement('div');
        d.textContent = t == null ? '' : String(t);
        return d.innerHTML;
    }

    function dismiss(el) {
        if (!el || el.classList.contains('is-leaving')) return;
        el.classList.remove('is-visible');
        el.classList.add('is-leaving');
        setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 380);
    }

    function show(item) {
        var host = document.getElementById('flashToastHost');
        if (!host) return;

        var type = (item && ICONS[item.type]) ? item.type : 'info';
        var msg  = item && item.message ? String(item.message) : '';
        if (!msg) return;

        var toast = document.createElement('div');
        toast.className = 'flash-toast flash-toast--' + type;
        toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
        toast.innerHTML =
            '<span class="flash-toast__stripe" aria-hidden="true"></span>' +
            '<div class="flash-toast__inner">' +
                '<span class="flash-toast__icon" aria-hidden="true"><i class="fas ' + ICONS[type] + '"></i></span>' +
                '<div class="flash-toast__body">' +
                    '<p class="flash-toast__title">' + escHtml(TITLES[type]) + '</p>' +
                    '<p class="flash-toast__message">' + escHtml(msg) + '</p>' +
                '</div>' +
            '</div>' +
            '<button type="button" class="flash-toast__close" aria-label="Fermer"><i class="fas fa-xmark"></i></button>' +
            '<span class="flash-toast__progress" aria-hidden="true"></span>';

        toast.querySelector('.flash-toast__close').addEventListener('click', function () { dismiss(toast); });
        host.appendChild(toast);

        requestAnimationFrame(function () {
            requestAnimationFrame(function () { toast.classList.add('is-visible'); });
        });

        setTimeout(function () { dismiss(toast); }, 5400);
    }

    function boot() {
        var list = Array.isArray(window.__FLASH_TOASTS__) ? window.__FLASH_TOASTS__ : [];
        list.forEach(function (item, i) { setTimeout(function () { show(item); }, i * 140); });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    window.FlashToast = {
        show:    function (type, msg) { show({ type: type, message: msg }); },
        success: function (msg)       { show({ type: 'success', message: msg }); },
        error:   function (msg)       { show({ type: 'error',   message: msg }); },
        info:    function (msg)       { show({ type: 'info',    message: msg }); },
        warning: function (msg)       { show({ type: 'warning', message: msg }); }
    };
})();
