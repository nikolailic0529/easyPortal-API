<?php declare(strict_types = 1);

namespace Tests\Providers\Organizations;

use App\Models\Enums\OrganizationType;
use App\Models\Organization;
use Tests\TestCase;

use function array_filter;

class OrganizationProvider {
    public function __construct(
        protected ?string $id = null,
        protected ?OrganizationType $type = null,
    ) {
        // empty
    }

    public function __invoke(TestCase $test): Organization {
        return Organization::factory()->create(array_filter([
            'id'   => $this->id,
            'type' => $this->type,
        ]));
    }
}
