<?php declare(strict_types = 1);

namespace App\Services\Audit\Listeners;

use App\Models\Organization;
use App\Services\Audit\Auditor;
use App\Services\Audit\Enums\Action;
use App\Services\Organization\Events\OrganizationChanged;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Audit\Listeners\OrganizationListener
 */
class OrganizationListenerTest extends TestCase {
    public function testSubscribe(): void {
        $event = new OrganizationChanged(null, null);

        $this->override(
            OrganizationListener::class,
            static function (MockInterface $mock) use ($event): void {
                $mock
                    ->shouldReceive('__invoke')
                    ->with($event)
                    ->once()
                    ->andReturns();
                $mock
                    ->shouldReceive('__invoke')
                    ->never();
            },
        );

        $dispatcher = $this->app->make(Dispatcher::class);

        $dispatcher->dispatch($event);
    }

    public function testInvoke(): void {
        $previous = Organization::factory()->make();
        $current  = Organization::factory()->make();
        $event    = new OrganizationChanged($previous, $current);

        $this->override(Auditor::class, static function (MockInterface $mock) use ($event): void {
            $mock
                ->shouldReceive('create')
                ->with(
                    $event->getPrevious(),
                    Action::orgChanged(),
                )
                ->once()
                ->andReturns();
            $mock
                ->shouldReceive('create')
                ->with(
                    $event->getCurrent(),
                    Action::orgChanged(),
                    $event->getCurrent(),
                )
                ->once()
                ->andReturns();
        });

        $listener = $this->app->make(OrganizationListener::class);

        $listener($event);
    }

    public function testInvokeNoPrevious(): void {
        $current = Organization::factory()->make();
        $event   = new OrganizationChanged(null, $current);

        $this->override(Auditor::class, static function (MockInterface $mock) use ($event): void {
            $mock
                ->shouldReceive('create')
                ->with(
                    $event->getCurrent(),
                    Action::orgChanged(),
                    $event->getCurrent(),
                )
                ->once()
                ->andReturns();
        });

        $listener = $this->app->make(OrganizationListener::class);

        $listener($event);
    }

    public function testInvokeNoCurrent(): void {
        $previous = Organization::factory()->make();
        $event    = new OrganizationChanged($previous, null);

        $this->override(Auditor::class, static function (MockInterface $mock) use ($event): void {
            $mock
                ->shouldReceive('create')
                ->with(
                    $event->getPrevious(),
                    Action::orgChanged(),
                )
                ->once()
                ->andReturns();
        });

        $listener = $this->app->make(OrganizationListener::class);

        $listener($event);
    }
}
