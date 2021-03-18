<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasLocationsTests;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Models\Organization
 */
class OrganizationTest extends TestCase {
    use HasLocationsTests;

    protected function getModel(): Model {
        return new Organization();
    }
}
