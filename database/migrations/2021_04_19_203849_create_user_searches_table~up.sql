-- MySQL Workbench Synchronization
-- Generated: 2021-04-19 22:48
-- Model: New Model
-- Version: 1.0
-- Project: Easy portal
-- Author: Mohamed Osama

SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `user_searches` (
    `id`          CHAR(36)  NOT NULL,
    `user_id`     CHAR(36)  NOT NULL,
    `name`        VARCHAR(255)  NOT NULL,
    `key`         VARCHAR(255)  NOT NULL,
    `conditions`  TEXT NOT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`  TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    INDEX `fk_user_searches_users1_idx`(`user_id` ASC),
    INDEX `idx__deleted_at`(`deleted_at` ASC),
    CONSTRAINT `fk_user_searches_users1`
        FOREIGN KEY (`user_id`)
            REFERENCES `users`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT
);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;