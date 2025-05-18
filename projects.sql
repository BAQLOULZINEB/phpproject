-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : dim. 18 mai 2025 à 15:42
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `projects`
--

-- --------------------------------------------------------

--
-- Structure de la table `brand`
--

CREATE TABLE `brand` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `brand`
--

INSERT INTO `brand` (`id`, `name`) VALUES
(1, 'Estee Lauder'),
(2, 'Fenty Beauty'),
(3, 'Maybelline'),
(4, 'NARS'),
(5, 'MAC'),
(6, 'Charlotte Tilbury'),
(7, 'Dior'),
(8, 'NYX'),
(9, 'The Ordinary'),
(10, 'Drunk Elephant'),
(11, 'La Roche-Posay'),
(12, 'CeraVe'),
(13, 'Clinique'),
(14, 'Neutrogena'),
(15, 'Tatcha'),
(16, 'Olaplex'),
(17, 'Moroccanoil'),
(18, 'Pantene'),
(19, 'Briogeo'),
(20, 'Herbal Essences'),
(21, 'Living Proof'),
(22, 'Real Techniques'),
(23, 'Sigma Beauty'),
(24, 'EcoTools'),
(25, 'Sephora Collection'),
(26, 'Beautyblender'),
(27, 'Tweezerman'),
(28, 'Dyson'),
(29, 'GHD'),
(30, 'Chanel'),
(31, 'Jo Malone'),
(32, 'Yves Saint Laurent'),
(33, 'Victoria’s Secret'),
(34, 'Bath & Body Works'),
(35, 'Sol de Janeiro'),
(36, 'The Body Shop'),
(37, 'Urban Decay'),
(38, 'Huda Beauty'),
(39, 'Anastasia Beverly Hills'),
(40, 'Fresh'),
(41, 'SheaMoisture'),
(42, 'Simplehuman'),
(43, 'Fancii'),
(44, 'Conair'),
(45, 'Impressions Vanity'),
(46, 'Diptyque'),
(47, 'GlamGlow'),
(48, 'Origins'),
(49, 'Juice Beauty'),
(50, 'Herbivore Botanicals'),
(51, 'Dr. Bronner’s');

-- --------------------------------------------------------

--
-- Structure de la table `category`
--

CREATE TABLE `category` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `parent_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `category`
--

INSERT INTO `category` (`id`, `name`, `parent_id`) VALUES
(1, 'Makeup', NULL),
(2, 'Hair', NULL),
(3, 'Skin Care', NULL),
(4, 'Accessory', NULL),
(5, 'Lipstick', 1),
(6, 'Mascara', 1),
(7, 'Shampoo', 2),
(8, 'Cream', 3),
(9, 'Facial Mask', 3),
(10, 'Serum & Lotion', 3),
(11, 'Organic Products', 3),
(12, 'Serums', 3),
(13, 'Lashes', 1),
(14, 'Blush', 1),
(15, 'Cleanser', 3),
(16, 'Moisturizer', 3);

-- --------------------------------------------------------

--
-- Structure de la table `commande`
--

CREATE TABLE `commande` (
  `id_commande` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `date_commande` timestamp NOT NULL DEFAULT current_timestamp(),
  `statut_commande` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commande`
--

INSERT INTO `commande` (`id_commande`, `id_client`, `date_commande`, `statut_commande`) VALUES
(9, 2, '2025-05-11 22:41:19', 'Payée'),
(10, 2, '2025-05-11 22:51:21', 'Payée'),
(11, 2, '2025-05-11 22:51:54', 'Payée'),
(12, 2, '2025-05-11 22:52:05', 'En attente'),
(13, 2, '2025-05-11 22:53:28', 'Payée'),
(14, 2, '2025-05-11 22:54:15', 'Payée'),
(15, 2, '2025-05-12 18:47:06', 'En attente'),
(16, 2, '2025-05-12 18:48:44', 'Payée'),
(17, 2, '2025-05-12 18:57:17', 'Payée'),
(18, 2, '2025-05-12 19:47:08', 'Payée'),
(19, 2, '2025-05-12 20:20:13', 'Payée'),
(20, 2, '2025-05-12 21:12:02', 'Payée'),
(21, 10, '2025-05-13 17:15:22', 'PayÃ©e');

-- --------------------------------------------------------

--
-- Structure de la table `ligne_commande`
--

CREATE TABLE `ligne_commande` (
  `id_commande` int(11) NOT NULL,
  `id_produit` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `ligne_commande`
--

INSERT INTO `ligne_commande` (`id_commande`, `id_produit`, `quantite`, `prix_unitaire`) VALUES
(9, 16, 1, 69.00),
(10, 12, 1, 29.99),
(11, 18, 1, 24.00),
(12, 54, 1, 39.99),
(13, 52, 1, 12.99),
(14, 55, 1, 49.99),
(15, 16, 1, 69.00),
(16, 12, 1, 29.99),
(17, 17, 1, 30.00),
(18, 18, 1, 24.00),
(18, 4, 1, 49.00),
(19, 16, 1, 69.00),
(20, 18, 7, 24.00),
(20, 64, 2, 10.99),
(21, 20, 1, 36.00),
(21, 12, 1, 29.99);

-- --------------------------------------------------------

--
-- Structure de la table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` varchar(50) NOT NULL,
  `subcategory` varchar(50) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `image_url` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `category`, `subcategory`, `brand`, `image_url`) VALUES
(2, 'Fenty Beauty Pro Filt\'r Soft Matte Longwear Foundation', 'Soft matte, longwear foundation.', 40.00, 'Makeup', 'foundation', 'Fenty Beauty', ''),
(3, 'Maybelline Fit Me Matte + Poreless Foundation', 'Natural matte finish for normal to oily skin.', 7.99, 'Makeup', 'foundation', 'Maybelline', ''),
(4, 'NARS Natural Radiant Longwear Foundation', 'Lightweight, radiant finish foundation.', 49.00, 'Makeup', 'foundation', 'NARS', ''),
(5, 'MAC Matte Lipstick - Ruby Woo', 'Iconic matte red lipstick.', 19.00, 'Makeup', 'Lipstick', 'MAC', ''),
(6, 'Charlotte Tilbury Matte Revolution Lipstick - Pillow Talk', 'Nude-pink matte lipstick.', 34.00, 'Makeup', 'Lipstick', 'Charlotte Tilbury', ''),
(7, 'Dior Rouge Dior Lipstick', 'Long-wear couture color lipstick.', 42.00, 'Makeup', 'Lipstick', 'Dior', ''),
(8, 'NYX Professional Makeup Soft Matte Lip Cream', 'Velvety smooth matte lip cream.', 6.50, 'Makeup', 'Lipstick', 'NYX', ''),
(9, 'The Ordinary Hyaluronic Acid B5', 'Hydrating serum with hyaluronic acid.', 8.90, 'Skin Care', 'Serum & Lotion', 'The Ordinary', ''),
(10, 'Estee Lauder Advanced Night Repair Serum', 'Anti-aging night serum.', 75.00, 'Skin Care', 'Serum & Lotion', 'Estee Lauder', ''),
(11, 'Drunk Elephant C-Firma Fresh Day Serum', 'Vitamin C serum for brightening.', 78.00, 'Skin Care', 'Serum & Lotion', 'Drunk Elephant', ''),
(12, 'La Roche-Posay Hyalu B5 Serum', 'Anti-wrinkle, hydrating serum.', 29.99, 'Skin Care', 'serum', 'La Roche-Posay', ''),
(13, 'CeraVe Moisturizing Cream', 'Rich, non-greasy moisturizer.', 16.99, 'Skin Care', 'Moisturizer', 'CeraVe', ''),
(14, 'Clinique Moisture Surge 72-Hour Auto-Replenishing Hydrator', 'Gel-cream moisturizer.', 39.00, 'Skin Care', 'Moisturizer', 'Clinique', ''),
(15, 'Neutrogena Hydro Boost Water Gel', 'Lightweight, hydrating gel moisturizer.', 18.99, 'Skin Care', 'Moisturizer', 'Neutrogena', ''),
(16, 'Tatcha The Dewy Skin Cream', 'Rich cream for plump, dewy skin.', 69.00, 'Skin Care', 'moisturizer', 'Tatcha', ''),
(17, 'Olaplex No.4 Bond Maintenance Shampoo', 'Repairs and protects hair.', 30.00, 'Hair', 'Shampoo', 'Olaplex', ''),
(18, 'Moroccanoil Moisture Repair Shampoo', 'Restores moisture and strength.', 24.00, 'Hair', 'Shampoo', 'Moroccanoil', ''),
(19, 'Pantene Pro-V Daily Moisture Renewal Shampoo', 'Hydrating shampoo for daily use.', 5.99, 'Hair', 'Shampoo', 'Pantene', ''),
(20, 'Briogeo Don\'t Despair, Repair! Super Moisture Shampoo', 'Deeply hydrating shampoo.', 36.00, 'Hair', 'shampoo', 'Briogeo', ''),
(21, 'Olaplex No.5 Bond Maintenance Conditioner', 'Repairs and strengthens hair.', 30.00, 'Hair', 'conditioner', 'Olaplex', ''),
(22, 'Moroccanoil Hydrating Conditioner', 'Hydrates and detangles hair.', 24.00, 'Hair', 'conditioner', 'Moroccanoil', ''),
(23, 'Herbal Essences Hello Hydration Conditioner', 'Moisturizing conditioner with coconut.', 4.99, 'Hair', 'conditioner', 'Herbal Essences', ''),
(24, 'Living Proof Restore Conditioner', 'Restores damaged hair.', 32.00, 'Hair', 'conditioner', 'Living Proof', ''),
(25, 'Real Techniques Everyday Essentials Brush Set', '5-piece makeup brush set.', 19.99, 'accessories', 'Brushes', 'Real Techniques', ''),
(26, 'Sigma Beauty Essential Kit', '12 professional makeup brushes.', 160.00, 'accessories', 'Brushes', 'Sigma Beauty', ''),
(27, 'EcoTools Start the Day Beautifully Kit', 'Eco-friendly makeup brush set.', 12.99, 'accessories', 'Brushes', 'EcoTools', ''),
(41, 'Urban Decay Naked3 Eyeshadow Palette', '12 rose-hued neutral shades.', 54.00, 'Makeup', 'eyeshadow', 'Urban Decay', ''),
(42, 'Huda Beauty Rose Quartz Eyeshadow Palette', '18 shades inspired by rose quartz.', 67.00, 'Makeup', 'eyeshadow', 'Huda Beauty', ''),
(43, 'Anastasia Beverly Hills Soft Glam Palette', '14 shades for day-to-night looks.', 45.00, 'Makeup', 'eyeshadow', 'Anastasia Beverly Hills', ''),
(44, 'NYX Professional Makeup Ultimate Shadow Palette', '16 high-performance shades.', 18.00, 'Makeup', 'eyeshadow', 'NYX', ''),
(45, 'CeraVe Hydrating Facial Cleanser', 'Gentle cleanser for normal to dry skin.', 14.99, 'Skin Care', 'Cleanser', 'CeraVe', ''),
(46, 'La Roche-Posay Toleriane Hydrating Gentle Cleanser', 'Hydrating cleanser for sensitive skin.', 15.99, 'Skin Care', 'Cleanser', 'La Roche-Posay', ''),
(47, 'Neutrogena Ultra Gentle Hydrating Cleanser', 'Gentle cleanser for sensitive skin.', 8.99, 'Skin Care', 'Cleanser', 'Neutrogena', ''),
(48, 'Fresh Soy Face Cleanser', 'Gentle gel cleanser for all skin types.', 38.00, 'Skin Care', 'cleanser', 'Fresh', ''),
(49, 'Olaplex No.8 Bond Intense Moisture Mask', 'Moisturizing mask for damaged hair.', 30.00, 'Hair', 'hair mask', 'Olaplex', ''),
(50, 'Moroccanoil Intense Hydrating Mask', 'Deep hydration for medium to thick hair.', 38.00, 'Hair', 'hair mask', 'Moroccanoil', ''),
(51, 'Briogeo Don\'t Despair, Repair! Deep Conditioning Mask', 'Strengthens and restores moisture.', 39.00, 'Hair', 'hair mask', 'Briogeo', ''),
(52, 'SheaMoisture Manuka Honey & Mafura Oil Hair Mask', 'Intensive hydration for dry hair.', 12.99, 'Hair', 'hair mask', 'SheaMoisture', ''),
(53, 'Simplehuman Sensor Mirror', 'Tru-lux light system for perfect makeup.', 200.00, 'accessories', 'mirrors', 'Simplehuman', ''),
(54, 'Fancii LED Lighted Vanity Mirror', 'Portable mirror with LED lighting.', 39.99, 'accessories', 'mirrors', 'Fancii', ''),
(55, 'Conair Reflections LED Lighted Mirror', 'Double-sided mirror with LED lighting.', 49.99, 'accessories', 'mirrors', 'Conair', ''),
(61, 'Too Faced Better Than Sex Mascara', 'Volumizing mascara for dramatic lashes.', 28.00, 'Makeup', 'Mascara', 'Too Faced', ''),
(62, 'Maybelline Lash Sensational Mascara', 'Full fan effect for long lashes.', 9.99, 'Makeup', 'Mascara', 'Maybelline', ''),
(63, 'Benefit Cosmetics They\'re Real! Mascara', 'Lengthens and volumizes lashes.', 27.00, 'Makeup', 'Mascara', 'Benefit Cosmetics', ''),
(64, 'L\'Oreal Paris Voluminous Lash Paradise Mascara', 'Soft wavy brush for full lashes.', 10.99, 'Makeup', 'mascara', 'L\'Oreal Paris', ''),
(65, 'Origins Clear Improvement Charcoal Mask', 'Detoxifying mask with charcoal.', 27.00, 'Skin Care', 'Facial Mask', 'Origins', ''),
(66, 'GlamGlow SuperMud Clearing Treatment', 'Clears pores and fights acne.', 60.00, 'Skin Care', 'Facial Mask', 'GlamGlow', ''),
(67, 'The Body Shop Himalayan Charcoal Purifying Glow Mask', 'Purifies and refines skin.', 25.00, 'Skin Care', 'Facial Mask', 'The Body Shop', ''),
(68, 'Juice Beauty Stem Cellular Anti-Wrinkle Moisturizer', 'Organic anti-aging moisturizer.', 70.00, 'Skin Care', 'Organic Products', 'Juice Beauty', ''),
(69, 'Herbivore Botanicals Blue Tansy Mask', 'Natural exfoliating mask.', 48.00, 'Skin Care', 'Organic Products', 'Herbivore Botanicals', ''),
(70, 'Dr. Bronner\'s Organic Lavender Hand & Body Lotion', 'Organic lavender lotion.', 10.99, 'Skin Care', 'Organic Products', 'Dr. Bronner\'s', ''),
(73, 'Neutrogena Ultra Sheer Dry-Touch Sunscreen SPF 55', 'Lightweight, non-greasy sunblock for daily use.', 12.99, 'Skin Care', 'Sunblock', 'Neutrogena', 'images/sunblock-neutrogena.png'),
(74, 'La Roche-Posay Anthelios Melt-in Milk Sunscreen SPF 100', 'High SPF sunblock for sensitive skin.', 29.99, 'Skin Care', 'Sunblock', 'La Roche-Posay', 'images/sunblock-laroche.png'),
(75, 'Supergoop! Unseen Sunscreen SPF 40', 'Invisible, weightless, and scentless sunblock.', 36.00, 'Skin Care', 'Sunblock', 'Supergoop!', 'images/sunblock-supergoop.png'),
(76, 'Good Genese - Serum avec l\'acide glycolique', 'Enrichi en acide glycolique pour sublimer les peaux ternes,  par le soleil ou marques par les imperfections.\r\n', 99.90, 'Skin Care', 'Serum & Lotion', 'Good Genese', '');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `ID` int(11) NOT NULL,
  `NOM` varchar(50) NOT NULL,
  `PRENOM` varchar(50) NOT NULL,
  `EMAIL` varchar(100) NOT NULL,
  `PASSWORD` varchar(255) NOT NULL,
  `role` enum('admin','client') NOT NULL DEFAULT 'client'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`ID`, `NOM`, `PRENOM`, `EMAIL`, `PASSWORD`, `role`) VALUES
(2, 'EL Attaoui', 'test', 'kaouthar@gmail.com', 'KAOUTHAR', 'client'),
(3, 'EL ALAOUI', 'AMAL', 'ALAOUI@gmail.com', 'EL ALAOUI', 'client'),
(7, 'admin', 'admin', 'admin@gmail.com', 'SUPERVISER', 'admin'),
(9, 'ADMIN', 'ADMIN', 'teste@gmail.com', 'MOROCCO12', 'admin'),
(10, 'BAQLOUL', 'ZINEB', 'zinebbaq@gmail.com', '123AZEqsd', 'admin');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `brand`
--
ALTER TABLE `brand`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id`),
  ADD KEY `f` (`parent_id`);

--
-- Index pour la table `commande`
--
ALTER TABLE `commande`
  ADD PRIMARY KEY (`id_commande`),
  ADD KEY `id_client` (`id_client`);

--
-- Index pour la table `ligne_commande`
--
ALTER TABLE `ligne_commande`
  ADD KEY `id_commande` (`id_commande`),
  ADD KEY `id_produit` (`id_produit`);

--
-- Index pour la table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `EMAIL` (`EMAIL`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `brand`
--
ALTER TABLE `brand`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT pour la table `category`
--
ALTER TABLE `category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `commande`
--
ALTER TABLE `commande`
  MODIFY `id_commande` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT pour la table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `category`
--
ALTER TABLE `category`
  ADD CONSTRAINT `f` FOREIGN KEY (`parent_id`) REFERENCES `category` (`id`);

--
-- Contraintes pour la table `commande`
--
ALTER TABLE `commande`
  ADD CONSTRAINT `commande_ibfk_1` FOREIGN KEY (`id_client`) REFERENCES `users` (`ID`);

--
-- Contraintes pour la table `ligne_commande`
--
ALTER TABLE `ligne_commande`
  ADD CONSTRAINT `ligne_commande_ibfk_1` FOREIGN KEY (`id_commande`) REFERENCES `commande` (`id_commande`),
  ADD CONSTRAINT `ligne_commande_ibfk_2` FOREIGN KEY (`id_produit`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
