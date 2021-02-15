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

### Groups of const, variables, class properties, and arrays MUST be aligned by `=` or `=>` respectively

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
} elseif ('condition3') {
    // ...
} else {
    // empty
}
```

### Default value SHOULD be placed before If-ElseIf-Else block

```php
<?php declare(strict_types=1);

function test(): ?string {
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
```

## Laravel Best Practices

### Auto Complete MUST work

Use `ide-helper` (see below) and `/* @var \stdClass $a */` when necessary.

### DI SHOULD be used where possible

not helpers and facades.


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


### Routes

#### Actions MUST be defined via valid callback

```php
<?php declare(strict_types=1); 

Route::get('/users', [UserController::class, 'index']);
```


### GraphQL

_TODO_


### See also

- http://www.laravelbestpractices.com/
- https://github.com/alexeymezenin/laravel-best-practices
  (except "Fat models, skinny controllers" and "Mass assignment")

_TODO review required_


## Testing

### You SHOULD write the tests

Except trivial methods like getters/setters.


### Test file SHOULD be placed in the same directory with class

```text
/project/path
    ClassUtils.php      - Class
    ClassUtilsTest.php  - Tests for ClassUtils
```


### Test data SHOULD be placed in the same directory with Test

When the test needs a lot of data, move data into `/tests` directory.

```text
/project/path
    ClassUtils.php      - Class
    ClassUtilsTest.php  - Tests for ClassUtils
    ClassUtilsTest.json - Data required for testing
```

### Simple Mocks SHOULD use anonymous classes

For big/difficult Mocks use [Mockery](https://github.com/mockery/mockery).


## Misc

### PHP CodeSniffer

Most of all (maybe except class properties aligning) rules can be checked by [PHP CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer):

```shell
$ composer run-script phpcs
```

You can also set up integration with your favorite IDE if you want.


### Laravel Ide Helper

Dev installation includes [Laravel Ide Helper](https://github.com/barryvdh/laravel-ide-helper), please use following command to generate proper data for auto-complete (please do not include generated files into the commit):

```shell
$ composer run-script ide-helper
```
