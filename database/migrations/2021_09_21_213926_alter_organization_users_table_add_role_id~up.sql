SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=1;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=1;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `organization_users`
    ADD COLUMN `role_id` CHAR(36) NULL DEFAULT NULL AFTER `user_id`,
    ADD INDEX `fk_organization_users_roles1_idx` (`role_id` ASC) VISIBLE;

ALTER TABLE `organization_users`
    ADD CONSTRAINT `fk_organization_users_roles1`
    FOREIGN KEY (`role_id`)
    REFERENCES `roles` (`id`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT;

UPDATE `organization_users`
    INNER JOIN `user_roles` ON `user_roles`.`user_id` = `organization_users`.`user_id`
    INNER JOIN `roles` ON `roles`.`id` = `user_roles`.`role_id`
    SET `organization_users`.`role_id` = `user_roles`.`role_id`
    WHERE `roles`.`organization_id` = `organization_users`.`organization_id`;

DROP TABLE IF EXISTS `user_roles`;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
