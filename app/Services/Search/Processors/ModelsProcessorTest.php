<?php declare(strict_types = 1);

namespace App\Services\Search\Processors;

use App\Models\Customer;
use Illuminate\Support\Facades\Event;
use Laravel\Scout\Events\ModelsImported;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @covers \App\Services\Search\Processors\ModelsProcessor
 */
class ModelsProcessorTest extends TestCase {
    use WithQueryLogs;

    public function testProcess(): void {
        // Setup
        $this->overrideDateFactory('2022-02-02T00:00:00.000+00:00');
        $this->overrideUuidFactory('0b63533a-713b-4a6b-b49c-849612feb478');
        $this->setSettings([
            'ep.headquarter_type' => null,
        ]);

        // Generate
        Customer::factory()->create([
            'id' => '2482aaa9-b619-4fc2-8d25-e6d9dd7e6be3',
        ]);

        // Pretest
        self::assertModelsCount([
            Customer::class => 1,
        ]);

        // Test
        $events  = Event::fake(ModelsImported::class);
        $queries = $this->getQueryLog();

        $this->app->make(ModelsProcessor::class)
            ->setModels([Customer::class])
            ->start();

        self::assertQueryLogEquals('~process-queries.json', $queries);
        self::assertDispatchedEventsEquals(
            '~process-events.json',
            $events->dispatched(ModelsImported::class),
        );

        $queries->flush();

        unset($events);
    }
}
