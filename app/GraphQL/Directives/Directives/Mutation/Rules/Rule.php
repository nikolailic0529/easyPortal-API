<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation\Rules;

use App\Utils\Description;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use ReflectionClass;

abstract class Rule extends BaseDirective {
    public function __construct() {
        // empty
    }

    public static function definition(): string {
        $class       = new ReflectionClass(static::class);
        $directive   = "@is{$class->getShortName()}";
        $description = (new Description())->get($class);

        return /** @lang GraphQL */ <<<GRAPHQL
            """
            {$description}
            """
            directive {$directive} on INPUT_FIELD_DEFINITION
            GRAPHQL;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getRuleArguments(): array {
        if (!isset($this->directiveArgs)) {
            $this->loadArgValues();
        }

        return $this->directiveArgs;
    }
}
