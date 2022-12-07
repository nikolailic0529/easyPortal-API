# Application GraphQL Cache

| [Cache store](Cache.md) | `permanent` |
|---------------------------------------|-------------|


## Overview

Some endpoints can return cached values to reduce server load and/or response time. Each value has a corresponding key that includes current locale, organization, query path, args, etc. Unfortunately, there is no way to track which keys should be reset when something changed to DB. Also, resetting the cache/key is not an optimal solution because can create load spikes after the value is gone. For these reasons the application marks (whole) cache "expired" when something changed (eg data imported or recalculated) and then gracefully update it.

To be more flexible there are two types of cache:

1. `Normal`: For long-running queries (eg Asset statistics). These queries will be cached always.
2. `Threshold`: For queries that may be slow (eg Map areas). They will be cached only if their execution time is greater than `EP_CACHE_GRAPHQL_THRESHOLD`.

Independent of type each key/value pair has a lifetime limited by

* `EP_CACHE_GRAPHQL_LIFETIME` - value can be "expired" only after this amount of time. The setting allows reducing the server/db load when the key expires very often (eg while data importing).
* `EP_CACHE_GRAPHQL_TTL` - the key will be automatically removed from the cache after this amount of time.


## Graceful Expiration

As mentioned in the previous chapter when the key is gone it may create a load spike if many requests want this key. To avoid this application will execute the query for the expired key with some probability inside the following interval

    (EP_CACHE_GRAPHQL_TTL - EP_CACHE_GRAPHQL_TTL_EXPIRATION, EP_CACHE_GRAPHQL_TTL)

This interval is called "Expiration Interval". The probability of executing is growing linearly from 0 to 1 inside it. In practice, it means that at the beginning of the interval only a few requests will execute the query and will update the cache, all others requests will use the "expired" value.

The same applies to `EP_CACHE_GRAPHQL_LIFETIME`, but the interval is

    (EP_CACHE_GRAPHQL_LIFETIME, EP_CACHE_GRAPHQL_LIFETIME + EP_CACHE_GRAPHQL_LIFETIME_EXPIRATION)


## Atomic Locks

In addition to "Graceful Expiration", the Application also tries to use [Locks](https://laravel.com/docs/cache#atomic-locks) to reduce the amount of queries executed at the same time.

When Locks is enabled (`EP_CACHE_GRAPHQL_LOCK_ENABLED = true`) and query executing time is unknown (for `Normal` type only) or greater than `EP_CACHE_GRAPHQL_LOCK_THRESHOLD` (for all types) the first request locks the query for `EP_CACHE_GRAPHQL_LOCK_TIMEOUT` (the lock will be released after this amount of time to avoid stuck) and execute it. All other requests just wait until it is finished (or timeout defined by `EP_CACHE_GRAPHQL_LOCK_WAIT` is ended) and then will use the calculated value from the cache/first query (or will run the query itself if the wait timeout ended).

The Lock is also guaranteeing that inside "Expiration Interval" only one request will execute the query. All other requests will immediately receive the expired value from the cache.
