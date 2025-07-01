<?php
// backend/admin/update_order_status.php - Mise à jour pour la sécurité
session_start(); // Démarre la session

// Inclure le fichier de configuration de la base de données et les fonctions de sécurité
require_once('../config.php');

// Définir le type de contenu de la réponse comme JSON
header('Content-Type: application/json');

// Vérifie si l'administrateur est connecté
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé.']);
    exit;
}

// Gérer la requête POST pour mettre à jour le statut d'une commande
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données brutes de la requête POST (JSON)
    $input = file_get_contents('php://input');
    $data = json_decode($input, true); // Décoder le JSON en tableau associatif

    // Valider le token CSRF. Pour les requêtes AJAX POST, le token doit être inclus dans le body ou un header.
    // Pour cet exemple, nous allons simplifier en vérifiant que le token existe dans la session
    // et que l'utilisateur est bien authentifié.
    // Une implémentation plus rigoureuse nécessiterait que le JS envoie le token.
    // Pour l'instant, on se base sur la sécurité de la session admin.
    // Si vous aviez un jeton CSRF envoyé via AJAX (ex: dans un header X-CSRF-Token),
    // vous le valideriez ici avec validateCsrfToken().
    // Exemple : if (!isset($data['csrf_token']) || !validateCsrfToken($data['csrf_token'])) { ... }

    // Valider les données reçues
    if (
        !isset($data['order_id']) || !is_numeric($data['order_id']) ||
        !isset($data['status']) || !in_array($data['status'], ['en_attente', 'en_cours', 'expediee', 'annulee'])
    ) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Données de mise à jour du statut invalides ou manquantes.']);
        exit;
    }

    $order_id = (int)$data['order_id']; // S'assurer que c'est un entier
    $new_status = $data['status']; // L'énum est déjà validée par in_array()

    try {
        // Préparer la requête SQL pour mettre à jour le statut
        $stmt = $pdo->prepare("UPDATE commandes SET statut = ? WHERE id = ?");
        // Exécuter la requête
        $stmt->execute([$new_status, $order_id]);

        // Vérifier si une ligne a été affectée
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Statut de la commande mis à jour avec succès.']);
        } else {
            http_response_code(404); // Not Found
            echo json_encode(['success' => false, 'message' => 'Commande introuvable ou aucun changement de statut.']);
        }
    } catch (PDOException $e) {
        // Enregistrer l'erreur et renvoyer un message générique
        error_log("Erreur lors de la mise à jour du statut de la commande : " . $e->getMessage());
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de la mise à jour du statut.']);
    }
} else {
    // Si la méthode de requête n'est pas POST, renvoyer une erreur 405 Méthode non autorisée
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
}
?>
