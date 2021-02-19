-- MySQL Workbench Synchronization
-- Generated: 2021-02-11 12:48
-- Model: New Model
-- Version: 1.0
-- Project: Name of the project
-- Author: Aleksei

SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

INSERT INTO `organizations` (`id`, `type`, `subdomain`, `abbr`, `name`, `created_at`, `updated_at`, `deleted_at`)
VALUES ('bc899f0e-47f7-4af7-9789-2f44a1afa995', 'reseller', '@root', 'root', 'Root Organization', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL);

INSERT INTO `organizations` (`id`, `type`, `subdomain`, `abbr`, `name`, `created_at`, `updated_at`, `deleted_at`)
VALUES ('5f35eab3-c382-4f92-b5b6-92fb12e77041', 'reseller', 'testreseller', 'testreseller', 'Test Reseller', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, NULL);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
