<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonMatchesSchema;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Bodies\JsonBody;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\ContentTypes\JsonContentType;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Ok;

use function array_filter;

abstract class GraphQLResponse extends Response {
    protected string  $root;
    protected ?string $schema;

    /**
     * @param class-string|null $schema
     */
    public function __construct(string $root, ?string $schema) {
        $this->schema = $schema;
        $this->root   = $root;

        parent::__construct(
            new Ok(),
            new JsonContentType(),
            new JsonBody(...array_filter([
                new JsonMatchesSchema(new SchemaWrapper(self::class, $this->root)),
                new JsonMatchesSchema(new SchemaWrapper($this::class, $this->root, $this->schema)),
                ...$this->getResponseConstraints(),
            ])),
        );
    }

    /**
     * @return array<\PHPUnit\Framework\Constraint\Constraint>
     */
    abstract protected function getResponseConstraints(): array;
}
