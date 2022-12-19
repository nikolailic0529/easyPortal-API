<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Data\Language;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\LanguageResolver;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
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

            public function getModel(): string {
                return Model::class;
            }

            public function create(Type $type): ?Model {
                return null;
            }
        };

        // If model exists - no action required
        $queries = $this->getQueryLog()->flush();

        self::assertEquals($language, $factory->language($language->code));
        self::assertCount(1, $queries);

        // If not - it should be created
        $queries = $this->getQueryLog()->flush();
        $created = $factory->language('nw ');

        self::assertNotNull($created);
        self::assertTrue($created->wasRecentlyCreated);
        self::assertEquals('nw', $created->code);
        self::assertEquals('nw', $created->name);
        self::assertCount(2, $queries);

        // If null - null should be returned
        self::assertNull($factory->language(null));
    }
}
