<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use JsonSerializable;
use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonMatchesFragment;
use SplFileInfo;
use stdClass;

class GraphQLPaginated extends GraphQLSuccess {
    protected JsonFragment|null $data      = null;
    protected JsonFragment|null $paginator = null;

    public function __construct(
        string $root,
        JsonFragmentSchema|string|null $schema,
        JsonFragment|JsonSerializable|SplFileInfo|stdClass|array|string|null $data = null,
        JsonFragment|JsonSerializable|SplFileInfo|stdClass|array|string|null $paginator = null,
    ) {
        $schema          = $this->getJsonFragmentSchema('data', $schema);
        $this->data      = $this->getJsonFragment("data.{$root}.data", $data);
        $this->paginator = $this->getJsonFragment("data.{$root}.paginatorInfo", $paginator);

        parent::__construct($root, $schema, null);
    }

    /**
     * @return array<\PHPUnit\Framework\Constraint\Constraint>
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
