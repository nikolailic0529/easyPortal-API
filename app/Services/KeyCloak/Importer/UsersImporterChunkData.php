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
     * @var \Illuminate\Support\Collection<\App\Models\User>
     */
    private Collection $usersById;

    /**
     * @param array<\App\Services\KeyCloak\Client\Types\User> $users
     */
    public function __construct(array $users) {
        foreach ($users as $user) {
            $this->ids[] = $user->id;
        }
    }

    /**
     * @return \Illuminate\Support\Collection<\App\Models\User>
     */
    public function getUsersById(): Collection {
        if (!isset($this->usersById)) {
            $key             = (new User())->getKeyName();
            $this->usersById = User::query()
                ->with('organizations')
                ->whereIn($key, $this->ids)
                ->get()
                ->keyBy(new GetKey());
        }

        return $this->usersById;
    }
}
