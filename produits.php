<?php
// backend/api/produits.php - Mise à jour pour le cache

// Inclure le fichier de configuration de la base de données et les fonctions de cache
require_once('../config.php');

// Définir le type de contenu de la réponse comme JSON
header('Content-Type: application/json');

// Clé de cache pour la liste des produits
$cache_key = 'all_products_list';

// Gérer la requête GET pour récupérer les produits
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Tenter de récupérer les données depuis le cache
    $produits = get_cached_data($cache_key);

    if ($produits !== false) {
        // Si les données sont dans le cache et sont valides, les renvoyer immédiatement
        echo json_encode(['success' => true, 'data' => $produits, 'source' => 'cache']);
    } else {
        // Sinon, récupérer depuis la base de données
        try {
            // Préparer la requête SQL pour récupérer tous les produits
            $stmt = $pdo->query("SELECT id, nom, description, prix, image FROM produits ORDER BY nom ASC");
            // Exécuter la requête et récupérer tous les résultats
            $produits = $stmt->fetchAll();

            // Stocker les données dans le cache pour les futures requêtes
            set_cached_data($cache_key, $produits);

            // Renvoyer les produits au format JSON
            echo json_encode(['success' => true, 'data' => $produits, 'source' => 'database']);
        } catch (PDOException $e) {
            // En cas d'erreur, enregistre l'erreur et renvoie une réponse d'erreur JSON générique
            error_log("Erreur lors de la récupération des produits : " . $e->getMessage());
            http_response_code(500); // Internal Server Error
            echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de la récupération des produits.']);
        }
    }
} else {
    // Si la méthode de requête n'est pas GET, renvoyer une erreur 405 Méthode non autorisée
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
}
?>
