# Upgrade

## How to

1. Backup!
2. `php artisan ep:maintenance-start --duration="1 hour" --message="Updating"` (optional)
3. `sudo supervisorctl stop ep-api:*` (where `ep-api:` the group name from supervisor config; before running this command also recommended stop all jobs on the Settings / Services page)
4. Update application files (just for example; there are a few other ways how it can be done)
    * `storage` - must not be touched
    * `graphql` - must be removed (or app will fail)
    * Remove `bootstrap/cache/*.php` (needed because in some situations these files can lead to a "Class 'XXX' not found" error and the app will be broken)
    * All other directories (except `vendor`) and files is recommended to remove
    * Copy new files
5. `composer install`
6. `composer dump-autoload --optimize`
7. `php artisan optimize:clear`
8. `php artisan cache:clear`
9. `php artisan lighthouse:clear-cache`
10. `php artisan config:cache`
11. `php artisan event:cache`
12. `php artisan optimize` (just for the case, because it in Laravel 8/9 it is equivalent of `config:cache` + `route:cache`)
13. `php artisan route:clear` (required because when Laravel run from subdirectory and routes cached the root route `/` will not work; if Laravel run from the root it probably can be omitted)
14. `php artisan migrate`
15. `php artisan storage:link`
16. `php artisan ep:maintenance-version-update "version"` (required to update application version)
    > The Following command can be used for Jenkins:
    > ```shell
    > php artisan ep:maintenance-version-update "$(cd "$WORKSPACE" && git describe --abbrev=0 --tags | sed -nr "s/^v\.?(.+)$/\1/p")" --commit="$GIT_COMMIT" --build="$BUILD_NUMBER"
    > ```
17. `sudo supervisorctl restart ep-api:*` (where `ep-api:` the group name from supervisor config)
18. `php artisan ep:maintenance-stop` (optional; requred if `ep:maintenance-start` was used)
