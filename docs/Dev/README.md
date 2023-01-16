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

### Localization

Application uses out the box localization with few fixes/additions: all strings are stored in JSON files (php files should not be used) and strings from fallback locales will be loaded automatically (see [#36760](https://github.com/laravel/framework/issues/36760) for more details), Administer users can modify translations through UI.

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

## Audit

Transparently records required events and changes of Models which are marked by [`Auditable`](../../app/Services/Audit/Contracts/Auditable.php) to provide an Audit Log for Administer Users.

## Auth

Defines the list of available permissions, its properties (eg which type of organization can use them) and implementation to check them when [Gates](https://laravel.com/docs/authorization#gates) call.

## DataLoader

Probably the most important part of Application, that import data from external source and store it in the database. Because of huge amount of items which should be imported (few millions), the DataLoader is optimized to reduce resources usage and queries count - it is process items chunk by chunk with to free memory between each chunk. Implementation is fully based on Processors concept and split into several entities to simplify the logic:

- [`Resolver`](../../app/Services/DataLoader/Resolver/Resolver.php) (internal) - performs a search of the model with given properties in the database and returns it or calls the factory if it does not exist (must be used in all cases when you need to find something in the database);
- [`Factory`](../../app/Services/DataLoader/Factory/Factory.php) (internal) - implements logic on how to create an application's model from an external data;
- [`Importer`](../../app/Services/DataLoader/Processors/Importer/Importer.php) (internal) - iterates over all external items and converts them into models though `Factory`, also implements prefetch logic to reduce the number of database queries and emit events with affected models (events contain not only imported but all models which related to the processed items in the current chunk; it is needed because may affect visibility of the model(s) for organizations);
- [`Loader`](../../app/Services/DataLoader/Processors/Loader/Loader.php) - composite processor to sync single item by KEY (Asset/Reseller/etc);
- [`Synchronizer`](../../app/Services/DataLoader/Processors/Synchronizer/Synchronizer.php) - composite processor to sync multiple items and perform sync of existing models which were missed while items iteration (eg if deleted from external source). Checking existing models is the fundamental difference over `Importer`;
- [`Finder`](../../app/Services/DataLoader/Finders) (internal) - contract which provides an implementation of how to find the model (in the database or in external source), used only in `Factory` and usually just call the `Loader`;

The last important thing - the Service is also overrides and extends the `Container` contract to simplify memory management. The [Custom Container](../../app/Services/DataLoader/Container/Container.php) is needed to create singletons (`Factory`/`Resolver`) while processing and reset them between chunks and after the end (unfortunately standard container is not designed for this and will require too much the boilerplate code).

## Filesystem

The service provides helpers to simplify work with [standard disks](https://laravel.com/docs/filesystem) - you don't need to worry about name, setting, etc, just inject required [`Disk`](../../app/Services/Filesystem/Disk.php) and use it. It is especially useful for files related to Models. The [`JsonStorage`](../../app/Services/Filesystem/JsonStorage.php) is another useful helper designed to save/load data into/from the JSON file stored on the Disk.

## I18n

The service wraps all logic related to internationalization and localization. It is implements load/save functionality for translations, extends standard Translator to work with fallback JSON files, and defines few useful helpers:

- [`CurrentLocale`](../../app/Services/I18n/CurrentLocale.php) - the Application has several levels where Locale can be set: config (default), Organization, User Settings, Session. This helper must be used to get the actual Locale;
- [`CurrentTimezone`](../../app/Services/I18n/CurrentTimezone.php) - same as `CurrentLocale` but for Time Zone;
- [`Formatter`](../../app/Services/I18n/Formatter.php) - converts different types (numbers/currencies/dates/etc) into string for End User according to its Locale/Timezone;

## Keycloak

Provides [Custom User Provider](https://laravel.com/docs/authentication#adding-custom-user-providers) for [Keycloak](https://keycloak.org/) to authenticate users , Also defines processors to synchronize users and permissions with Keycloak.

## Logger

Similar to [Telescope](https://laravel.com/docs/telescope) but more smart and can be used with long-running jobs. Currently used mostly to log queue jobs events.

## Maintenance

Defines various helpers for maintenance, and jobs to enable/disable maintenance by schedule.

## Notificator

Wrapper around standard [Notifications](https://laravel.com/docs/notifications) to simplify their translations.
