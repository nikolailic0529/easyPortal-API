SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=1;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=1;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `invitations` (
  `id`              CHAR(36)     NOT NULL,
  `organization_id` CHAR(36)     NOT NULL,
  `user_id`         CHAR(36)     NOT NULL,
  `role_id`         CHAR(36)     NOT NULL,
  `email`           VARCHAR(255) NOT NULL,
  `used_at`         TIMESTAMP    NULL DEFAULT NULL,
  `created_by`      CHAR(36)     NOT NULL,
  `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at`      TIMESTAMP    NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_invitations_organizations1_idx` (`organization_id` ASC) VISIBLE,
  INDEX `fk_invitations_users1_idx` (`user_id` ASC) VISIBLE,
  INDEX `fk_invitations_roles1_idx` (`role_id` ASC) VISIBLE,
  INDEX `idx__deleted_at` (`deleted_at` ASC) VISIBLE,
  INDEX `fk_invitations_users2_idx` (`created_by` ASC) VISIBLE,
  CONSTRAINT `fk_invitations_organizations1`
    FOREIGN KEY (`organization_id`)
    REFERENCES `organizations` (`id`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_invitations_users1`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_invitations_roles1`
    FOREIGN KEY (`role_id`)
    REFERENCES `roles` (`id`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_invitations_users2`
    FOREIGN KEY (`created_by`)
    REFERENCES `users` (`id`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT
);


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
