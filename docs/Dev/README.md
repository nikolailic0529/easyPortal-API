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

### [Audit](../../app/Services/Audit)

Transparently records required events and changes of Models which are marked by [`Auditable`](../../app/Services/Audit/Contracts/Auditable.php) to provide an Audit Log for Administer Users.

### [Auth](../../app/Services/Auth)

Defines the list of available permissions, its properties (eg which type of organization can use them) and implementation to check them when [Gates](https://laravel.com/docs/authorization#gates) call.

### [DataLoader](../../app/Services/DataLoader)

Probably the most important part of Application, that import data from external source and store it in the database. Because of huge amount of items which should be imported (few millions), the DataLoader is optimized to reduce resources usage and queries count - it is process items chunk by chunk with to free memory between each chunk. Implementation is fully based on Processors concept and split into several entities to simplify the logic:

- [`Resolver`](../../app/Services/DataLoader/Resolver/Resolver.php) (internal) - performs a search of the model with given properties in the database and returns it or calls the factory if it does not exist (must be used in all cases when you need to find something in the database);
- [`Factory`](../../app/Services/DataLoader/Factory/Factory.php) (internal) - implements logic on how to create an application's model from an external data;
- [`Importer`](../../app/Services/DataLoader/Processors/Importer/Importer.php) (internal) - iterates over all external items and converts them into models though `Factory`, also implements prefetch logic to reduce the number of database queries and emit events with affected models (events contain not only imported but all models which related to the processed items in the current chunk; it is needed because may affect visibility of the model(s) for organizations);
- [`Loader`](../../app/Services/DataLoader/Processors/Loader/Loader.php) - composite processor to sync single item by KEY (Asset/Reseller/etc);
- [`Synchronizer`](../../app/Services/DataLoader/Processors/Synchronizer/Synchronizer.php) - composite processor to sync multiple items and perform sync of existing models which were missed while items iteration (eg if deleted from external source). Checking existing models is the fundamental difference over `Importer`;
- [`Finder`](../../app/Services/DataLoader/Finders) (internal) - contract which provides an implementation of how to find the model (in the database or in external source), used only in `Factory` and usually just call the `Loader`;

The last important thing - the Service is also overrides and extends the `Container` contract to simplify memory management. The [Custom Container](../../app/Services/DataLoader/Container/Container.php) is needed to create singletons (`Factory`/`Resolver`) while processing and reset them between chunks and after the end (unfortunately standard container is not designed for this and will require too much the boilerplate code).

### [Filesystem](../../app/Services/Filesystem)

The service provides helpers to simplify work with [standard disks](https://laravel.com/docs/filesystem) - you don't need to worry about name, setting, etc, just inject required [`Disk`](../../app/Services/Filesystem/Disk.php) and use it. It is especially useful for files related to Models. The [`JsonStorage`](../../app/Services/Filesystem/JsonStorage.php) is another useful helper designed to save/load data into/from the JSON file stored on the Disk.

### [I18n](../../app/Services/I18n)

The service wraps all logic related to internationalization and localization. It is implements load/save functionality for translations, extends standard Translator to work with fallback JSON files, and defines few useful helpers:

- [`CurrentLocale`](../../app/Services/I18n/CurrentLocale.php) - the Application has several levels where Locale can be set: config (default), Organization, User Settings, Session. This helper must be used to get the actual Locale;
- [`CurrentTimezone`](../../app/Services/I18n/CurrentTimezone.php) - same as `CurrentLocale` but for Time Zone;
- [`Formatter`](../../app/Services/I18n/Formatter.php) - converts different types (numbers/currencies/dates/etc) into string for End User according to its Locale/Timezone;

### [Keycloak](../../app/Services/Keycloak)

Provides [Custom User Provider](https://laravel.com/docs/authentication#adding-custom-user-providers) for [Keycloak](https://keycloak.org/) to authenticate users , Also defines processors to synchronize users and permissions with Keycloak.

### [Logger](../../app/Services/Logger)

Similar to [Telescope](https://laravel.com/docs/telescope) but more smart and can be used with long-running jobs. Currently used mostly to log queue jobs events.

### [Maintenance](../../app/Services/Maintenance)

Defines various helpers for maintenance, and jobs to enable/disable maintenance by schedule.

### [Notificator](../../app/Services/Notificator)

Wrapper around standard [Notifications](https://laravel.com/docs/notifications) to simplify their translations.

### [Organization](../../app/Services/Organization)

Implements all logic related to Multi-tenancy the most used classes are [`CurrentOrganization`](../../app/Services/Organization/CurrentOrganization.php) and [`RootOrganization`](../../app/Services/Organization/RootOrganization.php). Behind the scenes, service use [eloquent scopes](https://laravel.com/docs/eloquent#query-scopes) to hide models from other organizations.

### [Passwords](../../app/Services/Passwords)

Extends [passwords](https://laravel.com/docs/passwords) to provide better validation. Probably should be merged with the Auth service.

### [Queue](../../app/Services/Queue)

Application uses [Horizon](https://laravel.com/docs/horizon) as a supervisor. The service improves [queues](https://laravel.com/docs/queues) to satisfy application requirements:

- each scheduled job can be paused/resumed (based on Processors, see [`ProcessorJob`](../../app/Services/Queue/Concerns/ProcessorJob.php))
- each job may have progress/status that can be shown in UI (this is actually a Processor's State)
- each job can be stopped (external signals like `queue:restart` are also handled)

### [Recalculator](../../app/Services/Recalculator)

Defines the jobs to recalculate calculated properties of models, based on Processors and optimized to huge amount of object.

### [Search](../../app/Services/Search)

Customized [Elastic](https://www.elastic.co/) search engine implementation for [Scout](https://laravel.com/docs/scout) based on [elastic-scout-driver](https://github.com/babenkoivan/elastic-scout-driver) and [elastic-scout-driver-plus](https://github.com/babenkoivan/elastic-scout-driver-plus). The main goals of customization were:

- Metadata support (for multi-tenancy)
- Index settings/Fields types support (for better search by serial number/etc)
- Provide a simple syntax for search (see [`SearchString`](../../graphql/Scalars/SearchString.graphql))
- Search in multiple indexes (see [UnionModel](../../app/Services/Search/Eloquent/UnionModel.php))
- Data migration with zero downtime

### [Settings](../../app/Services/Settings)

One requirement was "all settings must be stored in one file", the second was "settings should be editable from UI". The service implements these requirements and a bit more, eg you can define settings type and validation rules, can use ENV, get the list of settings, group them, etc.

Please see [Settings](../Application/Settings.md) for more details.

### [Tokens](../../app/Services/Tokens)

External services usually required Access Token for access. The service defines [OAuth2Token](../../app/Services/Tokens/OAuth2Token.php) helper that encapsulates all logic related to obtaining OAuth 2.0 Access Token for Client Credentials Grant.

### [View](../../app/Services/View)

Defines custom directives for [Blade](https://laravel.com/docs/blade).

## [GraphQL](../../app/GraphQL)

One of the main parts of the Application that implements GraphQL API. It is based on [Lighthouse](https://lighthouse-php.com/) with custom directives which add required functionality:

- `@authOrg`, `@authOrgRoot`, `@authOrgReseller`, [etc](../../app/GraphQL/Directives/Directives/Auth) - to check current Organization;
- `@authGuest`, `@authMe`, `@authRoot`, [etc](../../app/GraphQL/Directives/Directives/Auth) - to check User and Permissions;
- [`@paginated`](../../app/GraphQL/Directives/Directives/Paginated) (and `@paginatedRelation` for relations) - pagination support. Unlike similar Lighthouse directives, ours do not perform page counting and much faster for this reason. They are also automatically add queries to get aggregated data into the schema;
- [`@aggregated`](../../app/GraphQL/Directives/Directives/Aggregated) - allow to get aggregated data (will be added automatically by `@paginated` so you need to use them directly);
- [`@orgProperty`](../../app/GraphQL/Directives/Directives/Org) (and `@orgRelation` for relations) - model's property may have unique value for Root Organization and for each Reseller, these directive allow get the right value transparently;
- [`@cached`](../../app/GraphQL/Directives/Directives/Cached) - advanced cache, please see [GraphQL Cache](../Application/GraphQL-Cache.md) for more details;
- [`@translate`](../../app/GraphQL/Directives/Directives/Translate.php) - translate model's properties;
- [`@mutation`](../../app/GraphQL/Directives/Directives/Cached) - nested mutations support and advanced input validation with [validation directives](../../app/GraphQL/Directives/Rules). The main reason it uses that some validation rules required context/parent object for correct validation, but, unfortunately, it is not possible out the box. Please note that you may find few mutations which are not use `@mutation`, all of them are deprecated and should/will be replaced in the future;
- [`@relation`](../../app/GraphQL/Directives/Directives/Relation.php) - unified directive to get related models (required for `@paginated`);

These directives replace Lighthouse's directives and should be used in all cases. The (almost) full list of not recommended directives and its replacements can be found [here](../../app/GraphQL/ServiceTest.php).

Application also provides several [scalars](../../app/GraphQL/Scalars). The most important is `HtmlString`. It must be used in all cases when input contains HTML (to perform HTML sanitization).
