<?php declare(strict_types = 1);

namespace Tests\Providers\Users;

use App\Models\Enums\UserType;

class RootUserProvider extends UserProvider {
    /**
     * @inheritDoc
     */
    public function __construct(?string $id = null, array $permissions = []) {
        parent::__construct($id, $permissions, UserType::local());
    }
}
