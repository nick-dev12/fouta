<?php
/**
 * Partage de la boutique vendeur — modal + script réutilisable
 * Programmation procédurale uniquement
 */

if (!function_exists('vendeur_share_boutique_get_data')) {

function vendeur_share_boutique_get_data()
{
    static $data = null;
    if ($data !== null) {
        return $data;
    }

    $role = function_exists('admin_normalize_role_for_route')
        ? admin_normalize_role_for_route($_SESSION['admin_role'] ?? 'admin')
        : (string) ($_SESSION['admin_role'] ?? '');

    $slug = ($role === 'vendeur') ? trim((string) ($_SESSION['admin_boutique_slug'] ?? '')) : '';
    if ($slug === '') {
        $data = ['available' => false];
        return $data;
    }

    if (!function_exists('get_site_base_url')) {
        require_once __DIR__ . '/../../includes/site_url.php';
    }
    if (!function_exists('boutique_url')) {
        require_once __DIR__ . '/../../includes/marketplace_helpers.php';
    }

    $nom = trim((string) ($_SESSION['admin_boutique_nom'] ?? ''));
    if ($nom === '') {
        $nom = 'Ma boutique';
    }

    $path = boutique_url('index.php', $slug);
    $url = rtrim(get_site_base_url(), '/') . $path;
    $subject = 'Découvrez ma boutique « ' . $nom . ' » sur COLObanes';
    $message = $subject . ' : ' . $url;

    $data = [
        'available' => true,
        'slug' => $slug,
        'nom' => $nom,
        'url' => $url,
        'subject' => $subject,
        'message' => $message,
    ];

    return $data;
}

function vendeur_share_boutique_is_available()
{
    $data = vendeur_share_boutique_get_data();
    return !empty($data['available']);
}

function vendeur_share_boutique_render_modal($modal_id = 'vendeurShareModal')
{
    $data = vendeur_share_boutique_get_data();
    if (empty($data['available'])) {
        return;
    }

    $modal_id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $modal_id);
    if ($modal_id === '') {
        $modal_id = 'vendeurShareModal';
    }

    $suffix = preg_replace('/[^a-zA-Z0-9]/', '', $modal_id);
    $id_wa = 'vShareWa' . $suffix;
    $id_gm = 'vShareGm' . $suffix;
    $id_fb = 'vShareFb' . $suffix;
    $id_tg = 'vShareTg' . $suffix;
    $id_mail = 'vShareMail' . $suffix;
    $id_copy = 'vShareCopy' . $suffix;
    $id_preview = 'vShareUrl' . $suffix;
    $id_title = 'vShareTitle' . $suffix;
    ?>
    <div class="prm-share-modal" id="<?php echo htmlspecialchars($modal_id, ENT_QUOTES, 'UTF-8'); ?>" role="dialog"
        aria-modal="true" aria-labelledby="<?php echo htmlspecialchars($id_title, ENT_QUOTES, 'UTF-8'); ?>"
        aria-hidden="true" hidden>
        <div class="prm-share-modal__backdrop" data-share-close tabindex="-1"></div>
        <div class="prm-share-modal__panel" role="document">
            <div class="prm-share-modal__head">
                <div>
                    <h2 class="prm-share-modal__title" id="<?php echo htmlspecialchars($id_title, ENT_QUOTES, 'UTF-8'); ?>">
                        Partager ma boutique
                    </h2>
                    <p class="prm-share-modal__sub">Choisissez un canal pour envoyer le lien de votre vitrine.</p>
                </div>
                <button type="button" class="prm-share-modal__close" data-share-close aria-label="Fermer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
            <p class="prm-share-modal__url" id="<?php echo htmlspecialchars($id_preview, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($data['url'], ENT_QUOTES, 'UTF-8'); ?>
            </p>
            <div class="prm-share-grid">
                <a href="#" class="prm-share-item prm-share-item--wa" id="<?php echo htmlspecialchars($id_wa, ENT_QUOTES, 'UTF-8'); ?>"
                    target="_blank" rel="noopener noreferrer">
                    <span class="prm-share-item__icon"><i class="fab fa-whatsapp"></i></span>
                    <span>WhatsApp</span>
                </a>
                <a href="#" class="prm-share-item prm-share-item--gmail" id="<?php echo htmlspecialchars($id_gm, ENT_QUOTES, 'UTF-8'); ?>"
                    target="_blank" rel="noopener noreferrer">
                    <span class="prm-share-item__icon"><i class="fab fa-google"></i></span>
                    <span>Gmail</span>
                </a>
                <a href="#" class="prm-share-item prm-share-item--fb" id="<?php echo htmlspecialchars($id_fb, ENT_QUOTES, 'UTF-8'); ?>"
                    target="_blank" rel="noopener noreferrer">
                    <span class="prm-share-item__icon"><i class="fab fa-facebook-f"></i></span>
                    <span>Facebook</span>
                </a>
                <a href="#" class="prm-share-item prm-share-item--tg" id="<?php echo htmlspecialchars($id_tg, ENT_QUOTES, 'UTF-8'); ?>"
                    target="_blank" rel="noopener noreferrer">
                    <span class="prm-share-item__icon"><i class="fab fa-telegram-plane"></i></span>
                    <span>Telegram</span>
                </a>
                <a href="#" class="prm-share-item prm-share-item--mail" id="<?php echo htmlspecialchars($id_mail, ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="prm-share-item__icon"><i class="fas fa-envelope"></i></span>
                    <span>Email</span>
                </a>
                <button type="button" class="prm-share-item prm-share-item--copy"
                    id="<?php echo htmlspecialchars($id_copy, ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="prm-share-item__icon"><i class="fas fa-link"></i></span>
                    <span>Copier le lien</span>
                </button>
            </div>
            <p class="prm-share-modal__hint">Le lien ouvre votre boutique publique sur COLObanes.</p>
        </div>
    </div>
    <?php
}

function vendeur_share_boutique_render_script(array $options = [])
{
    $data = vendeur_share_boutique_get_data();
    if (empty($data['available'])) {
        return;
    }

    $modal_id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($options['modal_id'] ?? 'vendeurShareModal'));
    if ($modal_id === '') {
        $modal_id = 'vendeurShareModal';
    }

    $suffix = preg_replace('/[^a-zA-Z0-9]/', '', $modal_id);
    $open_ids = $options['open_button_ids'] ?? [];
    if (!is_array($open_ids)) {
        $open_ids = [$open_ids];
    }
    $open_ids = array_values(array_filter(array_map('strval', $open_ids)));

    $input_id = (string) ($options['url_input_id'] ?? '');
    $feedback_id = (string) ($options['feedback_id'] ?? '');

    $id_wa = 'vShareWa' . $suffix;
    $id_gm = 'vShareGm' . $suffix;
    $id_fb = 'vShareFb' . $suffix;
    $id_tg = 'vShareTg' . $suffix;
    $id_mail = 'vShareMail' . $suffix;
    $id_copy = 'vShareCopy' . $suffix;

    $url_json = json_encode($data['url'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $title_json = json_encode($data['subject'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $message_json = json_encode($data['message'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $open_ids_json = json_encode($open_ids, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    ?>
    <script>
    (function () {
        var url = <?php echo $url_json; ?>;
        var title = <?php echo $title_json; ?>;
        var message = <?php echo $message_json; ?>;
        var openBtnIds = <?php echo $open_ids_json; ?>;
        var modal = document.getElementById(<?php echo json_encode($modal_id); ?>);
        var linkWa = document.getElementById(<?php echo json_encode($id_wa); ?>);
        var linkGm = document.getElementById(<?php echo json_encode($id_gm); ?>);
        var linkFb = document.getElementById(<?php echo json_encode($id_fb); ?>);
        var linkTg = document.getElementById(<?php echo json_encode($id_tg); ?>);
        var linkMail = document.getElementById(<?php echo json_encode($id_mail); ?>);
        var btnCopyModal = document.getElementById(<?php echo json_encode($id_copy); ?>);
        var input = <?php echo $input_id !== '' ? 'document.getElementById(' . json_encode($input_id) . ')' : 'null'; ?>;
        var feedback = <?php echo $feedback_id !== '' ? 'document.getElementById(' . json_encode($feedback_id) . ')' : 'null'; ?>;
        var lastFocus = null;

        function enc(v) { return encodeURIComponent(v); }

        if (linkWa) linkWa.href = 'https://wa.me/?text=' + enc(message);
        if (linkGm) linkGm.href = 'https://mail.google.com/mail/?view=cm&fs=1&su=' + enc(title) + '&body=' + enc(message);
        if (linkFb) linkFb.href = 'https://www.facebook.com/sharer/sharer.php?u=' + enc(url);
        if (linkTg) linkTg.href = 'https://t.me/share/url?url=' + enc(url) + '&text=' + enc(title);
        if (linkMail) linkMail.href = 'mailto:?subject=' + enc(title) + '&body=' + enc(message);

        function showFeedback(msg) {
            if (!feedback) return;
            feedback.textContent = msg;
            window.setTimeout(function () { if (feedback) feedback.textContent = ''; }, 3500);
        }

        function copyUrl(doneMsg) {
            doneMsg = doneMsg || 'Lien copié dans le presse-papiers.';
            function fallbackCopy() {
                if (input) {
                    input.focus();
                    input.select();
                    input.setSelectionRange(0, input.value.length);
                }
                var ok = false;
                try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
                if (feedback) {
                    showFeedback(ok ? doneMsg : 'Copie impossible — sélectionnez le lien manuellement.');
                }
            }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function () {
                    if (feedback) showFeedback(doneMsg);
                }).catch(fallbackCopy);
                return;
            }
            fallbackCopy();
        }

        function openShareModal() {
            if (!modal) return;
            lastFocus = document.activeElement;
            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
            modal.classList.add('is-open');
            document.body.style.overflow = 'hidden';
            var closeBtn = modal.querySelector('.prm-share-modal__close');
            if (closeBtn) closeBtn.focus();
        }

        function closeShareModal() {
            if (!modal) return;
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            modal.hidden = true;
            document.body.style.overflow = '';
            if (lastFocus && typeof lastFocus.focus === 'function') {
                lastFocus.focus();
            }
        }

        openBtnIds.forEach(function (btnId) {
            var btn = document.getElementById(btnId);
            if (btn) btn.addEventListener('click', openShareModal);
        });

        var externalCopyId = <?php echo json_encode((string) ($options['external_copy_button_id'] ?? '')); ?>;
        if (externalCopyId) {
            var externalCopyBtn = document.getElementById(externalCopyId);
            if (externalCopyBtn) {
                externalCopyBtn.addEventListener('click', function () {
                    copyUrl('Lien copié dans le presse-papiers.');
                });
            }
        }

        if (btnCopyModal) {
            btnCopyModal.addEventListener('click', function () {
                copyUrl('Lien copié — vous pouvez le coller où vous voulez.');
            });
        }

        if (modal) {
            modal.querySelectorAll('[data-share-close]').forEach(function (el) {
                el.addEventListener('click', closeShareModal);
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && modal.classList.contains('is-open')) {
                    closeShareModal();
                }
            });
        }

        window.vendeurShareBoutiqueCopyUrl = copyUrl;
    })();
    </script>
    <?php
}

}
