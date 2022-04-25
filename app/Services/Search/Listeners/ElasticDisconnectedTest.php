<?php declare(strict_types = 1);

namespace App\Services\Search\Listeners;

use App\Exceptions\ErrorReport;
use App\Services\Search\Exceptions\ElasticUnavailable;
use Closure;
use Elasticsearch\Client;
use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Listeners\ElasticDisconnected
 */
class ElasticDisconnectedTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::subscribe
     *
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

    /**
     * @covers ::__invoke
     */
    public function testInvokeNoNodesAvailableException(): void {
        $this->override(Application::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('forgetInstance')
                ->once()
                ->with(Client::class);
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
