<?php
// backend/api/commande.php

// Inclure le fichier de configuration de la base de données
require_once('../config.php');

// Définir le type de contenu de la réponse comme JSON
header('Content-Type: application/json');

// Gérer la requête POST pour passer une commande
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données brutes de la requête POST (JSON)
    $input = file_get_contents('php://input');
    $data = json_decode($input, true); // Décoder le JSON en tableau associatif

    // Valider les données reçues
    if (
        !isset($data['client_nom']) || empty($data['client_nom']) ||
        !isset($data['client_email']) || !filter_var($data['client_email'], FILTER_VALIDATE_EMAIL) ||
        !isset($data['client_adresse']) || empty($data['client_adresse']) ||
        !isset($data['produits']) || !is_array($data['produits']) || empty($data['produits'])
    ) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Données de commande invalides ou manquantes.']);
        exit;
    }

    $client_nom = $data['client_nom'];
    $client_email = $data['client_email'];
    $client_adresse = $data['client_adresse'];
    $produits_commandes_array = $data['produits']; // Tableau des produits avec id, quantite, prix_unitaire

    // Calculer le total de la commande
    $total_commande = 0;
    foreach ($produits_commandes_array as $item) {
        if (!isset($item['produit_id']) || !isset($item['quantite']) || !isset($item['prix_unitaire']) ||
            !is_numeric($item['quantite']) || $item['quantite'] <= 0 || !is_numeric($item['prix_unitaire']) || $item['prix_unitaire'] < 0) {
            http_response_code(400); // Bad Request
            echo json_encode(['success' => false, 'message' => 'Détails des produits commandés invalides.']);
            exit;
        }
        $total_commande += ($item['quantite'] * $item['prix_unitaire']);
    }

    // Convertir le tableau des produits commandés en JSON string pour le stockage
    $produits_commandes_json = json_encode($produits_commandes_array);

    try {
        // Préparer la requête SQL pour insérer une nouvelle commande
        $stmt = $pdo->prepare("INSERT INTO commandes (client_nom, client_email, client_adresse, produits_commandes, total_commande) VALUES (?, ?, ?, ?, ?)");
        // Exécuter la requête avec les données
        $stmt->execute([$client_nom, $client_email, $client_adresse, $produits_commandes_json, $total_commande]);

        // Renvoyer une réponse de succès
        echo json_encode(['success' => true, 'message' => 'Commande passée avec succès!', 'commande_id' => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        // En cas d'erreur, renvoyer une réponse d'erreur JSON
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement de la commande : ' . $e->getMessage()]);
    }
} else {
    // Si la méthode de requête n'est pas POST, renvoyer une erreur 405 Méthode non autorisée
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
}
?>
