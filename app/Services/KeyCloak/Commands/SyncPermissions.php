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
        // Sync available permissions with Models and KeyCloak Roles
        $permissions = $this->auth->getPermissions();
        $models      = Permission::query()
            ->withTrashed()
            ->orderByDesc('deleted_at')
            ->get()
            ->keyBy('key');
        $roles       = (new Collection($this->client->getRoles()))->keyBy('name');

        foreach ($permissions as $permission) {
            // Prepare
            $name = $permission->getName();

            // Create Role on KeyCloak
            $role = $roles->get($name)
                ?? $this->client->createRole(new Role([
                    'name'        => $name,
                    'description' => $name,
                ]));

            // Create Model
            $model                         = $models->get($name) ?? new Permission();
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

        // Mark unused Roles
        foreach ($roles as $role) {
            if (!str_contains($role->description ?? '', self::DELETED_SUFFIX)) {
                $role->description = self::DELETED_SUFFIX.' '.($role->description ?? '');
                $this->client->updateRoleByName($role->name, $role);
            }
        }
    }
}
