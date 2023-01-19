SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `documents`
    DROP COLUMN `is_contract`,
    DROP COLUMN `is_quote`,
    DROP COLUMN `is_hidden`,
    DROP INDEX `idx__number__deleted_at`,
    DROP INDEX `idx__is_contract__is_quote__is_hidden__deleted_at`,
    ADD INDEX `idx__number__type_id__deleted_at`(`number` ASC, `type_id` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__reseller_id__type_id__deleted_at`(`reseller_id` ASC, `type_id` ASC, `deleted_at` ASC) VISIBLE,
    ADD INDEX `idx__statuses_count__type_id__deleted_at`(`statuses_count` ASC, `type_id` ASC, `deleted_at` ASC) VISIBLE;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
