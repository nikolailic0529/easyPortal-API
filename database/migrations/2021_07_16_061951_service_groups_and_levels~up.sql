SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- Cleanup
-- 1) Data is invalid => no reason to save/covert it
TRUNCATE `asset_warranty_services`;

-- 2)
UPDATE `products`
SET `deleted_at` = CURRENT_TIMESTAMP()
WHERE `type` != 'asset';


-- Migrate
ALTER TABLE `documents`
    DROP FOREIGN KEY `fk_documents_products1`;

ALTER TABLE `asset_warranties`
    DROP FOREIGN KEY `fk_asset_warranties_products1`;

ALTER TABLE `document_entries`
    DROP FOREIGN KEY `fk_document_entries_products1`;

ALTER TABLE `asset_warranty_services`
    DROP FOREIGN KEY `fk_asset_warranties_services_products1`;

CREATE TABLE IF NOT EXISTS `service_groups` (
    `id`          CHAR(36)     NOT NULL,
    `oem_id`      CHAR(36)     NOT NULL,
    `sku`         VARCHAR(64)  NOT NULL,
    `name`        VARCHAR(255) NOT NULL,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`  TIMESTAMP    NULL     DEFAULT NULL,
    `deleted_not` TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null), 1, NULL)) VIRTUAL,
    PRIMARY KEY (`id`),
    INDEX `fk_service_groups_oems1_idx`(`oem_id` ASC) INVISIBLE,
    UNIQUE INDEX `unique__sku__oem_id__deleted_not`(`sku` ASC, `oem_id` ASC, `deleted_not` ASC) VISIBLE,
    CONSTRAINT `fk_service_groups_oems1`
        FOREIGN KEY (`oem_id`)
            REFERENCES `oems`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT
);

CREATE TABLE IF NOT EXISTS `service_levels` (
    `id`               CHAR(36)      NOT NULL,
    `oem_id`           CHAR(36)      NOT NULL,
    `service_group_id` CHAR(36)      NOT NULL,
    `sku`              VARCHAR(64)   NOT NULL,
    `name`             VARCHAR(255)  NOT NULL,
    `description`      VARCHAR(1024) NOT NULL,
    `created_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`       TIMESTAMP     NULL     DEFAULT NULL,
    `deleted_not`      TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null), 1, NULL)) VIRTUAL,
    PRIMARY KEY (`id`),
    INDEX `fk_service_levels_oems1_idx`(`oem_id` ASC) VISIBLE,
    INDEX `fk_service_levels_oem_service_groups1_idx`(`service_group_id` ASC) VISIBLE,
    UNIQUE INDEX `unique__sku__oem_id__oem_service_group_id__deleted_not`(`sku` ASC, `oem_id` ASC, `service_group_id` ASC, `deleted_not` ASC) VISIBLE,
    CONSTRAINT `fk_service_levels_oems1`
        FOREIGN KEY (`oem_id`)
            REFERENCES `oems`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_service_levels_oem_service_groups1`
        FOREIGN KEY (`service_group_id`)
            REFERENCES `service_groups`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT
);

ALTER TABLE `products`
    DROP COLUMN `type`,
    DROP INDEX `unique__sku__oem_id__type__deleted_not`,
    ADD UNIQUE INDEX `unique__sku__oem_id__deleted_not`(`sku` ASC, `oem_id` ASC, `deleted_not` ASC) VISIBLE;
;

ALTER TABLE `documents`
    DROP COLUMN `support_id`,
    ADD COLUMN `service_group_id` CHAR(36) NULL DEFAULT NULL AFTER `number`,
    ADD INDEX `fk_documents_service_groups1_idx`(`service_group_id` ASC) VISIBLE,
    DROP INDEX `fk_documents_products1_idx`;
;

ALTER TABLE `asset_warranties`
    DROP COLUMN `support_id`,
    ADD COLUMN `service_group_id` CHAR(36) NULL DEFAULT NULL AFTER `document_number`,
    ADD INDEX `fk_asset_warranties_service_groups1_idx`(`service_group_id` ASC) VISIBLE,
    DROP INDEX `fk_asset_warranties_products1_idx`;
;

ALTER TABLE `document_entries`
    DROP COLUMN `service_id`,
    ADD COLUMN `service_level_id` CHAR(36) NULL DEFAULT NULL AFTER `asset_id`,
    ADD INDEX `fk_document_entries_service_levels1_idx`(`service_level_id` ASC) VISIBLE,
    DROP INDEX `fk_document_entries_products1_idx`;
;

ALTER TABLE `asset_warranty_services`
    DROP COLUMN `service_id`,
    ADD COLUMN `service_level_id` CHAR(36) NOT NULL AFTER `asset_warranty_id`,
    DROP INDEX `unique__asset_warranty_id__service_id__deleted_not`,
    ADD UNIQUE INDEX `unique__asset_warranty_id__service_id__deleted_not`(`asset_warranty_id` ASC, `deleted_not` ASC) VISIBLE,
    ADD INDEX `fk_asset_warranty_services_service_levels1_idx`(`service_level_id` ASC) VISIBLE,
    DROP INDEX `fk_asset_warranties_services_products1_idx`;
;

ALTER TABLE `documents`
    ADD CONSTRAINT `fk_documents_service_groups1`
        FOREIGN KEY (`service_group_id`)
            REFERENCES `service_groups`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;

ALTER TABLE `asset_warranties`
    ADD CONSTRAINT `fk_asset_warranties_service_groups1`
        FOREIGN KEY (`service_group_id`)
            REFERENCES `service_groups`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;

ALTER TABLE `document_entries`
    ADD CONSTRAINT `fk_document_entries_service_levels1`
        FOREIGN KEY (`service_level_id`)
            REFERENCES `service_levels`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;

ALTER TABLE `asset_warranty_services`
    ADD CONSTRAINT `fk_asset_warranty_services_service_levels1`
        FOREIGN KEY (`service_level_id`)
            REFERENCES `service_levels`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
