# IT Asset Hub API

## Requirements

| Package       | Version / Notes                                                                                                                                         |
|---------------|---------------------------------------------------------------------------------------------------------------------------------------------------------|
| PHP           | [~8.0.0](https://php.net/)                                                                                                                              |
| MySQL         | [8.0+](https://www.mysql.com/)                                                                                                                          |
| Redis         | and [phpredis](https://github.com/phpredis/phpredis) (recommended) or see https://laravel.com/docs/redis                                                |
| Composer      | [^2.3](https://getcomposer.org/)                                                                                                                        |
| Elasticsearch | [~7.10.0](https://www.elastic.co/) + [passwords for default users](https://www.elastic.co/guide/en/elasticsearch/reference/current/built-in-users.html) |
| Other         | Run `composer check-platform-reqs` to get list of required PHP extensions.                                                                              |


### MySQL

| Property                    | Value                | Description                                                            |
|-----------------------------|----------------------|------------------------------------------------------------------------|
| `character_set_server`      | `utf8mb4`            | Default Charset[^1]                                                    |
| `collation_server`          | `utf8mb4_0900_as_ci` | Default Collation[^1]                                                  |
| `default_storage_engine`    | `InnoDB`             |                                                                        |
| `innodb_default_row_format` | DYNAMIC/COMPRESSED   | "Large Index Key Prefix Support" required                              |
| Fulltext `ngram` parser     | required             | See https://dev.mysql.com/doc/refman/8.0/en/fulltext-search-ngram.html |
| `innodb_ft_enable_stopword` | `0`                  |                                                                        |
| `ngram_token_size`          | `1`                  | Depended on min searchable word length.                                |

[^1]: can be set on database level, but should be applied for all app databases.


## Documentation

### Application

* [Upgrade](./docs/Upgrade.md)
* [Settings](./docs/Application-Settings.md)
* [Translation](./docs/Application-Translation.md)
* [Cache](./docs/Application-Cache.md)
* [GraphQL Cache](./docs/Application-GraphQL-Cache.md)
* [Data Loader](./docs/DataLoader.md)
* [Keycloak Settings](docs/Keycloak/README.md)


### Development

* [Coding Standards](./docs/Coding-Standards.md)
* [API Interaction](./docs/API-Interaction.md)
* [Commits & Versioning](./docs/Commits-Versioning.md)
