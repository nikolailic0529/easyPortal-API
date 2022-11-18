# API Interaction

## GraphQL

The main API is [GraphQL](https://graphql.org/). The main endpoint is located by `/api/graphql` URL. You can use [GraphQL Playground](/api/graphql-playground) (`/api/graphql-playground`) or any other similar tool to check the schema and run the queries.

## REST API

There are also few REST endpoints, most of them described in [REST section](REST.md) but if you want to find all of them or check for parameters/permissions please see the next section.

### Basics

API is the REST-Based, and uses following methods:

| Method   | Action        |
| -------- | ------------- |
| `GET`    | Get object(s) |
| `POST`   | Create object |
| `PUT`    | Update object |
| `DELETE` | Delete object |

in addition to them it uses `POST` to perform some actions and to search objects:

```
POST /objects/search
Content-Type: application/json

{
    ...
}
```

will return filtered results. The main reasons why search uses `POST` are

* Angular out the box doesn't support encoding query params in the PHP style (`q[]=1&q[]=2`)
* Length of the URL is limited

### Types

API is the strict typed, so for example if Request requires the `int` UI must pass integer value `123` not `'121'`. Same for Responses (if schema contains `bool` it must be `true` or `false` not `0`, `1`, etc). Date and datetime always must be in ISO 8601:

| Type     | Format          |
| -------- | --------------- |
| Date     | `Y-m-d`         |
| DateTime | `Y-m-d\TH:i:sP` | 

### Available methods

_This section is actual until we don't have api docs auto-generation. Information actual for most cases._

#### How to find supported routes?

```shell
$ php artisan route:list
+--------+----------+---------------+------+-------------------------------------------------------------------+------------+
| Domain | Method   | URI           | Name | Action                                                            | Middleware |
+--------+----------+---------------+------+-------------------------------------------------------------------+------------+
|        | GET|HEAD | api/user      |      | Closure                                                           | api        |
|        |          |               |      |                                                                   | auth:api   |
|        | GET|HEAD | auth/profile  |      | App\Http\Controllers\AuthController@profile                       | web        |
|        |          |               |      |                                                                   | auth       |
|        | GET|HEAD | auth/signin   |      | App\Http\Controllers\AuthController@signin                        | web        |
|        |          |               |      |                                                                   | guest      |
|        | GET|HEAD | auth/signout  |      | App\Http\Controllers\AuthController@signout                       | web        |
|        |          |               |      |                                                                   | auth       |
|        | POST     | auth/signup   |      | App\Http\Controllers\AuthController@signup                        | web        |
|        |          |               |      |                                                                   | guest      |
+--------+----------+---------------+------+-------------------------------------------------------------------+------------+
```

#### How to use this information?

##### Middleware

- `web` - accessible for UI
- `guest` - route available only for guest
- `auth` - only for user
- `can:permission` - only for user with permission

##### Action

Controller class and method - when we know them we can find what this method will return. For example `App\Http\Controllers\AuthController@signup`: first we need to find file, so go to `<project>/src/App/Http/Controllers/AuthController.php` and open it

```php
<?php declare(strict_types=1);

use App\Http\Controllers\Controller;

use App\Http\Requests\Auth\SignupRequest;
use App\Http\Resources\Auth\SignupResource;

class AuthController extends Controller {
    // ... 

    public function signup(SignupRequest $request): SignupResource {
        // ...
    }
}
```

The most useful information (phpdoc is also useful, please read it too):

- `SignupRequest $request` - the request object, describes what data expected
- `SignupResource` - response

Next, go to `SignupRequest`:

```php
<?php declare(strict_types = 1);

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Translation\Translator;
use LastDragon_ru\LaraASP\Spa\Http\Request;
use LastDragon_ru\LaraASP\Spa\Validation\Rules\StringRule;

class SignupRequest extends Request {
    public function rules(Translator $translator): array {
        return [
            'given_name'  => ['required', new StringRule($translator), 'min:3', 'max:255'],
            'family_name' => ['required', new StringRule($translator), 'min:3', 'max:255'],
            'email'       => ['required', new StringRule($translator), 'min:3', 'max:255', 'email'],
            'phone'       => ['required', new StringRule($translator), 'phone'],
            'company'     => ['required', new StringRule($translator), 'min:3', 'max:255'],
            'reseller'    => ['nullable', new StringRule($translator), 'min:3', 'max:255'],
        ];
    }
}
```

and you can see all properties and their types. Laravel defines a lot of rules, you can find them in the [docs](https://laravel.com/docs/8.x/validation#available-validation-rules), plus API also uses its own rules (not listed in the Laravel documentation), most of them is the classes, so you can navigate into it and read about (for example `StringRule` means that value must be a string, same thing for `IntRule`, `BoolRule`, etc). If you have any questions about what particular rules do - please don't hesitate to ask.

The final step is the resource that will be returned, but they are much easier to understand, because most of them have a [Json Schema](https://json-schema.org/), eg `SignupResource`

```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "enum": [
        true
    ]
}
```

or `ValidationErrorResponse`

```json
{
    "$schema": "http://json-schema.org/draft-07/schema#",
    "type": "object",
    "required": [
        "message",
        "errors"
    ],
    "additionalProperties": false,
    "properties": {
        "message": {
            "type": "string"
        },
        "errors": {
            "type": "object",
            "minProperties": 1,
            "patternProperties": {
                ".*": {
                    "type": "array",
                    "minItems": 1,
                    "items": {
                        "type": "string"
                    }
                }
            },
            "additionalProperties": false
        }
    }
}
```
