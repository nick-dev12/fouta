<?php
/**
 * Génère firebase-messaging-sw.js depuis config/firebase_config.php
 * Usage : php scripts/sync_firebase_sw.php
 */
$config_path = __DIR__ . '/../config/firebase_config.php';
$output_path = __DIR__ . '/../firebase-messaging-sw.js';

if (!file_exists($config_path)) {
    fwrite(STDERR, "config/firebase_config.php introuvable\n");
    exit(1);
}

$cfg = require $config_path;

$js = <<<'JS'
/**
 * Service Worker Firebase Cloud Messaging
 * Généré depuis config/firebase_config.php — ne pas éditer à la main.
 * Regénérer : php scripts/sync_firebase_sw.php
 */
self.addEventListener('install', function () {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim());
});

importScripts('https://www.gstatic.com/firebasejs/12.9.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/12.9.0/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: '%s',
    authDomain: '%s',
    projectId: '%s',
    storageBucket: '%s',
    messagingSenderId: '%s',
    appId: '%s'
});

var messaging = firebase.messaging();

self.addEventListener('message', function (event) {
    if (!event.data || event.data.type !== 'FCM_PING') {
        return;
    }
    var port = event.ports && event.ports[0];
    if (port) {
        port.postMessage({
            type: 'FCM_PONG',
            ready: typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length > 0
        });
    }
});

messaging.onBackgroundMessage(function (payload) {
    var title = (payload.notification && payload.notification.title)
        || (payload.data && payload.data.title)
        || 'COLObanes';
    var body = (payload.notification && payload.notification.body)
        || (payload.data && payload.data.body)
        || '';
    return self.registration.showNotification(title, {
        body: body,
        icon: '/image/produit1.jpg',
        badge: '/image/produit1.jpg',
        tag: (payload.data && payload.data.tag) ? payload.data.tag : 'colobanes-' + Date.now(),
        data: payload.data || {}
    });
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    var url = (event.notification.data && event.notification.data.link)
        || (event.notification.data && event.notification.data.url)
        || '/user/mes-commandes.php';
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (list) {
            for (var i = 0; i < list.length; i++) {
                var client = list[i];
                if (client.url.indexOf(self.location.origin) === 0 && 'focus' in client) {
                    if ('navigate' in client) {
                        return client.navigate(url).then(function () { return client.focus(); });
                    }
                    client.focus();
                    return;
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});

JS;

$js = sprintf(
    $js,
    addslashes($cfg['apiKey']),
    addslashes($cfg['authDomain']),
    addslashes($cfg['projectId']),
    addslashes($cfg['storageBucket']),
    addslashes($cfg['messagingSenderId']),
    addslashes($cfg['appId'])
);

file_put_contents($output_path, $js);
echo "firebase-messaging-sw.js synchronisé (projet: {$cfg['projectId']})\n";
