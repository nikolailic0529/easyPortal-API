<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Currency;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\CurrencyResolver;
use App\Services\DataLoader\Schema\DocumentEntry;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Schema\ViewDocument;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\CurrencyFactory
 */
class CurrencyFactoryTest extends TestCase {
    use WithQueryLog;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::find
     */
    public function testFind(): void {
        $factory = $this->app->make(CurrencyFactory::class);
        $entry   = new DocumentEntry([
            'currencyCode' => $this->faker->currencyCode,
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
        $factory = Mockery::mock(CurrencyFactory::class);
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
                'currencyCode' => $this->faker->currencyCode,
                'document'     => [
                    'currencyCode' => $this->faker->currencyCode,
                ],
            ],
        ]);

        $factory = Mockery::mock(CurrencyFactory::class);
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
        $code     = $this->faker->currencyCode;
        $document = new ViewAssetDocument([
            'currencyCode' => $code,
        ]);

        $factory = Mockery::mock(CurrencyFactory::class);
        $factory->makePartial();
        $factory->shouldAllowMockingProtectedMethods();
        $factory->shouldReceive('currency')
            ->once()
            ->with($code)
            ->andReturn(null);

        $factory->create($document);
    }

    /**
     * @covers ::createFromDocument
     */
    public function testCreateFromDocument(): void {
        $code     = $this->faker->currencyCode;
        $document = new ViewDocument([
            'currencyCode' => $code,
        ]);

        $factory = Mockery::mock(CurrencyFactory::class);
        $factory->makePartial();
        $factory->shouldAllowMockingProtectedMethods();
        $factory->shouldReceive('currency')
            ->once()
            ->with($code)
            ->andReturn(null);

        $factory->create($document);
    }

    /**
     * @covers ::createFromDocumentEntry
     */
    public function testCreateFromDocumentEntry(): void {
        $code     = $this->faker->currencyCode;
        $document = new DocumentEntry([
            'currencyCode' => $code,
        ]);

        $factory = Mockery::mock(CurrencyFactory::class);
        $factory->makePartial();
        $factory->shouldAllowMockingProtectedMethods();
        $factory->shouldReceive('currency')
            ->once()
            ->with($code)
            ->andReturn(null);

        $factory->create($document);
    }

    /**
     * @covers ::currency
     */
    public function testCurrency(): void {
        // Prepare
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = $this->app->make(CurrencyResolver::class);
        $currency   = Currency::factory()->create();

        $factory = new class($normalizer, $resolver) extends CurrencyFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected CurrencyResolver $currencies,
            ) {
                // empty
            }

            public function currency(?string $code): ?Currency {
                return parent::currency($code);
            }
        };

        $this->flushQueryLog();

        // If model exists - no action required
        $this->assertEquals($currency, $factory->currency($currency->code));
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // If not - it should be created
        $created = $factory->currency(' CD ');

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
            DocumentEntry::class       => ['createFromDocumentEntry', new DocumentEntry()],
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
