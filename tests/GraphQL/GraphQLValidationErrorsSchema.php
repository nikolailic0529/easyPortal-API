<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use App\Utils\Cast;
use Closure;
use Illuminate\Container\Container;
use JsonSerializable;

use function array_keys;
use function assert;
use function is_array;
use function is_callable;
use function str_replace;
use function trans;

/**
 * @phpstan-type ValidationErrors array<string, non-empty-array<string>>
 */
class GraphQLValidationErrorsSchema implements JsonSerializable {
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
        return $this->getErrorsSchema();
    }

    /**
     * @return array<mixed>
     */
    protected function getErrorsSchema(): array {
        // Get errors
        $errors = $this->errors;

        if (is_callable($errors)) {
            $errors = Container::getInstance()->call($errors);

            assert(is_array($errors));
        }

        // Generate schema
        $properties = [];

        foreach ($errors as $fieldName => $fieldErrors) {
            assert(is_array($fieldErrors));

            $items = [];

            foreach ($fieldErrors as $fieldError) {
                $items[] = [
                    'const' => str_replace(':attribute', $fieldName, Cast::toString($fieldError)),
                ];
            }

            $properties[$fieldName] = [
                'type'            => 'array',
                'additionalItems' => false,
                'items'           => $items,
            ];
        }

        return [
            '$schema'              => 'http://json-schema.org/draft-07/schema#',
            'type'                 => 'object',
            'additionalProperties' => true,
            'required'             => [
                'errors',
            ],
            'properties'           => [
                'errors' => [
                    'type'            => 'array',
                    'additionalItems' => false,
                    'items'           => [
                        [
                            'type'                 => 'object',
                            'additionalProperties' => true,
                            'required'             => [
                                'message',
                                'extensions',
                            ],
                            'properties'           => [
                                'message'    => [
                                    'const' => trans('errors.validation_failed'),
                                ],
                                'extensions' => [
                                    'type'                 => 'object',
                                    'additionalProperties' => true,
                                    'required'             => [
                                        'validation',
                                    ],
                                    'properties'           => [
                                        'validation' => [
                                            'type'            => 'object',
                                            'additionalItems' => false,
                                            'required'        => array_keys($properties),
                                            'properties'      => $properties,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
