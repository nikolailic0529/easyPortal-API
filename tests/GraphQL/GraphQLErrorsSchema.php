<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use Closure;
use JsonSerializable;

use function array_keys;

class GraphQLErrorsSchema implements JsonSerializable {
    /**
     * @param array<string>|\Closure():array<string>|null $errors
     */
    public function __construct(
        protected Closure|array $errors,
    ) {
        // empty
    }

    public function jsonSerialize(): mixed {
        return $this->errors instanceof Closure
            ? $this->getErrorsSchema(($this->errors)())
            : $this->getErrorsSchema($this->errors);
    }

    /**
     * @param array<string> $errors
     *
     * @return array<mixed>
     */
    protected function getErrorsSchema(array $errors): array {
        $items = [];

        foreach ($errors as $error) {
            $items[] = [
                'type'       => 'object',
                'required'   => [
                    'message',
                ],
                'properties' => [
                    'message' => [
                        'const' => $error,
                    ],
                ],
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
                    'required'        => array_keys($items),
                    'items'           => $items,
                ],
            ],
        ];
    }
}
