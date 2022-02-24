SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `document_entries`
    ADD COLUMN `start` DATE NULL DEFAULT NULL AFTER `serial_number`,
    ADD COLUMN `end`   DATE NULL DEFAULT NULL AFTER `start`;

UPDATE `document_entries`
    LEFT JOIN `documents` ON `documents`.`id` = `document_entries`.`document_id`
SET `document_entries`.`start` = `documents`.`start`,
    `document_entries`.`end`   = `documents`.`end`
WHERE `document_entries`.`deleted_at` IS NULL;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
