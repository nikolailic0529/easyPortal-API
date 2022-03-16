<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Customer;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Commands\UpdateCustomer;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Jobs\CustomerSync
 */
class CustomerSyncTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     */
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

        $this->override(Kernel::class, static function (MockInterface $mock) use ($customer): void {
            $mock
                ->shouldReceive('call')
                ->with(UpdateCustomer::class, [
                    '--no-interaction'   => true,
                    'id'                 => $customer->getKey(),
                    '--assets'           => true,
                    '--assets-documents' => true,
                ])
                ->once()
                ->andReturn(Command::SUCCESS);
        });

        $job      = $this->app->make(CustomerSync::class)->init($customer);
        $actual   = $this->app->call($job);
        $expected = [
            'result'   => true,
            'warranty' => true,
        ];

        self::assertEquals($expected, $actual);
    }

    /**
     * @covers ::__invoke
     */
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

        $this->override(Kernel::class, static function (MockInterface $mock) use ($exception): void {
            $mock
                ->shouldReceive('call')
                ->with(UpdateCustomer::class, Mockery::any())
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

    /**
     * @covers ::__invoke
     */
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

        $this->override(Kernel::class, static function (MockInterface $mock) use ($customer): void {
            $mock
                ->shouldReceive('call')
                ->with(UpdateCustomer::class, [
                    '--no-interaction'   => true,
                    'id'                 => $customer->getKey(),
                    '--no-assets'        => true,
                    '--assets-documents' => true,
                ])
                ->once()
                ->andReturn(Command::SUCCESS);
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
