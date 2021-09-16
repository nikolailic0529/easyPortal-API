<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Commands;

use App\Models\Permission as PermissionModel;
use App\Services\Auth\Auth;
use App\Services\Auth\Permission;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\Role;
use Illuminate\Console\Command;
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
    protected $description = 'Sync keycloak permissions.';

    public function __construct(
        protected Auth $auth,
        protected Client $client,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): void {
        // Sync Permissions with Models and KeyCloak Roles
        $permissions = (new Collection($this->auth->getPermissions()))
            ->keyBy(static function (Permission $permission): string {
                return $permission->getName();
            });
        $models      = PermissionModel::query()
            ->withTrashed()
            ->orderByDesc('deleted_at')
            ->get()
            ->keyBy(static function (PermissionModel $model): string {
                return $model->key;
            });
        $roles       = (new Collection($this->client->getRoles()))
            ->filter(static function (Role $role) use ($permissions, $models): bool {
                // KeyCloak may contain Roles that don't relate to the
                // application, we must not touch them.
                return $permissions->has($role->name)
                    || $models->get($role->name)?->trashed() === false;
            })
            ->keyBy(static function (Role $role): string {
                return $role->name;
            });

        foreach ($permissions as $name => $permission) {
            // Create Role on KeyCloak
            $role = $roles->get($name)
                ?? $this->client->createRole(new Role([
                    'name'        => $name,
                    'description' => $name,
                ]));

            // Create Model
            $model                         = $models->get($name) ?? new PermissionModel();
            $model->{$model->getKeyName()} = $role->id;
            $model->key                    = $name;

            if ($model->trashed()) {
                $model->restore();
            }

            $model->save();

            // Mark as existing
            $models->forget($name);
            $roles->forget($name);
        }

        // Remove unused Models
        foreach ($models as $model) {
            $model->delete();
        }

        // Remove unused Roles
        foreach ($roles as $role) {
            $this->client->deleteRoleByName($role->name);
        }
    }
}
