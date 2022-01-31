<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Importer;

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
     * @var \Illuminate\Support\Collection<string,\App\Models\User>
     */
    private Collection $usersById;

    /**
     * @var \Illuminate\Support\Collection<string,\App\Models\User>
     */
    private Collection $usersByEmail;

    /**
     * @param array<\App\Services\KeyCloak\Client\Types\User> $users
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
     * @return \Illuminate\Support\Collection<\App\Models\User>
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
     * @return \Illuminate\Support\Collection<\App\Models\User>
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
