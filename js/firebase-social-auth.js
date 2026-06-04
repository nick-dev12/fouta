(function () {
    'use strict';

    var CALLBACK_URL = '/auth-firebase-callback.php';
    var APPLE_PENDING_KEY = 'colobanes_apple_auth_pending';
    var APPLE_FLAG_KEY = 'colobanes_apple_redirect_in_progress';

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

    function isIosWebBrowser() {
        return /iPhone|iPad|iPod/i.test(navigator.userAgent || '') && !isColobanesNativeApp();
    }

    function appleRedirectWasStarted() {
        try {
            if (localStorage.getItem(APPLE_FLAG_KEY) === '1') {
                return true;
            }
        } catch (e) {
            // ignore
        }
        return !!readAppleRedirectPending();
    }

    function storeAppleRedirectPending(button) {
        var accountType = button.getAttribute('data-social-auth-type') || button.getAttribute('data-google-auth-type') || 'auto';
        var redirect = button.getAttribute('data-social-auth-redirect') || button.getAttribute('data-google-auth-redirect') || '';
        var payload = JSON.stringify({
            accountType: accountType,
            redirect: redirect
        });
        try {
            sessionStorage.setItem(APPLE_PENDING_KEY, payload);
            localStorage.setItem(APPLE_PENDING_KEY, payload);
            localStorage.setItem(APPLE_FLAG_KEY, '1');
            return true;
        } catch (e) {
            return false;
        }
    }

    function readAppleRedirectPending() {
        try {
            var raw = sessionStorage.getItem(APPLE_PENDING_KEY) || localStorage.getItem(APPLE_PENDING_KEY);
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
            sessionStorage.removeItem(APPLE_PENDING_KEY);
            localStorage.removeItem(APPLE_PENDING_KEY);
            localStorage.removeItem(APPLE_FLAG_KEY);
        } catch (e) {
            // ignore
        }
    }

    function userIsAppleProvider(user) {
        if (!user || !user.providerData || !user.providerData.length) {
            return false;
        }
        for (var i = 0; i < user.providerData.length; i++) {
            if (user.providerData[i].providerId === 'apple.com') {
                return true;
            }
        }
        return false;
    }

    /**
     * Safari iOS : getRedirectResult() peut être vide alors que currentUser est déjà connecté (Face ID).
     */
    function resolveAppleFirebaseUser() {
        return firebase.auth().getRedirectResult().then(function (result) {
            if (result && result.user && userIsAppleProvider(result.user)) {
                return result.user;
            }
            var current = firebase.auth().currentUser;
            if (current && userIsAppleProvider(current)) {
                return current;
            }
            return new Promise(function (resolve) {
                var settled = false;
                var unsub = firebase.auth().onAuthStateChanged(function (user) {
                    if (settled) {
                        return;
                    }
                    if (user && userIsAppleProvider(user)) {
                        settled = true;
                        unsub();
                        resolve(user);
                    }
                });
                setTimeout(function () {
                    if (settled) {
                        return;
                    }
                    settled = true;
                    unsub();
                    var late = firebase.auth().currentUser;
                    resolve(late && userIsAppleProvider(late) ? late : null);
                }, isIosWebBrowser() ? 2500 : 1200);
            });
        });
    }

    function showAppleCompletionUi(isError, message) {
        var wrap = document.querySelector('.social-auth');
        if (!wrap) {
            return;
        }
        var btn = wrap.querySelector('.apple-auth-btn');
        if (!btn) {
            return;
        }
        if (isError) {
            setMessage(btn, message, true);
            disableSocialButtons(wrap, false);
        } else {
            disableSocialButtons(wrap, true);
            setMessage(btn, message || 'Finalisation de la connexion Apple…', false);
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
     * Après signInWithRedirect Apple, finalise la connexion au retour sur la page (Safari / Face ID).
     */
    var appleCompletionInFlight = false;

    function completeAppleRedirectIfNeeded() {
        if (!appleRedirectWasStarted() || appleCompletionInFlight) {
            return Promise.resolve();
        }
        if (typeof firebase === 'undefined' || !firebase.auth) {
            return Promise.resolve();
        }

        appleCompletionInFlight = true;
        showAppleCompletionUi(false, 'Finalisation de la connexion Apple…');

        var pending = readAppleRedirectPending() || { accountType: 'auto', redirect: '' };

        return resolveAppleFirebaseUser().then(function (user) {
            if (!user) {
                throw new Error(
                    'Connexion Apple non finalisée. Réessayez ou connectez-vous avec votre email et mot de passe.'
                );
            }
            return user.getIdToken(true).then(function (idToken) {
                clearAppleRedirectPending();
                return postAppleTokenToServer(pending, idToken);
            });
        }).catch(function (error) {
            clearAppleRedirectPending();
            showAppleCompletionUi(
                true,
                error && error.message ? error.message : 'Connexion Apple impossible.'
            );
        }).finally(function () {
            appleCompletionInFlight = false;
        });
    }

    function scheduleAppleRedirectCompletion(attempt) {
        if (!appleRedirectWasStarted()) {
            return;
        }
        attempt = attempt || 0;
        if (typeof firebase === 'undefined' || !firebase.auth) {
            if (attempt < 40) {
                setTimeout(function () {
                    scheduleAppleRedirectCompletion(attempt + 1);
                }, 100);
            }
            return;
        }
        completeAppleRedirectIfNeeded();
    }

    window.colobanesCompleteAppleRedirect = completeAppleRedirectIfNeeded;
    window.colobanesScheduleAppleRedirect = scheduleAppleRedirectCompletion;

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

    document.addEventListener('DOMContentLoaded', function () {
        scheduleAppleRedirectCompletion(0);
    });
})();
