<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasContactsTests;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Models\Customer
 */
class CustomerTest extends TestCase {
    use HasContactsTests;

    protected function getModel(): Model {
        return new Customer();
    }
}
