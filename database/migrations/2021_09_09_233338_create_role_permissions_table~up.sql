SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=1;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=1;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id`            CHAR(36) NOT NULL,
  `role_id`       CHAR(36) NOT NULL,
  `permission_id` CHAR(36) NOT NULL,
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at`    TIMESTAMP NULL DEFAULT NULL,
  `deleted_not`   TINYINT(1) GENERATED ALWAYS AS (if((`deleted_at` is null),1,NULL)) STORED,
  PRIMARY KEY (`id`),
  INDEX `fk_role_permissions_roles1_idx` (`role_id` ASC) VISIBLE,
  INDEX `fk_role_permissions_permissions1_idx` (`permission_id` ASC) VISIBLE,
  INDEX `idx__deleted_at` (`deleted_at` ASC) VISIBLE,
  UNIQUE INDEX `unique__role_id__permission_id__deleted_not` (`role_id` ASC, `permission_id` ASC, `deleted_not` ASC) VISIBLE,
  CONSTRAINT `fk_role_permissions_roles1`
    FOREIGN KEY (`role_id`)
    REFERENCES `roles` (`id`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  CONSTRAINT `fk_role_permissions_permissions1`
    FOREIGN KEY (`permission_id`)
    REFERENCES `permissions` (`id`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT
);


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
