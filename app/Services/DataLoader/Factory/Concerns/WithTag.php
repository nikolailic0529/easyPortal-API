<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\Tag;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Resolver\Resolvers\TagResolver;

/**
 * @mixin Factory
 */
trait WithTag {
    abstract protected function getTagResolver(): TagResolver;

    protected function tag(string $name): Tag {
        return $this->getTagResolver()->get($name, static function (?Tag $model) use ($name): Tag {
            if ($model) {
                return $model;
            }

            $model       = new Tag();
            $model->name = $name;

            $model->save();

            return $model;
        });
    }
}
