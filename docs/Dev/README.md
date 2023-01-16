# Development

## Introduction

The application is based on Laravel and Lighthouse. It is uses standard/reference implementation where possible but with required (to business) changes. The project uses more logical, easy for development and maintenance and maybe a bit unusual structure to organize the code: the code is split into a few (mostly) independent parts/services. So usually you don't need to worry about other parts when you work on something.

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
* [Settings hierarchy](../Application/Settings.md)
* [Cache](../Application/Cache.md) structure and [GraphQL Cache](../Application/GraphQL-Cache.md) details
* this document

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

### Models

PK is a UUID. The records must not be deleted from the database (including pivots), except inside migrations when the structure changes.

### Multi-tenancy

This is a multi-tenant application - all data is related to a specific organization and can be viewed from different points of view (root/reseller/customer). The Root User and users from Root Org can see data from all organizations. But the Normal User can see data related to its own organization only. Application automatically applies scopes based on the current org/user to all queries and hides data which the current user should not see. In almost all cases, you should not worry about it, just keep this in mind.

### Authentication & Authorization

Application use [Custom User Provider](https://laravel.com/docs/authentication#adding-custom-user-providers) for [Keycloak](https://keycloak.org/) to authenticate users (except root users that should sign in directly). And pretty simple implementation based on standard [Gates](https://laravel.com/docs/authorization#gates) to check permissions (implemetation is also respect multi-tenancy). Please see the [Keycloak](../../app/Services/Keycloak) and [Auth](../../app/Services/Auth) services for more details.

## Services

### Utils

Contain shared code that can be used in any part of Application.

#### JsonObject

There are a lot of places where JSON from external services needs to be converted into application data. Very often we need to covert JSON types into application types, eg ISO 8601 string into `DateTimeInterface` to simplify this and satisfy PHP Stan the [`JsonObject`](../../app/Utils/JsonObject) should be used. It transparently converts JSON into PHP object(s) and vice versa.

#### Models

As mentioned in the [Coding Standards](Coding-Standards.md) all models are subclasses of [`Model`](../../app/Utils/Eloquent/Model.php) (or [`Pivot`](../../app/Utils/Eloquent/Pivot.php) for pivots). The class provides some basic features (like PKs, `SoftDeletes`, etc), and, in additional to them, it is also implements few concepts which are required (mostly) for DataLoader part. The main/most important concepts are described below. Please look at the code to check the others.

While importing data the DataLoader may produce a lot of inserts. The [`SmartSave`](../../app/Utils/Eloquent/SmartSave) transparently groups the sequence of insert requests into one query, if possible. It is also allow delay saving/inserting relations until `Model::save()` called which makes code cleaner.

All multi-tenancy works happen inside query scopes. It is good and makes sure that the User will see only allowed data. Unfortunately, Laravel doesn't provide a way to disable scopes completely for all models when it is necessary. Here the [`GlobalScopes`](../../app/Utils/Eloquent/GlobalScopes) comes into play. Please note that it is work only for our/app scopes.

#### Iterators & Processors

By default, Laravel doesn't provide any way to iterate over a potentially infinite count of objects with abilities to stop, pause and resume processing. It is one of the base requirements for the Application, so we have following cool things :)

[Iterators](../../app/Utils/Iterators) which are specially designed to process a huge amount of items from different sources. They are supports iteration restoration from the specified offset, provides chunks support, and error handling to avoid stopping iteration if one item failed.

The [Processor](../../app/Utils/Processor) that extends the concept of Iterators to provide a generic way to perform action(s) over the iterable items with ability to stop/pause/resume the processing. You can also combine multiple processors into one with [`CompositeProcessor`](../../app/Utils/Processor/CompositeProcessor.php) and/or easy convert any Processor into console command with [`ProcessorCommand`](../../app/Utils/Processor/Commands/ProcessorCommand.php).

Processors/Iterators are the main parts of how application import/process the data and used for all jobs/commands, so it is very important to understand these concepts.
