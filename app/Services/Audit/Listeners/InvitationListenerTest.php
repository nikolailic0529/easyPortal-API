<?php declare(strict_types = 1);

namespace App\Services\Audit\Listeners;

use App\GraphQL\Events\InvitationAccepted;
use App\GraphQL\Events\InvitationCreated;
use App\GraphQL\Events\InvitationExpired;
use App\GraphQL\Events\InvitationOutdated;
use App\GraphQL\Events\InvitationUsed;
use App\Models\Invitation;
use App\Services\Audit\Auditor;
use App\Services\Audit\Enums\Action;
use Illuminate\Contracts\Events\Dispatcher;
use LogicException;
use Mockery;
use Mockery\MockInterface;
use stdClass;
use Tests\TestCase;
use Tests\WithOrganization;

/**
 * @internal
 * @covers \App\Services\Audit\Listeners\InvitationListener
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 */
class InvitationListenerTest extends TestCase {
    public function testSubscribe(): void {
        $invitation = Invitation::factory()->make();
        $created    = new InvitationCreated($invitation);
        $accepted   = new InvitationAccepted($invitation);
        $outdated   = new InvitationOutdated($invitation);
        $expired    = new InvitationExpired($invitation);
        $used       = new InvitationUsed($invitation);
        $listener   = Mockery::mock(InvitationListener::class);
        $listener
            ->shouldReceive('__invoke')
            ->with($created)
            ->once()
            ->andReturns();
        $listener
            ->shouldReceive('__invoke')
            ->with($accepted)
            ->once()
            ->andReturns();
        $listener
            ->shouldReceive('__invoke')
            ->with($outdated)
            ->once()
            ->andReturns();
        $listener
            ->shouldReceive('__invoke')
            ->with($expired)
            ->once()
            ->andReturns();
        $listener
            ->shouldReceive('__invoke')
            ->with($used)
            ->once()
            ->andReturns();
        $listener
            ->shouldReceive('__invoke')
            ->never();

        $this->override(
            InvitationListener::class,
            static function () use ($listener): MockInterface {
                return $listener;
            },
        );

        $dispatcher = $this->app->make(Dispatcher::class);

        $dispatcher->dispatch($created);
        $dispatcher->dispatch($accepted);
        $dispatcher->dispatch($outdated);
        $dispatcher->dispatch($expired);
        $dispatcher->dispatch($used);
    }

    public function testInvokeUnsupported(): void {
        self::expectException(LogicException::class);

        $listener = $this->app->make(InvitationListener::class);

        $listener(new stdClass());
    }

    public function testInvokeInvitationCreatedEvent(): void {
        $invitation = Invitation::factory()->make();
        $event      = new InvitationCreated($invitation);

        $this->override(Auditor::class, static function (MockInterface $mock) use ($invitation): void {
            $mock
                ->shouldReceive('create')
                ->with(
                    $invitation->organization_id,
                    Action::invitationCreated(),
                    $invitation,
                )
                ->once()
                ->andReturns();
        });

        $listener = $this->app->make(InvitationListener::class);

        $listener($event);
    }

    public function testInvokeInvitationAcceptedEvent(): void {
        $invitation = Invitation::factory()->make();
        $event      = new InvitationAccepted($invitation);

        $this->override(Auditor::class, static function (MockInterface $mock) use ($invitation): void {
            $mock
                ->shouldReceive('create')
                ->with(
                    $invitation->organization_id,
                    Action::invitationAccepted(),
                    $invitation,
                )
                ->once()
                ->andReturns();
        });

        $listener = $this->app->make(InvitationListener::class);

        $listener($event);
    }

    public function testInvokeInvitationOutdatedEvent(): void {
        $invitation = Invitation::factory()->make();
        $event      = new InvitationOutdated($invitation);

        $this->override(Auditor::class, static function (MockInterface $mock) use ($invitation): void {
            $mock
                ->shouldReceive('create')
                ->with(
                    $invitation->organization_id,
                    Action::invitationOutdated(),
                    $invitation,
                )
                ->once()
                ->andReturns();
        });

        $listener = $this->app->make(InvitationListener::class);

        $listener($event);
    }

    public function testInvokeInvitationExpiredEvent(): void {
        $invitation = Invitation::factory()->make();
        $event      = new InvitationExpired($invitation);

        $this->override(Auditor::class, static function (MockInterface $mock) use ($invitation): void {
            $mock
                ->shouldReceive('create')
                ->with(
                    $invitation->organization_id,
                    Action::invitationExpired(),
                    $invitation,
                )
                ->once()
                ->andReturns();
        });

        $listener = $this->app->make(InvitationListener::class);

        $listener($event);
    }

    public function testInvokeInvitationUsedEvent(): void {
        $invitation = Invitation::factory()->make();
        $event      = new InvitationUsed($invitation);

        $this->override(Auditor::class, static function (MockInterface $mock) use ($invitation): void {
            $mock
                ->shouldReceive('create')
                ->with(
                    $invitation->organization_id,
                    Action::invitationUsed(),
                    $invitation,
                )
                ->once()
                ->andReturns();
        });

        $listener = $this->app->make(InvitationListener::class);

        $listener($event);
    }
}
