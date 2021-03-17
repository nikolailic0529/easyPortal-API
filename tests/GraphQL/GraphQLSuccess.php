<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use JsonSerializable;
use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonMatchesFragment;
use SplFileInfo;
use stdClass;

class GraphQLSuccess extends GraphQLResponse {
    protected JsonSerializable|SplFileInfo|stdClass|array|string|null $content = null;

    public function __construct(
        string $root,
        ?string $schema,
        JsonSerializable|SplFileInfo|stdClass|array|string|null $content = null,
    ) {
        $this->content = $content;

        parent::__construct($root, $schema);
    }

    /**
     * @return array<\PHPUnit\Framework\Constraint\Constraint>
     */
    protected function getResponseConstraints(): array {
        return [
            $this->content
                ? new JsonMatchesFragment("data.{$this->root}", $this->content)
                : null,
        ];
    }
}
