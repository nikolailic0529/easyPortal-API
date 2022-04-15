SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `quote_request_assets`
    DROP FOREIGN KEY `fk_quote_request_assets_service_levels1`;

ALTER TABLE `quote_requests`
    DROP FOREIGN KEY `fk_quote_requests_oems1`,
    DROP FOREIGN KEY `fk_quote_requests_types1`;

ALTER TABLE `quote_request_assets`
    DROP COLUMN `service_level_custom`,
    CHANGE COLUMN `service_level_id` `service_level_id` CHAR(36) NOT NULL;

ALTER TABLE `quote_requests`
    DROP COLUMN `type_custom`,
    DROP COLUMN `oem_custom`,
    CHANGE COLUMN `customer_custom` `customer_name` VARCHAR(255) NULL DEFAULT NULL AFTER `customer_id`,
    CHANGE COLUMN `oem_id` `oem_id` CHAR(36) NOT NULL AFTER `id`,
    CHANGE COLUMN `type_id` `type_id` CHAR(36) NOT NULL;

ALTER TABLE `quote_request_assets`
    ADD CONSTRAINT `fk_quote_request_assets_service_levels1`
        FOREIGN KEY (`service_level_id`)
            REFERENCES `service_levels`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT;

ALTER TABLE `quote_requests`
    ADD CONSTRAINT `fk_quote_requests_oems1`
        FOREIGN KEY (`oem_id`)
            REFERENCES `oems`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT,
    ADD CONSTRAINT `fk_quote_requests_types1`
        FOREIGN KEY (`type_id`)
            REFERENCES `types`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
