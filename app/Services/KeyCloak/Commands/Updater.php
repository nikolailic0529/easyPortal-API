<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Commands;

use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\KeyCloak\Client\Client;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use Closure;
use Laravel\Telescope\Telescope;
use Psr\Log\LoggerInterface;
use Throwable;


class Updater {
    use GlobalScopes;

    protected ?Closure $onInit   = null;
    protected ?Closure $onChange = null;
    protected ?Closure $onFinish = null;

    public function __construct(
        protected LoggerInterface $logger,
        protected Client $client,
        protected UsersIterator $usersIterator,
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

    public function update(
        string|int $continue = null,
        int $chunk = null,
        int $limit = null,
    ): void {
        $this->call(function () use ($continue, $chunk, $limit): void {
            $status   = new Status($continue, $this->getTotal());
            $iterator = $this
                ->usersIterator
                ->setChunkSize($chunk)
                ->setLimit($limit)
                ->onBeforeChunk(function () use ($status): void {
                    $this->onBeforeChunk($status);
                })
                ->onAfterChunk(function () use (&$iterator, $status): void {
                    $this->onAfterChunk($status, $iterator->getOffset());
                })
                ->getIterator();

            $this->onBeforeImport($status);

            foreach ($iterator as $item) {
                /** @var \App\Services\KeyCloak\Client\Types\User $item */
                try {
                    $user = User::whereKey($item->id)->first();
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

    private function call(Closure $closure): void {
        $this->callWithoutGlobalScope(OwnedByOrganizationScope::class, static function () use ($closure): void {
            Telescope::withoutRecording($closure);
        });
    }

    protected function getIterator(int $chunk): UsersIterator {
        return $this->usersIterator;
    }


    protected function getTotal(): ?int {
        return $this->client->usersCount();
    }

    protected function onBeforeChunk(Status $status): void {
        // Empty
    }

    protected function onAfterChunk(Status $status, string|int|null $continue): void {
        // Update status
        $status->continue = $continue;
        $status->chunk++;

        // Call callback
        if ($this->onChange) {
            ($this->onChange)(clone $status);
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
