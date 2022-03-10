# Current implementation

## Jobs

### Importers

These jobs load all objects from Cosmos and needed only for one time while initial loading (in this case they must be run in order as in the table) or if you want to update all objects.

| Job                                    | Parallel? | 
|----------------------------------------|-----------|
| `ep-data-loader-distributors-importer` | No        |
| `ep-data-loader-resellers-importer`    | No        |
| `ep-data-loader-customers-importer`    | No        |
| `ep-data-loader-assets-importer`       | No        |
| `ep-data-loader-documents-importer`    | No        |


### Updaters

These jobs update/create changed/new objects and required to sync changes with Cosmos.

| Job                                   | Parallel? | 
|---------------------------------------|-----------|
| `ep-data-loader-distributors-updater` | No        |
| `ep-data-loader-resellers-updater`    | No        |
| `ep-data-loader-customers-updater`    | No        |
| `ep-data-loader-assets-updater`       | No        |
| `ep-data-loader-documents-updater`    | No        |


## Commands

There are also a few commands that allow to import/update objects, please use following command to find all of them:

```shell
php artisan list | grep ep:data-loader
```


## Limitations/Issues/Notes

1. DataLoader doesn't delete Distributor/Resellers/Customers/Assets/Documents if they no longer exist in Cosmos, it just writes an error into the logs if possible. Current realization cannot find deleted Distributor/Resellers/Customers. Assets can be found only while processing Reseller/Customer;
2. DataLoader skips Locations without zip and/or city;
3. Asset may be placed on:
    * Customer Location
    * Reseller Location (if Customer doesn't have required location)
    * Asset location (if Reseller doesn't have required location)
