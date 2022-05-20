CREATE TABLE IF NOT EXISTS `testing__search__fulltext_processors` (
    `id`          CHAR(36)    NOT NULL,
    `name`        VARCHAR(45) NOT NULL,
    `description` TEXT        NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx__name`(`name` ASC) INVISIBLE,
    FULLTEXT INDEX `ftx__name`(`name`) INVISIBLE,
    FULLTEXT INDEX `ftx__description`(`description`) WITH PARSER `ngram` INVISIBLE,
    FULLTEXT INDEX `ftx__name__description`(`description`, `name`) WITH PARSER `ngram` VISIBLE
);
