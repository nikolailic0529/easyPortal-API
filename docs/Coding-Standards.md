# Coding Standards

The key words “MUST”, “MUST NOT”, “REQUIRED”, “SHALL”, “SHALL NOT”, “SHOULD”, “SHOULD NOT”, “RECOMMENDED”, “MAY”, and “OPTIONAL” in this document are to be interpreted as described in [RFC 2119](http://tools.ietf.org/html/rfc2119).

* [General](#general)
    + [Always strict is a MUST](#always-strict-is-a-must)
    + [The `{` MUST be on the same line](#the-----must-be-on-the-same-line)
    + [Compound namespaces MUST NOT be used](#compound-namespaces-must-not-be-used)
    + [Multi-line calls, arguments, and arrays MUST have a comma on the last line](#multi-line-calls--arguments--and-arrays-must-have-a-comma-on-the-last-line)
    + [Groups of constants, variables, class properties, and arrays MUST be aligned by `=` or `=>` respectively](#groups-of-constants--variables--class-properties--and-arrays-must-be-aligned-by-----or------respectively)
    + [Every object SHOULD have a body](#every-object-should-have-a-body)
    + [Each If-ElseIf-ElseIf SHOULD have `else` block](#each-if-elseif-elseif-should-have--else--block)
    + [Each Switch-Case SHOULD have `default` block](#each-switch-case-should-have--default--block)
    + [Default value SHOULD be placed before If-ElseIf-Else/Switch-Case block](#default-value-should-be-placed-before-if-elseif-else-switch-case-block)
    + [Prefixes/Suffixes like `Trait`/`Interface`/`Abstract` SHOULD NOT be used.](#prefixes-suffixes-like--trait---interface---abstract--should-not-be-used)
    + [You MAY use code-folding to group code by logic](#you-may-use-code-folding-to-group-code-by-logic)
* [Laravel Best Practices](#laravel-best-practices)
    + [Auto Complete MUST work](#auto-complete-must-work)
    + [DI SHOULD be used where possible](#di-should-be-used-where-possible)
    + [Models](#models)
        - [Table name MUST be declared](#table-name-must-be-declared)
        - [Class MUST have proper docblock](#class-must-have-proper-docblock)
    + [Routes](#routes)
        - [Actions SHOULD be defined via valid callback](#actions-should-be-defined-via-valid-callback)
    + [GraphQL](#graphql)
    + [Database Schema & Migrations](#database-schema---migrations)
    + [See also](#see-also)
* [Testing](#testing)
    + [You SHOULD write the tests](#you-should-write-the-tests)
    + [Test file SHOULD be placed in the same directory with class](#test-file-should-be-placed-in-the-same-directory-with-class)
    + [Test data SHOULD be placed in the same directory with Test](#test-data-should-be-placed-in-the-same-directory-with-test)
    + [Simple Mocks SHOULD use anonymous classes](#simple-mocks-should-use-anonymous-classes)
    + [Each TestCase class MUST have proper docblock](#each-testcase-class-must-have-proper-docblock)
* [Misc](#misc)
    + [PHP CodeSniffer](#php-codesniffer)
    + [Laravel Ide Helper](#laravel-ide-helper)


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

If you extend external (from other packages) classes without proper type-hint, you should try to provide the best possible type-hint. In some cases CodeSniffer may show an error about missing types to avoid it you can use `@inheritdoc`:

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


## Laravel Best Practices

### Auto Complete MUST work

Use `ide-helper` (see below) and `/* @var \stdClass $a */` when necessary.


### DI SHOULD be used where possible

not helpers and facades. There are few exceptions:

- `Illuminate\\Support\\Facades\\Date` should be used to getting dates;
- `Illuminate\\Support\\Facades\\Route` should be for top-level routes;


### Models

#### Table name MUST be declared

```php
<?php declare(strict_types=1);

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model {
    use HasFactory;

    /**
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

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @property int                          $id
 * @property string|null                  $sub Auth0 User ID
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
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereBlocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereFamilyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereGivenName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePermissions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhoneVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereSub($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
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

The [`database.mwb`](./database.mwb) ([MySQL Workbench](https://www.mysql.com/products/workbench/) Schema) have the biggest priority. Thus the highly recommended way to create migration(s) is using the "Synchronize Model" feature and create a raw-sql migration(s).


### See also

- http://www.laravelbestpractices.com/
- https://github.com/alexeymezenin/laravel-best-practices
  (except "Fat models, skinny controllers" and "Mass assignment")

_TODO review required_


## Testing

### You SHOULD write the tests

Except for trivial methods like getters/setters.


### Test file SHOULD be placed in the same directory with class

```text
/project/path
    ClassUtils.php      - Class
    ClassUtilsTest.php  - Tests for ClassUtils
```


### Test data SHOULD be placed in the same directory with Test

When the test needs a lot of data, move data into `/tests/data` directory with full path saving.

```text
// Simple test
/project/src/namespace/path
    ClassUtils.php      - Class
    ClassUtilsTest.php  - Tests for ClassUtils
    ClassUtilsTest.json - Data required for testing

// Test with a lot of data
/project/tests/data/namespace/path
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
