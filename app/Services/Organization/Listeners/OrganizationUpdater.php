<?php declare(strict_types = 1);

namespace App\Services\Organization\Listeners;

use App\Events\Subscriber;
use App\Models\Organization;
use App\Models\Reseller;
use App\Services\DataLoader\Events\ResellerUpdated;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;

use function filter_var;
use function is_int;
use function mb_strtolower;
use function preg_replace;
use function str_replace;
use function str_starts_with;
use function trim;

use const FILTER_VALIDATE_INT;

class OrganizationUpdater implements Subscriber {
    public function subscribe(Dispatcher $events): void {
        $events->listen(ResellerUpdated::class, $this::class);
    }

    public function handle(ResellerUpdated $event): void {
        // Get or Create
        $reseller     = $event->getReseller();
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
            $organization->keycloak_scope                = $this->getKeycloakScope($reseller);
        }

        // Update
        $organization->name = $reseller->name;
        $organization->save();
    }

    protected function getKeycloakScope(Reseller $reseller): string {
        $scope = $this->normalizeKeycloakScope($reseller->name);
        $last  = Organization::query()
            ->where(static function (Builder $builder) use ($scope): void {
                $builder->orWhere('keycloak_scope', '=', $scope);
                $builder->orWhere('keycloak_scope', 'like', "{$scope}_%");
            })
            ->withTrashed()
            ->get()
            ->map(static function (Organization $organization): string {
                return $organization->keycloak_scope;
            })
            ->filter(static function (string $value) use ($scope): bool {
                return str_starts_with($value, $scope);
            })
            ->map(static function (string $value) use ($scope): int|bool {
                $value = str_replace("{$scope}_", '', $value);
                $value = str_replace($scope, '', $value);

                if ($value !== '') {
                    $value = filter_var($value, FILTER_VALIDATE_INT);
                } else {
                    $value = 0;
                }

                return $value;
            })
            ->filter(static function (int|bool $value): bool {
                return $value !== false;
            })
            ->sort()
            ->last();

        if (is_int($last)) {
            $scope = $scope.'_'.($last + 1);
        }

        return $scope;
    }

    protected function normalizeKeycloakScope(string $scope): string {
        $scope = trim(mb_strtolower($scope));
        $scope = preg_replace('/\P{L}+/ui', '', $scope);

        return $scope;
    }
}
