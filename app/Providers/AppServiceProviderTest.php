<?php declare(strict_types = 1);

namespace App\Providers;

use Tests\Helpers\Models;
use Tests\TestCase;
use Throwable;

use function implode;

use const PHP_EOL;

/**
 * @internal
 * @coversDefaultClass \App\Providers\AppServiceProvider
 */
class AppServiceProviderTest extends TestCase {
    /**
     * @covers ::bootMorphMap
     */
    public function testBootMorphMap(): void {
        // Search missed
        $missed = [];

        foreach (Models::get() as $model) {
            try {
                if ($model->getName() === $model->newInstance()->getMorphClass()) {
                    $missed[] = $model->getName();
                }
            } catch (Throwable) {
                $missed[] = $model->getName();
            }
        }

        // Assert
        $message = 'Following models missed in MorphMap:'.PHP_EOL.'- '.implode(PHP_EOL.'- ', $missed).PHP_EOL;

        $this->assertEmpty($missed, $message);
    }
}
