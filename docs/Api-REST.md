# REST API

How to find permissions/etc please see in [API Interaction](./API-Interaction.md).

## `GET /application`

Returns Application information in JSON.

## `GET /oems/{oem}`

Returns Service Groups/Levels translations as attachment.

| Parameter | Description |
|-----------|-------------|
| `{oem}`   | Oem UUID    |

## `GET /files/{file}`

Returns specified `File` as attachment.

| Parameter | Description |
|-----------|-------------|
| `{file}`  | File UUID   |

## `POST /download/{format}`

Returns GraphQL query result as attachment of the specified format.

| Parameter       | Required? | Type                    | Description                         |
|-----------------|-----------|-------------------------|-------------------------------------|
| `{format}`      | Yes       | `csv`, `xlsx` or `pdf`  | File format.                        |
| `root`          | Yes       | `string`                | The `selector` of main data point.  |
| `query`         | Yes       | `string`                | GraphQL query.                      |
| `operationName` |           | `string`                | GraphQL operation name.             |
| `variables`     |           | `array<string, mixed>`  | GraphQL variables.                  |
| `headers`       | Yes       | `array<string, string>` | Columns names and value `selector`s |

> ⚠ **Important**
>
> Please do not include unnecessary/not used fields into the query because it will lead to performance degradation.

The `query`, `variables` and `operationName` is the standard [GraphQL POST Request](https://graphql.org/learn/serving-over-http/#post-request) parameters.

The `selector` is the dot separated string that defines a path to select value from results. Scalar values will be returned as is, but if the value is an array or object it will be encoded into JSON. For columns the path is relative to the `root` selector. It also supports a few simple functions to modify the value, they are described below.

- `path.to.field` - get the value from the path;
- `items.*.field` - get the value for each item in `items` and return a string containing a string representation of all the items in the same order, with the `, ` between each item. If `items` is not an array or `field` doesn't exist, the `` (empty string) will be returned. Please note that the `field` cannot contain dots.

| Example                 | Result                                           |
|-------------------------|--------------------------------------------------|
| `location.country.name` | `Country A`                                      |
| `coverages.*.name`      | `Covered, On Contract`                           |
| `coverages`             | `[{"name": "Covered"}, {"name": "On Contract"}]` |

```graphql
query {
    assets {            # root: data.assets
        id              # selector: id
        nickname        # selector: nickname
        product {       # selector: product
            name        # selector: product.name
        }
        location {      # selector: location
            country {   # selector: location.country
                name    # selector: location.country.name
            }
            city {      # selector: location.city
                name    # selector: location.city.name
            }
        }
        coverages {     # selector: coverages
            name        # selector: coverages.name
        }
    }
}
```

```json
{
    "data": {
        "assets": [
            {
                "id": "00000000-0000-0000-0000-000000000000",
                "nickname": null,
                "product": {
                    "name": "Product A"
                },
                "location": {
                    "country": {
                        "name": "Country A"
                    },
                    "city": {
                        "name": "City A"
                    }
                },
                "coverages": [
                    {
                        "name": "Covered"
                    },
                    {
                        "name": "On Contract"
                    }
                ]
            }
        ]
    }
}
```


### Available functions

#### `concat(selector-a, selector-b, ...)`

Returns the string that results from concatenating the `selector` values by the space (` `).

| Example                                             | Result             |
|-----------------------------------------------------|--------------------|
| `concat(location.country.name, location.city.name)` | `Country A City A` |

### Non-paginated query


### Paginated query
