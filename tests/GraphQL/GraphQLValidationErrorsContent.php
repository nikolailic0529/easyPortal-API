<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use App\Utils\Cast;
use Closure;
use Illuminate\Container\Container;
use JsonSerializable;

use function assert;
use function is_array;
use function is_callable;
use function str_replace;

/**
 * @phpstan-type ValidationErrors array<string, non-empty-array<string>>
 */
class GraphQLValidationErrorsContent implements JsonSerializable {
    /**
     * @template T
     *
     * @param ValidationErrors|Closure(T):ValidationErrors $errors
     */
    public function __construct(
        protected Closure|array $errors,
    ) {
        // empty
    }

    public function jsonSerialize(): mixed {
        // Get errors
        $errors = $this->errors;

        if (is_callable($errors)) {
            $errors = Container::getInstance()->call($errors);

            assert(is_array($errors));
        }

        // Generate
        $properties = [];

        foreach ($errors as $fieldName => $fieldErrors) {
            assert(is_array($fieldErrors));

            $items = [];

            foreach ($fieldErrors as $fieldError) {
                $items[] = str_replace(':attribute', $fieldName, Cast::toString($fieldError));
            }

            $properties[$fieldName] = $items;
        }

        return $properties;
    }
}
