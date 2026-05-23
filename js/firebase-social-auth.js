(function () {
    'use strict';

    var CALLBACK_URL = '/auth-firebase-callback.php';

    function setMessage(button, message, isError) {
        var wrap = button.closest('.social-auth');
        var msg = wrap ? wrap.querySelector('.social-auth-message') : null;
        if (!msg) return;
        msg.textContent = message || '';
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
            setMessage(button, 'Firebase Auth n’est pas chargé. Rechargez la page.', true);
            return;
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

    function signInWithGoogle(button) {
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

    function signInWithApple(button) {
        var provider = new firebase.auth.OAuthProvider('apple.com');
        provider.addScope('email');
        provider.addScope('name');

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
