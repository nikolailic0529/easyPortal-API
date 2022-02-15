# Upgrade

## How to

0. Backup!
1. `sudo supervisorctl stop ep-api:*` (where `ep-api:` the group name from supervisor config)
2. Update application files. The recommended way is using `git checkout` and then remove `bootstrap/cache/*.php` (needed because in some situations these files can lead to a "Class 'XXX' not found" error and the app will be broken). If it is not possible then
    * `storage` - must not be touched
    * `graphql` - must be removed (or app will fail)
    * Remove `bootstrap/cache/*.php`
    * All other directories (except `vendor`) and files is recommended to remove
    * Copy new files
3. `composer install`
4. `composer dump-autoload --optimize`
5. `php artisan optimize:clear`
6. `php artisan cache:clear`
7. `php artisan lighthouse:clear-cache`
8. `php artisan config:cache`
9. `php artisan route:cache`
10. `php artisan event:cache`
11. `php artisan optimize` (just for the case, because it in Laravel 8/9 it is equivalent of `config:cache` + `route:cache`)
12. `php artisan migrate`
13. `php artisan storage:link`
14. `sudo supervisorctl restart ep-api:*` (where `ep-api:` the group name from supervisor config)
