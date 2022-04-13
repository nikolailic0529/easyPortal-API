<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation\Rules;

use GraphQL\Language\Parser;
use LogicException;
use Stringable;

use function array_filter;
use function array_values;
use function implode;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function str_replace;

abstract class LaravelRule extends Rule {
    public function getRule(): string {
        $name = $this->getRuleName();
        $args = implode(',', array_values($this->getRuleArguments()));
        $rule = implode(':', array_filter([$name, $args]));

        return $rule;
    }

    abstract protected function getRuleName(): string;

    /**
     * @return array<string, string>
     */
    protected function getRuleArguments(): array {
        $args      = [];
        $arguments = static::arguments();

        if ($arguments) {
            $arguments = Parser::inputFieldsDefinition($arguments);

            foreach ($arguments as $arg) {
                $name  = $arg->name->value;
                $value = $this->directiveArgValue($name);

                if (is_string($value) || is_int($value) || $value instanceof Stringable) {
                    $value = (string) $value;
                } elseif ($value === null) {
                    $value = 'null';
                } elseif (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                } elseif (is_float($value)) {
                    $value = str_replace(',', '.', (string) $value);
                } else {
                    throw new LogicException('Impossible convert `$value` to `string`.');
                }

                $args[$name] = $value;
            }
        }

        return $args;
    }
}
