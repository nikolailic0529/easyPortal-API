<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonSchemaFile;
use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonSchemaWrapper;
use LastDragon_ru\LaraASP\Testing\Utils\WithTestData;
use Tests\GraphQL\Schemas\AnySchema;

class SchemaWrapper extends JsonSchemaWrapper {
    use WithTestData;

    /**
     * @param class-string<GraphQLResponse> $response
     */
    public function __construct(string $response, string $root, string $schema = null) {
        parent::__construct(
            new JsonSchemaFile($this->getTestData($schema ?? AnySchema::class)->file('.json')),
            $this->getTestData($response)->file('.json'),
            [
                'graphql.root' => $root,
            ],
        );
    }
}
