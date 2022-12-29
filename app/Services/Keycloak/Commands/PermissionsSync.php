<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Commands;

use App\Models\Permission as PermissionModel;
use App\Models\Role as RoleModel;
use App\Services\Auth\Auth;
use App\Services\Auth\Permission;
use App\Services\Keycloak\Client\Client;
use App\Services\Keycloak\Client\Types\Role;
use App\Services\Keycloak\Exceptions\OrgAdminGroupNotFound;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'ep:keycloak-permissions-sync')]
class PermissionsSync extends Command {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:keycloak-permissions-sync';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string|null
     */
    protected $description = 'Sync Keycloak permissions.';

    public function __construct(
        protected Auth $auth,
        protected Client $client,
        protected Repository $config,
        protected ExceptionHandler $exceptionHandler,
    ) {
        parent::__construct();
    }

    public function handle(): int {
        GlobalScopes::callWithoutAll(function (): void {
            $this->process();
        });

        $this->info('Done.');

        return Command::SUCCESS;
    }

    protected function process(): void {
        // Sync Permissions with Models and Keycloak Roles
        /** @var EloquentCollection<int, PermissionModel> $usedModels */
        $usedModels   = new EloquentCollection();
        $permissions  = (new Collection($this->auth->getPermissions()))
            ->keyBy(static function (Permission $permission): string {
                return $permission->getName();
            });
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
                // Keycloak may contain Roles that don't relate to the
                // application, we must not touch them.
                return $permissions->has($role->name)
                    || $actualModels->get($role->name)?->trashed() === false;
            })
            ->keyBy(static function (Role $role): string {
                return $role->name;
            });

        foreach ($permissions as $name => $permission) {
            // Create Role on Keycloak
            $role = $actualRoles->get($name)
                ?? $this->client->createRole(new Role([
                    'name'        => $name,
                    'description' => $name,
                ]));

            // Create Model
            $model      = $actualModels->get($name) ?? new PermissionModel();
            $model->id  = $role->id;
            $model->key = $name;

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
            $this->client->deleteRole($role->name);
        }

        // Update Org Admin group
        $orgAdminGroupId = $this->config->get('ep.keycloak.org_admin_group');

        if ($orgAdminGroupId) {
            $orgAdminGroup = $this->client->getGroup($orgAdminGroupId);

            if ($orgAdminGroup) {
                // Update Permissions
                $this->client->updateGroupRoles(
                    $orgAdminGroup,
                    $actualRoles->intersectByKeys($permissions)->values()->all(),
                );

                // Create/Update Role Model
                $role               = RoleModel::query()->whereKey($orgAdminGroup->id)->first()
                    ?? new RoleModel();
                $role->id           = $orgAdminGroup->id;
                $role->name         = $orgAdminGroup->name;
                $role->permissions  = $usedModels->intersectByKeys($permissions);
                $role->organization = null;
                $role->save();
            } else {
                $this->exceptionHandler->report(new OrgAdminGroupNotFound(
                    $orgAdminGroupId,
                ));
            }
        }
    }
}
