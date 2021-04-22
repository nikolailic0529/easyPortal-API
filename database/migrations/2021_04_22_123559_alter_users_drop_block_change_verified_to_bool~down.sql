-- MySQL Workbench Synchronization
-- Generated: 2021-04-22 16:35
-- Model: New Model
-- Version: 1.0
-- Project: Name of the project
-- Author: Aleksei

SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `users`
    ADD COLUMN `blocked`           TINYINT(1) NOT NULL DEFAULT 1 AFTER `organization_id`,
    ADD COLUMN `email_verified_at` TIMESTAMP  NULL     DEFAULT NULL AFTER `email_verified`,
    ADD COLUMN `phone_verified_at` TIMESTAMP  NULL     DEFAULT NULL AFTER `phone_verified`;

UPDATE `users`
SET `email_verified_at` = IF(`email_verified`, CURRENT_TIMESTAMP(), NULL),
    `phone_verified_at` = IF(`phone_verified`, CURRENT_TIMESTAMP(), NULL);

ALTER TABLE `users`
    DROP COLUMN `email_verified`,
    DROP COLUMN `phone_verified`;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
