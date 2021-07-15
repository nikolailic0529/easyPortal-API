SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `assets`
    DROP INDEX `idx__serial_number__deleted_at`,
    ADD INDEX `idx__deleted_at`(`deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__serial_number`(`serial_number` ASC) VISIBLE;
;

ALTER TABLE `contacts`
    DROP INDEX `idx__object_id__object_type__deleted_at`,
    ADD INDEX `idx__object_id__object_type__deleted_not`(`object_id` ASC, `object_type` ASC, `deleted_not` ASC) VISIBLE;
;

ALTER TABLE `customers`
    DROP INDEX `idx__name__deleted_at`,
    ADD INDEX `idx__name`(`name` ASC) VISIBLE;
;

ALTER TABLE `organizations`
    DROP INDEX `idx__name__deleted_at`,
    ADD INDEX `idx__name`(`name` ASC) VISIBLE;
;

ALTER TABLE `locations`
    ADD INDEX `idx__postcode`(`postcode` ASC) VISIBLE,
    DROP INDEX `idx__postcode__deleted_at`,
    ADD INDEX `idx__object_id__object_type__deleted_not`(`object_id` ASC, `object_type` ASC, `deleted_not` ASC) VISIBLE,
    DROP INDEX `idx__object_id__object_type__deleted_at`,
    ADD INDEX `idx__latitude__longitude__deleted_not`(`latitude` ASC, `longitude` ASC, `deleted_not` ASC) VISIBLE,
    DROP INDEX `idx__latitude__longitude__deleted_at`;
;

ALTER TABLE `products`
    ADD INDEX `idx__sku`(`sku` ASC) VISIBLE,
    DROP INDEX `idx__sku__name__deleted_at`,
    ADD INDEX `idx__name`(`name` ASC) VISIBLE,
    DROP INDEX `idx__name__deleted_at`;
;

ALTER TABLE `documents`
    ADD INDEX `idx__number`(`number` ASC) VISIBLE,
    DROP INDEX `idx__number__deleted_at`;
;

ALTER TABLE `resellers`
    DROP INDEX `idx__name__deleted_at`,
    ADD INDEX `idx__name`(`name` ASC) VISIBLE;
;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
