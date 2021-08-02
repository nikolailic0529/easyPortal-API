<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\Relations\HasContactsTests;
use App\Models\Concerns\Relations\HasLocationsTests;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Models\Customer
 */
class CustomerTest extends TestCase {
    use HasLocationsTests;
    use HasContactsTests;

    protected function getModel(): Model {
        return new Customer();
    }
}
