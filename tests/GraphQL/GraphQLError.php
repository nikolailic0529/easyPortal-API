<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonMatchesSchema;
use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonSchema;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Bodies\JsonBody;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\ContentTypes\JsonContentType;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Ok;

use function array_filter;
use function array_keys;

class GraphQLError extends Response {
    /**
     * @param array<string>|null $errors
     */
    public function __construct(string $root, array $errors = null) {
        parent::__construct(
            new Ok(),
            new JsonContentType(),
            new JsonBody(...array_filter([
                new JsonMatchesSchema(new SchemaWrapper($this::class, $root)),
                $errors
                    ? new JsonMatchesSchema(new JsonSchema($this->getErrorsSchema($errors)))
                    : null,
            ])),
        );
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
