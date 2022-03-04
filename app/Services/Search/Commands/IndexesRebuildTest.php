<?php declare(strict_types = 1);

namespace App\Services\Search\Commands;

use App\Models\Customer;
use Tests\TestCase;

use function sprintf;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Commands\IndexesRebuild
 */
class IndexesRebuildTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testCommand(): void {
        $this->assertCommandDescription('ep:search-indexes-rebuild');
    }

    /**
     * @covers ::__invoke
     */
    public function testInvoke(): void {
        $this
            ->artisan(
                'ep:search-indexes-rebuild',
                [
                    'model' => Customer::class,
                ],
            )
            ->expectsOutput(sprintf('Processing `%s`:', Customer::class))
            ->expectsOutput('Done.')
            ->assertSuccessful();
    }
}
