<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Distributor;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Collector\Collector;
use App\Services\DataLoader\Exceptions\DistributorNotFound;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Finders\DistributorFinder;
use App\Services\DataLoader\Resolver\Resolvers\DistributorResolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\Types\Document;
use App\Services\DataLoader\Schema\Types\ViewDocument;
use App\Utils\Eloquent\Model;
use Closure;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Concerns\WithDistributor
 */
class WithDistributorTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::distributor
     *
     * @dataProvider dataProviderDistributor
     */
    public function testDistributorExistsThroughProvider(Closure $objectFactory): void {
        $distributor = Distributor::factory()->make();
        $resolver    = Mockery::mock(DistributorResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($distributor->getKey(), Mockery::any())
            ->once()
            ->andReturn($distributor);

        $factory = new WithDistributorTestObject($resolver);
        $object  = $objectFactory($this, $distributor);

        self::assertEquals($distributor, $factory->distributor($object));
    }

    /**
     * @covers ::distributor
     *
     * @dataProvider dataProviderDistributor
     */
    public function testDistributorExistsThroughFinder(Closure $objectFactory): void {
        $collector   = $this->app->make(Collector::class);
        $distributor = Distributor::factory()->make();
        $resolver    = Mockery::mock(DistributorResolver::class, [$collector]);
        $resolver->shouldAllowMockingProtectedMethods();
        $resolver->makePartial();
        $resolver
            ->shouldReceive('find')
            ->withArgs(static function (Key $key) use ($distributor): bool {
                return (string) $key === (string) (new Key([$distributor->getKey()]));
            })
            ->once()
            ->andReturn(null);
        $finder = Mockery::mock(DistributorFinder::class);
        $finder
            ->shouldReceive('find')
            ->with($distributor->getKey())
            ->once()
            ->andReturn($distributor);

        $factory = new WithDistributorTestObject($resolver, $finder);
        $object  = $objectFactory($this, $distributor);

        self::assertEquals($distributor, $factory->distributor($object));
    }

    /**
     * @covers ::distributor
     *
     * @dataProvider dataProviderDistributor
     */
    public function testDistributorDistributorNotFound(Closure $objectFactory): void {
        $collector   = Mockery::mock(Collector::class);
        $distributor = Distributor::factory()->make();
        $finder      = Mockery::mock(DistributorFinder::class);
        $finder
            ->shouldReceive('find')
            ->with($distributor->getKey())
            ->once()
            ->andReturn(null);
        $resolver = Mockery::mock(DistributorResolver::class, [$collector]);
        $resolver->shouldAllowMockingProtectedMethods();
        $resolver->makePartial();
        $resolver
            ->shouldReceive('find')
            ->once()
            ->andReturn(null);

        $factory = new WithDistributorTestObject($resolver, $finder);
        $object  = $objectFactory($this, $distributor);

        self::expectException(DistributorNotFound::class);

        self::assertEquals($distributor, $factory->distributor($object));
    }
    //</editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderDistributor(): array {
        return [
            ViewDocument::class => [
                static function (TestCase $test, Distributor $distributor) {
                    return new ViewDocument([
                        'distributorId' => $distributor->getKey(),
                    ]);
                },
            ],
            Document::class     => [
                static function (TestCase $test, Distributor $distributor) {
                    return new Document([
                        'distributorId' => $distributor->getKey(),
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
class WithDistributorTestObject extends Factory {
    use WithDistributor {
        distributor as public;
    }

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(
        protected DistributorResolver $distributorResolver,
        protected ?DistributorFinder $distributorFinder = null,
    ) {
        // empty
    }

    protected function getDistributorResolver(): DistributorResolver {
        return $this->distributorResolver;
    }

    protected function getDistributorFinder(): ?DistributorFinder {
        return $this->distributorFinder;
    }

    public function create(Type $type): ?Model {
        return null;
    }

    public function getModel(): string {
        return Model::class;
    }
}
