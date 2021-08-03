<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\Relations\HasLocationsTests;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Models\Reseller
 */
class ResellerTest extends TestCase {
    use HasLocationsTests;

    protected function getModel(): Model {
        return new Reseller();
    }
}
