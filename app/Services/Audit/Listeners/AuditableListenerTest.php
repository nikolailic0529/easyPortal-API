<?php declare(strict_types = 1);

namespace App\Services\Audit\Listeners;

use App\Services\Audit\Auditor;
use App\Services\Audit\Contracts\Auditable;
use App\Services\Audit\Enums\Action;
use App\Services\Logger\Listeners\EloquentObject;
use App\Services\Organization\CurrentOrganization;
use App\Utils\Eloquent\Model;
use Closure;
use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Date;
use LogicException;
use Mockery;
use Mockery\MockInterface;
use stdClass;
use Tests\TestCase;
use Tests\WithOrganization;

/**
 * @internal
 * @coversDefaultClass \App\Services\Audit\Listeners\AuditableListener
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 */
class AuditableListenerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::subscribe
     */
    public function testSubscribe(): void {
        $this->override(
            AuditableListener::class,
            static function (MockInterface $mock): void {
                $mock
                    ->shouldReceive('__invoke')
                    ->with('eloquent.created: Model', [])
                    ->once()
                    ->andReturns();
                $mock
                    ->shouldReceive('__invoke')
                    ->with('eloquent.updated: Model', [])
                    ->once()
                    ->andReturns();
                $mock
                    ->shouldReceive('__invoke')
                    ->with('eloquent.deleted: Model', [])
                    ->once()
                    ->andReturns();
                $mock
                    ->shouldReceive('__invoke')
                    ->with('eloquent.restored: Model', [])
                    ->once()
                    ->andReturns();
                $mock
                    ->shouldReceive('__invoke')
                    ->never();
            },
        );

        $dispatcher = $this->app->make(Dispatcher::class);

        $dispatcher->dispatch('eloquent.created: Model');
        $dispatcher->dispatch('eloquent.updated: Model');
        $dispatcher->dispatch('eloquent.deleted: Model');
        $dispatcher->dispatch('eloquent.restored: Model');
    }

    /**
     * @covers ::getModelAction
     *
     * @dataProvider dataProviderGetModelAction
     */
    public function testGetModelAction(Action|Exception $expected, string $event): void {
        $listener = Mockery::mock(AuditableListener::class);
        $listener->shouldAllowMockingProtectedMethods();
        $listener->makePartial();

        if ($expected instanceof Exception) {
            self::expectExceptionObject($expected);
        }

        self::assertEquals($expected, $listener->getModelAction($event));
    }

    /**
     * @covers ::isModelChanged
     *
     * @dataProvider dataProviderIsModelChanged
     *
     * @param Closure(static): Model $modelFactory
     */
    public function testIsModelChanged(bool $expected, Closure $modelFactory): void {
        $model    = $modelFactory($this);
        $listener = Mockery::mock(AuditableListener::class);
        $listener->shouldAllowMockingProtectedMethods();
        $listener->makePartial();

        self::assertEquals($expected, $listener->isModelChanged($model));
    }

    /**
     * @covers ::getModelContext
     *
     * @dataProvider dataProviderGetModelContext
     */
    public function testGetModelContext(string $expected, Action $action): void {
        $listener = Mockery::mock(AuditableListener::class);
        $listener->shouldAllowMockingProtectedMethods();
        $listener->makePartial();

        $object = Mockery::mock(EloquentObject::class);
        $object
            ->shouldReceive($expected)
            ->once()
            ->andReturn([
                // empty
            ]);

        self::assertEquals(
            [
                'properties' => [],
            ],
            $listener->getModelContext($action, $object),
        );
    }

    /**
     * @covers ::__invoke
     */
    public function testInvoke(): void {
        $org     = Mockery::mock(CurrentOrganization::class);
        $model   = new class() extends Model implements Auditable {
            /**
             * @inheritdoc
             */
            public function getDirtyRelations(): array {
                return [];
            }
        };
        $context = ['context' => ''];
        $auditor = Mockery::mock(Auditor::class);
        $auditor
            ->shouldReceive('create')
            ->with(
                $org,
                Action::modelCreated(),
                $model,
                $context,
            )
            ->once()
            ->andReturns();

        $listener = Mockery::mock(AuditableListener::class, [$org, $auditor]);
        $listener->shouldAllowMockingProtectedMethods();
        $listener->makePartial();
        $listener
            ->shouldReceive('getModelContext')
            ->once()
            ->andReturn($context);

        $listener('eloquent.created', [$model]);
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeNoModel(): void {
        $org     = Mockery::mock(CurrentOrganization::class);
        $auditor = Mockery::mock(Auditor::class);
        $auditor
            ->shouldReceive('create')
            ->never();

        $listener = new AuditableListener($org, $auditor);

        $listener('test', [new stdClass()]);
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeUpdateNoChanges(): void {
        $org     = Mockery::mock(CurrentOrganization::class);
        $model   = new class() extends Model implements Auditable {
            /**
             * @inheritdoc
             */
            public function getDirtyRelations(): array {
                return [];
            }
        };
        $auditor = Mockery::mock(Auditor::class);
        $auditor
            ->shouldReceive('create')
            ->never();

        $listener = Mockery::mock(AuditableListener::class, [$org, $auditor]);
        $listener->shouldAllowMockingProtectedMethods();
        $listener->makePartial();
        $listener
            ->shouldReceive('isModelChanged')
            ->once()
            ->andReturn(false);

        $listener('eloquent.updated', [$model]);
    }
    //</editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array<mixed>>
     */
    public function dataProviderGetModelAction(): array {
        return [
            'eloquent.created'  => [Action::modelCreated(), 'eloquent.created'],
            'eloquent.updated'  => [Action::modelUpdated(), 'eloquent.updated'],
            'eloquent.deleted'  => [Action::modelDeleted(), 'eloquent.deleted'],
            'eloquent.restored' => [Action::modelRestored(), 'eloquent.restored'],
            'another'           => [new LogicException('Event `eloquent.another` is unknown.'), 'eloquent.another'],
        ];
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function dataProviderIsModelChanged(): array {
        return [
            'wasRecentlyCreated'    => [
                true,
                static function (): Model {
                    $model = new class() extends Model {
                        // empty
                    };

                    $model->wasRecentlyCreated = true;
                    $model->exists             = true;

                    return $model;
                },
            ],
            'not exists'            => [
                true,
                static function (): Model {
                    $model = new class() extends Model {
                        // empty
                    };

                    $model->wasRecentlyCreated = false;
                    $model->exists             = false;

                    return $model;
                },
            ],
            'changes'               => [
                true,
                static function (): Model {
                    $model = new class() extends Model {
                        // empty
                    };

                    $model->setAttribute('attribute', Date::now());

                    return $model;
                },
            ],
            'no changes'            => [
                false,
                static function (): Model {
                    $model = new class() extends Model {
                        // empty
                    };

                    $model->wasRecentlyCreated = false;
                    $model->exists             = true;

                    return $model;
                },
            ],
            'no meaningful changes' => [
                false,
                static function (): Model {
                    $model = new class() extends Model {
                        // empty
                    };

                    $model->wasRecentlyCreated = false;
                    $model->exists             = true;

                    $model->setAttribute('updated_at', Date::now());
                    $model->setAttribute('synced_at', Date::now());

                    return $model;
                },
            ],
        ];
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function dataProviderGetModelContext(): array {
        return [
            'eloquent.created'  => ['getProperties', Action::modelCreated()],
            'eloquent.updated'  => ['getChanges', Action::modelUpdated()],
            'eloquent.deleted'  => ['getChanges', Action::modelDeleted()],
            'eloquent.restored' => ['getChanges', Action::modelRestored()],
        ];
    }
    //</editor-fold>
}
