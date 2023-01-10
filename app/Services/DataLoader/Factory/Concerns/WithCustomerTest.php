<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Customer;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Collector\Collector;
use App\Services\DataLoader\Exceptions\CustomerNotFound;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Finders\CustomerFinder;
use App\Services\DataLoader\Resolver\Resolvers\CustomerResolver;
use App\Services\DataLoader\Schema\Type;
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
 * @coversDefaultClass \App\Services\DataLoader\Factory\Concerns\WithCustomer
 */
class WithCustomerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::customer
     *
     * @dataProvider dataProviderCustomer
     */
    public function testCustomerExistsThroughProvider(Closure $objectFactory): void {
        $customer = Customer::factory()->make();
        $resolver = Mockery::mock(CustomerResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($customer->getKey(), Mockery::any())
            ->once()
            ->andReturn($customer);

        $factory = new WithCustomerTestObject($resolver);
        $object  = $objectFactory($this, $customer);

        self::assertEquals($customer, $factory->customer($object));
    }

    /**
     * @covers ::customer
     *
     * @dataProvider dataProviderCustomer
     */
    public function testCustomerExistsThroughFinder(Closure $objectFactory): void {
        $collector = $this->app->make(Collector::class);
        $customer  = Customer::factory()->make();
        $resolver  = Mockery::mock(CustomerResolver::class, [$collector]);
        $resolver->shouldAllowMockingProtectedMethods();
        $resolver->makePartial();
        $resolver
            ->shouldReceive('find')
            ->withArgs(static function (Key $key) use ($customer): bool {
                return (string) $key === (string) (new Key([$customer->getKey()]));
            })
            ->once()
            ->andReturn(null);
        $finder = Mockery::mock(CustomerFinder::class);
        $finder
            ->shouldReceive('find')
            ->with($customer->getKey())
            ->once()
            ->andReturn($customer);

        $factory = new WithCustomerTestObject($resolver, $finder);
        $object  = $objectFactory($this, $customer);

        self::assertEquals($customer, $factory->customer($object));
    }

    /**
     * @covers ::customer
     *
     * @dataProvider dataProviderCustomer
     */
    public function testCustomerCustomerNotFound(Closure $objectFactory): void {
        $collector = Mockery::mock(Collector::class);
        $customer  = Customer::factory()->make();
        $finder    = Mockery::mock(CustomerFinder::class);
        $finder
            ->shouldReceive('find')
            ->with($customer->getKey())
            ->once()
            ->andReturn(null);
        $resolver = Mockery::mock(CustomerResolver::class, [$collector]);
        $resolver->shouldAllowMockingProtectedMethods();
        $resolver->makePartial();
        $resolver
            ->shouldReceive('find')
            ->once()
            ->andReturn(null);

        $factory = new WithCustomerTestObject($resolver, $finder);
        $object  = $objectFactory($this, $customer);

        self::expectException(CustomerNotFound::class);

        self::assertEquals($customer, $factory->customer($object));
    }

    /**
     * @covers ::customer
     */
    public function testCustomerAssetWithoutCustomer(): void {
        $object   = new ViewAsset();
        $resolver = Mockery::mock(CustomerResolver::class);
        $resolver
            ->shouldReceive('get')
            ->never();

        $factory = new WithCustomerTestObject($resolver);

        self::assertNull($factory->customer($object));
    }
    //</editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderCustomer(): array {
        return [
            ViewAssetDocument::class => [
                static function (TestCase $test, Customer $customer) {
                    return new ViewAssetDocument([
                        'customer' => [
                            'id' => $customer->getKey(),
                        ],
                    ]);
                },
            ],
            ViewDocument::class      => [
                static function (TestCase $test, Customer $customer) {
                    return new ViewDocument([
                        'customerId' => $customer->getKey(),
                    ]);
                },
            ],
            ViewAsset::class         => [
                static function (TestCase $test, Customer $customer) {
                    return new ViewAsset([
                        'customerId' => $customer->getKey(),
                    ]);
                },
            ],
            Document::class          => [
                static function (TestCase $test, Customer $customer) {
                    return new Document([
                        'customerId' => $customer->getKey(),
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
class WithCustomerTestObject extends Factory {
    use WithCustomer {
        customer as public;
    }

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(
        protected CustomerResolver $customerResolver,
        protected ?CustomerFinder $customerFinder = null,
    ) {
        // empty
    }

    protected function getCustomerResolver(): CustomerResolver {
        return $this->customerResolver;
    }

    protected function getCustomerFinder(): ?CustomerFinder {
        return $this->customerFinder;
    }

    public function create(Type $type): ?Model {
        return null;
    }

    public function getModel(): string {
        return Model::class;
    }
}
