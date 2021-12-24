<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Models\Coverage;
use App\Models\Reseller;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\I18n\Formatter;
use App\Utils\Iterators\OneChunkOffsetBasedObjectIterator;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Tests\TestCase;

use function max;
use function mb_strlen;
use function mb_strtoupper;
use function str_pad;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Commands\CountAssets
 */
class CountAssetsTest extends TestCase {
    /**
     * @covers ::__invoke
     */
    public function testInvoke(): void {
        $reseller = Reseller::factory()->create();
        $coverage = Coverage::factory()->create();
        $assets   = [
            [
                'id'            => $this->faker->uuid,
                'assetCoverage' => [mb_strtoupper($coverage->key)],
            ],
            [
                'id'            => $this->faker->uuid,
                'assetCoverage' => null,
            ],
        ];

        $this->override(Client::class, static function (MockInterface $mock) use ($reseller, $assets): void {
            // $assets->toArray()
            $mock
                ->shouldReceive('getAssetsByResellerId')
                ->with($reseller->getKey())
                ->once()
                ->andReturn(new OneChunkOffsetBasedObjectIterator(
                    static function () use ($assets): array {
                        return $assets;
                    },
                    static function (array $item): ViewAsset {
                        return ViewAsset::make($item);
                    },
                ));
        });

        $length          = max(mb_strlen('Total Assets'), mb_strlen($coverage->name));
        $formatter       = $this->app->make(Formatter::class);
        $expectedTotal   = str_pad(Str::title('Total Assets'), $length).': '.$formatter->integer(2);
        $expectedCovered = str_pad(Str::title($coverage->name), $length).': '.$formatter->integer(1);

        $this
            ->artisan('ep:data-loader-count-assets', [
                '--reseller' => $reseller->getKey(),
                '--coverage' => $coverage->key,
            ])
            ->assertSuccessful()
            ->expectsOutput($expectedTotal)
            ->expectsOutput($expectedCovered);
    }
}
