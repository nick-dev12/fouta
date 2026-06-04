(function () {
    'use strict';

    var CALLBACK_URL = '/auth-firebase-callback.php';

    function isColobanesNativeApp() {
        if (window.__COLOBANES_NATIVE_APP) return true;
        if (window.flutter_inappwebview) return true;
        if (window.ColobanesNative && window.ColobanesNative.isNativeApp) return true;
        return /ColobanesApp/i.test(navigator.userAgent || '');
    }

    function hasNativeGoogleSignIn() {
        return !!(window.ColobanesNative && typeof window.ColobanesNative.signInWithGoogle === 'function')
            || !!window.flutter_inappwebview;
    }

    function hasNativeAppleSignIn() {
        return !!(window.ColobanesNative && typeof window.ColobanesNative.signInWithApple === 'function')
            || !!window.flutter_inappwebview;
    }

    function ensureSocialAuthMessage(wrap) {
        var msg = wrap.querySelector('.social-auth-message');
        if (msg) return msg;
        msg = document.createElement('p');
        msg.className = 'social-auth-message';
        msg.setAttribute('aria-live', 'polite');
        var buttons = wrap.querySelector('.social-auth__buttons');
        if (buttons) {
            buttons.insertAdjacentElement('afterend', msg);
        } else {
            wrap.appendChild(msg);
        }
        return msg;
    }

    function setMessage(button, message, isError) {
        var wrap = button.closest('.social-auth');
        if (!wrap) return;
        var msg = wrap.querySelector('.social-auth-message');
        if (!message) {
            if (msg) msg.remove();
            return;
        }
        if (!msg) {
            msg = ensureSocialAuthMessage(wrap);
        }
        msg.textContent = message;
        msg.classList.toggle('is-error', !!isError);
    }

    function disableSocialButtons(wrap, disabled) {
        if (!wrap) return;
        wrap.querySelectorAll('.google-auth-btn, .apple-auth-btn').forEach(function (btn) {
            btn.disabled = disabled;
        });
    }

    function sendTokenToServer(button, provider, getTokenPromise) {
        if (typeof firebase === 'undefined' || !firebase.auth) {
            if ((provider === 'google' && hasNativeGoogleSignIn())
                || (provider === 'apple' && hasNativeAppleSignIn())) {
                // Firebase web optionnel si le token vient de l'app native
            } else {
                setMessage(button, 'Firebase Auth n’est pas chargé. Rechargez la page.', true);
                return;
            }
        }

        var accountType = button.getAttribute('data-social-auth-type') || button.getAttribute('data-google-auth-type') || 'auto';
        var redirect = button.getAttribute('data-social-auth-redirect') || button.getAttribute('data-google-auth-redirect') || '';
        var wrap = button.closest('.social-auth');
        var originalHtml = button.innerHTML;
        var loadingLabel = provider === 'apple' ? 'Connexion Apple...' : 'Connexion Google...';

        disableSocialButtons(wrap, true);
        button.innerHTML = button.innerHTML.replace(/Continuer avec (Google|Apple)/, loadingLabel);
        setMessage(button, '', false);

        getTokenPromise()
            .then(function (idToken) {
                return fetch(CALLBACK_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        idToken: idToken,
                        accountType: accountType,
                        redirect: redirect,
                        provider: provider
                    })
                });
            })
            .then(function (response) {
                return response.json().catch(function () {
                    throw new Error('Réponse serveur invalide.');
                });
            })
            .then(function (data) {
                if (!data || !data.success || !data.redirect) {
                    throw new Error((data && data.message) ? data.message : 'Connexion refusée.');
                }
                window.location.href = data.redirect;
            })
            .catch(function (error) {
                setMessage(button, error && error.message ? error.message : 'Connexion annulée ou impossible.', true);
                disableSocialButtons(wrap, false);
                button.innerHTML = originalHtml;
            });
    }

    function getGoogleIdTokenNative() {
        if (window.ColobanesNative && typeof window.ColobanesNative.signInWithGoogle === 'function') {
            return window.ColobanesNative.signInWithGoogle().then(function (result) {
                if (!result || !result.idToken) {
                    throw new Error('Token Google introuvable depuis l’application.');
                }
                return result.idToken;
            });
        }
        if (window.flutter_inappwebview) {
            return window.flutter_inappwebview.callHandler('signInWithGoogle').then(function (result) {
                if (!result || !result.success || !result.idToken) {
                    throw new Error((result && result.error) ? result.error : 'Connexion Google impossible.');
                }
                return result.idToken;
            });
        }
        throw new Error('Connexion Google native indisponible.');
    }

    function getAppleIdTokenNative() {
        if (window.ColobanesNative && typeof window.ColobanesNative.signInWithApple === 'function') {
            return window.ColobanesNative.signInWithApple().then(function (result) {
                if (!result || !result.idToken) {
                    throw new Error('Token Apple introuvable depuis l’application.');
                }
                return result.idToken;
            });
        }
        if (window.flutter_inappwebview) {
            return window.flutter_inappwebview.callHandler('signInWithApple').then(function (result) {
                if (!result || !result.success || !result.idToken) {
                    throw new Error((result && result.error) ? result.error : 'Connexion Apple impossible.');
                }
                return result.idToken;
            });
        }
        throw new Error('Connexion Apple native indisponible.');
    }

    function signInWithGoogle(button) {
        if (isColobanesNativeApp()) {
            if (!hasNativeGoogleSignIn()) {
                setMessage(
                    button,
                    'Mettez à jour l’application COLObanes pour vous connecter avec Google.',
                    true
                );
                return;
            }

            sendTokenToServer(button, 'google', getGoogleIdTokenNative);
            return;
        }

        if (/ColobanesApp/i.test(navigator.userAgent || '') || window.flutter_inappwebview) {
            setMessage(button, 'Connexion Google indisponible dans l’application. Rechargez la page.', true);
            return;
        }

        var provider = new firebase.auth.GoogleAuthProvider();
        provider.setCustomParameters({ prompt: 'select_account' });

        sendTokenToServer(button, 'google', function () {
            return firebase.auth().signInWithPopup(provider).then(function (result) {
                if (!result.user) {
                    throw new Error('Compte Google introuvable.');
                }
                return result.user.getIdToken();
            });
        });
    }

    function storeAppleRedirectPending(button) {
        var accountType = button.getAttribute('data-social-auth-type') || button.getAttribute('data-google-auth-type') || 'auto';
        var redirect = button.getAttribute('data-social-auth-redirect') || button.getAttribute('data-google-auth-redirect') || '';
        try {
            sessionStorage.setItem('colobanes_apple_auth_pending', JSON.stringify({
                accountType: accountType,
                redirect: redirect
            }));
        } catch (e) {
            // sessionStorage indisponible (mode privé strict) : popup en secours
            return false;
        }
        return true;
    }

    function readAppleRedirectPending() {
        try {
            var raw = sessionStorage.getItem('colobanes_apple_auth_pending');
            if (!raw) {
                return null;
            }
            return JSON.parse(raw);
        } catch (e) {
            return null;
        }
    }

    function clearAppleRedirectPending() {
        try {
            sessionStorage.removeItem('colobanes_apple_auth_pending');
        } catch (e) {
            // ignore
        }
    }

    function postAppleTokenToServer(pending, idToken) {
        return fetch(CALLBACK_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                idToken: idToken,
                accountType: pending.accountType || 'auto',
                redirect: pending.redirect || '',
                provider: 'apple'
            })
        }).then(function (response) {
            return response.json().catch(function () {
                throw new Error('Réponse serveur invalide.');
            });
        }).then(function (data) {
            if (!data || !data.success || !data.redirect) {
                throw new Error((data && data.message) ? data.message : 'Connexion refusée.');
            }
            window.location.href = data.redirect;
        });
    }

    /**
     * Après signInWithRedirect Apple, finalise la connexion au retour sur la page.
     */
    function completeAppleRedirectIfNeeded() {
        if (typeof firebase === 'undefined' || !firebase.auth) {
            return Promise.resolve();
        }

        var pending = readAppleRedirectPending();
        if (!pending) {
            return Promise.resolve();
        }

        var wrap = document.querySelector('.social-auth');
        if (wrap) {
            disableSocialButtons(wrap, true);
            var firstBtn = wrap.querySelector('.apple-auth-btn');
            if (firstBtn) {
                setMessage(firstBtn, 'Finalisation de la connexion Apple…', false);
            }
        }

        return firebase.auth().getRedirectResult().then(function (result) {
            if (!result || !result.user) {
                clearAppleRedirectPending();
                if (wrap) {
                    disableSocialButtons(wrap, false);
                }
                return;
            }
            return result.user.getIdToken().then(function (idToken) {
                clearAppleRedirectPending();
                return postAppleTokenToServer(pending, idToken);
            });
        }).catch(function (error) {
            clearAppleRedirectPending();
            if (wrap) {
                disableSocialButtons(wrap, false);
                var btn = wrap.querySelector('.apple-auth-btn');
                if (btn) {
                    setMessage(btn, error && error.message ? error.message : 'Connexion Apple impossible.', true);
                }
            }
        });
    }

    window.colobanesCompleteAppleRedirect = completeAppleRedirectIfNeeded;

    function signInWithApple(button) {
        if (isColobanesNativeApp()) {
            if (!hasNativeAppleSignIn()) {
                setMessage(
                    button,
                    'Mettez à jour l’application COLObanes pour vous connecter avec Apple.',
                    true
                );
                return;
            }

            sendTokenToServer(button, 'apple', getAppleIdTokenNative);
            return;
        }

        if (typeof firebase === 'undefined' || !firebase.auth) {
            setMessage(button, 'Firebase Auth n’est pas chargé. Rechargez la page.', true);
            return;
        }

        var provider = new firebase.auth.OAuthProvider('apple.com');
        provider.addScope('email');
        provider.addScope('name');

        // Redirect : plus fiable et souvent plus rapide que la popup Apple (surtout Safari / mobile).
        if (storeAppleRedirectPending(button)) {
            var wrap = button.closest('.social-auth');
            disableSocialButtons(wrap, true);
            setMessage(button, 'Redirection vers Apple…', false);
            firebase.auth().signInWithRedirect(provider);
            return;
        }

        sendTokenToServer(button, 'apple', function () {
            return firebase.auth().signInWithPopup(provider).then(function (result) {
                if (!result.user) {
                    throw new Error('Compte Apple introuvable.');
                }
                return result.user.getIdToken();
            });
        });
    }

    document.addEventListener('click', function (event) {
        var googleBtn = event.target.closest('.google-auth-btn');
        if (googleBtn) {
            signInWithGoogle(googleBtn);
            return;
        }

        var appleBtn = event.target.closest('.apple-auth-btn');
        if (appleBtn) {
            signInWithApple(appleBtn);
        }
    });
})();
