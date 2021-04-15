# Application Config


## Priorities

Global/Application setting has the following priorities (first win):

- `.env` (or `.evn.<name>`)
- `settings.json`
- Constants.php
- other files

Values from `.env` will be loaded only if the config is not cached, but regardless of config cached or not the `settings.json` cannot overwrite settings defined in `.env` (GraphQL endpoint mark these settings as `readonly`).

Each time when `settings.json` changed SettingsService will dispatch `config:cache` and `queue:restart` into `EP_SETTINGS_CONFIG_UPDATE_QUEUE` queue. Even if `config:cache` is queued (=waiting to run) application will correctly determine values of settings (except queues, they always require the restart to use fresh settings).

Some settings may be used by Laravel at a very early stage when our settings are not yet loaded, these settings can be set only in the cached config and will be used only after `config:cache`.


## Recovering if error

If for some reason `settings.json` is corrupted (= contains invalid JSON) there is a choice of what the application should do in this case - `EP_SETTINGS_RECOVERABLE`. This setting can be set **only** in `.env` and may have the following values:

- `true` - default, the application will continue to run with default settings (without `settings.json`)
- `false` - the application will show an error


## `Constants.php`

The [`./config/Constants.php`](../config/Constants.php) contains all known settings, each setting is a class constant with special attributes (full list of attributes can be found in [this directory](../app/Services/Settings/Attributes)):

- `Setting`, `Service`, `Job` - required (one of), determines name and type of the setting;
- `Internal` - settings marked by this attribute cannot be set in `settings.json` and will not be visible for UI;
- `Secret` - can be used to hide confidential values (passwords, etc) from UI (= UI will receive `********` instead of real value);
- `Type` - the type, also used for validation (mandatory for arrays);
- `Group` - (UI) group name
- `PublicName` - (UI) setting will be returned by `client {settings: []}` endpoint;


## Localization

By default, Service uses phpdoc as the description of the setting, but it can be translated by the [special strings](Application-Translation.md#settingsgroupsgroup).
