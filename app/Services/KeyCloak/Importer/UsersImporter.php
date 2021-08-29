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
use App\Services\KeyCloak\Commands\UsersIterator;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use Closure;
use Illuminate\Support\Collection;
use Laravel\Telescope\Telescope;
use Psr\Log\LoggerInterface;
use Throwable;

use function array_map;

class UsersImporter {
    use GlobalScopes;

    protected ?Closure $onInit   = null;
    protected ?Closure $onChange = null;
    protected ?Closure $onFinish = null;

    // Prefetch collection
    protected Collection $users;

    public function __construct(
        protected LoggerInterface $logger,
        protected Client $client,
        protected UsersIterator $iterator,
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
        int $total = null,
    ): void {
        $this->call(function () use ($continue, $chunk, $limit, $total): void {
            if (!$total) {
                $total = $this->getTotal();
            }
            $status   = new Status($continue, $total);
            $iterator = $this->getIterator();
            if ($chunk) {
                $iterator->setChunkSize($chunk);
            }

            if ($limit) {
                $iterator->setLimit($limit);
            }

            $iterator
                ->onBeforeChunk(function ($items) use ($status): void {
                    $this->onBeforeChunk($items, $status);
                })
                ->onAfterChunk(function () use (&$iterator, $status): void {
                    $this->onAfterChunk($status, $iterator->getOffset());
                })
                ->getIterator();

            $this->onBeforeImport($status);

            foreach ($iterator as $item) {
                /** @var \App\Services\KeyCloak\Client\Types\User $item */
                try {
                    $user = $this->users->get($item->id);
                    if (!$user) {
                        $user                        = new User();
                        $user->{$user->getKeyName()} = $item->id;
                        $user->email                 = $item->email;
                        $user->type                  = UserType::keycloak();
                        $user->given_name            = $item->firstName;
                        $user->family_name           = $item->lastName;
                        $user->email_verified        = $item->emailVerified;
                        $user->permissions           = [];
                    }
                    $organizations = [];
                    $roles         = [];
                    foreach ($item->groups as $group) {
                        $organization = Organization::whereKey($group)->first();
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

    protected function getTotal(): ?int {
        return $this->client->usersCount();
    }

    private function call(Closure $closure): void {
        $this->callWithoutGlobalScope(OwnedByOrganizationScope::class, static function () use ($closure): void {
            Telescope::withoutRecording($closure);
        });
    }

    protected function getIterator(): QueryIterator {
        return $this->iterator;
    }

    /**
     * @param array<string, mixed> $items
     */
    protected function onBeforeChunk(array $items, Status $status): void {
        $ids         = array_map(static function (TypesUser $item) {
            return $item->id;
        }, $items);
        $key         = (new User())->getKeyName();
        $users       = User::whereIn($key, $ids)->get();
        $this->users = $users->keyBy($key);
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
}
