<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use JsonSerializable;
use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonMatchesFragment;
use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonMatchesSchema;
use SplFileInfo;
use stdClass;

use function array_merge;
use function is_null;

class GraphQLSuccess extends GraphQLResponse {
    protected JsonFragment|null $content;

    /**
     * @param JsonFragmentSchema|class-string|null $schema
     */
    public function __construct(
        string $root,
        JsonFragmentSchema|string|null $schema,
        JsonFragment|JsonSerializable|SplFileInfo|stdClass|array|string|null $content = null,
    ) {
        $this->content = $this->getJsonFragment("data.{$root}", $content);

        parent::__construct($root, $schema);
    }

    /**
     * @inheritDoc
     */
    protected function getSchemaConstraints(): array {
        $constraints = parent::getSchemaConstraints();

        if ($this::class === self::class) {
            $constraints = array_merge($constraints, [
                new JsonMatchesSchema(new SchemaWrapper(self::class, $this->root)),
            ]);
        }

        return $constraints;
    }

    /**
     * @inheritDoc
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
            $fragment = (clone $content)->setPath("{$prefix}.{$content->getPath()}");
        } elseif (!is_null($content)) {
            $fragment = new JsonFragment("{$prefix}", $content);
        } else {
            // empty
        }

        return $fragment;
    }
}
