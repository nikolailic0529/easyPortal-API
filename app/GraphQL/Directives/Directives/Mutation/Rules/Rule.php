<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation\Rules;

use App\Utils\Description;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Validation\Rule as RuleContract;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use ReflectionClass;

abstract class Rule extends BaseDirective {
    public function __construct(
        protected Container $container,
    ) {
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

    public function getRule(): RuleContract {
        return $this->container->make($this->getRuleClass(), $this->getRuleArguments());
    }

    /**
     * @return class-string<\Illuminate\Contracts\Validation\Rule>
     */
    abstract protected static function getRuleClass(): string;

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
