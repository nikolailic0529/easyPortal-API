<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use JsonSerializable;
use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonMatchesFragment;
use SplFileInfo;
use stdClass;

class GraphQLPaginated extends GraphQLSuccess {
    protected JsonSerializable|SplFileInfo|stdClass|array|string|null $paginator = null;

    public function __construct(
        string $root,
        ?string $schema,
        JsonSerializable|SplFileInfo|stdClass|array|string|null $content = null,
        JsonSerializable|SplFileInfo|stdClass|array|string|null $paginator = null,
    ) {
        $this->paginator = $paginator;

        parent::__construct($root, $schema, $content);
    }

    /**
     * @return array<\PHPUnit\Framework\Constraint\Constraint>
     */
    protected function getResponseConstraints(): array {
        return [
            $this->content
                ? new JsonMatchesFragment("data.{$this->root}.data", $this->content)
                : null,
            $this->paginator
                ? new JsonMatchesFragment("data.{$this->root}.paginatorInfo", $this->paginator)
                : null,
        ];
    }
}
