-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : sam. 25 oct. 2025 à 07:50
-- Version du serveur : 8.0.43-0ubuntu0.24.04.2
-- Version de PHP : 8.3.26

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `native_php_restful_api`
--

-- --------------------------------------------------------

--
-- Structure de la table `refresh_token`
--

DROP TABLE IF EXISTS `game_attempt`;
DROP TABLE IF EXISTS `game`;
DROP TABLE IF EXISTS `refresh_token`;
CREATE TABLE IF NOT EXISTS `refresh_token` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `revoked` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `fk_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pseudo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `birth_date` date NOT NULL,
  `gender` enum('m','f','o') COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `last_connected_at` datetime DEFAULT NULL,
  `last_seen_at` datetime DEFAULT NULL,
  `deactivated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pseudo` (`pseudo`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_user_last_seen` (`last_seen_at`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `game`
--

CREATE TABLE IF NOT EXISTS `game` (
  `id` int NOT NULL AUTO_INCREMENT,
  `player1_id` int NOT NULL,
  `player2_id` int NOT NULL,
  `target_word` char(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','won','draw','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `current_turn_user_id` int NOT NULL,
  `turn_started_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `winner_id` int DEFAULT NULL,
  `attempts_count` tinyint NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `finished_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_game_player1` (`player1_id`),
  KEY `idx_game_player2` (`player2_id`),
  KEY `idx_game_status` (`status`),
  KEY `fk_game_turn` (`current_turn_user_id`),
  KEY `fk_game_winner` (`winner_id`),
  CONSTRAINT `fk_game_player1` FOREIGN KEY (`player1_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_game_player2` FOREIGN KEY (`player2_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_game_turn` FOREIGN KEY (`current_turn_user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_game_winner` FOREIGN KEY (`winner_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `game_attempt`
--

CREATE TABLE IF NOT EXISTS `game_attempt` (
  `id` int NOT NULL AUTO_INCREMENT,
  `game_id` int NOT NULL,
  `user_id` int NOT NULL,
  `word` char(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `result` json NOT NULL,
  `attempt_number` tinyint NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_game_attempt` (`game_id`,`attempt_number`),
  KEY `fk_attempt_user` (`user_id`),
  CONSTRAINT `fk_attempt_game` FOREIGN KEY (`game_id`) REFERENCES `game` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attempt_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `user`
--

INSERT INTO `user` (`id`, `first_name`, `last_name`, `pseudo`, `birth_date`, `gender`, `email`, `password`, `created_at`, `last_connected_at`, `deactivated_at`) VALUES
(1, 'Lucas', 'Morel', 'ShadowByte', '2004-06-15', 'm', 'lucas.morel@example.com', '$2y$12$qguFjwsPM/DnYWZ.CSV.XuSAzXJgUzaC7xcNIIx7zok0YXqn/.OBi', '2025-01-10 14:22:00', '2025-10-18 20:30:00', NULL),
(2, 'Clara', 'Duval', 'NekoNova', '2005-02-21', 'f', 'clara.duval@example.com', '$2y$12$il/1Y5Q5vTbDzNsjvnkA3uo13JXgvE7JnYBE2Y8h/iWPBlGfESvoi', '2025-02-05 09:12:00', '2025-10-19 16:45:00', NULL),
(3, 'Eliott', 'Bernard', 'ZeroPing', '2003-11-02', 'm', 'eliott.bernard@example.com', '$2y$12$1KAUoIPKxVm4Zbjt.SbpOuhKd2Kp0XPVzGC4.2UpdGybQankt2BSu', '2025-03-01 18:30:00', '2025-10-20 22:10:00', NULL),
(4, 'Sofia', 'Martinez', 'Pixela', '2004-09-11', 'f', 'sofia.martinez@example.com', '$2y$12$SCf1Ldh599JAjo2Kldpm/et.g5qffiKYUzvg4YwmxAQ8OchmGR92q', '2025-01-28 11:00:00', '2025-10-18 10:05:00', NULL),
(5, 'Noa', 'Leclerc', 'CyberWisp', '2005-07-04', 'o', 'noa.leclerc@example.com', '$2y$12$Fib/vrOHy0t2a6Bs3hZYU..uhENXaK3RYNQbrhX/tjA0kgcyBDWPi', '2025-04-12 15:40:00', '2025-10-21 08:20:00', NULL);

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `refresh_token`
--
ALTER TABLE `refresh_token`
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
