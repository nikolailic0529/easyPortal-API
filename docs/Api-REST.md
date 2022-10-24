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

The `selector` is the dot separated string that defines a path to select value from results. 


- 


For columns the path is relative to the `root` selector. 

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
        }
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
