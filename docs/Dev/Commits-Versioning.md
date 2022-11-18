# Commits & Versioning

We are following [Semantic Versioning](https://semver.org/) and use [Conventional Commits](https://www.conventionalcommits.org/) with [semantic-release](https://github.com/semantic-release/semantic-release) to automatically generate version/changelog.


## Breaking Changes

Following changes must be marked as "Breaking Change" (by `!`):

- Model class renamed/deleted
- Job class renamed/deleted
- Commands renamed/deleted, arguments/options renamed/deleted, default values of arguments/options changed
- Incompatible GraphQL/REST API changes (query/mutation deleted, renamed, etc)
- Database table renamed/deleted, column renamed/deleted, other incompatible changes
- Setting rename/delete
- Migration that is not possible to rollback fully
- Any other changes that required action to restore previous behavior

## Commits structure

All useful comment messages should be structured as follows:

```
<type>[<scope>]: <description>

[optional body]

[optional footer(s)]
```

Where `<type>` required and can be (defaults from "conventionalcommits" preset)

| Type       | Description              | Increment version? |
|------------|--------------------------|--------------------|
| `feat`     | Features                 | **Yes** (minor)    |
| `fix`      | Bug Fixes                | **Yes** (patch)    |
| `perf`     | Performance Improvements | **Yes** (patch)    |
| `revert`   | Reverts                  | **Yes** (patch)    |
| `docs`     | Documentation            | No                 |
| `style`    | Styles                   | No                 |
| `chore`    | Miscellaneous Chores     | No                 |
| `refactor` | Code Refactoring         | No                 |
| `test`     | Tests                    | No                 |
| `build`    | Build System             | No                 |
| `ci`       | Continuous Integration   | No                 |

The `<scope>` is optional and should be:

- a Service name (eg `DataLoader`, `Search`) if changes related to the service
- `Utils` for utils
- `Export`
- etc.

The breaking changes should be marked as `!` (eg `feat(DataLoader)!: description`) and will increase the major version.

And finally `<description>`, it is also required and should provide the short useful summary about change that also will be used for changelog generation.
