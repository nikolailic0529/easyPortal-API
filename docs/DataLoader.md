# Current implementation

## Jobs

### Importers

These jobs load all objects from Cosmos and needed only for one time while initial loading (in this case they must be run in order as in the table) or if you want to update all objects.

| Job                                    | Queue                 | Parallel? | 
| -------------------------------------- | --------------------- | --------- |
| `ep-data-loader-distributors-importer` | data-loader-default   | No        |
| `ep-data-loader-resellers-importer`    | data-loader-default   | No        |
| `ep-data-loader-customers-importer`    | data-loader-default   | No        |
| `ep-data-loader-assets-importer`       | data-loader-default   | No        |


### Updaters

These jobs update/create changed/new objects and required to sync changes with Cosmos.

| Job                                    | Queue                 | Parallel? | 
| -------------------------------------- | --------------------- | --------- |
| `ep-data-loader-distributors-updater`  | data-loader-default   | No        |
| `ep-data-loader-resellers-updater`     | data-loader-default   | No        |
| `ep-data-loader-customers-updater`     | data-loader-default   | No        |
| `ep-data-loader-assets-updater`        | data-loader-default   | No        |


## Commands

### `php artisan ep:data-loader-update-distributor`

Sync the Distributor properties with Cosmos.


### `php artisan ep:data-loader-update-reseller`

Sync the Reseller properties with Cosmos. 


### `php artisan ep:data-loader-update-customer`

Sync the Customer properties with Cosmos.


### `php artisan ep:data-loader-update-asset`

Sync the Asset properties with Cosmos.


### `php artisan ep:data-loader-analyze-assets`

Search assets which

- not in the database
- without `reseller` or `customer`
- `reseller` or `customer` has an invalid type


### `php artisan ep:data-loader-import-discributors`

Import all distributors.


### `php artisan ep:data-loader-import-resellers`

Import all resellers.


### `php artisan ep:data-loader-import-customers`

Import all customers.


### `php artisan ep:data-loader-import-assets`

Import all assets.


## Limitations/Issues/Notes

2. (potential) If the Reseller/Customer has a huge amount of assets the sync may fail. The problem comes from the necessity to find all assets which are no longer related to this Reseller/Customer, current implementation uses `WHERE IN()` and the very long query may fail. When this will be actual then probably will be better just to skip this step, because we always update all resellers and will find these Assets just a bit later;
3. DataLoader doesn't delete Resellers/Customers/Assets if they no longer exist in Cosmos, it just writes an error into the logs if possible. Current realization cannot find deleted Resellers/Customers. Assets can be found only while processing Reseller/Customer;
4. DataLoader skips Locations without zip and/or city;
5. Asset may be placed on:
    * Customer Location
    * Reseller Location (if Customer doesn't have required location)
    * Asset location (if Reseller doesn't have required location, in this case `locations.object_type` will be `asset` and `locations.object_id = null`)
