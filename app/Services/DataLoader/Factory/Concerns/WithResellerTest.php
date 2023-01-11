<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Reseller;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Collector\Collector;
use App\Services\DataLoader\Exceptions\ResellerNotFound;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\Types\CompanyKpis;
use App\Services\DataLoader\Schema\Types\Document;
use App\Services\DataLoader\Schema\Types\ViewAsset;
use App\Services\DataLoader\Schema\Types\ViewAssetDocument;
use App\Services\DataLoader\Schema\Types\ViewDocument;
use App\Utils\Eloquent\Model;
use Closure;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\DataLoader\Factory\Concerns\WithReseller
 */
class WithResellerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderReseller
     */
    public function testResellerExistsThroughProvider(Closure $objectFactory): void {
        $reseller = Reseller::factory()->make();
        $resolver = Mockery::mock(ResellerResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($reseller->getKey(), Mockery::any())
            ->once()
            ->andReturn($reseller);

        $factory = new WithResellerTestObject($resolver);
        $object  = $objectFactory($this, $reseller);

        self::assertEquals($reseller, $factory->reseller($object));
    }

    /**
     * @dataProvider dataProviderReseller
     */
    public function testResellerExistsThroughFinder(Closure $objectFactory): void {
        $collector = $this->app->make(Collector::class);
        $reseller  = Reseller::factory()->make();
        $resolver  = Mockery::mock(ResellerResolver::class, [$collector]);
        $resolver->shouldAllowMockingProtectedMethods();
        $resolver->makePartial();
        $resolver
            ->shouldReceive('find')
            ->withArgs(static function (Key $key) use ($reseller): bool {
                return (string) $key === (string) (new Key([$reseller->getKey()]));
            })
            ->once()
            ->andReturn(null);
        $finder = Mockery::mock(ResellerFinder::class);
        $finder
            ->shouldReceive('find')
            ->with($reseller->getKey())
            ->once()
            ->andReturn($reseller);

        $factory = new WithResellerTestObject($resolver, $finder);
        $object  = $objectFactory($this, $reseller);

        self::assertEquals($reseller, $factory->reseller($object));
    }

    /**
     * @dataProvider dataProviderReseller
     */
    public function testResellerResellerNotFound(Closure $objectFactory): void {
        $collector = Mockery::mock(Collector::class);
        $reseller  = Reseller::factory()->make();
        $finder    = Mockery::mock(ResellerFinder::class);
        $finder
            ->shouldReceive('find')
            ->with($reseller->getKey())
            ->once()
            ->andReturn(null);
        $resolver = Mockery::mock(ResellerResolver::class, [$collector]);
        $resolver->shouldAllowMockingProtectedMethods();
        $resolver->makePartial();
        $resolver
            ->shouldReceive('find')
            ->once()
            ->andReturn(null);

        $factory = new WithResellerTestObject($resolver, $finder);
        $object  = $objectFactory($this, $reseller);

        self::expectException(ResellerNotFound::class);

        self::assertEquals($reseller, $factory->reseller($object));
    }

    public function testResellerAssetWithoutReseller(): void {
        $object   = new ViewAsset();
        $resolver = Mockery::mock(ResellerResolver::class);
        $resolver
            ->shouldReceive('get', Mockery::any())
            ->never();

        $factory = new WithResellerTestObject($resolver);

        self::assertNull($factory->reseller($object));
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
            Document::class          => [
                static function (TestCase $test, Reseller $reseller) {
                    return new Document([
                        'resellerId' => $reseller->getKey(),
                    ]);
                },
            ],
            CompanyKpis::class       => [
                static function (TestCase $test, Reseller $reseller) {
                    return new CompanyKpis([
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
 *
 * @extends Factory<Model>
 */
class WithResellerTestObject extends Factory {
    use WithReseller {
        reseller as public;
    }

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(
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

    public function create(Type $type, bool $force = false): ?Model {
        return null;
    }

    public function getModel(): string {
        return Model::class;
    }
}
