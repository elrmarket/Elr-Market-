-- SQL Schema de la base de données 'Elr_code'
-- Ce script crée les tables et insère des données d'exemple pour la boutique 'Elr Market'.

-- Suppression de la base de données si elle existe pour assurer un nouveau départ propre.
-- ATTENTION: Cette commande effacera toutes les données existantes dans 'Elr_code'.
DROP DATABASE IF EXISTS `Elr_code`;

-- Création de la base de données si elle n'existe pas
CREATE DATABASE IF NOT EXISTS `Elr_code` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Sélection de la base de données pour les opérations suivantes
USE `Elr_code`;

--
-- Table: `fournisseurs` - Stocke les informations sur les fournisseurs des produits.
--
DROP TABLE IF EXISTS `fournisseurs`; -- Supprime la table si elle existe déjà
CREATE TABLE `fournisseurs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(255) NOT NULL COMMENT 'Nom du fournisseur',
    `contact_email` VARCHAR(255) COMMENT 'Adresse email du contact fournisseur',
    `contact_tel` VARCHAR(50) COMMENT 'Numéro de téléphone du contact fournisseur'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table: `produits` - Stocke les informations sur les articles vendus dans la boutique.
-- Inclut directement la clé étrangère `fournisseur_id`.
--
DROP TABLE IF EXISTS `produits`; -- Supprime la table si elle existe déjà
CREATE TABLE `produits` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(255) NOT NULL COMMENT 'Nom du produit',
    `description` TEXT COMMENT 'Description détaillée du produit',
    `prix` DECIMAL(10, 2) NOT NULL COMMENT 'Prix du produit (ex: 12.99)',
    `image` VARCHAR(255) COMMENT 'URL ou chemin de l''image du produit',
    `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Date et heure de création du produit',
    `fournisseur_id` INT COMMENT 'ID du fournisseur du produit',
    CONSTRAINT `fk_produit_fournisseur` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table: `commandes` - Enregistre les informations sur les commandes passées par les clients.
--
DROP TABLE IF EXISTS `commandes`; -- Supprime la table si elle existe déjà
CREATE TABLE `commandes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `client_nom` VARCHAR(255) NOT NULL COMMENT 'Nom complet du client',
    `client_email` VARCHAR(255) NOT NULL COMMENT 'Adresse email du client',
    `client_adresse` TEXT NOT NULL COMMENT 'Adresse de livraison du client',
    `produits_commandes` JSON NOT NULL COMMENT 'Tableau JSON des produits commandés [{produit_id, quantite, prix_unitaire}]',
    `statut` ENUM('en_attente', 'en_cours', 'expediee', 'annulee') DEFAULT 'en_attente' COMMENT 'Statut actuel de la commande',
    `date_commande` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Date et heure de la commande',
    `total_commande` DECIMAL(10, 2) NOT NULL COMMENT 'Montant total de la commande'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table: `admin_users` - Gère les comptes des administrateurs pour le panneau d'administration.
--
DROP TABLE IF EXISTS `admin_users`; -- Supprime la table si elle existe déjà
CREATE TABLE `admin_users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Nom d''utilisateur de l''administrateur',
    `password_hash` VARCHAR(255) NOT NULL COMMENT 'Mot de passe haché de l''administrateur (BCrypt recommandé)',
    `email` VARCHAR(255) UNIQUE COMMENT 'Email de l''administrateur (optionnel, pour récupération mdp)',
    `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création du compte admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


--
-- Insertion de données d'exemple
--

-- Insertion de données d'exemple pour les fournisseurs
INSERT INTO `fournisseurs` (`nom`, `contact_email`, `contact_tel`) VALUES
('TechGlobal Fournisseur', 'contact@techglobal.com', '123-456-7890'),
('Elektronik Plus', 'info@elektronikplus.net', '987-654-3210');

-- Insertion de données d'exemple pour les produits
-- Les `fournisseur_id` correspondent aux IDs insérés ci-dessus.
INSERT INTO `produits` (`nom`, `description`, `prix`, `image`, `fournisseur_id`) VALUES
('Smartphone "Elr Phone X"', 'Un smartphone de dernière génération avec un appareil photo haute résolution et une batterie longue durée.', 799.99, 'https://placehold.co/400x300/a0a0a0/ffffff?text=Elr+Phone+X', 1),
('Casque Audio "Elr Sound Pro"', 'Casque sans fil avec technologie de réduction de bruit avancée et son immersif.', 199.99, 'https://placehold.co/400x300/b0b0b0/ffffff?text=Elr+Sound+Pro', 1),
('Smartwatch "Elr TimeFit"', 'Montre connectée élégante avec suivi de la fréquence cardiaque, GPS et autonomie de 7 jours.', 249.50, 'https://placehold.co/400x300/c0c0c0/ffffff?text=Elr+TimeFit', 2),
('Ordinateur Portable "Elr Book Air"', 'Ultra-léger et ultra-performant, idéal pour les professionnels en déplacement. Écran Retina.', 1499.00, 'https://placehold.co/400x300/d0d0d0/ffffff?text=Elr+Book+Air', 2),
('Tablette "Elr Tab Pro"', 'Une tablette polyvalente avec un grand écran et une performance optimisée pour le jeu et le travail créatif.', 499.99, 'https://placehold.co/400x300/e0e0e0/ffffff?text=Elr+Tab+Pro', 1),
('Webcam HD "Elr Cam"', 'Webcam Full HD 1080p avec correction automatique de la lumière pour des appels vidéo clairs.', 49.99, 'https://placehold.co/400x300/f0f0f0/333333?text=Elr+Cam', 1);

-- Insertion de données d'exemple pour les commandes
-- Note: 'produits_commandes' est un JSON array. Assurez-vous que les IDs de produit existent.
INSERT INTO `commandes` (`client_nom`, `client_email`, `client_adresse`, `produits_commandes`, `statut`, `total_commande`) VALUES
('Alice Dubois', 'alice.dubois@email.com', '15 Rue des Écoles, 75005 Paris', '[{"produit_id": 1, "quantite": 1, "prix_unitaire": 799.99}]', 'en_attente', 799.99),
('Bernard Durand', 'bernard.d@email.com', '2 Place du Marché, 13001 Marseille', '[{"produit_id": 2, "quantite": 2, "prix_unitaire": 199.99}, {"produit_id": 3, "quantite": 1, "prix_unitaire": 249.50}]', 'expediee', 649.48),
('Carole Lefevre', 'carole.l@email.com', '8 Avenue de la Liberté, 59000 Lille', '[{"produit_id": 4, "quantite": 1, "prix_unitaire": 1499.00}, {"produit_id": 6, "quantite": 3, "prix_unitaire": 49.99}]', 'en_cours', 1648.97);

-- Insertion de l'utilisateur administrateur par défaut
-- Mot de passe: 'password123' haché avec un algorithme de hachage fort comme BCrypt.
-- IMPORTANT: Le hash ci-dessous est un EXEMPLE.
-- En production, générez toujours un NOUVEAU hash sécurisé pour chaque mot de passe.
-- Vous pouvez utiliser password_hash('votre_mot_de_passe', PASSWORD_DEFAULT) en PHP,
-- ou des fonctions similaires dans d'autres langages.
INSERT INTO `admin_users` (`username`, `password_hash`, `email`) VALUES
('admin', '$2y$10$FakeHashForExampleOnly.DoNotInProduction!!', 'admin@elrmarket.com');
