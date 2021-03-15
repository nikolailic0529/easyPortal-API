<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use JsonSerializable;
use LastDragon_ru\LaraASP\Testing\Responses\Laravel\Json\OkResponse;
use SplFileInfo;
use stdClass;

class GraphQLPaginated extends OkResponse {
    public function __construct(
        string $root,
        ?string $schema,
        SplFileInfo|array|string|stdClass|JsonSerializable|null $content = null,
    ) {
        $contentPaginated = [
            'data' => [
                $root => [
                    'data' => [
                        $content,
                    ],
                ],
            ],
        ];
        parent::__construct(new SchemaWrapper($this::class, $root, $schema), $contentPaginated);
    }
}
