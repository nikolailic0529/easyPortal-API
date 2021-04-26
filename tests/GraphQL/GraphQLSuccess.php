<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use JsonSerializable;
use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonMatchesFragment;
use SplFileInfo;
use stdClass;

use function is_null;

class GraphQLSuccess extends GraphQLResponse {
    protected JsonFragment|null $content;

    public function __construct(
        string $root,
        JsonFragmentSchema|string|null $schema,
        JsonFragment|JsonSerializable|SplFileInfo|stdClass|array|string|null $content = null,
    ) {
        $this->content = $this->getJsonFragment("data.{$root}", $content);

        parent::__construct($root, $schema);
    }

    /**
     * @return array<\PHPUnit\Framework\Constraint\Constraint>
     */
    protected function getResponseConstraints(): array {
        return [
            $this->content
                ? new JsonMatchesFragment($this->content->getPath(), $this->content->getJson())
                : null,
        ];
    }

    protected function getJsonFragment(
        string $prefix,
        JsonFragment|JsonSerializable|SplFileInfo|stdClass|array|string|null $content,
    ): ?JsonFragment {
        $fragment = null;

        if ($content instanceof JsonFragment) {
            $fragment = new JsonFragment("{$prefix}.{$content->getPath()}", $content->getJson());
        } elseif (!is_null($content)) {
            $fragment = new JsonFragment("{$prefix}", $content);
        } else {
            // empty
        }

        return $fragment;
    }
}
