<?php declare(strict_types = 1);

namespace App\Services\Organization\Testing\Database;

use App\Models\Customer;
use App\Models\Enums\OrganizationType;
use App\Models\Organization;
use App\Models\Reseller;
use App\Services\Organization\Eloquent\OwnedByCustomer;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Services\Organization\Eloquent\OwnedByReseller;
use App\Services\Organization\Eloquent\OwnedByScope;
use Illuminate\Database\Eloquent\Factories\Factory;
use LogicException;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @mixin Factory
 */
trait OwnedBy {
    /**
     * @return static<TModel>
     */
    public function ownedBy(Organization $organization): static {
        return $this->state(function () use ($organization): array {
            // Owned?
            $class    = $this->modelName();
            $model    = new $class();
            $property = OwnedByScope::getProperty($organization, $model);

            if ($property === null) {
                throw new LogicException('Model is not supported.');
            }

            if ($property->isRelation()) {
                throw new LogicException('Relations not supported yet.');
            }

            // Owner
            $owner = null;

            if ($model instanceof OwnedByOrganization) {
                $owner = $organization;
            } else {
                switch ($organization->type) {
                    case OrganizationType::reseller():
                        if ($model instanceof OwnedByReseller) {
                            $owner = Reseller::query()->whereKey($organization->getKey())->first()
                                ?? Reseller::factory()->create(['id' => $organization->getKey()]);
                        }
                        break;
                    case OrganizationType::customer():
                        if ($model instanceof OwnedByCustomer) {
                            $owner = Customer::query()->whereKey($organization->getKey())->first()
                                ?? Customer::factory()->create(['id' => $organization->getKey()]);
                        }
                        break;
                    default:
                        // empty
                        break;
                }
            }

            if ($owner === null) {
                throw new LogicException('Something wrong....');
            }

            // Return
            return [
                $property->getName() => $owner,
            ];
        });
    }
}
