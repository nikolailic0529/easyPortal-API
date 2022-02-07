<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Tag;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\TagResolver;

/**
 * @mixin \App\Services\DataLoader\Factory\Factory
 */
trait WithTag {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getTagResolver(): TagResolver;

    protected function tag(string $name): Tag {
        $tag = $this->getTagResolver()->get($name, $this->factory(function () use ($name): Tag {
            $model       = new Tag();
            $normalizer  = $this->getNormalizer();
            $model->name = $normalizer->string($name);

            $model->save();

            return $model;
        }));

        return $tag;
    }
}
