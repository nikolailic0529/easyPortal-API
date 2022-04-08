<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use JsonSerializable;
use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonMatchesFragment;
use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonMatchesSchema;
use PHPUnit\Framework\Constraint\Constraint;
use SplFileInfo;
use stdClass;

use function array_merge;

class GraphQLPaginated extends GraphQLSuccess {
    protected JsonFragment|null $data      = null;
    protected JsonFragment|null $paginator = null;

    /**
     * @param JsonFragment|JsonSerializable|SplFileInfo|stdClass|array<mixed>|string|null $data
     * @param JsonFragment|JsonSerializable|SplFileInfo|stdClass|array<mixed>|string|null $paginator
     */
    public function __construct(
        string $root,
        JsonFragment|JsonSerializable|SplFileInfo|stdClass|array|string|null $data = null,
        JsonFragment|JsonSerializable|SplFileInfo|stdClass|array|string|null $paginator = null,
    ) {
        $schema          = $this->getJsonFragmentSchema('data', null);
        $this->data      = $this->getJsonFragment("data.{$root}", $data);
        $this->paginator = $this->getJsonFragment("data.{$root}Aggregated", $paginator);

        parent::__construct($root, $schema);
    }

    /**
     * @inheritDoc
     */
    protected function getSchemaConstraints(): array {
        return array_merge(parent::getSchemaConstraints(), [
            new JsonMatchesSchema(new SchemaWrapper(self::class, $this->root)),
        ]);
    }

    /**
     * @return array<Constraint>
     */
    protected function getResponseConstraints(): array {
        return [
            $this->data
                ? new JsonMatchesFragment($this->data->getPath(), $this->data->getJson())
                : null,
            $this->paginator
                ? new JsonMatchesFragment($this->paginator->getPath(), $this->paginator->getJson())
                : null,
        ];
    }
}
