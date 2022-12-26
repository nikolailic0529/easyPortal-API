<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\Tag;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\TagResolver;

/**
 * @mixin Factory
 */
trait WithTag {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getTagResolver(): TagResolver;

    protected function tag(string $name): Tag {
        $tag = $this->getTagResolver()->get($name, static function () use ($name): Tag {
            $model       = new Tag();
            $model->name = $name;

            $model->save();

            return $model;
        });

        return $tag;
    }
}
