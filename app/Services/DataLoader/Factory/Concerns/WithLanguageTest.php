<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Language;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\LanguageResolver;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Concerns\WithLanguage
 */
class WithLanguageTest extends TestCase {
    use WithQueryLog;

    /**
     * @covers ::language
     */
    public function testLanguage(): void {
        // Prepare
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = $this->app->make(LanguageResolver::class);
        $language   = Language::factory()->create();
        $factory    = new class($normalizer, $resolver) extends ModelFactory {
            use WithLanguage {
                language as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected LanguageResolver $languageResolver,
            ) {
                // empty
            }

            protected function getNormalizer(): Normalizer {
                return $this->normalizer;
            }

            protected function getLanguageResolver(): LanguageResolver {
                return $this->languageResolver;
            }

            public function create(Type $type): ?Model {
                return null;
            }
        };

        $this->flushQueryLog();

        // If model exists - no action required
        $this->assertEquals($language, $factory->language($language->code));
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // If not - it should be created
        $created = $factory->language('nw ');

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals('nw', $created->code);
        $this->assertEquals('nw', $created->name);
        $this->assertCount(2, $this->getQueryLog());

        // If null - null should be returned
        $this->assertNull($factory->language(null));
    }
}
