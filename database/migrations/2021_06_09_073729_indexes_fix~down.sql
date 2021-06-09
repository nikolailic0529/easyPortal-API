SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `tags`
    DROP INDEX `idx__deleted_at`,
    ADD INDEX `idx__deleted_at`(`id` ASC, `deleted_at` ASC) VISIBLE;

ALTER TABLE `distributors`
    DROP INDEX `idx__deleted_at`,
    ADD INDEX `idx__deleted_at`(`id` ASC, `deleted_at` ASC) VISIBLE;

ALTER TABLE `asset_tags`
    DROP INDEX `idx__deleted_at`,
    ADD INDEX `idx__deleted_at`(`id` ASC, `deleted_at` ASC) VISIBLE;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
