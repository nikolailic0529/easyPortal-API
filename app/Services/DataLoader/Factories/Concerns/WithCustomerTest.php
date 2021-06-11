<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Customer;
use App\Services\DataLoader\Exceptions\CustomerNotFoundException;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Schema\ViewDocument;
use Closure;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\Concerns\WithCustomer
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
            ->with($customer->getKey())
            ->once()
            ->andReturn($customer);

        $factory = new WithCustomerTestObject($resolver);
        $object  = $objectFactory($this, $customer);

        $this->assertEquals($customer, $factory->customer($object));
    }

    /**
     * @covers ::customer
     *
     * @dataProvider dataProviderCustomer
     */
    public function testCustomerCustomerNotFound(Closure $objectFactory): void {
        $customer = Customer::factory()->make();
        $resolver = Mockery::mock(CustomerResolver::class);
        $resolver
            ->shouldReceive('get')
            ->with($customer->getKey())
            ->once()
            ->andReturn(null);

        $factory = new WithCustomerTestObject($resolver);
        $object  = $objectFactory($this, $customer);

        $this->expectException(CustomerNotFoundException::class);

        $this->assertEquals($customer, $factory->customer($object));
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

        $this->assertNull($factory->customer($object));
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
class WithCustomerTestObject {
    use WithCustomer {
        customer as public;
    }

    public function __construct(
        protected CustomerResolver $resolver,
    ) {
        // empty
    }

    protected function getCustomerResolver(): CustomerResolver {
        return $this->resolver;
    }
}
