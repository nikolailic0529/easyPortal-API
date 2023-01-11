<?php declare(strict_types = 1);

namespace App\Services\Audit\Listeners;

use App\Http\Controllers\Export\Events\QueryExported;
use App\Services\Audit\Auditor;
use App\Services\Audit\Enums\Action;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Audit\Listeners\ExportListener
 */
class ExportListenerTest extends TestCase {
    public function testSubscribe(): void {
        $exported = new QueryExported('test', [
            'root'    => 'data.assets',
            'query'   => 'query { assets { id } }',
            'columns' => [
                [
                    'name'  => 'Id',
                    'value' => 'id',
                ],
            ],
        ]);

        $this->override(
            ExportListener::class,
            static function (MockInterface $mock) use ($exported): void {
                $mock
                    ->shouldReceive('__invoke')
                    ->with($exported)
                    ->once()
                    ->andReturns();
                $mock
                    ->shouldReceive('__invoke')
                    ->never();
            },
        );

        $dispatcher = $this->app->make(Dispatcher::class);

        $dispatcher->dispatch($exported);
    }

    public function testInvoke(): void {
        $event = new QueryExported('test', [
            'root'    => 'data.assets',
            'query'   => 'query { assets { id } }',
            'columns' => [
                [
                    'name'  => 'Id',
                    'value' => 'id',
                ],
            ],
        ]);

        $this->override(Auditor::class, static function (MockInterface $mock) use ($event): void {
            $mock
                ->shouldReceive('create')
                ->with(
                    Mockery::type(CurrentOrganization::class),
                    Action::exported(),
                    null,
                    [
                        'type'  => $event->getType(),
                        'query' => $event->getQuery(),
                    ],
                )
                ->once()
                ->andReturns();
        });

        $listener = $this->app->make(ExportListener::class);

        $listener($event);
    }
}
