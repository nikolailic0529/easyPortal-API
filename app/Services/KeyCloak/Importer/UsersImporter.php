<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Importer;

use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\DataLoader\Client\QueryIterator;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\User as TypesUser;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use Closure;
use Illuminate\Support\Collection;
use Laravel\Telescope\Telescope;
use Psr\Log\LoggerInterface;
use Throwable;

use function array_key_exists;
use function array_map;
use function min;

class UsersImporter {
    use GlobalScopes;

    protected ?Closure $onInit   = null;
    protected ?Closure $onChange = null;
    protected ?Closure $onFinish = null;

    public function __construct(
        protected LoggerInterface $logger,
        protected Client $client,
    ) {
        // empty
    }

    protected function getClient(): Client {
        return $this->client;
    }

    protected function getLogger(): LoggerInterface {
        return $this->logger;
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
                    $user = $users->get($item->id);
                    if (!$user) {
                        $user                        = new User();
                        $user->{$user->getKeyName()} = $item->id;
                        $user->email                 = $item->email;
                        $user->type                  = UserType::keycloak();
                        $user->given_name            = $item->firstName ?? null;
                        $user->family_name           = $item->lastName ?? null;
                        $user->email_verified        = $item->emailVerified;
                        $user->enabled               = $item->enabled;
                        $user->permissions           = [];
                        // profile
                        $this->updateProfile($user, $item);
                    }

                    $organizations = [];
                    $roles         = [];
                    foreach ($item->groups as $group) {
                        $organization = Organization::where('keycloak_group_id', '=', $group)->first();
                        $role         = Role::whereKey($group)->first();
                        if ($organization) {
                            $organizations[] = $organization;
                        }
                        if ($role) {
                            $roles[] = $role;
                        }
                    }
                    $user->organizations = $organizations;
                    $user->roles         = $roles;

                    $user->save();
                } catch (Throwable $exception) {
                    // TODO: Use Exception + handler
                    $this->logger->warning('Failed to sync user.', [
                        'importer'  => $this::class,
                        'object'    => $item,
                        'exception' => $exception,
                    ]);
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
        $this->callWithoutGlobalScope(OwnedByOrganizationScope::class, static function () use ($closure): void {
            Telescope::withoutRecording($closure);
        });
    }

    protected function getIterator(int $chunk = null, int $limit = null): QueryIterator {
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
        $ids   = array_map(static function (TypesUser $item) {
            return $item->id;
        }, $items);
        $key   = (new User())->getKeyName();
        $users = User::whereIn($key, $ids)->get();
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

    protected function updateProfile(User $user, TypesUser $item): void {
        $properties = [
            'office_phone',
            'contact_email',
            'title',
            'academic_title',
            'mobile_phone',
            'department',
            'job_title',
            'phone',
            'company',
            'photo',
        ];
        $attributes = $item->attributes;
        foreach ($properties as $property) {
            if (array_key_exists($property, $attributes)) {
                $user->{$property} = $attributes[$property][0];
            }
        }
    }
}
