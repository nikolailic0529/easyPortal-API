# Development

## Introduction

The application is based on Laravel and Lighthouse. It is uses standard/reference implementation where possible but with required (to business) changes. The project uses more logical, easy for development and maintenance and maybe a bit unusual structure to organize the code: the code is split into a few (mostly) independent parts/services (so usually you don't need to worry about other parts when you work on something).

## Where To Begin

* [Laravel documentation](https://laravel.com/docs)
* [GraphQL](https://graphql.org/) and [Lighthouse](https://lighthouse-php.com/)
* [Lara ASP documentation](https://github.com/LastDragon-ru/lara-asp)
* [Coding Standards](Coding-Standards.md)
* [Commits & Versioning](Commits-Versioning.md)
* [Database Structure](../database.mwb) ([MySQL Workbench](https://www.mysql.com/products/workbench/))
* [Authorization Flow](../AuthorizationFlow.drawio) (use https://diagrams.net/)
* [Laravel Scout](https://laravel.com/docs/scout) and [Elasticsearch](https://www.elastic.co/)
* [Keycloak](https://www.keycloak.org/) and [Oauth 2](https://oauth.net/2/)
* [PHPStan](https://phpstan.org/), etc

## Key points

### APIs

We have two types of APIs:

* GraphQL. It is one of the main parts of the application; It is based on Lighthouse with several directives which add required functionality;
* REST. It has only a few endpoints and is mostly used to download files that is not possible through GraphQL;

Both use standard `web` guard and middlewares.

### Dates

Dates are immutable and use ISO 8601 format for serialization. Application internal timezone is always `UTC`. GraphQL will also automatically convert incoming `DateTime` into the internal timezone, so you need to worry about it. Please note that `REST` doesn't convert `DateTime` yet.

### Input validation

The input validation is more strict and reasonable than default. Please check [the code](../../app/Utils/Validation/Validator.php) to understand why these changes are required. Differences from Laravel:

* Empty strings will be validated
    ```php
    Validator::make(['value' => ''], ['value' => ['size:2']])->passes();
    // `true` in Laravel
    // `false` in App
    ```
* Validation will be stopped for `null` after `nullable`, so
    ```php
    Validator::make(['value' => null], ['value' => ['nullable', 'required']])->passes();
    // `false` in Laravel
    // `true` in App
    ```
