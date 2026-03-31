<?php
/**
 * Comptes d'accès internes (administration) — réservé au rôle administrateur
 */
session_start();

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_email'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../includes/require_access.php';

$role = $_SESSION['admin_role'] ?? '';
if ($role === 'utilisateur') {
    $role = 'gestion_stock';
}
if (!in_array($role, ['admin', 'rh'], true)) {
    $_SESSION['error_message'] = 'Accès réservé aux administrateurs ou aux RH.';
    header('Location: ../dashboard.php');
    exit;
}

require_once __DIR__ . '/../../models/model_admin.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['admin_id'])) {
    $admin_id = isset($_POST['admin_id']) ? (int) $_POST['admin_id'] : 0;

    if ($admin_id > 0) {
        if ($admin_id === (int) $_SESSION['admin_id']) {
            $error_message = 'Vous ne pouvez pas modifier votre propre compte depuis cette page.';
        } else {
            if (isset($_POST['toggle_statut'])) {
                $nouveau_statut = $_POST['nouveau_statut'] ?? '';
                if (in_array($nouveau_statut, ['actif', 'inactif']) && update_admin_statut($admin_id, $nouveau_statut)) {
                    $success_message = $nouveau_statut === 'actif' ? 'Compte activé avec succès.' : 'Compte désactivé avec succès.';
                } else {
                    $error_message = 'Erreur lors de la modification du statut.';
                }
            } elseif (isset($_POST['definir_role'])) {
                $nouveau_role = $_POST['nouveau_role'] ?? '';
                if (in_array($nouveau_role, admin_roles_valides(), true) && update_admin_role($admin_id, $nouveau_role)) {
                    $success_message = 'Rôle mis à jour avec succès.';
                } else {
                    $error_message = 'Erreur lors de la modification du rôle.';
                }
            }
        }
    }
}

$admins = get_all_admins();
$total = count($admins);
$admins_actifs = count(array_filter($admins, function ($a) { return $a['statut'] === 'actif'; }));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include __DIR__ . '/../../includes/favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comptes d’accès — Administration</title>
    <?php require_once __DIR__ . '/../../includes/asset_version.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/admin-dashboard.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/admin-users-cards.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/admin-comptes-page.css<?php echo asset_version_query(); ?>">
</head>
<body class="page-comptes">
    <?php include '../includes/nav.php'; ?>

    <header class="comptes-header-bar">
        <div>
            <h1><i class="fas fa-user-shield" aria-hidden="true"></i> Comptes d’accès administration</h1>
        </div>
        <div class="comptes-header-actions">
            <a href="../inscription-admin.php" class="btn-open-emp-modal btn-inscription-admin-link">
                <i class="fas fa-user-plus"></i> Ajouter un compte d’accès
            </a>
        </div>
    </header>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="message success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="message error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="message success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="message error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <div class="users-stats">
        <div class="stat-box">
            <h3>Comptes (interne)</h3>
            <div class="stat-value"><?php echo $total; ?></div>
        </div>
        <div class="stat-box">
            <h3>Actifs</h3>
            <div class="stat-value"><?php echo $admins_actifs; ?></div>
        </div>
    </div>

    <h2 class="hub-section-title"><i class="fas fa-users-gear"></i> Utilisateurs de l’espace admin</h2>
    <p class="section-subtitle" style="margin: -8px 0 20px; max-width: 720px;">
        Connexions à l’administration (rôles : commercial, comptabilité, RH, etc.). Distinct des
        <strong>clients du site</strong> (<a href="../users/index.php">Clients du site</a>).
    </p>

    <?php if (empty($admins)): ?>
        <div class="empty-state">
            <i class="fas fa-user-shield"></i>
            <h3>Aucun compte</h3>
            <p>Aucun compte administrateur n'est enregistré.</p>
            <a href="../inscription-admin.php" class="btn-primary"><i class="fas fa-plus"></i> Créer le premier compte</a>
        </div>
    <?php else: ?>
        <div class="users-grid">
            <?php foreach ($admins as $admin): ?>
                <?php $is_self = ($admin['id'] == $_SESSION['admin_id']); ?>
                <div class="user-card <?php echo $admin['statut'] === 'inactif' ? 'inactive' : ''; ?>">
                    <div class="card-header-wrap">
                        <div class="card-badges">
                            <span class="user-statut statut-<?php echo $admin['statut']; ?>">
                                <?php echo $admin['statut'] === 'actif' ? 'Actif' : 'Inactif'; ?>
                            </span>
                            <span class="role-badge role-<?php echo htmlspecialchars($admin['role']); ?>">
                                <?php echo htmlspecialchars(admin_role_label($admin['role'] ?? 'utilisateur')); ?>
                            </span>
                        </div>
                        <div class="user-header">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($admin['prenom'], 0, 1)); ?>
                            </div>
                            <div class="user-info">
                                <div class="user-name"><?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?></div>
                                <div class="user-email"><?php echo htmlspecialchars($admin['email']); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="user-details">
                            <div class="detail-item">
                                <label>Date création</label>
                                <div class="value"><?php echo date('d/m/Y H:i', strtotime($admin['date_creation'])); ?></div>
                            </div>
                            <div class="detail-item">
                                <label>Dernière connexion</label>
                                <div class="value"><?php echo $admin['derniere_connexion'] ? date('d/m/Y H:i', strtotime($admin['derniere_connexion'])) : '—'; ?></div>
                            </div>
                        </div>

                        <div class="user-actions" style="flex-wrap: wrap;">
                            <a href="employe-activite.php?admin_id=<?php echo (int) $admin['id']; ?>" class="btn-action btn-role">
                                <i class="fas fa-chart-line"></i> Activité
                            </a>
                        <?php if (!$is_self): ?>
                        <?php if ($admin['statut'] === 'actif'): ?>
                            <form method="POST" action="" class="user-action-form">
                                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                <input type="hidden" name="nouveau_statut" value="inactif">
                                <button type="submit" name="toggle_statut" class="btn-action btn-deactivate"
                                        onclick="return confirm('Désactiver ce compte ?');">
                                    <i class="fas fa-ban"></i> Désactiver
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" action="" class="user-action-form">
                                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                <input type="hidden" name="nouveau_statut" value="actif">
                                <button type="submit" name="toggle_statut" class="btn-action btn-activate"
                                        onclick="return confirm('Activer ce compte ?');">
                                    <i class="fas fa-check"></i> Activer
                                </button>
                            </form>
                        <?php endif; ?>

                        <form method="POST" action="" class="user-action-form" style="flex-wrap: wrap; align-items: center; gap: 8px;">
                            <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                            <label for="role-<?php echo (int) $admin['id']; ?>" class="visually-hidden">Rôle</label>
                            <select name="nouveau_role" id="role-<?php echo (int) $admin['id']; ?>" class="comptes-role-select">
                                <?php foreach (admin_roles_valides() as $r): ?>
                                <option value="<?php echo htmlspecialchars($r); ?>" <?php echo (($admin['role'] ?? '') === $r) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(admin_role_label($r)); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="definir_role" class="btn-action btn-role"
                                    onclick="return confirm('Enregistrer ce rôle pour ce compte ?');">
                                <i class="fas fa-save"></i> Enregistrer le rôle
                            </button>
                        </form>
                        <?php else: ?>
                        <span class="btn-action btn-self"><i class="fas fa-user"></i> Votre compte</span>
                        <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
