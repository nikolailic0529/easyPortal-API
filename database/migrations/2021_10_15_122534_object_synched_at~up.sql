SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `assets`
    ADD COLUMN `synced_at` TIMESTAMP NOT NULL AFTER `changed_at`;

UPDATE `assets`
SET `changed_at` = `updated_at`;

ALTER TABLE `customers`
    ADD COLUMN `synced_at` TIMESTAMP NOT NULL AFTER `changed_at`;

UPDATE `customers`
SET `changed_at` = `updated_at`;

ALTER TABLE `documents`
    ADD COLUMN `synced_at` TIMESTAMP NOT NULL AFTER `changed_at`;

UPDATE `documents`
SET `changed_at` = `updated_at`;

ALTER TABLE `resellers`
    ADD COLUMN `synced_at` TIMESTAMP NOT NULL AFTER `changed_at`;

UPDATE `resellers`
SET `changed_at` = `updated_at`;

ALTER TABLE `distributors`
    ADD COLUMN `synced_at` TIMESTAMP NOT NULL AFTER `changed_at`;

UPDATE `distributors`
SET `changed_at` = `updated_at`;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
