-- MySQL Workbench Synchronization
-- Generated: 2021-02-08 12:32
-- Model: New Model
-- Version: 1.0
-- Project: Name of the project
-- Author: Aleksei

SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `users` (
    `id`                INT(10) UNSIGNED                                    NOT NULL AUTO_INCREMENT,
    `sub`               VARCHAR(255)                                        NOT NULL COMMENT 'Auth0 User ID',
    `type`              ENUM ('oem', 'distributor', 'reseller', 'customer') NOT NULL,
    `given_name`        VARCHAR(255)                                        NOT NULL,
    `family_name`       VARCHAR(255)                                        NOT NULL,
    `email`             VARCHAR(255)                                        NOT NULL,
    `email_verified_at` TIMESTAMP                                           NOT NULL,
    `phone`             VARCHAR(64)                                         NULL     DEFAULT NULL,
    `phone_verified_at` TIMESTAMP                                           NULL     DEFAULT NULL,
    `photo`             VARCHAR(1024)                                       NULL     DEFAULT NULL,
    `organization_id`   INT(10) UNSIGNED                                    NULL     DEFAULT NULL,
    `customer_id`       INT(10) UNSIGNED                                    NULL     DEFAULT NULL,
    `created_at`        TIMESTAMP                                           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP                                           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `unique_email`(`email` ASC) VISIBLE,
    UNIQUE INDEX `unique_sub`(`sub` ASC) VISIBLE,
    INDEX `fk_users_organizations1_idx`(`organization_id` ASC) VISIBLE,
    INDEX `fk_users_customers1_idx`(`customer_id` ASC) VISIBLE,
    CONSTRAINT `fk_users_organizations1`
        FOREIGN KEY (`organization_id`)
            REFERENCES `organizations`(`id`)
            ON DELETE CASCADE
            ON UPDATE NO ACTION,
    CONSTRAINT `fk_users_customers1`
        FOREIGN KEY (`customer_id`)
            REFERENCES `customers`(`id`)
            ON DELETE CASCADE
            ON UPDATE NO ACTION
);


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
