<?php
/**
 * Modèle pour la gestion des catégories
 * Programmation procédurale uniquement
 */

// Inclusion du fichier de connexion à la BDD
require_once __DIR__ . '/../conn/conn.php';

/**
 * Colonne présente sur la table categories
 */
function categories_table_has_column($name) {
    static $map = null;
    global $db;
    if (!$db) {
        return false;
    }
    $field = (string) $name;
    if ($field === '') {
        return false;
    }
    if ($map === null) {
        $map = [];
        try {
            $st = $db->query('SHOW COLUMNS FROM `categories`');
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $map[$row['Field']] = true;
            }
        } catch (PDOException $e) {
            $map = [];
        }
    }
    return !empty($map[$field]);
}

/**
 * Récupère toutes les catégories actives
 * @return array|false Tableau des catégories ou False en cas d'erreur
 */
function get_all_categories()
{
    global $db;

    try {
        $stmt = $db->prepare("SELECT * FROM categories ORDER BY nom ASC");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $categories ? $categories : [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Catégories ayant au moins un produit actif pour ce vendeur (boutique)
 */
function get_all_categories_for_vendeur($vendeur_id)
{
    global $db;

    require_once __DIR__ . '/model_produits.php';
    if (!produits_has_column('admin_id')) {
        return get_all_categories();
    }
    $vid = (int) $vendeur_id;
    if ($vid <= 0) {
        return [];
    }

    try {
        $stmt = $db->prepare("
            SELECT DISTINCT c.*
            FROM categories c
            INNER JOIN produits p ON p.categorie_id = c.id AND p.statut = 'actif'
            WHERE p.admin_id = :vid
            ORDER BY c.nom ASC
        ");
        $stmt->execute(['vid' => $vid]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $categories ? $categories : [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Liste des catégories pour la page stock vendeur : uniquement les siennes,
 * sans les rayons plateforme (`est_plateforme` sans propriétaire).
 */
function get_categories_for_vendeur_stock($vendeur_id) {
    global $db;
    $vendeur_id = (int) $vendeur_id;
    if ($vendeur_id <= 0 || !categories_table_has_column('admin_id')) {
        return [];
    }
    require_once __DIR__ . '/model_produits.php';
    $with_orphan = produits_has_column('admin_id');
    $exclude_plat = '';
    if (categories_table_has_column('est_plateforme')) {
        $exclude_plat = ' AND NOT (c.`est_plateforme` = 1 AND (c.`admin_id` IS NULL OR c.`admin_id` = 0)) ';
    }
    // Même nom qu’un rayon categories_generales, sans propriétaire = doublon plateforme
    $exclude_nom_generale = '';
    if (categories_generales_table_exists()) {
        $exclude_nom_generale = '
                AND NOT (
                    (c.`admin_id` IS NULL OR c.`admin_id` = 0)
                    AND EXISTS (
                        SELECT 1 FROM `categories_generales` cg WHERE cg.`nom` = c.`nom`
                    )
                )';
    }
    try {
        if ($with_orphan) {
            $sql = "
                SELECT DISTINCT c.*
                FROM `categories` c
                WHERE (
                    c.`admin_id` = :v
                    OR (
                        (c.`admin_id` IS NULL OR c.`admin_id` = 0)
                        AND EXISTS (
                            SELECT 1 FROM `produits` p
                            WHERE p.`categorie_id` = c.`id` AND p.`admin_id` = :v2
                        )
                    )
                )
                {$exclude_plat}
                {$exclude_nom_generale}
                ORDER BY c.`nom` ASC
            ";
            $st = $db->prepare($sql);
            $st->execute(['v' => $vendeur_id, 'v2' => $vendeur_id]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
        $sql = "
            SELECT c.*
            FROM `categories` c
            WHERE c.`admin_id` = :v
            {$exclude_plat}
            {$exclude_nom_generale}
            ORDER BY c.`nom` ASC
        ";
        $st = $db->prepare($sql);
        $st->execute(['v' => $vendeur_id]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Le vendeur peut utiliser cette catégorie pour ses produits (propriétaire ou produits déjà liés).
 */
function categorie_est_utilisable_par_vendeur($categorie_id, $vendeur_id) {
    $categorie_id = (int) $categorie_id;
    $vendeur_id = (int) $vendeur_id;
    if ($categorie_id <= 0 || $vendeur_id <= 0) {
        return false;
    }
    $c = get_categorie_by_id($categorie_id);
    if (!$c) {
        return false;
    }
    if ((int) ($c['admin_id'] ?? 0) === $vendeur_id) {
        return true;
    }
    require_once __DIR__ . '/model_produits.php';
    if (!produits_has_column('admin_id')) {
        return false;
    }
    global $db;
    try {
        $st = $db->prepare('SELECT 1 FROM `produits` WHERE `categorie_id` = :c AND `admin_id` = :v LIMIT 1');
        $st->execute(['c' => $categorie_id, 'v' => $vendeur_id]);
        return (bool) $st->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Récupère une catégorie par son ID
 * @param int $id L'ID de la catégorie
 * @return array|false Les données de la catégorie ou False si non trouvée
 */
function get_categorie_by_id($id)
{
    global $db;

    try {
        $stmt = $db->prepare("SELECT * FROM categories WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $categorie = $stmt->fetch(PDO::FETCH_ASSOC);

        return $categorie ? $categorie : false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Récupère une catégorie par son nom
 * @param string $nom Le nom de la catégorie
 * @return array|false Les données de la catégorie ou False si non trouvée
 */
function get_categorie_by_nom($nom)
{
    global $db;

    try {
        $stmt = $db->prepare("SELECT * FROM categories WHERE nom = :nom");
        $stmt->execute(['nom' => $nom]);
        $categorie = $stmt->fetch(PDO::FETCH_ASSOC);

        return $categorie ? $categorie : false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Crée une nouvelle catégorie
 * @param string $nom Le nom de la catégorie
 * @param string $description La description
 * @param string|null $image Le chemin de l'image
 * @param int|null $admin_id Propriétaire vendeur (marketplace)
 * @param int|null $categorie_generale_id Rayon plateforme (categories_generales.id)
 * @return int|false L'ID de la catégorie créée ou False en cas d'erreur
 */
function create_categorie($nom, $description = null, $image = null, $admin_id = null, $categorie_generale_id = null)
{
    global $db;

    $nom = trim((string) $nom);
    if ($nom === '') {
        return false;
    }

    $cols = ['`nom`', '`description`', '`image`', '`date_creation`'];
    $holders = [':nom', ':description', ':image', 'NOW()'];
    $params = [
        'nom' => $nom,
        'description' => $description,
        'image' => $image,
    ];

    $aid = $admin_id !== null ? (int) $admin_id : 0;
    if ($aid > 0 && categories_table_has_column('admin_id')) {
        $cols[] = '`admin_id`';
        $holders[] = ':admin_id';
        $params['admin_id'] = $aid;
    }

    $cgid = $categorie_generale_id !== null ? (int) $categorie_generale_id : 0;
    if ($cgid > 0 && categories_table_has_column('categorie_generale_id')) {
        $cols[] = '`categorie_generale_id`';
        $holders[] = ':categorie_generale_id';
        $params['categorie_generale_id'] = $cgid;
    }

    try {
        $sql = 'INSERT INTO `categories` (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $holders) . ')';
        $stmt = $db->prepare($sql);
        if ($stmt->execute($params)) {
            return (int) $db->lastInsertId();
        }
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Met à jour une catégorie
 * @param int $id L'ID de la catégorie
 * @param string $nom Le nom de la catégorie
 * @param string $description La description
 * @param string|null $image Le chemin de l'image
 * @return bool True en cas de succès, False sinon
 */
function update_categorie($id, $nom, $description = null, $image = null)
{
    global $db;

    try {
        $stmt = $db->prepare("
            UPDATE categories SET
                nom = :nom,
                description = :description,
                image = :image
            WHERE id = :id
        ");

        return $stmt->execute([
            'id' => $id,
            'nom' => $nom,
            'description' => $description,
            'image' => $image
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Supprime une catégorie
 * @param int $id L'ID de la catégorie
 * @return bool True en cas de succès, False sinon
 */
function delete_categorie($id)
{
    global $db;

    try {
        $stmt = $db->prepare("DELETE FROM categories WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Vérifie si une catégorie a des produits associés
 * @param int $categorie_id L'ID de la catégorie
 * @return bool True si la catégorie a des produits, False sinon
 */
function categorie_has_produits($categorie_id)
{
    global $db;

    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM produits WHERE categorie_id = :id");
        $stmt->execute(['id' => $categorie_id]);
        $count = $stmt->fetchColumn();

        return $count > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Récupère toutes les catégories avec le nombre de produits
 * @return array Tableau des catégories avec le nombre de produits
 */
function get_all_categories_with_count()
{
    global $db;

    try {
        $stmt = $db->prepare("
            SELECT c.*, COUNT(p.id) as nb_produits
            FROM categories c
            LEFT JOIN produits p ON c.id = p.categorie_id AND p.statut = 'actif'
            GROUP BY c.id
            ORDER BY c.nom ASC
        ");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $categories ? $categories : [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Récupère les catégories les plus populaires (basées sur les visites et commandes)
 * @param int $limit Nombre maximum de catégories à retourner (par défaut 5)
 * @return array Tableau des catégories les plus populaires mélangées aléatoirement
 */
function get_top_categories($limit = 5)
{
    global $db;

    try {
        // Récupérer les catégories avec le nombre de visites et de commandes
        $stmt = $db->prepare("
            SELECT 
                c.*,
                COALESCE(visites_stats.nb_visites, 0) as nb_visites,
                COALESCE(commandes_stats.nb_commandes, 0) as nb_commandes,
                (COALESCE(visites_stats.nb_visites, 0) + COALESCE(commandes_stats.nb_commandes, 0)) as score_popularite
            FROM categories c
            LEFT JOIN (
                SELECT p.categorie_id, COUNT(pv.id) as nb_visites
                FROM produits_visites pv
                INNER JOIN produits p ON pv.produit_id = p.id
                WHERE p.statut = 'actif'
                GROUP BY p.categorie_id
            ) visites_stats ON c.id = visites_stats.categorie_id
            LEFT JOIN (
                SELECT p.categorie_id, COUNT(cp.id) as nb_commandes
                FROM commande_produits cp
                INNER JOIN produits p ON cp.produit_id = p.id
                WHERE p.statut = 'actif'
                GROUP BY p.categorie_id
            ) commandes_stats ON c.id = commandes_stats.categorie_id
            HAVING score_popularite > 0
            ORDER BY score_popularite DESC, c.nom ASC
            LIMIT :limit
        ");

        $stmt->bindValue(':limit', $limit * 2, PDO::PARAM_INT); // Récupérer plus pour avoir de la variété
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Si aucune catégorie avec visites/commandes, récupérer toutes les catégories
        if (empty($categories)) {
            $categories = get_all_categories();
        }

        // Mélanger aléatoirement les catégories
        if (!empty($categories)) {
            mt_srand(time() + (int) (microtime(true) * 1000000));
            shuffle($categories);
            // Limiter au nombre demandé
            $categories = array_slice($categories, 0, $limit);
        }

        return $categories ? $categories : [];
    } catch (PDOException $e) {
        // En cas d'erreur, retourner toutes les catégories mélangées
        $categories = get_all_categories();
        if (!empty($categories)) {
            mt_srand(time() + (int) (microtime(true) * 1000000));
            shuffle($categories);
            $categories = array_slice($categories, 0, $limit);
        }
        return $categories ? $categories : [];
    }
}

/**
 * Hiérarchie catégories (migration marketplace) active si colonne parent_id existe
 */
function categories_hierarchy_enabled() {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    global $db;
    $cached = false;
    if (!$db) {
        return false;
    }
    try {
        $st = $db->query("SHOW COLUMNS FROM `categories` LIKE 'parent_id'");
        $cached = (bool) $st->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $cached = false;
    }
    return $cached;
}

/**
 * Colonne est_plateforme (rayons officiels mega-menu / formulaire vendeur)
 */
function categories_est_plateforme_column() {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    global $db;
    $cached = false;
    if (!$db) {
        return false;
    }
    try {
        $st = $db->query("SHOW COLUMNS FROM `categories` LIKE 'est_plateforme'");
        $cached = (bool) $st->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $cached = false;
    }
    return $cached;
}

/**
 * Classe Font Awesome pour icône menu (champ icone en BDD)
 */
function categorie_fa_icon_class(array $row) {
    $ic_raw = trim((string) ($row['icone'] ?? ''));
    if ($ic_raw === '' || stripos($ic_raw, 'fa-') === false) {
        return 'fa-solid fa-layer-group';
    }
    if (preg_match('/^(fa-solid|fa-regular|fa-brands)\s+/i', $ic_raw)) {
        return $ic_raw;
    }
    return 'fa-solid ' . $ic_raw;
}

/**
 * Table dédiée catégories générales (rayons plateforme)
 */
function categories_generales_table_exists() {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    global $db;
    $cached = false;
    if (!$db) {
        return false;
    }
    try {
        $st = $db->query("
            SELECT COUNT(*) FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories_generales'
        ");
        $cached = ((int) $st->fetchColumn()) > 0;
    } catch (PDOException $e) {
        $cached = false;
    }
    return $cached;
}

/**
 * Colonne categories.categorie_generale_id (lien sous-cat. vendeur → rayon)
 */
function categories_has_categorie_generale_id_column() {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    global $db;
    $cached = false;
    if (!$db) {
        return false;
    }
    try {
        $st = $db->query("SHOW COLUMNS FROM `categories` LIKE 'categorie_generale_id'");
        $cached = (bool) $st->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $cached = false;
    }
    return $cached;
}

function get_categorie_generale_by_id($id) {
    global $db;
    $id = (int) $id;
    if ($id <= 0 || !categories_generales_table_exists()) {
        return false;
    }
    try {
        $st = $db->prepare('SELECT * FROM `categories_generales` WHERE `id` = :id');
        $st->execute(['id' => $id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ? $row : false;
    } catch (PDOException $e) {
        return false;
    }
}

function get_categorie_generale_by_nom($nom) {
    global $db;
    $nom = trim((string) $nom);
    if ($nom === '' || !categories_generales_table_exists()) {
        return false;
    }
    try {
        $st = $db->prepare('SELECT * FROM `categories_generales` WHERE `nom` = :n LIMIT 1');
        $st->execute(['n' => $nom]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ? $row : false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Parse filtre URL produits : "G12" = rayon id 12, sinon id catégorie feuille.
 * @return array{leaf_id:?int,generale_id:?int}
 */
function marketplace_parse_categorie_filter_param($raw) {
    $raw = isset($raw) ? trim((string) $raw) : '';
    if ($raw === '') {
        return ['leaf_id' => null, 'generale_id' => null];
    }
    if (preg_match('/^G(\d+)$/i', $raw, $m)) {
        return ['leaf_id' => null, 'generale_id' => (int) $m[1]];
    }
    if (ctype_digit($raw)) {
        return ['leaf_id' => (int) $raw, 'generale_id' => null];
    }
    return ['leaf_id' => null, 'generale_id' => null];
}

/**
 * IDs des catégories feuilles vendeur rattachées à un rayon
 */
function categorie_generale_leaf_category_ids($generale_id) {
    global $db;
    $generale_id = (int) $generale_id;
    if ($generale_id <= 0 || !categories_has_categorie_generale_id_column()) {
        return [];
    }
    try {
        $st = $db->prepare("
            SELECT `id` FROM `categories`
            WHERE `categorie_generale_id` = :g AND `admin_id` IS NOT NULL
        ");
        $st->execute(['g' => $generale_id]);
        return array_map('intval', array_column($st->fetchAll(PDO::FETCH_ASSOC), 'id'));
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Préremplissage formulaire vendeur depuis une ligne categories (produit ou pré-sélection)
 */
function vendeur_prefill_from_categorie_row($categorie_row) {
    $out = ['generale' => 0, 'sub' => 0];
    if (empty($categorie_row) || !is_array($categorie_row)) {
        return $out;
    }
    if ((int) ($categorie_row['admin_id'] ?? 0) > 0) {
        $out['sub'] = (int) ($categorie_row['id'] ?? 0);
        $out['generale'] = (int) ($categorie_row['categorie_generale_id'] ?? 0);
        if ($out['generale'] <= 0 && !empty($categorie_row['parent_id'])) {
            $par = get_categorie_by_id((int) $categorie_row['parent_id']);
            if ($par && categories_generales_table_exists()) {
                $cg = get_categorie_generale_by_nom((string) ($par['nom'] ?? ''));
                if ($cg) {
                    $out['generale'] = (int) $cg['id'];
                }
            }
        }
        return $out;
    }
    if (categories_generales_table_exists() && categorie_is_generale_plateforme($categorie_row)) {
        $cg = get_categorie_generale_by_nom((string) ($categorie_row['nom'] ?? ''));
        if ($cg) {
            $out['generale'] = (int) $cg['id'];
        }
        return $out;
    }
    return $out;
}

function categorie_is_generale_plateforme(array $categorie) {
    if (categories_est_plateforme_column()) {
        return (int) ($categorie['est_plateforme'] ?? 0) === 1
            && ((int) ($categorie['parent_id'] ?? 0) <= 0)
            && ((int) ($categorie['admin_id'] ?? 0) <= 0);
    }
    $pid = $categorie['parent_id'] ?? null;
    $aid = $categorie['admin_id'] ?? null;
    $noParent = $pid === null || $pid === '' || (int) $pid === 0;
    $noOwner = $aid === null || $aid === '' || (int) $aid === 0;
    return $noParent && $noOwner;
}

/**
 * Catégories générales (racine plateforme), ordre menu — table categories_generales si migrée
 */
function get_general_categories_ordered() {
    global $db;
    if (!categories_hierarchy_enabled()) {
        return get_all_categories();
    }
    if (categories_generales_table_exists()) {
        try {
            $stmt = $db->query("
                SELECT * FROM `categories_generales`
                ORDER BY `sort_ordre` ASC, `nom` ASC
            ");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $rows ? $rows : [];
        } catch (PDOException $e) {
            return [];
        }
    }
    try {
        if (categories_est_plateforme_column()) {
            $stmt = $db->query("
                SELECT * FROM `categories`
                WHERE `est_plateforme` = 1
                  AND (`parent_id` IS NULL OR `parent_id` = 0)
                  AND (`admin_id` IS NULL OR `admin_id` = 0)
                ORDER BY `sort_ordre` ASC, `nom` ASC
            ");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                return $rows;
            }
        }
        $stmt = $db->query("
            SELECT * FROM `categories`
            WHERE (`parent_id` IS NULL OR `parent_id` = 0)
              AND (`admin_id` IS NULL OR `admin_id` = 0)
            ORDER BY `sort_ordre` ASC, `nom` ASC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $rows ? $rows : [];
    } catch (PDOException $e) {
        return [];
    }
}

function get_child_category_ids($parent_id) {
    global $db;
    $parent_id = (int) $parent_id;
    if ($parent_id <= 0) {
        return [];
    }
    try {
        $st = $db->prepare('SELECT `id` FROM `categories` WHERE `parent_id` = :p');
        $st->execute(['p' => $parent_id]);
        return array_map('intval', array_column($st->fetchAll(PDO::FETCH_ASSOC), 'id'));
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * IDs catégorie pour listing produits : si générale, inclut les sous-catégories
 */
function category_expanded_ids_for_products($categorie_id) {
    $cid = (int) $categorie_id;
    if ($cid <= 0) {
        return [];
    }
    if (!categories_hierarchy_enabled()) {
        return [$cid];
    }
    $c = get_categorie_by_id($cid);
    if (!$c) {
        return [];
    }
    if (categories_has_categorie_generale_id_column() && (int) ($c['admin_id'] ?? 0) <= 0) {
        $cg = get_categorie_generale_by_nom((string) ($c['nom'] ?? ''));
        if ($cg && (int) $cg['id'] > 0) {
            return categorie_generale_leaf_category_ids((int) $cg['id']);
        }
    }
    if (categorie_is_generale_plateforme($c)) {
        if (categories_has_categorie_generale_id_column()) {
            $cg = get_categorie_generale_by_nom((string) ($c['nom'] ?? ''));
            if ($cg) {
                return categorie_generale_leaf_category_ids((int) $cg['id']);
            }
        }
        $children = get_child_category_ids($cid);
        return array_values(array_unique(array_merge([$cid], $children)));
    }
    return [$cid];
}

function get_vendeur_subcategories_for_parent($vendeur_id, $parent_id) {
    global $db;
    $vendeur_id = (int) $vendeur_id;
    $parent_id = (int) $parent_id;
    if ($vendeur_id <= 0 || $parent_id <= 0 || !categories_hierarchy_enabled()) {
        return [];
    }
    try {
        $st = $db->prepare("
            SELECT * FROM `categories`
            WHERE `parent_id` = :p AND `admin_id` = :v
            ORDER BY `nom` ASC
        ");
        $st->execute(['p' => $parent_id, 'v' => $vendeur_id]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        return $rows ? $rows : [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Crée une sous-catégorie pour un vendeur sous une catégorie générale
 * @return int|false id
 */
function create_vendeur_subcategorie($nom, $categorie_generale_ou_parent_id, $admin_id) {
    global $db;
    $nom = trim((string) $nom);
    $ref_id = (int) $categorie_generale_ou_parent_id;
    $admin_id = (int) $admin_id;
    if ($nom === '' || $ref_id <= 0 || $admin_id <= 0 || !categories_hierarchy_enabled()) {
        return false;
    }
    try {
        if (categories_has_categorie_generale_id_column()) {
            $cg = get_categorie_generale_by_id($ref_id);
            if (!$cg) {
                return false;
            }
            $st = $db->prepare("
                INSERT INTO `categories` (`nom`, `description`, `image`, `date_creation`, `parent_id`, `categorie_generale_id`, `admin_id`, `sort_ordre`)
                VALUES (:nom, '', NULL, NOW(), NULL, :cgid, :aid, 0)
            ");
            if ($st->execute(['nom' => $nom, 'cgid' => $ref_id, 'aid' => $admin_id])) {
                return (int) $db->lastInsertId();
            }
            return false;
        }
        $parent = get_categorie_by_id($ref_id);
        if (!$parent || !categorie_is_generale_plateforme($parent)) {
            return false;
        }
        $st = $db->prepare("
            INSERT INTO `categories` (`nom`, `description`, `image`, `date_creation`, `parent_id`, `admin_id`, `sort_ordre`)
            VALUES (:nom, '', NULL, NOW(), :pid, :aid, 0)
        ");
        if ($st->execute(['nom' => $nom, 'pid' => $ref_id, 'aid' => $admin_id])) {
            return (int) $db->lastInsertId();
        }
    } catch (PDOException $e) {
        return false;
    }
    return false;
}

/**
 * Sous-catégories (vendeur) ayant au moins un produit actif sous une générale
 */
function get_subcategories_with_active_products_for_general($general_id, $boutique_admin_id = null) {
    global $db;
    $general_id = (int) $general_id;
    if ($general_id <= 0 || !categories_hierarchy_enabled()) {
        return [];
    }
    $boutique_admin_id = $boutique_admin_id !== null && $boutique_admin_id !== '' ? (int) $boutique_admin_id : null;
    $use_cg = categories_has_categorie_generale_id_column() && categories_generales_table_exists();
    try {
        if ($use_cg) {
            if ($boutique_admin_id !== null && $boutique_admin_id > 0) {
                $st = $db->prepare("
                    SELECT DISTINCT c.`id`, c.`nom`
                    FROM `categories` c
                    INNER JOIN `produits` p ON p.`categorie_id` = c.`id` AND p.`statut` = 'actif' AND p.`admin_id` = :aid
                    WHERE c.`categorie_generale_id` = :gid AND c.`admin_id` = :aid2
                    ORDER BY c.`nom` ASC
                ");
                $st->execute(['gid' => $general_id, 'aid' => $boutique_admin_id, 'aid2' => $boutique_admin_id]);
            } else {
                $st = $db->prepare("
                    SELECT DISTINCT c.`id`, c.`nom`
                    FROM `categories` c
                    INNER JOIN `produits` p ON p.`categorie_id` = c.`id` AND p.`statut` = 'actif'
                    WHERE c.`categorie_generale_id` = :gid AND c.`admin_id` IS NOT NULL
                    ORDER BY c.`nom` ASC
                ");
                $st->execute(['gid' => $general_id]);
            }
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
        if ($boutique_admin_id !== null && $boutique_admin_id > 0) {
            $st = $db->prepare("
                SELECT DISTINCT c.`id`, c.`nom`
                FROM `categories` c
                INNER JOIN `produits` p ON p.`categorie_id` = c.`id` AND p.`statut` = 'actif' AND p.`admin_id` = :aid
                WHERE c.`parent_id` = :gid AND c.`admin_id` = :aid2
                ORDER BY c.`nom` ASC
            ");
            $st->execute(['gid' => $general_id, 'aid' => $boutique_admin_id, 'aid2' => $boutique_admin_id]);
        } else {
            $st = $db->prepare("
                SELECT DISTINCT c.`id`, c.`nom`
                FROM `categories` c
                INNER JOIN `produits` p ON p.`categorie_id` = c.`id` AND p.`statut` = 'actif'
                WHERE c.`parent_id` = :gid AND c.`admin_id` IS NOT NULL
                ORDER BY c.`nom` ASC
            ");
            $st->execute(['gid' => $general_id]);
        }
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Arbre pour méga-menu / nav : générales + sous-catégories avec produits publiés
 * @param int|null $boutique_admin_id filtre vendeur (boutique) ou null = tout le marketplace
 */
function get_megamenu_categories($boutique_admin_id = null) {
    $generals = get_general_categories_ordered();
    $out = [];
    foreach ($generals as $g) {
        $gid = (int) $g['id'];
        $subs = get_subcategories_with_active_products_for_general($gid, $boutique_admin_id);
        $out[] = [
            'general' => $g,
            'subcategories' => $subs,
        ];
    }
    return $out;
}

/**
 * JSON pour formulaire produit vendeur : { "generaleId": [ {id,nom}, ... ], ... }
 */
function get_vendeur_subcategories_grouped_json($vendeur_id) {
    global $db;
    $vendeur_id = (int) $vendeur_id;
    if ($vendeur_id <= 0 || !categories_hierarchy_enabled()) {
        return '{}';
    }
    try {
        if (categories_has_categorie_generale_id_column()) {
            $st = $db->prepare("
                SELECT `id`, `nom`, `categorie_generale_id` FROM `categories`
                WHERE `admin_id` = :v AND `categorie_generale_id` IS NOT NULL AND `categorie_generale_id` > 0
                ORDER BY `categorie_generale_id` ASC, `nom` ASC
            ");
            $st->execute(['v' => $vendeur_id]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $grouped = [];
            foreach ($rows as $r) {
                $p = (string) (int) $r['categorie_generale_id'];
                if (!isset($grouped[$p])) {
                    $grouped[$p] = [];
                }
                $grouped[$p][] = ['id' => (int) $r['id'], 'nom' => $r['nom']];
            }
            return json_encode($grouped, JSON_UNESCAPED_UNICODE);
        }
        $st = $db->prepare("
            SELECT `id`, `nom`, `parent_id` FROM `categories`
            WHERE `admin_id` = :v AND `parent_id` IS NOT NULL AND `parent_id` > 0
            ORDER BY `parent_id` ASC, `nom` ASC
        ");
        $st->execute(['v' => $vendeur_id]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $grouped = [];
        foreach ($rows as $r) {
            $p = (string) (int) $r['parent_id'];
            if (!isset($grouped[$p])) {
                $grouped[$p] = [];
            }
            $grouped[$p][] = ['id' => (int) $r['id'], 'nom' => $r['nom']];
        }
        return json_encode($grouped, JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        return '{}';
    }
}

/**
 * Toutes les sous-catégories vendeur pour selects (avec nom du rayon)
 */
function get_all_vendeur_subcategories_for_form($vendeur_id) {
    global $db;
    $vendeur_id = (int) $vendeur_id;
    if ($vendeur_id <= 0 || !categories_hierarchy_enabled()) {
        return [];
    }
    try {
        if (categories_has_categorie_generale_id_column() && categories_generales_table_exists()) {
            $st = $db->prepare("
                SELECT c.`id`, c.`nom`, c.`categorie_generale_id`, cg.`nom` AS `generale_nom`, cg.`sort_ordre` AS `generale_sort`
                FROM `categories` c
                LEFT JOIN `categories_generales` cg ON cg.`id` = c.`categorie_generale_id`
                WHERE c.`admin_id` = :v
                   OR (c.`admin_id` IS NULL AND EXISTS (
                        SELECT 1 FROM `produits` p
                        WHERE p.`categorie_id` = c.`id` AND p.`admin_id` = :v2
                   ))
                ORDER BY cg.`sort_ordre` ASC, cg.`nom` ASC, c.`nom` ASC
            ");
            $st->execute(['v' => $vendeur_id, 'v2' => $vendeur_id]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
        $st = $db->prepare("
            SELECT c.`id`, c.`nom`, c.`parent_id`,
                   p.`nom` AS `generale_nom`, p.`sort_ordre` AS `generale_sort`
            FROM `categories` c
            LEFT JOIN `categories` p ON p.`id` = c.`parent_id`
            WHERE c.`admin_id` = :v
               OR (c.`admin_id` IS NULL AND EXISTS (
                    SELECT 1 FROM `produits` p
                    WHERE p.`categorie_id` = c.`id` AND p.`admin_id` = :v2
               ))
            ORDER BY p.`sort_ordre` ASC, p.`nom` ASC, c.`nom` ASC
        ");
        $st->execute(['v' => $vendeur_id, 'v2' => $vendeur_id]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Rattache la sous-catégorie vendeur au rayon choisi (categories.categorie_generale_id).
 */
function vendeur_align_subcategorie_generale($categorie_id, $categorie_generale_id, $vendeur_admin_id) {
    $categorie_id = (int) $categorie_id;
    $categorie_generale_id = (int) $categorie_generale_id;
    $vendeur_admin_id = (int) $vendeur_admin_id;
    if ($categorie_id <= 0 || $categorie_generale_id <= 0 || $vendeur_admin_id <= 0) {
        return;
    }
    if (!categories_has_categorie_generale_id_column()) {
        return;
    }
    $c = get_categorie_by_id($categorie_id);
    if (!$c || (int) ($c['admin_id'] ?? 0) !== $vendeur_admin_id) {
        return;
    }
    global $db;
    try {
        $st = $db->prepare('
            UPDATE `categories`
            SET `categorie_generale_id` = :g
            WHERE `id` = :id AND `admin_id` = :v
        ');
        $st->execute(['g' => $categorie_generale_id, 'id' => $categorie_id, 'v' => $vendeur_admin_id]);
    } catch (PDOException $e) {
    }
}

?>