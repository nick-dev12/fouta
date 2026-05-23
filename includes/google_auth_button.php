<?php
/**
 * Boutons connexion sociale (Google + Apple).
 * Variables optionnelles :
 * - $google_auth_type / $social_auth_type : auto|client|vendor
 * - $google_auth_redirect / $social_auth_redirect : URL relative après connexion
 * - $google_auth_position / $social_auth_position : top|bottom
 */
$social_auth_type = isset($social_auth_type) ? trim((string) $social_auth_type) : (isset($google_auth_type) ? trim((string) $google_auth_type) : 'auto');
if (!in_array($social_auth_type, ['auto', 'client', 'vendor'], true)) {
    $social_auth_type = 'auto';
}
$social_auth_redirect = isset($social_auth_redirect)
    ? (string) $social_auth_redirect
    : (isset($google_auth_redirect) ? (string) $google_auth_redirect : '');
$social_auth_position = (isset($social_auth_position) && $social_auth_position === 'bottom')
    ? 'bottom'
    : ((isset($google_auth_position) && $google_auth_position === 'bottom') ? 'bottom' : 'top');
$position_class = $social_auth_position === 'top' ? ' social-auth--top' : ' social-auth--bottom';
?>
<div class="social-auth<?php echo $position_class; ?>">
    <?php if ($social_auth_position === 'bottom'): ?>
        <div class="social-auth__divider"><span>ou</span></div>
    <?php endif; ?>

    <div class="social-auth__buttons">
        <button type="button"
            class="google-auth-btn"
            data-social-auth-type="<?php echo htmlspecialchars($social_auth_type, ENT_QUOTES, 'UTF-8'); ?>"
            data-social-auth-redirect="<?php echo htmlspecialchars($social_auth_redirect, ENT_QUOTES, 'UTF-8'); ?>">
            <span class="google-auth-btn__icon" aria-hidden="true">G</span>
            <span>Continuer avec Google</span>
        </button>

        <button type="button"
            class="apple-auth-btn"
            data-social-auth-type="<?php echo htmlspecialchars($social_auth_type, ENT_QUOTES, 'UTF-8'); ?>"
            data-social-auth-redirect="<?php echo htmlspecialchars($social_auth_redirect, ENT_QUOTES, 'UTF-8'); ?>">
            <span class="apple-auth-btn__icon" aria-hidden="true"><i class="fab fa-apple"></i></span>
            <span>Continuer avec Apple</span>
        </button>
    </div>

    <p class="social-auth-message" aria-live="polite"></p>

    <?php if ($social_auth_position === 'top'): ?>
        <div class="social-auth__divider"><span>ou</span></div>
    <?php endif; ?>
</div>
