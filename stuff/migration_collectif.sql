-- Migration à exécuter une seule fois sur une base déjà initialisée
-- (base créée avant l'ajout de la partie collective Motus).
--
-- Exemple :
--   mysql -u admin -p native_php_restful_api < stuff/migration_collectif.sql

ALTER TABLE `user`
  ADD COLUMN `last_seen_at` datetime DEFAULT NULL AFTER `last_connected_at`,
  ADD KEY `idx_user_last_seen` (`last_seen_at`);

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
