<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Importer;

use App\Models\User;
use App\Utils\Eloquent\Callbacks\GetKey;
use Illuminate\Support\Collection;

class UsersImporterChunkData {
    /**
     * @var array<string>
     */
    private array $ids = [];

    /**
     * @var array<string>
     */
    private array $emails = [];

    /**
     * @var Collection<string,User>
     */
    private Collection $usersById;

    /**
     * @var Collection<string,User>
     */
    private Collection $usersByEmail;

    /**
     * @param array<\App\Services\Keycloak\Client\Types\User> $users
     */
    public function __construct(array $users) {
        foreach ($users as $user) {
            $this->ids[]    = $user->id;
            $this->emails[] = $user->email;
        }
    }

    /**
     * Returns existing User (with trashed).
     */
    public function getUserById(string $id): ?User {
        return $this->getUsersById()->get($id);
    }

    /**
     * Returns existing Users (with trashed).
     *
     * @return Collection<int, User>
     */
    public function getUsersById(): Collection {
        if (!isset($this->usersById)) {
            $key             = (new User())->getKeyName();
            $this->usersById = User::query()
                ->withTrashed()
                ->with('organizations')
                ->whereIn($key, $this->ids)
                ->get()
                ->keyBy(new GetKey());
        }

        return $this->usersById;
    }

    /**
     * Returns existing User (without trashed).
     */
    public function getUserByEmail(string $email): ?User {
        return $this->getUsersByEmail()->get($email);
    }

    /**
     * Returns existing Users (without trashed).
     *
     * @return Collection<string, User>
     */
    public function getUsersByEmail(): Collection {
        if (!isset($this->usersByEmail)) {
            $this->usersByEmail = User::query()
                ->whereIn('email', $this->emails)
                ->get()
                ->keyBy(static function (User $user): string {
                    return $user->email;
                });
        }

        return $this->usersByEmail;
    }
}
