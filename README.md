# EasyPortal API

## Requirements

| Package    | Version |
| ---------- | ------- |
| PHP        | 8.0+    | 
| MySQL      | 8.0+    | 


### MySQL

| Property                      | Value                     |
| ----------------------------- | ------------------------- |
| Charset                       | utf8mb4                   |
| Collation                     | utf8mb4_0900_as_ci        |
| `default_storage_engine`      | InnoDB                    |
| `innodb_default_row_format`   | DYNAMIC/COMPRESSED ("Large Index Key Prefix Support" required) |


## Documentation


### Application

* [Settings](./docs/Application-Settings.md)
* [Translation](./docs/Application-Translation.md)


### Development

* [Coding Standards](./Coding-Standards.md)
* [API Interaction](./docs/API-Interaction.md)
* [Data Loader](./docs/DataLoader.md)
