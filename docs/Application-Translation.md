# Translation

## Default files

All files located in [`resources/lang`](../resources/lang) directory.


## Addition strings

These strings are not included by default but supported.


### `errors.http.<status code>`

HTTP's errors with uncommon "status code".


### `errors.messages.<message>`

Any exception with "message".


### `settings.groups.<group>`

Setting group name.


### `settings.descriptions.<setting>`

Description for setting.


### `settings.services.<service>`

Description for service.


### `settings.jobs.<service>`

Description for jobs.


### `notifications.<service>.<notification>.<string>`

Default translations for Notification.

The `<string>` can be:

- `level` (`success`, `error`, etc)
- `subject`
- `greeting`
- `intro`
- `outro`
- `salutation`

Available replacements:

- `:appName`
- `:userName`
- `:userGivenName`
- `:userFamilyName`

### `graphql.directives.@mutation.object.<object>`

Object name for `@mutation`.
