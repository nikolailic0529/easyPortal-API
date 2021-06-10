<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Language;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\LanguageResolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Schema\ViewDocument;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\LanguageFactory
 */
class LanguageFactoryTest extends TestCase {
    use WithQueryLog;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::find
     */
    public function testFind(): void {
        $factory = $this->app->make(LanguageFactory::class);
        $entry   = new ViewDocument([
            'languageCode' => $this->faker->languageCode,
        ]);

        $this->flushQueryLog();

        $factory->find($entry);

        $this->assertCount(1, $this->getQueryLog());
    }

    /**
     * @covers ::create
     *
     * @dataProvider dataProviderCreate
     */
    public function testCreate(?string $expected, Type $type): void {
        $factory = Mockery::mock(LanguageFactory::class);
        $factory->makePartial();
        $factory->shouldAllowMockingProtectedMethods();

        if ($expected) {
            $factory->shouldReceive($expected)
                ->once()
                ->with($type)
                ->andReturns();
        } else {
            $this->expectException(InvalidArgumentException::class);
            $this->expectErrorMessageMatches('/^The `\$type` must be instance of/');
        }

        $factory->create($type);
    }

    /**
     * @covers ::createFromAssetDocumentObject
     */
    public function testCreateFromAssetDocumentObject(): void {
        $document = new AssetDocumentObject([
            'document' => [
                'languageCode' => $this->faker->languageCode,
                'document'     => [
                    'languageCode' => $this->faker->languageCode,
                ],
            ],
        ]);

        $factory = Mockery::mock(LanguageFactory::class);
        $factory->makePartial();
        $factory->shouldAllowMockingProtectedMethods();
        $factory
            ->shouldReceive('createFromDocument')
            ->once()
            ->with($document->document->document)
            ->andReturn(null);
        $factory
            ->shouldReceive('createFromAssetDocument')
            ->once()
            ->with($document->document)
            ->andReturn(null);

        $factory->create($document);
    }

    /**
     * @covers ::createFromAssetDocument
     */
    public function testCreateFromAssetDocument(): void {
        $code     = $this->faker->languageCode;
        $document = new ViewAssetDocument([
            'languageCode' => $code,
        ]);

        $factory = Mockery::mock(LanguageFactory::class);
        $factory->makePartial();
        $factory->shouldAllowMockingProtectedMethods();
        $factory
            ->shouldReceive('language')
            ->once()
            ->with($code)
            ->andReturn(null);

        $factory->create($document);
    }

    /**
     * @covers ::createFromDocument
     */
    public function testCreateFromDocument(): void {
        $code     = $this->faker->languageCode;
        $document = new ViewDocument([
            'languageCode' => $code,
        ]);

        $factory = Mockery::mock(LanguageFactory::class);
        $factory->makePartial();
        $factory->shouldAllowMockingProtectedMethods();
        $factory->shouldReceive('language')
            ->once()
            ->with($code)
            ->andReturn(null);

        $factory->create($document);
    }

    /**
     * @covers ::language
     */
    public function testLanguage(): void {
        // Prepare
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = $this->app->make(LanguageResolver::class);
        $language   = Language::factory()->create();

        $factory = new class($normalizer, $resolver) extends LanguageFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected LanguageResolver $languages,
            ) {
                // empty
            }

            public function language(?string $code): ?Language {
                return parent::language($code);
            }
        };

        $this->flushQueryLog();

        // If model exists - no action required
        $this->assertEquals($language, $factory->language($language->code));
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // If not - it should be created
        $created = $factory->language(' CD ');

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals('CD', $created->code);
        $this->assertEquals('CD', $created->name);
        $this->assertCount(1, $this->getQueryLog());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderCreate(): array {
        return [
            AssetDocumentObject::class => ['createFromAssetDocumentObject', new AssetDocumentObject()],
            ViewAssetDocument::class   => ['createFromAssetDocument', new ViewAssetDocument()],
            ViewDocument::class        => ['createFromDocument', new ViewDocument()],
            'Unknown'                  => [
                null,
                new class() extends Type {
                    // empty
                },
            ],
        ];
    }
    // </editor-fold>
}
