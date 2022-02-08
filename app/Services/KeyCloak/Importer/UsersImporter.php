<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Importer;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\User;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\User as KeyCloakUser;
use App\Services\KeyCloak\Exceptions\FailedToImport;
use App\Services\KeyCloak\Exceptions\FailedToImportObject;
use App\Services\KeyCloak\Exceptions\FailedToImportUserConflictType;
use App\Utils\Eloquent\Callbacks\GetKey;
use App\Utils\Iterators\ObjectIterator;
use App\Utils\Processor\Processor;
use App\Utils\Processor\State;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Throwable;

use function array_diff;
use function array_merge;
use function in_array;

/**
 * @extends \App\Utils\Processor\Processor<\App\Services\KeyCloak\Client\Types\User,\App\Services\KeyCloak\Importer\UsersImporterChunkData,\App\Services\KeyCloak\Importer\UsersImporterState>
 */
class UsersImporter extends Processor {
    public function __construct(
        ExceptionHandler $exceptionHandler,
        Dispatcher $dispatcher,
        private Repository $config,
        private Client $client,
    ) {
        parent::__construct($exceptionHandler, $dispatcher);
    }

    protected function getConfig(): Repository {
        return $this->config;
    }

    protected function getClient(): Client {
        return $this->client;
    }

    protected function getTotal(State $state): ?int {
        return $this->getClient()->usersCount();
    }

    protected function getIterator(State $state): ObjectIterator {
        return $this->getClient()->getUsersIterator();
    }

    protected function report(Throwable $exception, mixed $item = null): void {
        if (!($exception instanceof FailedToImport)) {
            $exception = new FailedToImportObject($item, $exception);
        }

        $this->getExceptionHandler()->report($exception);
    }

    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        return new UsersImporterChunkData($items);
    }

    /**
     * @param \App\Services\KeyCloak\Importer\UsersImporterState     $state
     * @param \App\Services\KeyCloak\Importer\UsersImporterChunkData $data
     * @param \App\Services\KeyCloak\Client\Types\User               $item
     */
    protected function process(State $state, mixed $data, mixed $item): void {
        // User
        $user    = $this->getUser($data, $item);
        $another = $data->getUserByEmail($item->email);

        if ($another && !$another->is($user)) {
            // Email should be unique, or we will get "Integrity constraint
            // violation" error. So we mark the conflicting user to update it
            // later.
            $another->email = "(conflict) {$another->email}";

            $another->save();
        }

        // Properties
        $attributes           = $item->attributes;
        $user->email          = $item->email;
        $user->given_name     = $item->firstName ?? null;
        $user->family_name    = $item->lastName ?? null;
        $user->email_verified = $item->emailVerified;
        $user->enabled        = $item->enabled;
        $user->permissions    = [];
        $user->office_phone   = $attributes['office_phone'][0] ?? null;
        $user->contact_email  = $attributes['contact_email'][0] ?? null;
        $user->title          = $attributes['title'][0] ?? null;
        $user->academic_title = $attributes['academic_title'][0] ?? null;
        $user->mobile_phone   = $attributes['mobile_phone'][0] ?? null;
        $user->job_title      = $attributes['job_title'][0] ?? null;
        $user->phone          = $attributes['phone'][0] ?? null;
        $user->company        = $attributes['company'][0] ?? null;
        $user->photo          = $attributes['photo'][0] ?? null;
        $user->organizations  = $this->getUserOrganizations($user, $item);
        $user->synced_at      = Date::now();

        // Save
        $user->save();
    }

    /**
     * @param \App\Services\KeyCloak\Importer\UsersImporterState $state
     */
    protected function finish(State $state): void {
        // Remove deleted users
        if ($state->overall && $state->failed === 0 && $state->started !== null) {
            $users = User::query()
                ->where('type', '=', UserType::keycloak())
                ->where(static function (Builder $builder) use ($state): void {
                    $builder->orWhereNull('synced_at');
                    $builder->orWhere('synced_at', '<', $state->started);
                })
                ->getChangeSafeIterator();

            foreach ($users as $user) {
                try {
                    $this->deleteUser($user);
                } catch (Throwable $exception) {
                    $this->report($exception, $user);
                }
            }
        }

        // Finish
        parent::finish($state);
    }

    protected function getUser(UsersImporterChunkData $data, KeyCloakUser $item): User {
        $user = $data->getUserById($item->id);

        if (!$user) {
            $user                        = new User();
            $user->type                  = UserType::keycloak();
            $user->{$user->getKeyName()} = $item->id;
        }

        if ($user->type !== UserType::keycloak()) {
            throw new FailedToImportUserConflictType($this, $item, $user);
        }

        if ($user->trashed()) {
            $user->restore();
        }

        return $user;
    }

    /**
     * @return \Illuminate\Support\Collection<\App\Models\OrganizationUser>
     */
    protected function getUserOrganizations(User $user, KeyCloakUser $item): Collection {
        // Organizations & Roles
        // (some groups refers to the organization some to roles)
        $organizations = Organization::query()
            ->whereIn('keycloak_group_id', $item->groups)
            ->get()
            ->keyBy(new GetKey());
        $existing      = $user->organizations
            ->keyBy(static function (OrganizationUser $user): string {
                return $user->organization_id;
            });
        $skipped       = clone $existing;
        $roles         = Role::query()
            ->whereIn('organization_id', $organizations->keys())
            ->whereIn(
                (new Role())->getKeyName(),
                array_diff($item->groups, $organizations->keys()->all()),
            )
            ->get();

        foreach ($roles as $role) {
            $key                      = $role->organization_id;
            $orgUser                  = $existing->get($key) ?? new OrganizationUser();
            $orgUser->organization_id = $key;
            $orgUser->role            = $role;
            $orgUser->enabled       ??= $item->enabled;

            $organizations->forget($key);
            $existing->put($key, $orgUser);
            $skipped->forget($key);
        }

        // Reset
        foreach ($skipped as $orgUser) {
            $orgUser->role = null;
        }

        // Technically we should update Shared Roles here. But they are not
        // related to any Organization thus we cannot associate them with
        // proper Organization...
        //
        // For this reason, we process the Owner role only.
        $orgAdminRole = Role::query()
            ->whereKey($this->getConfig()->get('ep.keycloak.org_admin_group'))
            ->first();

        if ($orgAdminRole && in_array($orgAdminRole->getKey(), $item->groups, true)) {
            foreach ($organizations as $organization) {
                $key     = $organization->getKey();
                $orgUser = $existing->get($key) ?: new OrganizationUser();

                if ($orgUser->role_id === null) {
                    $orgUser->organization = $organization;
                    $orgUser->role         = $orgAdminRole;
                    $orgUser->enabled    ??= $item->enabled;

                    $existing->put($key, $orgUser);
                    $skipped->forget($key);
                }
            }
        }

        // Return
        return $existing;
    }

    protected function deleteUser(User $user): void {
        $user->delete();
    }

    /**
     * @inheritDoc
     */
    protected function defaultState(array $state): array {
        return parent::defaultState(array_merge($state, [
            'started' => Date::now(),
            'overall' => $state['limit'] === null && $state['offset'] === null,
        ]));
    }

    /**
     * @inheritDoc
     */
    protected function restoreState(array $state): State {
        return new UsersImporterState($state);
    }
}
