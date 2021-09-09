<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Reseller;
use App\Services\DataLoader\Exceptions\ResellerNotFound;
use App\Services\DataLoader\Factory;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Schema\ViewDocument;
use Closure;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\Concerns\WithReseller
 */
class WithResellerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::reseller
     *
     * @dataProvider dataProviderReseller
     */
    public function testResellerExistsThroughProvider(Closure $objectFactory): void {
        $normalizer = $this->app->make(Normalizer::class);
        $reseller   = Reseller::factory()->make();
        $resolver   = Mockery::mock(ResellerResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($reseller->getKey(), Mockery::any())
            ->once()
            ->andReturn($reseller);

        $factory = new WithResellerTestObject($normalizer, $resolver);
        $object  = $objectFactory($this, $reseller);

        $this->assertEquals($reseller, $factory->reseller($object));
    }

    /**
     * @covers ::reseller
     *
     * @dataProvider dataProviderReseller
     */
    public function testResellerExistsThroughFinder(Closure $objectFactory): void {
        $normalizer = $this->app->make(Normalizer::class);
        $reseller   = Reseller::factory()->make();
        $resolver   = Mockery::mock(ResellerResolver::class, [$this->app->make(Normalizer::class)]);
        $resolver->shouldAllowMockingProtectedMethods();
        $resolver->makePartial();
        $resolver
            ->shouldReceive('find')
            ->with($reseller->getKey())
            ->once()
            ->andReturn(null);
        $finder = Mockery::mock(ResellerFinder::class);
        $finder
            ->shouldReceive('find')
            ->with($reseller->getKey())
            ->once()
            ->andReturn($reseller);

        $factory = new WithResellerTestObject($normalizer, $resolver, $finder);
        $object  = $objectFactory($this, $reseller);

        $this->assertEquals($reseller, $factory->reseller($object));
    }

    /**
     * @covers ::reseller
     *
     * @dataProvider dataProviderReseller
     */
    public function testResellerResellerNotFound(Closure $objectFactory): void {
        $normalizer = $this->app->make(Normalizer::class);
        $reseller   = Reseller::factory()->make();
        $resolver   = Mockery::mock(ResellerResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($reseller->getKey(), Mockery::any())
            ->once()
            ->andReturn(null);

        $factory = new WithResellerTestObject($normalizer, $resolver);
        $object  = $objectFactory($this, $reseller);

        $this->expectException(ResellerNotFound::class);

        $this->assertEquals($reseller, $factory->reseller($object));
    }

    /**
     * @covers ::reseller
     */
    public function testResellerAssetWithoutReseller(): void {
        $normalizer = $this->app->make(Normalizer::class);
        $object     = new ViewAsset();
        $resolver   = Mockery::mock(ResellerResolver::class);
        $resolver
            ->shouldReceive('get', Mockery::any())
            ->never();

        $factory = new WithResellerTestObject($normalizer, $resolver);

        $this->assertNull($factory->reseller($object));
    }
    //</editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderReseller(): array {
        return [
            ViewAssetDocument::class => [
                static function (TestCase $test, Reseller $reseller) {
                    return new ViewAssetDocument([
                        'reseller' => [
                            'id' => $reseller->getKey(),
                        ],
                    ]);
                },
            ],
            ViewDocument::class      => [
                static function (TestCase $test, Reseller $reseller) {
                    return new ViewDocument([
                        'resellerId' => $reseller->getKey(),
                    ]);
                },
            ],
            ViewAsset::class         => [
                static function (TestCase $test, Reseller $reseller) {
                    return new ViewAsset([
                        'resellerId' => $reseller->getKey(),
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
class WithResellerTestObject extends Factory {
    use WithReseller {
        reseller as public;
    }

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(
        protected Normalizer $normalizer,
        protected ResellerResolver $resellerResolver,
        protected ?ResellerFinder $resellerFinder = null,
    ) {
        // empty
    }

    protected function getResellerResolver(): ResellerResolver {
        return $this->resellerResolver;
    }

    protected function getResellerFinder(): ?ResellerFinder {
        return $this->resellerFinder;
    }
}
