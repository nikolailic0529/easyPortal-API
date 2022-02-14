# EasyPortal API

## Requirements

| Package    | Version                                          |
| ---------- |--------------------------------------------------|
| PHP        | 8.0+                                             |
| MySQL      | 8.0+                                             |
| Redis      | [phpredis](https://github.com/phpredis/phpredis) |

### MySQL

| Property                     | Value                | Description                                                            |
|------------------------------|----------------------|------------------------------------------------------------------------|
| Default Charset              | `utf8mb4`            |                                                                        |
| Default Collation            | `utf8mb4_0900_as_ci` |                                                                        |
| `default_storage_engine`     | `InnoDB`             |                                                                        |
| `innodb_default_row_format`  | DYNAMIC/COMPRESSED   | "Large Index Key Prefix Support" required                              |
| Fulltext `ngram` parser      | required             | See https://dev.mysql.com/doc/refman/8.0/en/fulltext-search-ngram.html |
| `innodb_ft_enable_stopword`  | `0`                  |                                                                        |
| `ngram_token_size`           | `1`                  | Depended on min searchable word length.                                |

## Documentation

### Application

* [Settings](./docs/Application-Settings.md)
* [Translation](./docs/Application-Translation.md)
* [Cache](./docs/Application-Cache.md)
* [GraphQL Cache Settings](./docs/Application-GraphQL-Cache-Settings.md)

### Development

* [Coding Standards](./docs/Coding-Standards.md)
* [API Interaction](./docs/API-Interaction.md)
* [Data Loader](./docs/DataLoader.md)
