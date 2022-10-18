SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `asset_warranty_service_levels` (
    `id`                CHAR(36)  NOT NULL,
    `asset_warranty_id` CHAR(36)  NOT NULL,
    `service_level_id`  CHAR(36)  NOT NULL,
    `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`        TIMESTAMP NULL     DEFAULT NULL,
    `deleted_not`       TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null), 1, NULL)) STORED,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `unique__asset_warranty_id__service_level_id__deleted_not`(`asset_warranty_id` ASC, `service_level_id` ASC, `deleted_not` ASC) VISIBLE,
    INDEX `fk_asset_warranty_service_levels_asset_warranties1_idx`(`asset_warranty_id` ASC) VISIBLE,
    INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE,
    INDEX `fk_asset_warranty_service_levels_service_levels1_idx`(`service_level_id` ASC) INVISIBLE,
    CONSTRAINT `fk_asset_warranty_service_levels_asset_warranties1`
        FOREIGN KEY (`asset_warranty_id`)
            REFERENCES `asset_warranties`(`id`)
            ON DELETE CASCADE
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_asset_warranty_service_levels_service_levels1`
        FOREIGN KEY (`service_level_id`)
            REFERENCES `service_levels`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT
);

INSERT INTO `asset_warranty_service_levels` (`id`, `asset_warranty_id`, `service_level_id`)
SELECT UUID(), `id`, `service_level_id`
FROM `asset_warranties`
WHERE `service_level_id` IS NOT NULL
    and `deleted_at` IS NULL;

ALTER TABLE `asset_warranties`
    DROP FOREIGN KEY `fk_asset_warranties_service_levels1`;

ALTER TABLE `asset_warranties`
    DROP COLUMN `service_level_id`,
    CHANGE COLUMN `description` `description` TEXT NULL DEFAULT NULL AFTER `deleted_at`,
    ADD INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__end__deleted_at`(`end` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__asset_id__deleted_at`(`asset_id` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__asset_id__end__deleted_at`(`asset_id` ASC, `end` ASC, `deleted_at` ASC) VISIBLE,
    DROP INDEX `idx__end__deleted_at__asset_id`,
    DROP INDEX `idx__deleted_at__asset_id`,
    DROP INDEX `fk_asset_warranties_service_levels1_idx`;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
