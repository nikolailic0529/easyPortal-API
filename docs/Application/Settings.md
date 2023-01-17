# Application Config


## Priorities

Global/Application settings has the following priorities (first win):

- `.env` (or `.evn.<name>`)
- `settings.json`
- `Constants.php`
- other files

Settings defined in `.env` are global and cannot be overwritten by `settings.json` (GraphQL endpoint mark these settings as `readonly`).

Each time when `settings.json` was changed SettingsService will dispatch `ep-settings-config-update` that will call `config:cache` (if config cached) and `queue:restart`.


## `Constants.php`

The [`./config/Constants.php`](../../config/Constants.php) contains all known settings, each setting is a class constant with special attributes (full list of attributes can be found in [this directory](../../app/Services/Settings/Attributes)):

- `Setting`, `Service`, `Job` - required (one of), determines name and type of the setting;
- `Internal` - settings marked by this attribute cannot be set in `settings.json` and will not be visible for UI;
- `Secret` - can be used to hide confidential values (passwords, etc) from UI (= UI will receive `********` instead of real value);
- `Type` - the type, also used for validation (mandatory for arrays);
- `Group` - (UI) group name
- `PublicName` - (UI) setting will be returned by `client {settings: []}` endpoint;


## Localization

By default, Service uses phpdoc as the description of the setting, but it can be translated by the [special strings](Translation.md#settingsgroupsgroup).
