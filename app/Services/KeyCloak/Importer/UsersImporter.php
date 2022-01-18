<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Importer;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\User;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\User as KeyCloakUser;
use App\Services\KeyCloak\Exceptions\FailedToImportObject;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Utils\Eloquent\Callbacks\GetKey;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use App\Utils\Eloquent\SmartSave\BatchSave;
use App\Utils\Iterators\ObjectIterator;
use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Collection;
use Laravel\Telescope\Telescope;
use Throwable;

use function array_diff;
use function array_map;
use function in_array;
use function min;

class UsersImporter {
    protected ?Closure $onInit   = null;
    protected ?Closure $onChange = null;
    protected ?Closure $onFinish = null;

    public function __construct(
        protected ExceptionHandler $exceptionHandler,
        protected Repository $config,
        protected Client $client,
    ) {
        // empty
    }

    protected function getClient(): Client {
        return $this->client;
    }

    protected function getExceptionHandler(): ExceptionHandler {
        return $this->exceptionHandler;
    }

    public function onInit(?Closure $closure): static {
        $this->onInit = $closure;

        return $this;
    }

    public function onChange(?Closure $closure): static {
        $this->onChange = $closure;

        return $this;
    }

    public function onFinish(?Closure $closure): static {
        $this->onFinish = $closure;

        return $this;
    }

    public function import(
        string|int $continue = null,
        int $chunk = null,
        int $limit = null,
    ): void {
        $this->call(function () use ($continue, $chunk, $limit): void {
            $status   = new Status($continue, $this->getTotal($limit));
            $iterator = $this->getIterator($chunk, $limit);
            $users    = new Collection();
            $iterator
                ->onBeforeChunk(function ($items) use ($status, &$users): void {
                    $users = $this->onBeforeChunk($items, $status);
                })
                ->onAfterChunk(function () use (&$iterator, $status): void {
                    $this->onAfterChunk($status, $iterator->getOffset());
                })
                ->getIterator();

            $this->onBeforeImport($status);

            foreach ($iterator as $item) {
                /** @var \App\Services\KeyCloak\Client\Types\User $item */
                try {
                    // Properties
                    $user       = $users->get($item->id);
                    $attributes = $item->attributes;

                    if (!$user) {
                        $user                        = new User();
                        $user->{$user->getKeyName()} = $item->id;
                    }

                    $user->email          = $item->email;
                    $user->type           = UserType::keycloak();
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
                    $user->department     = $attributes['department'][0] ?? null;
                    $user->job_title      = $attributes['job_title'][0] ?? null;
                    $user->phone          = $attributes['phone'][0] ?? null;
                    $user->company        = $attributes['company'][0] ?? null;
                    $user->photo          = $attributes['photo'][0] ?? null;
                    $user->organizations  = $this->getOrganizations($user, $item);

                    $user->save();
                } catch (Throwable $exception) {
                    $this->getExceptionHandler()->report(
                        new FailedToImportObject($this, $item, $exception),
                    );
                } finally {
                    $status->processed++;
                }
            }

            $this->onAfterImport($status);
        });
    }

    protected function getTotal(int $limit = null): ?int {
        $total = $this->getClient()->usersCount();
        if ($limit) {
            $total = min($total, $limit);
        }

        return $total;
    }

    private function call(Closure $closure): void {
        GlobalScopes::callWithoutGlobalScope(OwnedByOrganizationScope::class, static function () use ($closure): void {
            Telescope::withoutRecording(static function () use ($closure): void {
                BatchSave::enable($closure);
            });
        });
    }

    protected function getIterator(int $chunk = null, int $limit = null): ObjectIterator {
        $iterator = $this->getClient()->getUsersIterator();

        if ($chunk) {
            $iterator->setChunkSize($chunk);
        }

        if ($limit) {
            $iterator->setLimit($limit);
        }

        return $iterator;
    }

    /**
     * @param array<string, mixed> $items
     */
    protected function onBeforeChunk(array $items, Status $status): Collection {
        $ids   = array_map(static function (KeyCloakUser $item) {
            return $item->id;
        }, $items);
        $key   = (new User())->getKeyName();
        $users = User::query()
            ->with('organizations')
            ->whereIn($key, $ids)
            ->get();

        return $users->keyBy($key);
    }

    protected function onAfterChunk(Status $status, int|null $offset): void {
        // Update status
        $status->offset = $offset;
        $status->chunk++;

        // Call callback
        if ($this->onChange) {
            ($this->onChange)(clone $status, $offset);
        }
    }

    protected function onBeforeImport(Status $status): void {
        if ($this->onInit) {
            ($this->onInit)(clone $status);
        }
    }

    protected function onAfterImport(Status $status): void {
        if ($this->onFinish) {
            ($this->onFinish)(clone $status);
        }
    }

    /**
     * @return \Illuminate\Support\Collection<\App\Models\OrganizationUser>
     */
    protected function getOrganizations(User $user, KeyCloakUser $item): Collection {
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
            ->whereKey($this->config->get('ep.keycloak.org_admin_group'))
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
}
