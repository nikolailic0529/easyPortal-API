<?php declare(strict_types = 1);

namespace App\Services\Organization\Testing\Database;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Reseller;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Services\Organization\Eloquent\OwnedByReseller;
use App\Services\Organization\Eloquent\OwnedByScope;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use LogicException;
use function count;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @mixin Factory
 */
trait OwnedBy {
    /**
     * @return static<TModel>
     */
    public function ownedBy(?Organization $ownedBy): static {
        // Null?
        if ($ownedBy === null) {
            return $this;
        }

        // Owned?
        $org      = $ownedBy;
        $class    = $this->modelName();
        $model    = new $class();
        $property = OwnedByScope::getProperty($org, $model);

        if ($property === null) {
            throw new LogicException('Model is not supported.');
        }

        // Owner
        $owner = null;

        if ($model instanceof OwnedByOrganization) {
            $owner = $org;
        } elseif ($model instanceof OwnedByReseller) {
            $owner = $this->getOwner(Reseller::class, $org);
        } else {
            // empty
        }

        if ($owner === null) {
            throw new LogicException('Something wrong....');
        }

        // Possible?
        $factory = null;

        if ($property->isAttribute()) {
            /**
             * @see  https://github.com/slevomat/coding-standard/issues/1187
             * @phpcs:disable SlevomatCodingStandard.Functions.StaticClosure.ClosureNotStatic
             */
            $factory = $this->state(function () use ($model, $property, $owner): array {
                // For self-references we should not create an instance or we
                // will get "Integrity constraint violation: 1062 Duplicate
                // entry" error.
                if ($owner::class === $model::class && $property->getFullName() === $owner->getKeyName()) {
                    $owner = $owner->getKey();
                } else {
                    $owner->save();
                }

                return [
                    $property->getName() => $owner,
                ];
            });
            // @phpcs:enable
        } elseif ($property->isRelation() && count($property->getPath()) === 2) {
            $relation = $property->getRelation($model);

            if ($relation instanceof BelongsToMany) {
                $factory = $this
                    ->afterMaking(static function () use ($owner): void {
                        $owner->save();
                    })
                    ->afterCreating(static function (Model $model) use ($property, $owner): void {
                        $relation = $property->getRelation($model);

                        if ($relation instanceof BelongsToMany) {
                            $relation->attach($owner);
                        }
                    });
            }
        } else {
            // empty
        }

        if ($factory === null) {
            throw new LogicException('Not supported yet.');
        }

        // Return
        return $factory;
    }

    /**
     * @template T of Model
     *
     * @param class-string<T> $model
     *
     * @return T
     */
    private function getOwner(string $model, Organization|Reseller|Customer $owner): Model {
        return $model::query()->whereKey($owner->getKey())->first()
            ?? $model::factory()->make(['id' => $owner->getKey()]);
    }
}
