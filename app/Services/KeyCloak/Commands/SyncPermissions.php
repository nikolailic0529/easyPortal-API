<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Commands;

use App\Models\Permission;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\Role;
use Illuminate\Console\Command;

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
    protected $description = 'Sync keycloak permissions';

    public function __construct(protected Client $client) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): void {
        // Get all permissions
        $permissions = Permission::all()->keyBy((new Permission())->getKeyName());

        // Get all keycloak roles
        $roles = $this->client->getRoles();
        foreach ($roles as $role) {
            $permission = null;
            if (!$permissions->has($role->id)) {
                $permission = new Permission();
            } else {
                $permission = $permissions->get($role->id);
            }

            $this->savePermission($permission, $role);

            // remove it from permissions to delete the rest
            $permissions->forget($role->id);
        }

        foreach ($permissions as $permission) {
            $permission->delete();
        }
    }

    protected function savePermission(Permission $permission, Role $role): void {
        $permission->id  = $role->id;
        $permission->key = $role->name;
        $keycloak_fields = $role->getProperties();
        unset($keycloak_fields['id']);
        unset($keycloak_fields['name']);
        $permission->keycloak_fields = $keycloak_fields;
        $permission->save();
    }
}
