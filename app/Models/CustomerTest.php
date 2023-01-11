<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasContactsTests;
use App\Utils\Eloquent\Model;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Models\Customer
 */
class CustomerTest extends TestCase {
    use HasContactsTests;

    protected function getModel(): Model {
        return new Customer();
    }
}
