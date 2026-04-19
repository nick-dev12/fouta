<?php
/**
 * Modèle pour la gestion des administrateurs
 * Programmation procédurale uniquement
 */

// Inclusion du fichier de connexion à la BDD
require_once __DIR__ . '/../conn/conn.php';

/**
 * Rôles autorisés pour les comptes admin (alignés sur ENUM MySQL après migration B2B)
 */
function admin_roles_valides() {
    return ['admin', 'gestion_stock', 'commercial', 'comptabilite', 'rh', 'caissier', 'vendeur', 'plateforme'];
}

/**
 * Libellé affichage d'un rôle
 */
function admin_role_label($role) {
    $labels = [
        'admin' => 'Administrateur',
        'gestion_stock' => 'Gestion des stocks',
        'utilisateur' => 'Gestion des stocks',
        'commercial' => 'Commercial',
        'comptabilite' => 'Comptabilité',
        'rh' => 'Ressources humaines',
        'caissier' => 'Caissier (caissière)',
        'vendeur' => 'Vendeur (boutique)',
        'plateforme' => 'Plateforme',
    ];
    $r = (string) $role;
    if ($r === 'utilisateur') {
        $r = 'gestion_stock';
    }
    return isset($labels[$r]) ? $labels[$r] : $r;
}

/**
 * Normalise un rôle (legacy utilisateur → gestion_stock)
 */
function normalize_admin_role($role) {
    $r = (string) $role;
    if ($r === 'utilisateur') {
        return 'gestion_stock';
    }
    return in_array($r, admin_roles_valides(), true) ? $r : 'gestion_stock';
}

/**
 * Vérifie si un administrateur existe déjà avec cet email
 * @param string $email L'email à vérifier
 * @return bool True si l'email existe, False sinon
 */
function admin_email_exists($email)
{
    global $db;

    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM admin WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $count = $stmt->fetchColumn();

        return $count > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Vérifie si au moins un administrateur existe déjà
 * @return bool True si un admin existe, False sinon
 */
function admin_exists()
{
    global $db;

    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM admin");
        $stmt->execute();
        $count = $stmt->fetchColumn();

        return $count > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Insère un nouvel administrateur dans la base de données
 * @param string $nom Le nom de l'administrateur
 * @param string $prenom Le prénom de l'administrateur
 * @param string $email L'email de l'administrateur
 * @param string $password_hash Le mot de passe hashé
 * @param string $role Voir admin_roles_valides() (défaut: gestion_stock)
 * @return bool|int L'ID de l'admin créé en cas de succès, False en cas d'échec
 */
function create_admin($nom, $prenom, $email, $password_hash, $role = 'gestion_stock')
{
    global $db;

    $role = normalize_admin_role($role);

    try {
        $stmt = $db->prepare("
            INSERT INTO admin (nom, prenom, email, password, date_creation, statut, role) 
            VALUES (:nom, :prenom, :email, :password, NOW(), 'actif', :role)
        ");

        $result = $stmt->execute([
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'password' => $password_hash,
            'role' => $role
        ]);

        if ($result) {
            return $db->lastInsertId();
        }

        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Récupère un administrateur par son email
 * @param string $email L'email de l'administrateur
 * @return array|false Les données de l'admin ou False si non trouvé
 */
function get_admin_by_email($email)
{
    global $db;

    if ($email === null || $email === '') {
        return false;
    }

    try {
        $stmt = $db->prepare("SELECT * FROM admin WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        return $admin ? $admin : false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Récupère un admin par téléphone (vendeurs / connexion PIN)
 */
function get_admin_by_telephone($telephone)
{
    global $db;

    $digits = preg_replace('/\D/', '', (string) $telephone);
    if ($digits === '') {
        return false;
    }

    try {
        $stmt = $db->prepare("
            SELECT * FROM admin
            WHERE telephone IS NOT NULL AND TRIM(telephone) != ''
              AND REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(telephone,''), ' ', ''), '-', ''), '+', ''), '.', '') = :d
            LIMIT 1
        ");
        $stmt->execute(['d' => $digits]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($admin) {
            return $admin;
        }

        $t = preg_replace('/\s+/', '', (string) $telephone);
        if ($t === '' || $t === $digits) {
            return false;
        }
        $stmt = $db->prepare("SELECT * FROM admin WHERE telephone = :t LIMIT 1");
        $stmt->execute(['t' => $t]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        return $admin ? $admin : false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Récupère un compte par slug boutique (URL partageable)
 */
function get_admin_by_boutique_slug($slug)
{
    global $db;

    $s = trim((string) $slug, '/');
    if ($s === '') {
        return false;
    }

    try {
        $stmt = $db->prepare("SELECT * FROM admin WHERE boutique_slug = :s LIMIT 1");
        $stmt->execute(['s' => $s]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        return $admin ? $admin : false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Téléphone déjà utilisé par un admin
 */
function admin_telephone_exists($telephone)
{
    global $db;

    $t = preg_replace('/\s+/', '', (string) $telephone);
    if ($t === '') {
        return false;
    }

    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM admin WHERE telephone = :t");
        $stmt->execute(['t' => $t]);
        return ((int) $stmt->fetchColumn()) > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Slug boutique déjà pris
 */
function admin_boutique_slug_exists($slug)
{
    global $db;

    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM admin WHERE boutique_slug = :s");
        $stmt->execute(['s' => $slug]);
        return ((int) $stmt->fetchColumn()) > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Crée un compte vendeur (boutique)
 * @param string $identite Nom affiché (champ unique UI)
 * @param string|null $email
 * @param string $telephone
 * @param string $password_hash hash du PIN / mot de passe
 * @param string $boutique_nom Nom commercial
 * @param string $boutique_slug Slug URL unique
 * @return bool|int id ou false
 */
function create_vendeur_boutique($identite, $email, $telephone, $password_hash, $boutique_nom, $boutique_slug)
{
    global $db;

    $identite = trim((string) $identite);
    $boutique_nom = trim((string) $boutique_nom);
    $boutique_slug = trim((string) $boutique_slug);
    $telephone = preg_replace('/\s+/', '', (string) $telephone);
    $email = $email !== null && trim((string) $email) !== '' ? trim((string) $email) : null;

    try {
        $stmt = $db->prepare("
            INSERT INTO admin (nom, prenom, email, password, date_creation, statut, role, boutique_slug, boutique_nom, telephone)
            VALUES (:nom, '', :email, :password, NOW(), 'actif', 'vendeur', :boutique_slug, :boutique_nom, :telephone)
        ");
        $ok = $stmt->execute([
            'nom' => $identite,
            'email' => $email,
            'password' => $password_hash,
            'boutique_slug' => $boutique_slug,
            'boutique_nom' => $boutique_nom,
            'telephone' => $telephone,
        ]);
        if ($ok) {
            return (int) $db->lastInsertId();
        }
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Récupère un administrateur par son ID
 * @param int $id L'ID de l'administrateur
 * @return array|false Les données de l'admin ou False si non trouvé
 */
function get_admin_by_id($id)
{
    global $db;

    try {
        $stmt = $db->prepare("SELECT * FROM admin WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        return $admin ? $admin : false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Met à jour les informations d'un administrateur
 * @param int $id L'ID de l'administrateur
 * @param array $data Les nouvelles données
 * @return bool True en cas de succès, False sinon
 */
function update_admin($id, $data)
{
    global $db;

    try {
        $stmt = $db->prepare("
            UPDATE admin SET
                nom = :nom,
                prenom = :prenom,
                email = :email
            WHERE id = :id
        ");

        return $stmt->execute([
            'id' => $id,
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'email' => $data['email']
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Met à jour la dernière connexion d'un administrateur
 * @param int $admin_id L'ID de l'administrateur
 * @return bool True en cas de succès, False sinon
 */
function update_admin_last_login($admin_id)
{
    global $db;

    try {
        $stmt = $db->prepare("UPDATE admin SET derniere_connexion = NOW() WHERE id = :id");
        return $stmt->execute(['id' => $admin_id]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Mise à jour branding vitrine vendeur (logo, couleurs, adresse affichée).
 * Colonnes attendues : boutique_logo, boutique_couleur_principale, boutique_couleur_accent, boutique_adresse
 *
 * @param int $id ID admin vendeur
 * @param array $data Clés : boutique_logo (?string), boutique_couleur_principale, boutique_couleur_accent, boutique_adresse
 * @return bool
 */
function update_admin_boutique_branding($id, array $data)
{
    global $db;

    try {
        $stmt = $db->prepare("
            UPDATE admin SET
                boutique_logo = :boutique_logo,
                boutique_couleur_principale = :boutique_couleur_principale,
                boutique_couleur_accent = :boutique_couleur_accent,
                boutique_adresse = :boutique_adresse
            WHERE id = :id AND role = 'vendeur'
        ");

        return $stmt->execute([
            'id' => (int) $id,
            'boutique_logo' => $data['boutique_logo'] !== null && $data['boutique_logo'] !== ''
                ? (string) $data['boutique_logo']
                : null,
            'boutique_couleur_principale' => $data['boutique_couleur_principale'] !== null && $data['boutique_couleur_principale'] !== ''
                ? (string) $data['boutique_couleur_principale']
                : null,
            'boutique_couleur_accent' => $data['boutique_couleur_accent'] !== null && $data['boutique_couleur_accent'] !== ''
                ? (string) $data['boutique_couleur_accent']
                : null,
            'boutique_adresse' => $data['boutique_adresse'] !== null && trim((string) $data['boutique_adresse']) !== ''
                ? trim((string) $data['boutique_adresse'])
                : null,
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Met à jour le mot de passe d'un administrateur
 * @param int $admin_id L'ID de l'administrateur
 * @param string $password_hash Le nouveau mot de passe hashé
 * @return bool True en cas de succès, False sinon
 */
function update_admin_password($admin_id, $password_hash)
{
    global $db;

    try {
        $stmt = $db->prepare("UPDATE admin SET password = :password WHERE id = :id");
        return $stmt->execute(['id' => $admin_id, 'password' => $password_hash]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Crée un token de réinitialisation de mot de passe
 * @param string $email L'email de l'admin
 * @param string $token Le token généré
 * @param string $expires_at Date d'expiration (format DATETIME)
 * @return bool True en cas de succès, False sinon
 */
function create_password_reset_token($email, $token, $expires_at)
{
    global $db;

    try {
        // Supprimer les anciens tokens pour cet email
        $stmt = $db->prepare("DELETE FROM admin_password_reset WHERE email = :email");
        $stmt->execute(['email' => $email]);

        $stmt = $db->prepare("
            INSERT INTO admin_password_reset (email, token, expires_at) 
            VALUES (:email, :token, :expires_at)
        ");
        return $stmt->execute([
            'email' => $email,
            'token' => $token,
            'expires_at' => $expires_at
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Récupère un token de réinitialisation valide
 * @param string $token Le token à vérifier
 * @return array|false Les données du token ou False si invalide/expiré
 */
function get_valid_reset_token($token)
{
    global $db;

    try {
        $stmt = $db->prepare("
            SELECT * FROM admin_password_reset 
            WHERE token = :token AND used = 0 AND expires_at > NOW()
        ");
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row : false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Marque un token comme utilisé
 * @param string $token Le token à marquer
 * @return bool True en cas de succès, False sinon
 */
function mark_reset_token_used($token)
{
    global $db;

    try {
        $stmt = $db->prepare("UPDATE admin_password_reset SET used = 1 WHERE token = :token");
        return $stmt->execute(['token' => $token]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Récupère les emails de tous les administrateurs actifs
 * @return array Liste des emails
 */
function get_all_admin_emails()
{
    global $db;

    try {
        $stmt = $db->prepare("SELECT email FROM admin WHERE statut = 'actif' AND email IS NOT NULL AND email != ''");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Récupère tous les comptes administrateurs
 * @return array Liste des admins
 */
function get_all_admins()
{
    global $db;

    try {
        $stmt = $db->prepare("
            SELECT id, nom, prenom, email, date_creation, derniere_connexion, statut, 
                   COALESCE(role, 'admin') as role 
            FROM admin 
            ORDER BY date_creation DESC
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $rows ? $rows : [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Met à jour le rôle d'un administrateur
 * @param int $id ID de l'admin
 * @param string $role Voir admin_roles_valides()
 * @return bool
 */
function update_admin_role($id, $role)
{
    global $db;

    if (!in_array($role, admin_roles_valides(), true)) {
        return false;
    }

    try {
        $stmt = $db->prepare("UPDATE admin SET role = :role WHERE id = :id");
        return $stmt->execute(['id' => $id, 'role' => $role]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Met à jour le statut d'un administrateur
 * @param int $id ID de l'admin
 * @param string $statut 'actif' ou 'inactif'
 * @return bool
 */
function update_admin_statut($id, $statut)
{
    global $db;

    if (!in_array($statut, ['actif', 'inactif'])) {
        return false;
    }

    try {
        $stmt = $db->prepare("UPDATE admin SET statut = :statut WHERE id = :id");
        return $stmt->execute(['id' => $id, 'statut' => $statut]);
    } catch (PDOException $e) {
        return false;
    }
}

?>