<?php
/**
 * Page de profil administrateur - Modification des informations
 * Programmation procédurale uniquement
 */

session_start();

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Récupérer les informations de l'administrateur
require_once __DIR__ . '/../models/model_admin.php';
require_once __DIR__ . '/../includes/senegal_regions.php';
$admin = get_admin_by_id($_SESSION['admin_id']);

if (!$admin) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$is_vendeur = (($admin['role'] ?? '') === 'vendeur');
$profil_nom = (string) ($admin['nom'] ?? '');
$profil_prenom = (string) ($admin['prenom'] ?? '');
$profil_email = (string) ($admin['email'] ?? '');
$profil_telephone = (string) ($admin['telephone'] ?? '');
$profil_display_name = trim($profil_prenom . ' ' . $profil_nom);
if ($profil_display_name === '') {
    $profil_display_name = $profil_nom !== '' ? $profil_nom : 'Mon compte';
}
$profil_boutique_nom = (string) ($admin['boutique_nom'] ?? '');
$profil_boutique_region = (string) ($admin['boutique_region'] ?? '');
$profil_boutique_region_label = $profil_boutique_region !== ''
    ? senegal_region_label($profil_boutique_region)
    : '';

// Traitement des formulaires
require_once __DIR__ . '/../includes/flash_toast.php';
$error_message = '';

if (isset($_SESSION['success_message'])) {
    // success via flash_toast_collect() en footer
    unset($_SESSION['success_message']);
}

// Traitement du formulaire d'informations personnelles
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_profil'])) {
    // Récupération et nettoyage des données
    $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
    $prenom = isset($_POST['prenom']) ? trim($_POST['prenom']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $telephone = isset($_POST['telephone']) ? trim($_POST['telephone']) : '';
    
    $errors = [];
    
    // Validation du nom
    if (empty($nom)) {
        $errors[] = 'Le nom est obligatoire.';
    } elseif (strlen($nom) < 2) {
        $errors[] = 'Le nom doit contenir au moins 2 caractères.';
    }
    
    // Validation du prénom (facultatif pour les vendeurs)
    if (!$is_vendeur) {
        if (empty($prenom)) {
            $errors[] = 'Le prénom est obligatoire.';
        } elseif (strlen($prenom) < 2) {
            $errors[] = 'Le prénom doit contenir au moins 2 caractères.';
        }
    } elseif ($prenom !== '' && strlen($prenom) < 2) {
        $errors[] = 'Le prénom doit contenir au moins 2 caractères.';
    }
    
    // Validation de l'email (facultatif pour les vendeurs)
    if ($email === '') {
        if (!$is_vendeur) {
            $errors[] = 'L\'email est obligatoire.';
        }
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L\'email n\'est pas valide.';
    } else {
        // Vérifier si l'email existe déjà pour un autre administrateur
        $existing_admin = get_admin_by_email($email);
        if ($existing_admin && $existing_admin['id'] != $_SESSION['admin_id']) {
            $errors[] = 'Cet email est déjà utilisé par un autre administrateur.';
        }
    }
    
    // Validation du téléphone (obligatoire)
    if (empty($telephone)) {
        $errors[] = 'Le téléphone est obligatoire.';
    } elseif (!preg_match('/^[0-9+\-\s()]+$/', $telephone)) {
        $errors[] = 'Le format du téléphone n\'est pas valide.';
    }
    
    // Si aucune erreur, procéder à la mise à jour
    if (empty($errors)) {
        // Préparer les données à mettre à jour
        $data = [
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email !== '' ? $email : null,
            'telephone' => $telephone
        ];

        // Mettre à jour les informations de base
        if (update_admin($_SESSION['admin_id'], $data)) {
            if ($email !== '') {
                $_SESSION['admin_email'] = $email;
            } else {
                unset($_SESSION['admin_email']);
            }

            // Redirection pour éviter la double soumission (pattern Post-Redirect-Get)
            $_SESSION['success_message'] = 'Vos informations personnelles ont été mises à jour avec succès !';
            header('Location: profil.php');
            exit;
        } else {
            $error_message = 'Une erreur est survenue lors de la mise à jour. Veuillez réessayer.';
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}

// Traitement du formulaire gestion boutique (vendeurs)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_boutique']) && $is_vendeur) {
    $boutique_nom = isset($_POST['boutique_nom']) ? trim((string) $_POST['boutique_nom']) : '';
    $boutique_region = isset($_POST['boutique_region']) ? trim((string) $_POST['boutique_region']) : '';

    $errors = [];

    if (mb_strlen($boutique_nom) < 2) {
        $errors[] = 'Le nom de la boutique est obligatoire (2 caractères minimum).';
    }

    if (admin_has_boutique_region_column()) {
        if ($boutique_region === '' || !senegal_region_is_valid($boutique_region)) {
            $errors[] = 'Veuillez sélectionner une région valide pour votre boutique.';
        }
    }

    if (empty($errors)) {
        if (update_vendeur_boutique_profil($_SESSION['admin_id'], $boutique_nom, $boutique_region)) {
            $_SESSION['success_message'] = 'Les informations de votre boutique ont été mises à jour avec succès !';
            header('Location: profil.php');
            exit;
        }
        $error_message = 'Impossible d\'enregistrer les informations de la boutique. Réessayez.';
    } else {
        $error_message = implode('<br>', $errors);
        $profil_boutique_nom = $boutique_nom;
        $profil_boutique_region = $boutique_region;
        $profil_boutique_region_label = $boutique_region !== '' ? senegal_region_label($boutique_region) : '';
    }
}

// Traitement du formulaire de changement de mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_mot_de_passe'])) {
    // Récupération et nettoyage des données (protection XSS)
    $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
    
    $errors = [];
    
    // Validation du mot de passe actuel
    if (empty($current_password)) {
        $errors[] = 'Le mot de passe actuel est obligatoire.';
    } else {
        // Vérifier que le mot de passe actuel est correct
        if (!password_verify($current_password, $admin['password'])) {
            $errors[] = 'Le mot de passe actuel est incorrect.';
        }
    }
    
    // Validation du nouveau mot de passe
    if (empty($password)) {
        $errors[] = 'Le nouveau mot de passe est obligatoire.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Le nouveau mot de passe doit contenir au moins 6 caractères.';
    } elseif ($password === $current_password) {
        $errors[] = 'Le nouveau mot de passe doit être différent de l\'ancien.';
    } elseif ($password !== $password_confirm) {
        $errors[] = 'Les nouveaux mots de passe ne correspondent pas.';
    }
    
    // Si aucune erreur, procéder à la mise à jour
    if (empty($errors)) {
        require_once __DIR__ . '/../conn/conn.php';
        global $db;
        
        // Protection contre les injections SQL : utilisation de PDO avec prepared statements
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE admin SET password = :password WHERE id = :id");
        
        if ($stmt->execute([
            'id' => $_SESSION['admin_id'],
            'password' => $password_hash
        ])) {
            // Redirection pour éviter la double soumission (pattern Post-Redirect-Get)
            $_SESSION['success_message'] = 'Votre mot de passe a été modifié avec succès !';
            header('Location: profil.php');
            exit;
        } else {
            $error_message = 'Une erreur est survenue lors de la modification du mot de passe. Veuillez réessayer.';
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}
if (!empty($error_message)) {
    flash_toast_queue_page('error', str_replace('<br>', ' — ', $error_message));
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <?php include __DIR__ . '/../includes/favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Administration COLObanes</title>
    <?php require_once __DIR__ . '/../includes/asset_version.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/admin-dashboard.css<?php echo asset_version_query(); ?>">
    <?php include __DIR__ . '/../includes/auth_intl_tel_head.php'; ?>
    <style>
        /* intl-tel-input styles pour profil admin */
        .input-wrapper--intl-tel {
            display: block;
            position: relative;
        }
        .input-wrapper--intl-tel > .iti {
            width: 100%;
            display: block;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #ddd;
            box-sizing: border-box;
        }
        .input-wrapper--intl-tel .iti__tel-input {
            width: 100% !important;
            padding: 0.75rem 1rem !important;
            padding-left: 3.4rem !important;
            font-size: 1rem !important;
            font-family: inherit !important;
            border: none !important;
            border-radius: 0 !important;
            background: #fff !important;
            color: #333 !important;
            box-shadow: none !important;
        }
        .input-wrapper--intl-tel .iti__selected-country {
            width: auto;
            min-width: 2.6rem;
            max-width: 3rem;
        }
        .input-wrapper--intl-tel .iti__selected-country-primary {
            font-size: 0;
            visibility: hidden;
        }
        .input-wrapper--intl-tel .iti__selected-country-primary .iti__flag {
            visibility: visible;
            margin-right: 0;
        }
        .input-wrapper--intl-tel .iti:focus-within {
            border-color: var(--couleur-dominante, #3564a6);
            box-shadow: 0 0 0 3px rgba(53, 100, 166, 0.15);
        }
        .input-wrapper--intl-tel .iti__country-list {
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
        }
        .form-optional {
            font-weight: 400;
            color: #737373;
            font-size: 0.85rem;
        }
        .profil-form select.profil-select {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            font-family: inherit;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #fff;
            color: #333;
            cursor: pointer;
            box-sizing: border-box;
        }
        .profil-form select.profil-select:focus {
            outline: none;
            border-color: var(--couleur-dominante, #3564a6);
            box-shadow: 0 0 0 3px rgba(53, 100, 166, 0.15);
        }
    </style>
</head>

<body>
    <?php include 'includes/nav.php'; ?>

    <div class="content-header">
        <h1><i class="fas fa-user-shield"></i> Mon Profil</h1>
    </div>

    <section class="content-section">
        <div class="profil-container">
            <!-- En-tête du profil -->
            <div class="profil-header">
                <div class="avatar">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h2><?php echo htmlspecialchars($profil_display_name, ENT_QUOTES, 'UTF-8'); ?></h2>
                <p><?php echo $profil_email !== '' ? htmlspecialchars($profil_email, ENT_QUOTES, 'UTF-8') : 'Email non renseigné'; ?></p>
            </div>

            <!-- Messages via flash toast -->

            <!-- Informations du compte -->
            <div class="info-section">
                <h3><i class="fas fa-info-circle"></i> Informations du compte</h3>
                <div class="info-item">
                    <label>Date de création:</label>
                    <span class="value"><?php echo date('d/m/Y', strtotime($admin['date_creation'])); ?></span>
                </div>
                <?php if ($admin['derniere_connexion']): ?>
                    <div class="info-item">
                        <label>Dernière connexion:</label>
                        <span class="value"><?php echo date('d/m/Y à H:i', strtotime($admin['derniere_connexion'])); ?></span>
                    </div>
                <?php endif; ?>
                <div class="info-item">
                    <label>Statut:</label>
                    <span class="value statut-<?php echo $admin['statut']; ?>">
                        <?php echo ucfirst($admin['statut']); ?>
                    </span>
                </div>
            </div>

            <!-- Section Informations personnelles -->
            <form method="POST" action="" class="profil-form">
                <div class="form-section">
                    <h3><i class="fas fa-user"></i> Informations personnelles</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="nom">Nom <span class="required">*</span></label>
                            <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($profil_nom, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="prenom">Prénom <?php if (!$is_vendeur): ?><span class="required">*</span><?php else: ?><span class="form-optional">(facultatif)</span><?php endif; ?></label>
                            <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($profil_prenom, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $is_vendeur ? '' : ' required'; ?>>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email <?php if (!$is_vendeur): ?><span class="required">*</span><?php else: ?><span class="form-optional">(facultatif)</span><?php endif; ?></label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($profil_email, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $is_vendeur ? '' : ' required'; ?>>
                    </div>

                    <div class="form-group">
                        <label for="telephone">Téléphone <span class="required">*</span></label>
                        <div class="input-wrapper input-wrapper--intl-tel">
                            <input type="tel" id="telephone" name="telephone"
                                value="<?php echo htmlspecialchars($profil_telephone, ENT_QUOTES, 'UTF-8'); ?>"
                                placeholder="77 123 45 67"
                                required autocomplete="tel">
                        </div>
                        <small style="color: #666; font-size: 0.85rem;">Indicatif pays au choix (défaut Sénégal)</small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="modifier_profil" class="btn-submit">
                            <i class="fas fa-save"></i> Enregistrer les modifications
                        </button>
                        <a href="dashboard.php" class="btn-cancel">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                    </div>
                </div>
            </form>

            <?php if ($is_vendeur): ?>
            <!-- Section Gestion de la boutique -->
            <form method="POST" action="" class="profil-form">
                <div class="form-section">
                    <h3><i class="fas fa-store"></i> Gestion de la boutique</h3>

                    <div class="form-group">
                        <label for="boutique_nom">Nom de la boutique <span class="required">*</span></label>
                        <input type="text" id="boutique_nom" name="boutique_nom"
                            value="<?php echo htmlspecialchars($profil_boutique_nom, ENT_QUOTES, 'UTF-8'); ?>"
                            required maxlength="255" autocomplete="organization">
                    </div>

                    <?php if (admin_has_boutique_region_column()): ?>
                    <div class="form-group">
                        <label for="boutique_region">Région de la boutique <span class="required">*</span></label>
                        <select id="boutique_region" name="boutique_region" class="profil-select" required>
                            <?php echo senegal_regions_options_html($profil_boutique_region, true, 'Sélectionnez une région'); ?>
                        </select>
                        <?php if ($profil_boutique_region_label !== ''): ?>
                        <small style="color: #666; font-size: 0.85rem; display: block; margin-top: 0.35rem;">
                            Région actuelle : <?php echo htmlspecialchars($profil_boutique_region_label, ENT_QUOTES, 'UTF-8'); ?>
                        </small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="form-actions">
                        <button type="submit" name="modifier_boutique" class="btn-submit">
                            <i class="fas fa-save"></i> Enregistrer la boutique
                        </button>
                        <a href="parametres-boutique-vendeur.php" class="btn-cancel">
                            <i class="fas fa-palette"></i> Paramètres vitrine
                        </a>
                    </div>
                </div>
            </form>
            <?php endif; ?>

            <!-- Section Sécurité -->
            <form method="POST" action="" class="profil-form">
                <div class="form-section security-section">
                    <h3><i class="fas fa-lock"></i> Sécurité</h3>

                    <div class="form-group">
                        <label for="current_password">Mot de passe actuel <span class="required">*</span></label>
                        <input type="password" id="current_password" name="current_password" placeholder="Entrez votre mot de passe actuel" required autocomplete="current-password">
                        <div class="help-text">Vous devez confirmer votre mot de passe actuel pour le modifier</div>
                    </div>

                    <div class="form-group">
                        <label for="password">Nouveau mot de passe <span class="required">*</span></label>
                        <input type="password" id="password" name="password" placeholder="Entrez votre nouveau mot de passe" required autocomplete="new-password">
                        <div class="help-text">Minimum 6 caractères</div>
                    </div>

                    <div class="form-group">
                        <label for="password_confirm">Confirmer le nouveau mot de passe <span class="required">*</span></label>
                        <input type="password" id="password_confirm" name="password_confirm" placeholder="Confirmez votre nouveau mot de passe" required autocomplete="new-password">
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="modifier_mot_de_passe" class="btn-submit">
                            <i class="fas fa-key"></i> Changer le mot de passe
                        </button>
                        <a href="dashboard.php" class="btn-cancel">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <?php include __DIR__ . '/../includes/auth_intl_tel_scripts.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.initAuthIntlTel === 'function') {
                window.initAuthIntlTel('telephone');
            }
        });
    </script>
</body>
</html>