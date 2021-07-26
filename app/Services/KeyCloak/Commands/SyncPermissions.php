<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Commands;

use App\Models\Permission;
use App\Services\Auth\Auth;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

use function str_contains;

class SyncPermissions extends Command {

    protected const DELETED_SUFFIX = '(deleted)';

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
    protected $description = 'Sync keycloak permissions';

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
        // auth permissions
        $authPermissions = $this->auth->getPermissions();

        // Get all permissions
        $permissions = Permission::query()
            ->withTrashed()
            ->orderByDesc('deleted_at')
            ->get()
            ->keyBy('key');

        // Get all keycloak roles
        $roles = (new Collection($this->client->getRoles()))->keyBy('name');

        foreach ($authPermissions as $authPermission) {
            $role = null;
            if (!$roles->has($authPermission)) {
                $role = $this->createRole($authPermission);
            } else {
                $role = $roles->get($authPermission);
                // to be deleted from keycloak
                $roles->forget($authPermission);
            }

            $permission = null;

            if (!$permissions->has($role->name)) {
                $permission = new Permission();
            } else {
                $permission = $permissions->get($role->name);
                if ($permission->trashed()) {
                    $permission->restore();
                }
            }
            $this->savePermission($permission, $role);

            // remove it from permissions to delete the rest
            $permissions->forget($role->name);
        }

        foreach ($permissions as $permission) {
            $permission->delete();
        }

        foreach ($roles as $role) {
            if (!str_contains($role->description ?? '', self::DELETED_SUFFIX)) {
                $role->description = self::DELETED_SUFFIX.' '.($role->description ?? '');
                $this->client->updateRoleByName($role->name, $role);
            }
        }
    }

    protected function createRole(string $name): Role {
        $input = new Role([
            'name'        => $name,
            'description' => $name,
        ]);
        return $this->client->createRole($input);
    }

    protected function savePermission(Permission $permission, Role $role): void {
        $permission->id  = $role->id;
        $permission->key = $role->name;
        $permission->save();
    }
}
