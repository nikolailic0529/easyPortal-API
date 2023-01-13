<?php declare(strict_types = 1);

namespace App\Services\Search\Listeners;

use App\Exceptions\ErrorReport;
use App\Services\Search\Elastic\ClientBuilder;
use App\Services\Search\Exceptions\ElasticUnavailable;
use Closure;
use Elastic\Client\ClientBuilderInterface;
use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Search\Listeners\ElasticDisconnected
 */
class ElasticDisconnectedTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderSubscribe
     *
     * @param Closure(self): object $eventFactory
     */
    public function testSubscribe(Closure $eventFactory): void {
        $this->override(ElasticDisconnected::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('__invoke')
                ->once()
                ->andReturns();
        });

        $this->app->make(Dispatcher::class)
            ->dispatch($eventFactory($this));
    }

    public function testInvokeNoNodesAvailableException(): void {
        $this->override(ClientBuilderInterface::class, static function (): MockInterface {
            $mock = Mockery::mock(ClientBuilder::class);
            $mock
                ->shouldReceive('reset')
                ->once()
                ->andReturns();

            return $mock;
        });

        $event    = new ErrorReport(new ElasticUnavailable(new Exception()));
        $listener = $this->app->make(ElasticDisconnected::class);

        $listener($event);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{Closure(self): object}>
     */
    public function dataProviderSubscribe(): array {
        return [
            ErrorReport::class => [
                static function (): object {
                    return new ErrorReport(new Exception('test'));
                },
            ],
        ];
    }
    // </editor-fold>
}
