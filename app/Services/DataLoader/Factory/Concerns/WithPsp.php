<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\Psp;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\PspResolver;

/**
 * @mixin Factory
 */
trait WithPsp {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getPspResolver(): PspResolver;

    protected function psp(?string $key, string $name = null): ?Psp {
        // Null?
        if ($key === null || $key === '') {
            return null;
        }

        // Find/Create
        $created = false;
        $factory = static function (Psp $group) use (&$created, $key, $name): Psp {
            $created    = !$group->exists;
            $group->key = $key;

            if (!$group->name || $group->name === $key) {
                $group->name = $name ?: $key;
            }

            $group->save();

            return $group;
        };
        $psp     = $this->getPspResolver()->get(
            $key,
            static function () use ($factory): Psp {
                return $factory(new Psp());
            },
        );

        // Update
        if (!$created) {
            $factory($psp);
        }

        // Return
        return $psp;
    }
}
