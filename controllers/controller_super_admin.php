<?php
/**
 * Contrôleur Super Administrateur (marketplace)
 * Programmation procédurale uniquement
 */

require_once __DIR__ . '/../models/model_super_admin.php';

/**
 * Génère ou retourne le jeton CSRF super admin (session déjà démarrée)
 */
function super_admin_csrf_token() {
    if (empty($_SESSION['super_admin_csrf'])) {
        $_SESSION['super_admin_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['super_admin_csrf'];
}

/**
 * Vérifie le jeton CSRF (formulaires POST)
 */
function super_admin_csrf_valid($token) {
    $expected = $_SESSION['super_admin_csrf'] ?? '';
    return is_string($token) && is_string($expected) && $expected !== ''
        && hash_equals($expected, $token);
}

/**
 * Connexion super administrateur
 * @return array{success:bool,message:string,super_admin:array|false}
 */
function process_super_admin_login() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return ['success' => false, 'message' => '', 'super_admin' => false];
    }

    $email = isset($_POST['email']) ? trim((string) $_POST['email']) : '';
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
    $errors = [];

    if ($email === '') {
        $errors[] = 'L\'email est obligatoire.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L\'email n\'est pas valide.';
    }
    if ($password === '') {
        $errors[] = 'Le mot de passe est obligatoire.';
    }

    $csrf = $_POST['csrf_token'] ?? '';
    if (!super_admin_csrf_valid($csrf)) {
        $errors[] = 'Session expirée ou formulaire invalide. Réessayez.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'message' => implode('<br>', $errors), 'super_admin' => false];
    }

    $row = get_super_admin_by_email($email);
    if (!$row) {
        return ['success' => false, 'message' => 'Email ou mot de passe incorrect.', 'super_admin' => false];
    }
    if (($row['statut'] ?? '') !== 'actif') {
        return ['success' => false, 'message' => 'Votre compte est désactivé.', 'super_admin' => false];
    }
    if (!password_verify($password, $row['password'])) {
        return ['success' => false, 'message' => 'Email ou mot de passe incorrect.', 'super_admin' => false];
    }

    super_admin_update_last_login((int) $row['id']);
    return ['success' => true, 'message' => 'Connexion réussie.', 'super_admin' => $row];
}

/**
 * Inscription du premier super administrateur (aucun compte existant)
 * @return array{success:bool,message:string}
 */
function process_super_admin_inscription() {
    $errors = [];
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return ['success' => false, 'message' => ''];
    }

    if (super_admin_exists()) {
        $errors[] = 'Un super administrateur existe déjà. Connectez-vous ou contactez la plateforme.';
    }

    $nom = isset($_POST['nom']) ? trim((string) $_POST['nom']) : '';
    $prenom = isset($_POST['prenom']) ? trim((string) $_POST['prenom']) : '';
    $email = isset($_POST['email']) ? trim((string) $_POST['email']) : '';
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
    $password_confirm = isset($_POST['password_confirm']) ? (string) $_POST['password_confirm'] : '';

    if ($nom === '' || strlen($nom) < 2) {
        $errors[] = 'Le nom est obligatoire (au moins 2 caractères).';
    }
    if ($prenom === '' || strlen($prenom) < 2) {
        $errors[] = 'Le prénom est obligatoire (au moins 2 caractères).';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L\'email est invalide.';
    } elseif (super_admin_email_exists($email)) {
        $errors[] = 'Cet email est déjà utilisé.';
    }
    if ($password === '') {
        $errors[] = 'Le mot de passe est obligatoire.';
    } elseif (strlen($password) < 10) {
        $errors[] = 'Le mot de passe doit contenir au moins 10 caractères.';
    } elseif (!preg_match('/[A-Z]/u', $password) || !preg_match('/[a-z]/u', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = 'Le mot de passe doit contenir une majuscule, une minuscule et un chiffre.';
    }
    if ($password !== $password_confirm) {
        $errors[] = 'Les mots de passe ne correspondent pas.';
    }

    $csrf = $_POST['csrf_token'] ?? '';
    if (!super_admin_csrf_valid($csrf)) {
        $errors[] = 'Session expirée ou formulaire invalide. Réessayez.';
    }

    if (!empty($errors)) {
        return ['success' => false, 'message' => implode('<br>', $errors)];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $id = create_super_admin($nom, $prenom, $email, $hash);
    if ($id) {
        return ['success' => true, 'message' => 'Compte créé. Vous pouvez vous connecter.'];
    }
    return ['success' => false, 'message' => 'Erreur lors de la création du compte.'];
}
