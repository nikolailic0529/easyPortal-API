SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `asset_coverages` (
    `id`          CHAR(36)  NOT NULL,
    `asset_id`    CHAR(36)  NOT NULL,
    `coverage_id` CHAR(36)  NOT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`  TIMESTAMP NULL     DEFAULT NULL,
    `deleted_not` TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null), 1, NULL)) VIRTUAL,
    PRIMARY KEY (`id`),
    INDEX `fk_asset_coverages_coverages1_idx`(`coverage_id` ASC) VISIBLE,
    INDEX `fk_asset_coverages_assets1_idx`(`asset_id` ASC) INVISIBLE,
    INDEX `idx__deleted_at`(`deleted_at` ASC) INVISIBLE,
    UNIQUE INDEX `unique__asset_id__coverage_id__deleted_not`(`asset_id` ASC, `coverage_id` ASC, `deleted_not` ASC) VISIBLE,
    CONSTRAINT `fk_asset_coverages_assets1`
        FOREIGN KEY (`asset_id`)
            REFERENCES `assets`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT,
    CONSTRAINT `fk_asset_coverages_coverages1`
        FOREIGN KEY (`coverage_id`)
            REFERENCES `coverages`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT
);

INSERT INTO `asset_coverages` (`id`, `asset_id`, `coverage_id`)
SELECT UUID(), `id`, `coverage_id`
FROM `assets`
WHERE `coverage_id` IS NOT NULL;

ALTER TABLE `assets`
    DROP FOREIGN KEY `fk_assets_coverages1`;

ALTER TABLE `assets`
    DROP COLUMN `coverage_id`,
    DROP INDEX `fk_assets_coverages1_idx`;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
