SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `contacts`
    DROP INDEX `unique__email__object_id__object_type__deleted_at`,
    ADD UNIQUE INDEX `unique__email__object_id__object_type`(`email` ASC, `object_id` ASC, `object_type` ASC) INVISIBLE,
    DROP INDEX `unique__name__phone_number__object_id__object_type__deleted_at`,
    ADD UNIQUE INDEX `unique__name__phone__object_id__object_type`(`name` ASC, `phone_number` ASC, `object_id` ASC, `object_type` ASC) VISIBLE;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
