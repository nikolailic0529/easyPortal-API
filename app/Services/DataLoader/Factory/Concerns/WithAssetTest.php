<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Asset;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Collector\Collector;
use App\Services\DataLoader\Exceptions\AssetNotFound;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Finders\AssetFinder;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\AssetResolver;
use App\Services\DataLoader\Schema\Types\DocumentEntry;
use Closure;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Concerns\WithAsset
 */
class WithAssetTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::asset
     *
     * @dataProvider dataProviderAsset
     */
    public function testAssetExistsThroughProvider(Closure $objectFactory): void {
        $normalizer = $this->app->make(Normalizer::class);
        $asset      = Asset::factory()->make();
        $resolver   = Mockery::mock(AssetResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($asset->getKey(), Mockery::any())
            ->once()
            ->andReturn($asset);

        $factory = new WithAssetTestObject($normalizer, $resolver);
        $object  = $objectFactory($this, $asset);

        self::assertEquals($asset, $factory->asset($object));
    }

    /**
     * @covers ::asset
     *
     * @dataProvider dataProviderAsset
     */
    public function testAssetExistsThroughFinder(Closure $objectFactory): void {
        $normalizer = $this->app->make(Normalizer::class);
        $collector  = $this->app->make(Collector::class);
        $asset      = Asset::factory()->make();
        $resolver   = Mockery::mock(AssetResolver::class, [$normalizer, $collector]);
        $resolver->shouldAllowMockingProtectedMethods();
        $resolver->makePartial();
        $resolver
            ->shouldReceive('find')
            ->withArgs(static function (Key $key) use ($normalizer, $asset): bool {
                return (string) $key === (string) (new Key($normalizer, [$asset->getKey()]));
            })
            ->once()
            ->andReturn(null);
        $finder = Mockery::mock(AssetFinder::class);
        $finder
            ->shouldReceive('find')
            ->with($asset->getKey())
            ->once()
            ->andReturn($asset);

        $factory = new WithAssetTestObject($normalizer, $resolver, $finder);
        $object  = $objectFactory($this, $asset);

        self::assertEquals($asset, $factory->asset($object));
    }

    /**
     * @covers ::asset
     *
     * @dataProvider dataProviderAsset
     */
    public function testAssetAssetNotFound(Closure $objectFactory): void {
        $normalizer = $this->app->make(Normalizer::class);
        $collector  = Mockery::mock(Collector::class);
        $asset      = Asset::factory()->make();
        $finder     = Mockery::mock(AssetFinder::class);
        $finder
            ->shouldReceive('find')
            ->with($asset->getKey())
            ->once()
            ->andReturn(null);

        $resolver = Mockery::mock(AssetResolver::class, [$normalizer, $collector]);
        $resolver->shouldAllowMockingProtectedMethods();
        $resolver->makePartial();
        $resolver
            ->shouldReceive('find')
            ->once()
            ->andReturn(null);

        $factory = new WithAssetTestObject($normalizer, $resolver, $finder);
        $object  = $objectFactory($this, $asset);

        self::expectException(AssetNotFound::class);

        self::assertEquals($asset, $factory->asset($object));
    }

    /**
     * @covers ::asset
     */
    public function testAssetAssetWithoutAsset(): void {
        $normalizer = $this->app->make(Normalizer::class);
        $object     = new DocumentEntry();
        $resolver   = Mockery::mock(AssetResolver::class);
        $resolver
            ->shouldReceive('get')
            ->never();

        $factory = new WithAssetTestObject($normalizer, $resolver);

        self::assertNull($factory->asset($object));
    }
    //</editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderAsset(): array {
        return [
            DocumentEntry::class => [
                static function (TestCase $test, Asset $asset) {
                    return new DocumentEntry([
                        'assetId' => $asset->getKey(),
                    ]);
                },
            ],
        ];
    }
    // </editor-fold>
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class WithAssetTestObject extends Factory {
    use WithAsset {
        asset as public;
    }

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(
        protected Normalizer $normalizer,
        protected AssetResolver $assetResolver,
        protected ?AssetFinder $assetFinder = null,
    ) {
        // empty
    }

    protected function getAssetResolver(): AssetResolver {
        return $this->assetResolver;
    }

    protected function getAssetFinder(): ?AssetFinder {
        return $this->assetFinder;
    }
}
