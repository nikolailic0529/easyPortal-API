<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Model;
use App\Models\Oem;
use App\Services\DataLoader\Factories\ModelFactory;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\OemResolver;
use App\Services\DataLoader\Schema\Type;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\Concerns\WithOem
 */
class WithOemTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::status
     */
    public function testOem(): void {
        // Prepare
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = $this->app->make(OemResolver::class);
        $oem        = Oem::factory()->create();

        $factory = new class($normalizer, $resolver) extends ModelFactory {
            use WithOem {
                oem as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(Normalizer $normalizer, protected OemResolver $oems) {
                $this->normalizer = $normalizer;
            }

            public function create(Type $type): ?Model {
                return null;
            }
        };

        $this->flushQueryLog();

        // If model exists - no action required
        $this->assertEquals($oem, $factory->oem($oem->abbr, $oem->name));
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // If not - it should be created
        $created = $factory->oem('newabbr', ' New  Oem    Name ');

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals('newabbr', $created->abbr);
        $this->assertEquals('New Oem Name', $created->name);
        $this->assertCount(1, $this->getQueryLog());
    }
}
