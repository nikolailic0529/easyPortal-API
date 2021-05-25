<?php declare(strict_types = 1);

namespace App\Services\Organization\Listeners;

use App\Events\Subscriber;
use App\Models\Organization;
use App\Services\DataLoader\Events\ResellerUpdated;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;

class OrganizationUpdater implements Subscriber {
    public function subscribe(Dispatcher $events): void {
        $events->listen(ResellerUpdated::class, $this::class);
    }

    public function handle(ResellerUpdated $event): void {
        // Prepare
        $reseller = $event->getReseller();
        $company  = $event->getCompany();

        // Used?
        $existing = Organization::query()
            ->where(static function (Builder $query) use ($company): void {
                $query->orWhere('keycloak_scope', '=', $company->keycloakName);
                $query->orWhere('keycloak_group_id', '=', $company->keycloakGroupId);
            })
            ->get();

        foreach ($existing as $organization) {
            if ($organization->keycloak_scope === $company->keycloakName) {
                $organization->keycloak_scope = null;
            }

            if ($organization->keycloak_group_id === $company->keycloakGroupId) {
                $organization->keycloak_group_id = null;
            }

            $organization->save();
        }

        // Update
        $organization = Organization::query()
            ->whereKey($reseller->getKey())
            ->withTrashed()
            ->first();

        if ($organization) {
            if ($organization->trashed()) {
                $organization->restore();
            }
        } else {
            $organization                                = new Organization();
            $organization->{$organization->getKeyName()} = $reseller->getKey();
        }

        // Update
        $organization->name              = $reseller->name;
        $organization->keycloak_scope    = $company->keycloakName;
        $organization->keycloak_group_id = $company->keycloakGroupId;
        $organization->save();
    }
}
