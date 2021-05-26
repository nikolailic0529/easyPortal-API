SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `contacts`
    DROP INDEX `unique__name__phone__email__object_id__object_type__deleted_at`,
    CHANGE COLUMN `name` `name` VARCHAR(255) NULL DEFAULT NULL,
    ADD UNIQUE INDEX `unique__name__phone_number__object_id__object_type__deleted_at`(`name` ASC, `phone_number` ASC, `object_id` ASC, `object_type` ASC, `deleted_at` ASC) VISIBLE;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;