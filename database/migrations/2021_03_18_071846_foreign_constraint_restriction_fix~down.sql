-- MySQL Workbench Synchronization
-- Generated: 2021-03-18 11:11
-- Model: New Model
-- Version: 1.0
-- Project: Name of the project
-- Author: Aleksei

SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `cities`
    DROP FOREIGN KEY `fk_data_cities_countries`;

ALTER TABLE `assets`
    DROP FOREIGN KEY `fk_assets_customers1`,
    DROP FOREIGN KEY `fk_assets_locations1`,
    DROP FOREIGN KEY `fk_assets_organizations1`,
    DROP FOREIGN KEY `fk_assets_types1`;

ALTER TABLE `customers`
    DROP FOREIGN KEY `fk_customers_statuses1`,
    DROP FOREIGN KEY `fk_customers_types1`;

ALTER TABLE `locations`
    DROP FOREIGN KEY `fk_locations_cities10`,
    DROP FOREIGN KEY `fk_locations_countries10`;

ALTER TABLE `products`
    DROP FOREIGN KEY `fk_products_oems1`;

ALTER TABLE `users`
    DROP FOREIGN KEY `fk_users_organizations1`;

ALTER TABLE `cities`
    ADD CONSTRAINT `fk_data_cities_countries`
        FOREIGN KEY (`country_id`)
            REFERENCES `countries`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT;

ALTER TABLE `assets`
    DROP FOREIGN KEY `fk_assets_oems1`,
    DROP FOREIGN KEY `fk_assets_products1`;

ALTER TABLE `assets`
    ADD CONSTRAINT `fk_assets_customers1`
        FOREIGN KEY (`customer_id`)
            REFERENCES `customers`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT,
    ADD CONSTRAINT `fk_assets_oems1`
        FOREIGN KEY (`oem_id`)
            REFERENCES `oems`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT,
    ADD CONSTRAINT `fk_assets_locations1`
        FOREIGN KEY (`location_id`)
            REFERENCES `locations`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT,
    ADD CONSTRAINT `fk_assets_products1`
        FOREIGN KEY (`product_id`)
            REFERENCES `products`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT,
    ADD CONSTRAINT `fk_assets_organizations1`
        FOREIGN KEY (`organization_id`)
            REFERENCES `organizations`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT,
    ADD CONSTRAINT `fk_assets_types1`
        FOREIGN KEY (`type_id`)
            REFERENCES `types`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT;

ALTER TABLE `customers`
    ADD CONSTRAINT `fk_customers_statuses1`
        FOREIGN KEY (`status_id`)
            REFERENCES `statuses`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT,
    ADD CONSTRAINT `fk_customers_types1`
        FOREIGN KEY (`type_id`)
            REFERENCES `types`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT;

ALTER TABLE `locations`
    ADD CONSTRAINT `fk_locations_cities10`
        FOREIGN KEY (`city_id`)
            REFERENCES `cities`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT,
    ADD CONSTRAINT `fk_locations_countries10`
        FOREIGN KEY (`country_id`)
            REFERENCES `countries`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT;

ALTER TABLE `products`
    ADD CONSTRAINT `fk_products_oems1`
        FOREIGN KEY (`oem_id`)
            REFERENCES `oems`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT;

ALTER TABLE `users`
    ADD CONSTRAINT `fk_users_organizations1`
        FOREIGN KEY (`organization_id`)
            REFERENCES `organizations`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
