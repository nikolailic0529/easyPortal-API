<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonMatchesSchema;
use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonSchema;

use function array_keys;

class GraphQLError extends GraphQLResponse {
    /**
     * @var array<string>|null
     */
    protected ?array $errors = null;

    /**
     * @param array<string>|null $errors
     */
    public function __construct(string $root, ?array $errors = null) {
        $this->errors = $errors;

        parent::__construct($root, null);
    }

    /**
     * @inheritdoc
     */
    protected function getResponseConstraints(): array {
        return [
            $this->errors
                ? new JsonMatchesSchema(new JsonSchema($this->getErrorsSchema($this->errors)))
                : null,
        ];
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
