<?php declare(strict_types = 1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Tests\Helpers\Models;
use Tests\TestCase;

use function ksort;

/**
 * @internal
 * @coversDefaultClass \App\Providers\AppServiceProvider
 */
class AppServiceProviderTest extends TestCase {
    /**
     * @covers ::bootMorphMap
     */
    public function testBootMorphMap(): void {
        $expected = [];
        $actual   = Relation::$morphMap;

        foreach (Models::get() as $model) {
            $expected[$model->getShortName()] = $model->getName();
        }

        ksort($expected);

        $this->assertEquals($actual, $expected);
    }
}
