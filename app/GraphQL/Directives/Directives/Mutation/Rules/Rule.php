<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation\Rules;

use App\Utils\Description;
use Illuminate\Support\Str;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use ReflectionClass;

use function mb_strlen;
use function mb_substr;
use function str_ends_with;

abstract class Rule extends BaseDirective {
    public function __construct() {
        // empty
    }

    public static function definition(): string {
        $class       = new ReflectionClass(static::class);
        $suffix      = 'Directive';
        $directive   = '@'.Str::camel($class->getShortName());
        $arguments   = static::arguments();
        $arguments   = $arguments ? "(\n$arguments\n)" : '';
        $description = (new Description())->get($class);

        if (str_ends_with($directive, $suffix)) {
            $directive = mb_substr($directive, 0, -mb_strlen($suffix));
        }

        return /** @lang GraphQL */ <<<GRAPHQL
            """
            {$description}
            """
            directive {$directive}{$arguments} on INPUT_FIELD_DEFINITION
            GRAPHQL;
    }

    protected static function arguments(): ?string {
        return null;
    }
}
