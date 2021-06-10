<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\AssetCoverage;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\AssetCoverageResolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAsset;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\AssetCoverageFactory
 */
class AssetCoverageFactoryTest extends TestCase {
    use WithQueryLog;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::find
     */
    public function testFind(): void {
        $factory = $this->app->make(AssetCoverageFactory::class);
        $entry   = new ViewAsset([
            'assetCoverage' => $this->faker->word,
        ]);

        $this->flushQueryLog();

        $factory->find($entry);

        $this->assertCount(1, $this->getQueryLog());
    }

    /**
     * @covers ::create
     *
     * @dataProvider dataProviderCreate
     */
    public function testCreate(?string $expected, Type $type): void {
        $factory = Mockery::mock(AssetCoverageFactory::class);
        $factory->makePartial();
        $factory->shouldAllowMockingProtectedMethods();

        if ($expected) {
            $factory->shouldReceive($expected)
                ->once()
                ->with($type)
                ->andReturns();
        } else {
            $this->expectException(InvalidArgumentException::class);
            $this->expectErrorMessageMatches('/^The `\$type` must be instance of/');
        }

        $factory->create($type);
    }

    /**
     * @covers ::createFromAsset
     */
    public function testCreateFromAsset(): void {
        $key   = $this->faker->word;
        $asset = new ViewAsset([
            'assetCoverage' => $key,
        ]);

        $factory = Mockery::mock(AssetCoverageFactory::class);
        $factory->makePartial();
        $factory->shouldAllowMockingProtectedMethods();
        $factory->shouldReceive('assetCoverage')
            ->once()
            ->with($key)
            ->andReturn(null);

        $factory->create($asset);
    }

    /**
     * @covers ::assetCoverage
     */
    public function testAssetCoverage(): void {
        // Prepare
        $normalizer    = $this->app->make(Normalizer::class);
        $resolver      = $this->app->make(AssetCoverageResolver::class);
        $assetCoverage = AssetCoverage::factory()->create();

        $factory = new class($normalizer, $resolver) extends AssetCoverageFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected AssetCoverageResolver $assetCoverages,
            ) {
                // empty
            }

            public function assetCoverage(?string $key): ?AssetCoverage {
                return parent::assetCoverage($key);
            }
        };

        $this->flushQueryLog();

        // If model exists - no action required
        $this->assertEquals($assetCoverage, $factory->assetCoverage($assetCoverage->key));
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // If not - it should be created
        $created = $factory->assetCoverage(' COVERED_ON_CONTRACT ');

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals('COVERED_ON_CONTRACT', $created->key);
        $this->assertEquals('COVERED_ON_CONTRACT', $created->name);
        $this->assertCount(1, $this->getQueryLog());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderCreate(): array {
        return [
            ViewAsset::class => ['createFromAsset', new ViewAsset()],
            'Unknown'        => [
                null,
                new class() extends Type {
                    // empty
                },
            ],
        ];
    }
    // </editor-fold>
}
