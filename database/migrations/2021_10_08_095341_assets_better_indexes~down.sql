SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `assets`
    DROP INDEX `idx__deleted_at`,
    DROP INDEX `idx__product_id__deleted_at`,
    DROP INDEX `idx__reseller_id__deleted_at`,
    DROP INDEX `idx__customer_id__deleted_at`,
    DROP INDEX `idx__location_id__deleted_at`,
    DROP INDEX `idx__status_id__deleted_at`,
    DROP INDEX `idx__type_id__deleted_at`;

ALTER TABLE `asset_coverages`
    DROP INDEX `idx__asset_id__deleted_at`,
    DROP INDEX `idx__coverage_id__deleted_at`;

ALTER TABLE `asset_warranties`
    DROP INDEX `idx__asset_id__deleted_at`,
    DROP INDEX `idx__asset_id__end__deleted_at`;

ALTER TABLE `asset_tags`
    DROP INDEX `idx__tag_id__deleted_at`,
    DROP INDEX `idx__asset_id__deleted_at`;

ALTER TABLE `document_entries`
    DROP INDEX `idx__document_id__deleted_at`;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
