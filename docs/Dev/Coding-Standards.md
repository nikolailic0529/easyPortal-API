# Coding Standards

The key words “MUST”, “MUST NOT”, “REQUIRED”, “SHALL”, “SHALL NOT”, “SHOULD”, “SHOULD NOT”, “RECOMMENDED”, “MAY”, and “OPTIONAL” in this document are to be interpreted as described in [RFC 2119](http://tools.ietf.org/html/rfc2119).

## General

Code MUST follow all rules outlined in [PSR-12](https://www.php-fig.org/psr/psr-12/) with exceptions/addition declared below.


### Always strict is a MUST

Each PHP file MUST starts with:

```php
<?php declare(strict_types = 1);

// ...
```

Each property, argument, and return type MUST have a proper type-hint where possible, eg:

```php
<?php declare(strict_types = 1);

class A {
    public function a(string|int $a): void {
        $a = static function (bool $b): int {
            // ...
        };
        
        // ...
    }
}
```

If you extend external (from other packages) classes without proper type-hint, you should try to provide the best possible type-hint. In some cases CodeSniffer may show an error about missing types to avoid it you can use `@inheritdoc` or `@phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint`:

```php
<?php declare(strict_types = 1);

namespace App\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;

use function is_int;

class IntRule implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value) {
        return is_int($value);
    }
    
    /**
     * @inheritdoc
     */
    public function message(){
        return 'message';
    }
}
```

```php
<?php declare(strict_types=1);

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     * @var string
     */
    protected $table = 'organizations';
}
```


### The `{` MUST be on the same line

```php
<?php declare(strict_types = 1);

class A {
    public function a(): void {
        // ...
    }
}

function f(): void {
    // ...
}
```


### Compound namespaces MUST NOT be used

```php
<?php declare(strict_types=1);

// Forbidden
use function Another\Vendor\functionD; 
use function Vendor\Package\{functionA, functionB, functionC};

// MUST BE
use function Another\Vendor\functionD; 
use function Vendor\Package\functionA;
use function Vendor\Package\functionB;
use function Vendor\Package\functionC;
```


### Multi-line calls, arguments, and arrays MUST have a comma on the last line

```php
<?php declare(strict_types=1);

namespace Vendor\Package;

use stdClass;

class ClassName {
    public function aVeryLongMethodName(
        stdClass $arg1,
        string $arg2,
        array $arg3 = [], // <-- comma
    ): void {
        $this->aVeryLongMethodName(
            new stdClass(),
            '...',
            [
                'one',
                'two',
                'three', // <-- comma
            ], // <-- comma  
        );
    }
}
```


### Groups of constants, variables, class properties, and arrays MUST be aligned by `=` or `=>` respectively

```php
<?php declare(strict_types=1);

use Illuminate\Contracts\Container\Container;

$a     = '';
$ab    = '';
$abc   = '';
$array = [
    'a'   => '',
    'ab'  => '',
    'abc' => '',
];

class A {
    public const DateFormat     = 'Y-m-d';
    public const DateTimeFormat = 'Y-m-d\TH:i:sP';
    
    protected Container $container;
    protected array     $properties = [];
    protected ?array    $config     = null;
}
```


### Every object SHOULD have a body

If they should be empty, you can mark it:

```php
<?php declare(strict_types=1);

function() {
    // empty
}

interface Me {
    // empty
}
```


### Each If-ElseIf-ElseIf SHOULD have `else` block

even if `else` is empty

```php
<?php declare(strict_types=1);

if ('condition1') {
    // ...
} elseif ('condition2') {
    // ...
} else {
    // empty
}

if ('condition1') {
    // ...
} elseif ('condition2') {
    // ...
} elseif ('condition3') {
    // ...
} else {
    // empty
}
```


### Each Switch-Case SHOULD have `default` block

even if `default` is empty

```php
<?php declare(strict_types=1);

switch ('value') {
    case 'condition1':
        // ...
        break;
    case 'condition2':
        // ...
        break;
    default:
        // empty
        break;
}
```


### Default value SHOULD be placed before If-ElseIf-Else/Switch-Case block

```php
<?php declare(strict_types=1);

function IfElseExample(): ?string {
    $value = null;
    
    if ('condition1') {
        // ...
    } elseif ('condition2') {
        // ...
    } elseif ('condition3') {
        // ...
    } else {
        // empty
    }
    
    return $value;
}

function SwitchCaseExample(): ?string {
    $value = null;
    
    switch ($value) {
        case 'condition1':
            // ...
            break;
        case 'condition2':
            // ...
            break;
        default:
            // empty
            break;
    }
    
    return $value;
}
```


### Prefixes/Suffixes like `Trait`/`Interface`/`Abstract` SHOULD NOT be used.

Probably you just need to find a better name. On the other side there few well-known suffixes like `Controller` for Laravel's controllers, `Exception` for exceptions, please use it.


### You MAY use code-folding to group code by logic

This is especially recommended for tests.

```php
<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Http\Controllers\AuthController
 */
class AuthControllerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::info
     * @dataProvider dataProviderInfo
     */
    public function testInfo(): void {
        // ...
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    public function dataProviderInfo(): array {
        return [];
    }
    // </editor-fold>
}
```


### Chained methods SHOULD be indented correctly

```php
<?php declare(strict_types=1);

// Should be
$contactsRelation = Contact::factory()
    ->hasTypes(1, [
        'id' => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20935',
    ])
    ->state(function () {
        return [
            'name' => 'contact1',
        ];
    })
    ->create();
```

### Unused/Dead code MUST be removed

```php
<?php declare(strict_types=1);

// seems not needed anymore
// $location = Location::factory()
//     ->hasTypes(1,[
//         'id'   => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20934',
//         'name' => 'name1',
//         'key'  => 'key1'
//     ])
//     ->create();
```

### Empty line MUST BE added before each logical block/return/etc

```php
<?php declare(strict_types=1);

// Bad
function one(): ?string {
    $value = null;
    if ('condition1') {
        // ...
    } else {
        // empty
    }
    return $value;
}
function two(): ?string {
    $value = null;
    return $value;
}

// MUST BE
function one(): ?string {
    $value = null;
    
    if ('condition1') {
        // ...
    } else {
        // empty
    }
    
    return $value;
}

function two(): ?string {
    $value = null;
    
    return $value;
}
```

## Laravel Best Practices

### Auto Complete SHOULD work

Use `ide-helper` (see below) and `/* @var \stdClass $a */` when necessary, but it should not make any assumptions about actual types if they cannot be determined by `phpstan`.


### DI SHOULD be used where possible

not helpers and facades. There are few exceptions:

- `Illuminate\\Support\\Facades\\Date` should be used to getting dates;
- `Illuminate\\Support\\Facades\\Route` should be used for top-level routes;


### Models

#### Table name MUST be declared

```php
<?php declare(strict_types=1);

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     * @var string
     */
    protected $table = 'organizations';
}
```


#### Class MUST have proper docblock

It can be generated by ide-helper. If the schema was updated the recommended way to sync docblock is:

1. Remove existing docblock
2. Generate a new one

```php
<?php declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @property int                          $id
 * @property int                          $blocked
 * @property string                       $given_name
 * @property string                       $family_name
 * @property string                       $email
 * @property \Carbon\CarbonImmutable|null $email_verified_at
 * @property string                       $phone
 * @property \Carbon\CarbonImmutable|null $phone_verified_at
 * @property string|null                  $photo
 * @property mixed                        $permissions
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property string|null                  $deleted_at
 * @method static Builder<User> newModelQuery()
 * @method static Builder<User> newQuery()
 * @method static Builder<User> query()
 * @mixin \Eloquent
 */
class User extends Authenticatable {
    // ...
}
```


### Routes

#### Actions SHOULD be defined via valid callback

```php
<?php declare(strict_types=1); 

Route::get('/users', [UserController::class, 'index']);
```


### GraphQL

_TODO_


### Database Schema & Migrations

The [`database.mwb`](../database.mwb) ([MySQL Workbench](https://www.mysql.com/products/workbench/) Schema) have the biggest priority. Thus the highly recommended way to create migration(s) is using the "Synchronize Model" feature and create a raw-sql migration(s).


### See also

- http://www.laravelbestpractices.com/
- https://github.com/alexeymezenin/laravel-best-practices
  (except "Fat models, skinny controllers" and "Mass assignment")

_TODO review required_


## Testing

### You MUST write the tests

Except for trivial methods like getters/setters.


### Test file SHOULD be placed in the same directory with class

```text
/project/path
    ClassUtils.php      - Class
    ClassUtilsTest.php  - Tests for ClassUtils
```


### Test data SHOULD be placed in the same directory with Test

When the test needs a lot of data, move data into `/tests/Data` directory with full path saving.

```text
// Simple test
/project/src/Namespace/Path
    ClassUtils.php      - Class
    ClassUtilsTest.php  - Tests for ClassUtils
    ClassUtilsTest.json - Data required for testing

// Test with a lot of data
/project/tests/Data/Namespace/Path
    ClassUtilsData.php    - Data container
    ClassUtilsData.1.json - Data required for testing
    ClassUtilsData.2.json - Data required for testing
    ClassUtilsData.3.json - Data required for testing
```


### Simple Mocks SHOULD use anonymous classes

For big/difficult Mocks use [Mockery](https://github.com/mockery/mockery).


### Each TestCase class MUST have proper docblock

```php
<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Http\Controllers\AuthController
 */
class AuthControllerTest extends TestCase {
    /**
     * @covers ::info
     */
    public function testInfo(): void {
        // ...
    }
    
    /**
     * @coversNothing
     */
    public function testSomething(): void {
        // ...
    }
}
```

### DataProviders MUST be prefixed by `dataProvider`

```php
<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Http\Controllers\AuthController
 */
class AuthControllerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::info
     * @dataProvider dataProviderInfo
     */
    public function testInfo(): void {
        // ...
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    public function dataProviderInfo(): array {
        return [];
    }
    // </editor-fold>
}
```

### Models factories MUST define all properties

This is required to be able to compare models while testing.


### Models actories MUST extend `App\Utils\Eloquent\Testing\Database\Factory`

that makes sure that all our features work as expected and also provides a few useful states.


### The `Factory::ownedBy()` MUST be used to set the owner

Some records accessible only if they are related to the current organization the `ownedBy()` help to reduce number of lines/code duplication when setting the owner (and also makes possible to use the same code for all types of organization).

```php
<?php declare(strict_types = 1);

use App\Models\Asset;
use App\Models\Organization;

// Bad (may fail of $org is not a Reseller)
$org   = Organization::factory()->create();
$asset = Asset::factory()->create([
    'reseller_id' => $org,
]);

// MUST BE
$org   = Organization::factory()->create();
$asset = Asset::factory()->ownedBy($org)->create();
```


## Misc

### PHP CodeSniffer

Most of all (maybe except class properties aligning and empty `else`/`default`) rules can be checked by [PHP CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer):

```shell
$ composer run-script phpcs
```

You can also set up integration with your favorite IDE if you want.


### Laravel Ide Helper

Dev installation includes [Laravel Ide Helper](https://github.com/barryvdh/laravel-ide-helper), please use following command to generate proper data for auto-complete (please do not include generated files into the commit):

```shell
$ composer run-script ide-helper
```
