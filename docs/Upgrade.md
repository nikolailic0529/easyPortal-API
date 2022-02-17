# Upgrade

## How to

0. Backup!
1. `php artisan ep:maintenance-start --duration="1 hour" --message="Updating"` (optional)
2. `sudo supervisorctl stop ep-api:*` (where `ep-api:` the group name from supervisor config)
3. Update application files (just for example; there are a few other ways how it can be done)
    * `storage` - must not be touched
    * `graphql` - must be removed (or app will fail)
    * Remove `bootstrap/cache/*.php` (needed because in some situations these files can lead to a "Class 'XXX' not found" error and the app will be broken)
    * All other directories (except `vendor`) and files is recommended to remove
    * Copy new files
4. `composer install`
5. `composer dump-autoload --optimize`
6. `php artisan optimize:clear`
7. `php artisan cache:clear`
8. `php artisan lighthouse:clear-cache`
9. `php artisan config:cache`
10. `php artisan event:cache`
11. `php artisan optimize` (just for the case, because it in Laravel 8/9 it is equivalent of `config:cache` + `route:cache`)
12. `php artisan route:clear` (required because when Laravel run from subdirectory and routes cached the root route `/` will not work; if Laravel run from the root it probably can be omitted)
13. `php artisan migrate`
14. `php artisan storage:link`
15. `php artisan ep:maintenance-version-update "$TAG_NAME" --commit="$GIT_COMMIT" --build="$BUILD_NUMBER"`
16. `sudo supervisorctl restart ep-api:*` (where `ep-api:` the group name from supervisor config)
17. `php artisan ep:maintenance-stop` (optional)
