<?php
// backend/config.php - Mise à jour pour la sécurité et le cache

// --- Configuration de l'environnement ---
// Définir à 'development' pour afficher les erreurs détaillées (utile pour le débogage)
// Définir à 'production' pour masquer les erreurs détaillées aux utilisateurs et les logguer (recommandé en production)
define('ENVIRONMENT', 'development'); // Changez ceci à 'production' pour votre déploiement final

if (ENVIRONMENT === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0); // Ne pas afficher les erreurs aux utilisateurs
    ini_set('log_errors', 1);     // Enregistrer les erreurs dans un fichier log
    ini_set('error_log', __DIR__ . '/php_error.log'); // Chemin du fichier log
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED); // Rapporter toutes les erreurs sauf les notices et dépréciations
}

// --- Informations de connexion à la base de données ---
define('DB_HOST', 'localhost'); // Ou l'adresse de votre serveur MySQL si ce n'est pas localhost
define('DB_USER', 'root');     // Votre nom d'utilisateur MySQL
define('DB_PASS', '');         // Votre mot de passe MySQL
define('DB_NAME', 'Elr_code'); // Le nom de la base de données que nous avons créé

// Tente de se connecter à la base de données
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    // Configure PDO pour lancer des exceptions en cas d'erreur
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Définit le mode de récupération par défaut des résultats comme des tableaux associatifs
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO_FETCH_ASSOC);
} catch (PDOException $e) {
    // En cas d'erreur de connexion, enregistre l'erreur et affiche un message générique
    error_log("Erreur de connexion à la base de données : " . $e->getMessage());
    die("Désolé, une erreur technique est survenue. Veuillez réessayer plus tard.");
}

// --- Fonction pour générer un token CSRF (Cross-Site Request Forgery) ---
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Génère un token cryptographiquement sécurisé
    }
    return $_SESSION['csrf_token'];
}

// --- Fonction pour valider un token CSRF ---
function validateCsrfToken($token) {
    if (empty($token) || empty($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    unset($_SESSION['csrf_token']); // Supprimer le token après utilisation pour les requêtes sensibles
    return true;
}

// --- Fonctions de cache simples (basées sur des fichiers) ---
define('CACHE_DIR', __DIR__ . '/../cache/'); // Répertoire pour les fichiers de cache
define('CACHE_LIFETIME', 3600); // Durée de vie du cache en secondes (ici, 1 heure)

// Créer le répertoire de cache s'il n'existe pas
if (!is_dir(CACHE_DIR)) {
    mkdir(CACHE_DIR, 0755, true);
}

function get_cached_data($key) {
    $file = CACHE_DIR . md5($key) . '.cache'; // Utiliser MD5 du nom pour le nom de fichier
    if (file_exists($file) && (filemtime($file) + CACHE_LIFETIME > time())) {
        return unserialize(file_get_contents($file));
    }
    return false; // Cache invalide ou non trouvé
}

function set_cached_data($key, $data) {
    $file = CACHE_DIR . md5($key) . '.cache';
    file_put_contents($file, serialize($data));
}

function clear_cache($key = null) {
    if ($key) {
        $file = CACHE_DIR . md5($key) . '.cache';
        if (file_exists($file)) {
            unlink($file);
        }
    } else {
        // Supprimer tous les fichiers .cache
        $files = glob(CACHE_DIR . '*.cache');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}

?>
