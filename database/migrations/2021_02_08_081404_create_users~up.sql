-- MySQL Workbench Synchronization
-- Generated: 2021-02-08 12:32
-- Model: New Model
-- Version: 1.0
-- Project: Name of the project
-- Author: Aleksei

SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- FIXME [!] This migration creates incomplete object and must not be used for production.

CREATE TABLE IF NOT EXISTS `users` (
    `id`                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `organization_id`   INT UNSIGNED  NOT NULL,
    `sub`               VARCHAR(255)  NOT NULL COMMENT 'Auth0 User ID',
    `given_name`        VARCHAR(255)  NOT NULL,
    `family_name`       VARCHAR(255)  NOT NULL,
    `email`             VARCHAR(255)  NOT NULL,
    `email_verified_at` TIMESTAMP     NULL,
    `phone`             VARCHAR(64)   NOT NULL,
    `phone_verified_at` TIMESTAMP     NULL,
    `photo`             VARCHAR(1024) NULL,
    `created_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `unique_email`(`email` ASC) VISIBLE,
    UNIQUE INDEX `unique_sub`(`sub` ASC) VISIBLE,
    INDEX `fk_users_organizations1_idx`(`organization_id` ASC) VISIBLE
);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
