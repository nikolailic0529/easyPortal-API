<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonFragmentMatchesSchema;
use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonMatchesSchema;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Bodies\JsonBody;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\ContentTypes\JsonContentType;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Ok;

use function array_filter;
use function array_merge;

abstract class GraphQLResponse extends Response {
    protected string                         $root;
    protected JsonFragmentSchema|string|null $schema;

    /**
     * @param \Tests\GraphQL\JsonFragmentSchema|class-string|null $schema
     */
    public function __construct(string $root, JsonFragmentSchema|string|null $schema) {
        $this->schema = $this->getJsonFragmentSchema("data.{$root}", $schema);
        $this->root   = $root;
        $constraints  = [
            new JsonMatchesSchema(new SchemaWrapper(self::class, $this->root)),
        ];

        if ($schema instanceof JsonFragmentSchema) {
            $constraints[] = new JsonFragmentMatchesSchema(
                $this->schema->getPath(),
                $this->schema->getJsonSchema(),
            );
        } else {
            $constraints[] = new JsonMatchesSchema(
                new SchemaWrapper($this->getResponseClass(), $this->root, $this->schema),
            );
        }

        parent::__construct(
            new Ok(),
            new JsonContentType(),
            new JsonBody(...array_filter(array_merge($constraints, $this->getResponseConstraints()))),
        );
    }

    protected function getJsonFragmentSchema(
        string $prefix,
        JsonFragmentSchema|string|null $schema,
    ): JsonFragmentSchema|string|null {
        if ($schema instanceof JsonFragmentSchema) {
            $schema = (clone $schema)->setPath($schema->getPath() ? "{$prefix}.{$schema->getPath()}" : $prefix);
        }

        return $schema;
    }

    /**
     * @return class-string<\Tests\GraphQL\GraphQLResponse>
     */
    protected function getResponseClass(): string {
        return $this::class;
    }

    /**
     * @return array<\PHPUnit\Framework\Constraint\Constraint>
     */
    abstract protected function getResponseConstraints(): array;
}
