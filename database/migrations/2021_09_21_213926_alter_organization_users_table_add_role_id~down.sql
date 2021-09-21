SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=1;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=1;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

ALTER TABLE `organization_users`
    DROP INDEX `fk_organization_users_roles1_idx`,
    DROP CONSTRAINT `fk_organization_users_roles1`,
    DROP COLUMN `role_id`,

CREATE TABLE IF NOT EXISTS `user_roles` (
  `id`          CHAR(36)   NOT NULL,
  `user_id`     CHAR(36)   NOT NULL,
  `role_id`     CHAR(36)   NOT NULL,
  `created_at`  TIMESTAMP  NOT NULL DEFAULT  CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP  NOT NULL DEFAULT  CURRENT_TIMESTAMP,
  `deleted_at`  TIMESTAMP  NULL DEFAULT NULL,
  `deleted_not` TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  INDEX `fk_user_roles_users1_idx` (`user_id` ASC) VISIBLE,
  INDEX `fk_user_roles_roles1_idx` (`role_id` ASC) VISIBLE,
  UNIQUE INDEX `unique__user_id__role_id__deleted_not` (`user_id` ASC, `role_id` ASC, `deleted_not` ASC) VISIBLE,
  INDEX `idx__deleted_at` (`deleted_at` ASC) VISIBLE,
  CONSTRAINT `fk_user_roles_users1`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_user_roles_roles1`
    FOREIGN KEY (`role_id`)
    REFERENCES `roles` (`id`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT
);


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
