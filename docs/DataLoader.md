# Current implementation

## Jobs

### ResellersImporterCronJob

| Cron           | Queue                 | Parallel? |
| -------------- | --------------------- | --------- |
| `0 0 * * *`    | data-loader-default   | No        |

1. Get resellers from Cosmos
2. Dispatch `ResellerUpdate` if reseller not found


### ResellersUpdaterCronJob

| Cron           | Queue                 | Parallel? |
| -------------- | --------------------- | --------- |
| `*/5 * * * *`  | data-loader-default   | No        |

1. Search outdated resellers
2. Dispatch `ResellerUpdate` if reseller outdated


### ResellerUpdate

| Queue                 | Parallel? |
| --------------------- | --------- |
| data-loader-reseller  | Yes       |

1. Update the Reseller (= run `php artisan data-loader:reseller <id> --assets`)


### LocationsCleanupCronJob

| Cron           | Queue                 | Parallel? |
| -------------- | --------------------- | --------- |
| `0 */6 * * *`  | data-loader-default   | No        |

While the import, some locations may become unused, this job removes them from the database.


## Commands

### `php artisan data-loader:reseller`

Sync the Reseller with Cosmos.


### `php artisan data-loader:customer`

Sync the Customer with Cosmos.


## Limitations/Issues/Notes

0. Every time we need to load all data from Cosmos, this is slow and not optimal (just for note, cold run: started 2021-03-16 10:43:29 and finished 2021-03-16 11:29:13 = ~50 min).
1. Cosmos may contain resellers with `type = CUSTOMER`, they will be processed only if `Cosmos.getResellers()` return them (afaik they are not returned now). It can be imported by `php artisan data-loader:reseller` if needed.
2. (potential) If the Reseller/Customer has a huge amount of assets the sync may fail. The problem comes from the necessity to find all assets which are no longer related to this Reseller/Customer, current implementation uses `WHERE IN()` and the very long query may fail. When this will be actual then probably will be better just to skip this step, because we always update all resellers and will find these Assets just a bit later;
3. DataLoader doesn't delete Resellers/Customers/Assets if they no longer exist in Cosmos, it just writes an error into the logs.
4. DataLoader skips Locations without zip and/or city.
5. Asset may be placed on:
    * Customer Location
    * Reseller Location (if Customer doesn't have required location)
    * Asset location (if Reseller doesn't have required location, in this case `locations.object_type` will be `asset` and `locations.object_id = null`)
6. DataLoader skips Assets without `sku` and/or `productDescription`
7. DataLoader cannot load Assets without Reseller.


# What can be improved

Instead of fetching all data from Cosmos we can fetch only updated:

* `getAssets(updatedAt > xxx): [Asset]!` - should return Assets which created/updated later than `xxx`
* `getResellers(updatedAt > xxx): [Company]!`
* `getAssetsByCustomerId(updatedAt > xxx)`
* `getDocuments(limits, updatedAt > xxx)` (new)
* `getDocumentById(id: guid)` (new)
* etc

in this case, we can have

* `ResellersUpdaterCronJob`
* `CustomersUpdaterCronJob`
* `AssetsUpdaterCronJob`

These jobs will update only changed data - faster and required fewer resources. Also `WHERE IN()` will be not needed anymore and can be removed. Only one con I see here: will be not possible to find objects that were removed from Cosmos, but as we are not deleting them there is no difference.
