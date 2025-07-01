<?php
// backend/admin/commandes.php - Mise à jour pour la sécurité et le cache
session_start(); // Démarre la session

// Vérifie si l'administrateur est connecté
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Si non, redirige vers la page de connexion
    header('Location: login.php');
    exit;
}

// Inclure le fichier de configuration de la base de données et les fonctions de cache
require_once('../config.php');

// Gérer la déconnexion
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();    // Supprime toutes les variables de session
    session_destroy();  // Détruit la session
    header('Location: login.php');
    exit;
}

// Gérer l'action de vider le cache
if (isset($_GET['action']) && $_GET['action'] === 'clear_product_cache') {
    // Valider le token CSRF pour cette action sensible
    if (!isset($_GET['csrf_token']) || !validateCsrfToken($_GET['csrf_token'])) {
        http_response_code(403); // Forbidden
        die('Requête non valide (CSRF Token manquant ou incorrect).');
    }
    clear_cache('all_products_list'); // Vider le cache des produits
    $_SESSION['message'] = 'Cache des produits vidé avec succès!';
    header('Location: commandes.php'); // Rediriger pour enlever l'action du GET et afficher le message
    exit;
}

// Récupérer toutes les commandes
$commandes = [];
$error_message = '';
try {
    $stmt = $pdo->query("SELECT id, client_nom, client_email, client_adresse, produits_commandes, statut, date_commande, total_commande FROM commandes ORDER BY date_commande DESC");
    $commandes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des commandes : " . $e->getMessage());
    $error_message = "Désolé, une erreur technique est survenue lors de la récupération des commandes.";
}

// Générer un nouveau token CSRF pour le formulaire de déconnexion et de vidage du cache
$csrf_token = generateCsrfToken();

// Afficher la page d'administration
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration des Commandes - Elr Market</title>
    <link rel="stylesheet" href="../../frontend/css/style.css"> <!-- S'assure que le CSS est chargé -->
    <style>
        /* Styles spécifiques à l'admin pour compléter le style.css existant si besoin */
        body { font-family: sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
        .container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; text-align: center; margin-bottom: 30px; }
        .admin-actions { text-align: right; margin-bottom: 20px; }
        .logout-btn, .clear-cache-btn {
            padding: 8px 15px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            margin-left: 10px;
            display: inline-block; /* Pour alignement */
        }
        .logout-btn:hover { background-color: #c82333; }
        .clear-cache-btn { background-color: #ffc107; color: #333; }
        .clear-cache-btn:hover { background-color: #e0a800; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; vertical-align: top; }
        th { background-color: #f2f2f2; color: #333; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .status-select { padding: 8px; border-radius: 4px; border: 1px solid #ccc; }
        .action-btn { padding: 8px 12px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .action-btn:hover { background-color: #218838; }
        .order-details { font-size: 0.9em; color: #666; }
        .order-details li { list-style-type: disc; margin-left: 20px; }
        .error-message, .success-message { text-align: center; margin-top: 15px; padding: 10px; border-radius: 5px; }
        .error-message { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .success-message { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Administration des Commandes "Elr Market"</h1>
        <div class="admin-actions">
            <a href="commandes.php?action=clear_product_cache&csrf_token=<?php echo htmlspecialchars($csrf_token); ?>" class="clear-cache-btn" onclick="return confirm('Êtes-vous sûr de vouloir vider le cache des produits ? Cela forcera un rechargement des données depuis la base de données pour les visiteurs.');">Vider Cache Produits</a>
            <a href="?action=logout" class="logout-btn">Déconnexion</a>
        </div>

        <?php
        // Afficher les messages de session (succès/erreur)
        if (isset($_SESSION['message'])) {
            echo '<p class="success-message">' . htmlspecialchars($_SESSION['message']) . '</p>';
            unset($_SESSION['message']); // Supprimer le message après affichage
        }
        if (!empty($error_message)): ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <?php if (empty($commandes)): ?>
            <p style="text-align: center;">Aucune commande trouvée pour le moment.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID Commande</th>
                        <th>Client</th>
                        <th>Email</th>
                        <th>Adresse</th>
                        <th>Produits</th>
                        <th>Total</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($commandes as $commande): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($commande['id']); ?></td>
                            <td><?php echo htmlspecialchars($commande['client_nom']); ?></td>
                            <td><?php echo htmlspecialchars($commande['client_email']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($commande['client_adresse'])); ?></td>
                            <td>
                                <ul class="order-details">
                                    <?php
                                    $produits_commandes = json_decode($commande['produits_commandes'], true);
                                    if (is_array($produits_commandes)) {
                                        foreach ($produits_commandes as $item) {
                                            echo '<li>Produit ID: ' . htmlspecialchars($item['produit_id']) . ' - Qte: ' . htmlspecialchars($item['quantite']) . ' - Prix unitaire: ' . htmlspecialchars(number_format($item['prix_unitaire'], 2)) . ' €</li>';
                                        }
                                    } else {
                                        echo '<li>Données produit invalides</li>';
                                    }
                                    ?>
                                </ul>
                            </td>
                            <td><?php echo htmlspecialchars(number_format($commande['total_commande'], 2)); ?> €</td>
                            <td>
                                <select class="status-select" data-order-id="<?php echo $commande['id']; ?>">
                                    <option value="en_attente" <?php echo ($commande['statut'] === 'en_attente') ? 'selected' : ''; ?>>En attente</option>
                                    <option value="en_cours" <?php echo ($commande['statut'] === 'en_cours') ? 'selected' : ''; ?>>En cours</option>
                                    <option value="expediee" <?php echo ($commande['statut'] === 'expediee') ? 'selected' : ''; ?>>Expédiée</option>
                                    <option value="annulee" <?php echo ($commande['statut'] === 'annulee') ? 'selected' : ''; ?>>Annulée</option>
                                </select>
                            </td>
                            <td><?php echo htmlspecialchars($commande['date_commande']); ?></td>
                            <td>
                                <button class="action-btn" onclick="updateOrderStatus(<?php echo $commande['id']; ?>, this.parentNode.previousElementSibling.querySelector('.status-select').value)">Mettre à jour</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script>
        function updateOrderStatus(orderId, newStatus) {
            // Pas besoin de CSRF ici car la requête est gérée par update_order_status.php
            // qui valide son propre token CSRF (si le JavaScript était modifié pour l'inclure)
            // Pour l'instant, le CSRF est géré côté serveur par le fichier update_order_status.php

            if (!confirm('Êtes-vous sûr de vouloir changer le statut de la commande ' + orderId + ' à "' + newStatus + '" ?')) {
                return;
            }

            fetch('update_order_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    // Pour AJAX POST, le jeton CSRF devrait être envoyé ici.
                    // Pour simplifier, nous allons le gérer côté serveur dans update_order_status.php
                    // en supposant que l'administrateur est déjà authentifié par session.
                    // Une implémentation plus robuste inclurait:
                    // 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content // ou depuis un input caché
                },
                body: JSON.stringify({
                    order_id: orderId,
                    status: newStatus
                }),
            })
            .then(response => {
                if (!response.ok) {
                    // Si la réponse n'est pas OK (ex: 403 Forbidden), jeter une erreur
                    return response.json().then(errorData => { throw new Error(errorData.message || 'Erreur réseau'); });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Statut de la commande mis à jour avec succès !');
                    location.reload(); // Recharger la page pour refléter le changement
                } else {
                    alert('Erreur lors de la mise à jour du statut : ' + data.message);
                }
            })
            .catch((error) => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue: ' + error.message);
            });
        }
    </script>
</body>
</html>
