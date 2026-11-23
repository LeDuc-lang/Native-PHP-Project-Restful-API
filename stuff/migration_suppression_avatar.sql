-- Suppression du champ `avatar` de la table `user`.
-- Script idempotent : ne fait rien si la colonne n'existe déjà plus.
--
-- Utilisation :
--   mysql -u admin -p native_php_restful_api < stuff/migration_suppression_avatar.sql

SET @has_avatar = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'user'
      AND COLUMN_NAME = 'avatar'
);

SET @sql = IF(
    @has_avatar > 0,
    'ALTER TABLE `user` DROP COLUMN `avatar`',
    'SELECT ''La colonne avatar est deja absente'' AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
