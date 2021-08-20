SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS = 1;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 1;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `organization_users` (
  `id`              CHAR(36)   NOT NULL,
  `organization_id` CHAR(36)   NOT NULL,
  `user_id`         CHAR(36)   NOT NULL,
  `created_at`      TIMESTAMP  NOT NULL DEFAULT  CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP  NOT NULL DEFAULT  CURRENT_TIMESTAMP,
  `deleted_at`      TIMESTAMP  NULL DEFAULT NULL,
  `deleted_not`     TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  INDEX `fk_organization_users_users1_idx` (`user_id` ASC) VISIBLE,
  INDEX `fk_organization_users_organizations1_idx` (`organization_id` ASC) VISIBLE,
  UNIQUE INDEX `unique__organization_id__user_id__deleted_not` (`organization_id` ASC, `user_id` ASC) VISIBLE,
  INDEX `idx__deleted_at` (`deleted_at` ASC) VISIBLE,
  CONSTRAINT `fk_organization_users_users1`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_organization_users_organizations1`
    FOREIGN KEY (`organization_id`)
    REFERENCES `organizations` (`id`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT
);


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
