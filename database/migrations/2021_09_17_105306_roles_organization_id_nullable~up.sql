SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `roles`
    DROP FOREIGN KEY `fk_roles_organizations1`;

ALTER TABLE `roles`
    CHANGE COLUMN `organization_id` `organization_id` CHAR(36) NULL AFTER `name`,
    ADD COLUMN `_organization_id` CHAR(36) GENERATED ALWAYS AS (ifnull(`organization_id`, '00000000-0000-0000-0000-000000000000')) VIRTUAL AFTER `deleted_not`,
    DROP INDEX `unique__name__organization_id__deleted_not`,
    ADD UNIQUE INDEX `unique__name__organization_id__deleted_not`(`name` ASC, `_organization_id` ASC, `deleted_not` ASC) INVISIBLE;

ALTER TABLE `roles`
    ADD CONSTRAINT `fk_roles_organizations1`
        FOREIGN KEY (`organization_id`)
            REFERENCES `organizations`(`id`)
            ON DELETE RESTRICT
            ON UPDATE RESTRICT;


SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
