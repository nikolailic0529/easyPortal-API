<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Commands;

use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Models\Permission as PermissionModel;
use App\Models\Role as RoleModel;
use App\Services\Auth\Auth;
use App\Services\Auth\Permission;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\Role;
use App\Services\KeyCloak\Exceptions\OrgAdminGroupNotFound;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Collection;

class SyncPermissions extends Command {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:keycloak-sync-permissions';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = 'Sync KeyCloak permissions.';

    public function __construct(
        protected Auth $auth,
        protected Client $client,
        protected Repository $config,
        protected ExceptionHandler $exceptionHandler,
    ) {
        parent::__construct();
    }

    public function handle(): int {
        GlobalScopes::callWithoutGlobalScope(OwnedByOrganizationScope::class, function (): void {
            $this->process();
        });

        $this->info('Done.');

        return Command::SUCCESS;
    }

    protected function process(): void {
        // Sync Permissions with Models and KeyCloak Roles
        $permissions  = (new Collection($this->auth->getPermissions()))
            ->keyBy(static function (Permission $permission): string {
                return $permission->getName();
            });
        $usedModels   = new Collection();
        $actualModels = PermissionModel::query()
            ->withTrashed()
            ->orderByDesc('deleted_at')
            ->get()
            ->keyBy(static function (PermissionModel $model): string {
                return $model->key;
            });
        $usedRoles    = new Collection();
        $actualRoles  = (new Collection($this->client->getRoles()))
            ->filter(static function (Role $role) use ($permissions, $actualModels): bool {
                // KeyCloak may contain Roles that don't relate to the
                // application, we must not touch them.
                return $permissions->has($role->name)
                    || $actualModels->get($role->name)?->trashed() === false;
            })
            ->keyBy(static function (Role $role): string {
                return $role->name;
            });

        foreach ($permissions as $name => $permission) {
            // Create Role on KeyCloak
            $role = $actualRoles->get($name)
                ?? $this->client->createRole(new Role([
                    'name'        => $name,
                    'description' => $name,
                ]));

            // Create Model
            $model                         = $actualModels->get($name) ?? new PermissionModel();
            $model->{$model->getKeyName()} = $role->id;
            $model->key                    = $name;

            if ($model->trashed()) {
                $model->restore();
            }

            $model->save();

            // Mark as existing
            $usedRoles->put($name, $role);
            $usedModels->put($name, $model);
        }

        // Remove unused Models
        foreach ($actualModels->diffKeys($usedModels) as $model) {
            $model->delete();
        }

        // Remove unused Roles
        foreach ($actualRoles->diffKeys($usedRoles) as $role) {
            $this->client->deleteRoleByName($role->name);
        }

        // Update Org Admin group
        if ($this->config->get('ep.keycloak.org_admin_group')) {
            $orgAdminGroup = $this->client->getGroup($this->config->get('ep.keycloak.org_admin_group'));

            if ($orgAdminGroup) {
                $orgAdminPermissions = $permissions
                    ->filter(static function (Permission $permission): bool {
                        return $permission->isOrgAdmin();
                    });

                // Update Permissions
                $this->client->setGroupRoles(
                    $orgAdminGroup,
                    $actualRoles->intersectByKeys($orgAdminPermissions)->values()->all(),
                );

                // Create/Update Role Model
                $role                        = RoleModel::query()->whereKey($orgAdminGroup->id)->first()
                    ?? new RoleModel();
                $role->{$role->getKeyName()} = $orgAdminGroup->id;
                $role->name                  = $orgAdminGroup->name;
                $role->permissions           = $usedModels->intersectByKeys($orgAdminPermissions);
                $role->organization          = null;
                $role->save();
            } else {
                $this->exceptionHandler->report(new OrgAdminGroupNotFound(
                    $this->config->get('ep.keycloak.org_admin_group'),
                ));
            }
        }
    }
}
