<?php declare(strict_types = 1);

namespace Tests\Providers\Users;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\User;
use Tests\TestCase;

use function array_filter;

class UserProvider {
    /**
     * @param array<string> $permissions
     */
    public function __construct(
        protected ?string $id = null,
        protected array $permissions = [],
        protected ?UserType $type = null,
    ) {
        // empty
    }

    public function __invoke(TestCase $test, ?Organization $organization): User {
        return User::factory()->create(array_filter([
            'id'              => $this->id,
            'type'            => $this->type,
            'permissions'     => $this->permissions,
            'organization_id' => $organization,
        ]));
    }
}
