-- MySQL Workbench Synchronization
-- Generated: 2021-02-11 12:48
-- Model: New Model
-- Version: 1.0
-- Project: Name of the project
-- Author: Aleksei

SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- FIXME [!] This migration creates incomplete object and must not be used for production.

CREATE TABLE IF NOT EXISTS `organizations` (
    `id`         INT(10) UNSIGNED                                    NOT NULL AUTO_INCREMENT,
    `type`       ENUM ('oem', 'distributor', 'reseller', 'customer') NOT NULL,
    `subdomain`  VARCHAR(45)                                         NULL     DEFAULT NULL,
    `abbr`       VARCHAR(32)                                         NOT NULL,
    `name`       VARCHAR(255)                                        NOT NULL,
    `created_at` TIMESTAMP                                           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP                                           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);

SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
