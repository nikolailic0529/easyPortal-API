# Application Cache

The application stores different data in the Cache - queued jobs, calculated values, locks, etc. Dependent on type some data should be reset between application updates, some may be reset, some shouldn't be reset. To separate the data by type the application uses a few connections (= redis databases).

## `default`

| Property      | Value                           |
|---------------|---------------------------------|
| Setting       | `REDIS_DB = 2`                  |
| Cache store   | `state`                         |
| Reset command | `php artisan cache:clear state` |

Default connection. It is used for the application state data such as Unique Jobs, Atomic Locks, Service Data, Tokens, etc. that usually can be safely moved between application updates.


## `queue`

| Property      | Value                |
|---------------|----------------------|
| Setting       | `REDIS_QUEUE_DB = 0` |
| Cache store   | _none_               |
| Reset command | _none_               |

The connection is used only for [Queued Jobs](https://laravel.com/docs/9.x/queues) and [Laravel Horizon](https://laravel.com/docs/horizon). It must not be used for other types of data.


## `cache`

| Property      | Value                     |
|---------------|---------------------------|
| Setting       | `REDIS_CACHE_DB = 1`      |
| Cache store   | `null` (= default)        |
| Reset command | `php artisan cache:clear` |

The connection is used as the default for the cache and may be used for any other data. The data must be reset between application updates (this is required to avoid any possible breakage after update eg when `unserialize` will fail because of class deleted/moved). It is absolutely safe to remove all data.


## `permanent`

| Property      | Value                               |
|---------------|-------------------------------------|
| Setting       | `REDIS_PERMANENT_DB = 3`            |
| Cache store   | `permanent`                         |
| Reset command | `php artisan cache:clear permanent` |

This connection is used for data that may lead to performance degradation when gone and for this reason in most cases they should persist between application updates (or gracefully updated after). But it is still absolutely safe to remove all data.
