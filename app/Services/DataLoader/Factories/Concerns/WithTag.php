<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Tag;

/**
 * @property \App\Services\DataLoader\Normalizer             $normalizer
 * @property \App\Services\DataLoader\Resolvers\TagResolver $tags
 *
 * @mixin \App\Services\DataLoader\Factory
 */
trait WithTag {
    protected function tag(string $name): Tag {
        $tag = $this->tags->get($name, $this->factory(function () use ($name): Tag {
            $model = new Tag();

            $model->name = $this->normalizer->string($name);

            $model->save();

            return $model;
        }));

        return $tag;
    }
}
