SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `assets`
    ADD INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__product_id__deleted_at` (`product_id` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__reseller_id__deleted_at` (`reseller_id` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__customer_id__deleted_at` (`customer_id` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__location_id__deleted_at` (`location_id` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__status_id__deleted_at` (`status_id` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__type_id__deleted_at` (`type_id` ASC, `deleted_at` ASC) VISIBLE;

ALTER TABLE `asset_coverages`
    ADD INDEX `idx__asset_id__deleted_at` (`asset_id` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__coverage_id__deleted_at`(`coverage_id` ASC, `deleted_at` ASC) VISIBLE;

ALTER TABLE `asset_warranties`
    ADD INDEX `idx__asset_id__deleted_at` (`asset_id` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__asset_id__end__deleted_at` (`asset_id` ASC, `end` ASC, `deleted_at` ASC) VISIBLE;

ALTER TABLE `asset_tags`
    ADD INDEX `idx__tag_id__deleted_at` (`tag_id` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__asset_id__deleted_at` (`asset_id` ASC, `deleted_at` ASC) VISIBLE;

ALTER TABLE `document_entries`
    ADD INDEX `idx__document_id__deleted_at` (`document_id` ASC, `deleted_at` ASC) VISIBLE;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
