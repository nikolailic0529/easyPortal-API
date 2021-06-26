<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use Closure;
use JsonSerializable;
use Throwable;

use function array_keys;
use function is_array;

class GraphQLErrorsSchema implements JsonSerializable {
    /**
     * @param array<string>|\Throwable|\Closure():array<string>|\Exception|null $errors
     */
    public function __construct(
        protected Closure|Throwable|array $errors,
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
     * @return \Throwable|array<string>
     */
    protected function getErrorsSchema(Throwable|array $errors): array {
        if (!is_array($errors)) {
            $errors = [$errors];
        }

        $items = [];

        foreach ($errors as $error) {
            if ($error instanceof Throwable) {
                $error = $error->getMessage();
            }

            $items[] = [
                'oneOf' => [
                    [
                        'type'       => 'object',
                        'required'   => [
                            'message',
                            'debugMessage',
                        ],
                        'properties' => [
                            'message'      => [
                                'type' => 'string',
                            ],
                            'debugMessage' => [
                                'const' => $error,
                            ],
                        ],
                    ],
                    [
                        'type'       => 'object',
                        'required'   => [
                            'message',
                        ],
                        'properties' => [
                            'message' => [
                                'const' => $error,
                            ],
                        ],
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
                    'items'           => $items,
                ],
            ],
        ];
    }
}
