<?php declare(strict_types = 1);

namespace App\Providers;

use App\Utils\Eloquent\Events\Subject;
use Illuminate\Contracts\Events\Dispatcher;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Providers\EventServiceProvider
 */
class EventServiceProviderTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testDispatcher(): void {
        self::assertSame(
            $this->app->make(Dispatcher::class),
            $this->app->make(Dispatcher::class),
        );
    }

    /**
     * @covers ::register
     */
    public function testRegisterEloquentSubject(): void {
        self::assertSame(
            $this->app->make(Subject::class),
            $this->app->make(Subject::class),
        );
    }
}
