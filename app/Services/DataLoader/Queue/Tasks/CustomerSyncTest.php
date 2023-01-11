<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Queue\Tasks;

use App\Models\Customer;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Processors\Loader\Loaders\CustomerLoader;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\DataLoader\Queue\Tasks\CustomerSync
 */
class CustomerSyncTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    public function testInvoke(): void {
        $customer = Customer::factory()->make();

        $this->override(ExceptionHandler::class);

        $this->override(Client::class, static function (MockInterface $mock) use ($customer): void {
            $mock
                ->shouldReceive('runCustomerWarrantyCheck')
                ->with($customer->getKey())
                ->once()
                ->andReturn(true);
        });

        $this->override(CustomerLoader::class, static function (MockInterface $mock) use ($customer): void {
            $mock
                ->shouldReceive('setObjectId')
                ->with($customer->getKey())
                ->once()
                ->andReturnSelf();
            $mock
                ->shouldReceive('setWithDocuments')
                ->with(true)
                ->once()
                ->andReturnSelf();
            $mock
                ->shouldReceive('setWithAssets')
                ->with(true)
                ->once()
                ->andReturnSelf();
            $mock
                ->shouldReceive('setWithWarrantyCheck')
                ->with(false)
                ->once()
                ->andReturnSelf();
            $mock
                ->shouldReceive('start')
                ->once()
                ->andReturn(true);
        });

        $job      = $this->app->make(CustomerSync::class)->init($customer);
        $actual   = $this->app->call($job);
        $expected = [
            'result'   => true,
            'warranty' => true,
        ];

        self::assertEquals($expected, $actual);
    }

    public function testInvokeFailed(): void {
        $customer  = Customer::factory()->make();
        $exception = new Exception();

        $this->override(ExceptionHandler::class, static function (MockInterface $mock) use ($exception): void {
            $mock
                ->shouldReceive('report')
                ->with($exception)
                ->twice()
                ->andReturns();
        });

        $this->override(Client::class, static function (MockInterface $mock) use ($customer, $exception): void {
            $mock
                ->shouldReceive('runCustomerWarrantyCheck')
                ->with($customer->getKey())
                ->once()
                ->andThrow($exception);
        });

        $this->override(CustomerLoader::class, static function (MockInterface $mock) use ($exception): void {
            $mock
                ->shouldReceive('setObjectId')
                ->once()
                ->andThrow($exception);
        });

        $job      = $this->app->make(CustomerSync::class)->init($customer);
        $actual   = $this->app->call($job);
        $expected = [
            'result'   => false,
            'warranty' => false,
        ];

        self::assertEquals($expected, $actual);
    }

    public function testInvokeWarrantyFailed(): void {
        $customer = Customer::factory()->make();

        $this->override(ExceptionHandler::class);

        $this->override(Client::class, static function (MockInterface $mock) use ($customer): void {
            $mock
                ->shouldReceive('runCustomerWarrantyCheck')
                ->with($customer->getKey())
                ->once()
                ->andReturn(false);
        });

        $this->override(CustomerLoader::class, static function (MockInterface $mock) use ($customer): void {
            $mock
                ->shouldReceive('setObjectId')
                ->with($customer->getKey())
                ->once()
                ->andReturnSelf();
            $mock
                ->shouldReceive('setWithDocuments')
                ->with(true)
                ->once()
                ->andReturnSelf();
            $mock
                ->shouldReceive('setWithAssets')
                ->with(false)
                ->once()
                ->andReturnSelf();
            $mock
                ->shouldReceive('setWithWarrantyCheck')
                ->with(false)
                ->once()
                ->andReturnSelf();
            $mock
                ->shouldReceive('start')
                ->once()
                ->andReturn(true);
        });

        $job      = $this->app->make(CustomerSync::class)->init($customer);
        $actual   = $this->app->call($job);
        $expected = [
            'result'   => true,
            'warranty' => false,
        ];

        self::assertEquals($expected, $actual);
    }
    // </editor-fold>
}
