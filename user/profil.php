<?php
/**
 * Page de profil utilisateur - Modification des informations
 * Programmation procédurale uniquement
 */

require_once __DIR__ . '/../includes/session_user.php';
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header('Location: connexion.php');
    exit;
}

// Récupérer les informations de l'utilisateur
require_once __DIR__ . '/../models/model_users.php';
$user = get_user_by_id($_SESSION['user_id']);

if (!$user) {
    session_destroy();
    header('Location: connexion.php');
    exit;
}

$avatar_initial = '';
$prenom_trim = trim((string) ($user['prenom'] ?? ''));
if ($prenom_trim !== '') {
    $avatar_initial = mb_strtoupper(mb_substr($prenom_trim, 0, 1, 'UTF-8'), 'UTF-8');
} else {
    $avatar_initial = '?';
}

// Traitement des formulaires
$success_message = '';
$error_message = '';

// Récupérer le message de succès de la session (après redirection)
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
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

    // Validation du prénom
    if (empty($prenom)) {
        $errors[] = 'Le prénom est obligatoire.';
    } elseif (strlen($prenom) < 2) {
        $errors[] = 'Le prénom doit contenir au moins 2 caractères.';
    }

    // Validation de l'email
    if (empty($email)) {
        $errors[] = 'L\'email est obligatoire.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L\'email n\'est pas valide.';
    } else {
        // Vérifier si l'email existe déjà pour un autre utilisateur
        $existing_user = get_user_by_email($email);
        if ($existing_user && $existing_user['id'] != $_SESSION['user_id']) {
            $errors[] = 'Cet email est déjà utilisé par un autre compte.';
        }
    }

    // Validation du téléphone
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
            'email' => $email,
            'telephone' => $telephone
        ];

        // Mettre à jour les informations de base
        if (update_user($_SESSION['user_id'], $data)) {
            // Mettre à jour l'email dans la session
            $_SESSION['user_email'] = $email;

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
        if (!password_verify($current_password, $user['password'])) {
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
        $stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :id");

        if (
            $stmt->execute([
                'id' => $_SESSION['user_id'],
                'password' => $password_hash
            ])
        ) {
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

$compte_actif = ($user['statut'] ?? '') === 'actif';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once __DIR__ . '/../includes/asset_version.php'; ?>
    <?php include __DIR__ . '/../includes/pwa_meta.php'; ?>
    <title>Mon profil — COLObanes</title>
    <link rel="stylesheet" href="/css/variables.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/user-dashboard.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/user-mes-commandes.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/user-profil.css<?php echo asset_version_query(); ?>">
</head>

<body class="user-page-profil">
    <?php include 'includes/user_nav.php'; ?>

    <div class="mc-orders">
        <header class="mc-orders-hero">
            <div class="mc-orders-hero-text">
                <p class="mc-eyebrow">Paramètres du compte</p>
                <h1>
                    <span class="mc-hero-icon" aria-hidden="true"><i class="fas fa-id-card"></i></span>
                    Mon profil
                </h1>
                <p class="mc-orders-lead">Mettez à jour vos coordonnées et sécurisez votre mot de passe. Les
                    modifications sont appliquées immédiatement.</p>
            </div>
            <div class="mc-orders-stats">
                <?php if ($compte_actif): ?>
                <div class="mc-stat-pill">
                    <i class="fas fa-circle-check" aria-hidden="true"></i>
                    <div>
                        <strong>Actif</strong>
                        <span>Compte</span>
                    </div>
                </div>
                <?php else: ?>
                <div class="mc-stat-pill mc-stat-pill--inactive">
                    <i class="fas fa-circle-pause" aria-hidden="true"></i>
                    <div>
                        <strong>Inactif</strong>
                        <span>Compte</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </header>

        <section class="mc-continue-banner" aria-label="Navigation rapide">
            <div class="mc-continue-inner">
                <div class="mc-continue-icon" aria-hidden="true">
                    <i class="fas fa-circle-user"></i>
                </div>
                <div class="mc-continue-text">
                    <strong>Espace client</strong>
                    <p>Retour au tableau de bord ou suivez vos commandes.</p>
                </div>
                <div class="mc-continue-actions">
                    <a href="mon-compte.php" class="mc-btn mc-btn--primary">
                        <i class="fas fa-house-chimney" aria-hidden="true"></i>
                        Tableau de bord
                    </a>
                    <a href="mes-commandes.php" class="mc-btn mc-btn--secondary">
                        <i class="fas fa-shopping-bag" aria-hidden="true"></i>
                        Mes commandes
                    </a>
                </div>
            </div>
        </section>

        <section class="content-section mc-orders-section">
            <div class="profil-container">
                <div class="profil-header">
                    <div class="avatar" aria-hidden="true">
                        <?php if ($avatar_initial !== '?'): ?>
                            <?php echo htmlspecialchars($avatar_initial); ?>
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h2><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h2>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>

                <?php if ($success_message): ?>
                <div class="mc-alert mc-alert--success" role="status">
                    <i class="fas fa-circle-check" aria-hidden="true"></i>
                    <span><?php echo htmlspecialchars($success_message); ?></span>
                </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                <div class="mc-alert mc-alert--error" role="alert">
                    <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
                    <span><?php echo $error_message; ?></span>
                </div>
                <?php endif; ?>

                <div class="info-section">
                    <h3><i class="fas fa-circle-info" aria-hidden="true"></i> Informations du compte</h3>
                    <div class="info-item">
                        <label>Date d’inscription</label>
                        <span class="value"><?php echo date('d/m/Y', strtotime($user['date_creation'])); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Statut</label>
                        <span class="value <?php echo $compte_actif ? 'profil-statut--actif' : 'profil-statut--inactif'; ?>">
                            <?php echo htmlspecialchars(ucfirst($user['statut'] ?? '')); ?>
                        </span>
                    </div>
                </div>

                <form method="post" action="" class="profil-form">
                    <div class="form-section">
                        <h3><i class="fas fa-user-pen" aria-hidden="true"></i> Informations personnelles</h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="nom">Nom <span class="required" aria-hidden="true">*</span></label>
                                <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($user['nom']); ?>"
                                    required autocomplete="family-name">
                            </div>

                            <div class="form-group">
                                <label for="prenom">Prénom <span class="required" aria-hidden="true">*</span></label>
                                <input type="text" id="prenom" name="prenom"
                                    value="<?php echo htmlspecialchars($user['prenom']); ?>" required
                                    autocomplete="given-name">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">E-mail <span class="required" aria-hidden="true">*</span></label>
                            <input type="email" id="email" name="email"
                                value="<?php echo htmlspecialchars($user['email']); ?>" required autocomplete="email">
                        </div>

                        <div class="form-group">
                            <label for="telephone">Téléphone <span class="required" aria-hidden="true">*</span></label>
                            <input type="tel" id="telephone" name="telephone"
                                value="<?php echo htmlspecialchars($user['telephone']); ?>" required
                                autocomplete="tel">
                            <div class="help-text">Ex. : +225 XX XX XX XX XX ou 0X XX XX XX XX</div>
                        </div>

                        <div class="profil-form-actions">
                            <button type="submit" name="modifier_profil" class="btn-submit">
                                <i class="fas fa-floppy-disk" aria-hidden="true"></i> Enregistrer
                            </button>
                            <a href="mon-compte.php" class="btn-cancel">
                                <i class="fas fa-xmark" aria-hidden="true"></i> Annuler
                            </a>
                        </div>
                    </div>
                </form>

                <form method="post" action="" class="profil-form">
                    <div class="form-section security-section">
                        <h3><i class="fas fa-shield-halved" aria-hidden="true"></i> Sécurité</h3>

                        <div class="form-group">
                            <label for="current_password">Mot de passe actuel <span class="required" aria-hidden="true">*</span></label>
                            <input type="password" id="current_password" name="current_password"
                                placeholder="Mot de passe actuel" required autocomplete="current-password">
                            <div class="help-text">Saisie obligatoire pour modifier votre mot de passe.</div>
                        </div>

                        <div class="form-group">
                            <label for="password">Nouveau mot de passe <span class="required" aria-hidden="true">*</span></label>
                            <input type="password" id="password" name="password"
                                placeholder="Nouveau mot de passe" required autocomplete="new-password">
                            <div class="help-text">Au moins 6 caractères.</div>
                        </div>

                        <div class="form-group">
                            <label for="password_confirm">Confirmer le mot de passe <span class="required" aria-hidden="true">*</span></label>
                            <input type="password" id="password_confirm" name="password_confirm"
                                placeholder="Confirmez le mot de passe" required autocomplete="new-password">
                        </div>

                        <div class="profil-form-actions">
                            <button type="submit" name="modifier_mot_de_passe" class="btn-submit">
                                <i class="fas fa-key" aria-hidden="true"></i> Changer le mot de passe
                            </button>
                            <a href="mon-compte.php" class="btn-cancel">
                                <i class="fas fa-xmark" aria-hidden="true"></i> Annuler
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <?php include 'includes/user_footer.php'; ?>
