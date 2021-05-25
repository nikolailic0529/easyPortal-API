<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\DistributorResolver;
use App\Services\DataLoader\Schema\AssetDocument;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Testing\Helper;
use Closure;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\DistributorFactory
 */
class DistributorFactoryTest extends TestCase {
    use WithQueryLog;
    use Helper;

    // <editor-fold desc='Tests'>
    // =========================================================================
    /**
     * @covers ::find
     */
    public function testFind(): void {
        $factory = $this->app->make(DistributorFactory::class);
        $json    = $this->getTestData()->json('~distributor-full.json');
        $company = Company::create($json);

        $this->flushQueryLog();

        $factory->find($company);

        $this->assertCount(1, $this->getQueryLog());
    }

    /**
     * @covers ::create
     *
     * @dataProvider dataProviderCreate
     */
    public function testCreate(?string $expected, Type $type): void {
        $factory = Mockery::mock(DistributorFactory::class);
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
        $document = AssetDocumentObject::create([
            'document' => [
                'distributor' => [
                    'id' => $this->faker->uuid,
                ],
                'document'    => [
                    'distributor' => [
                        'id' => $this->faker->uuid,
                    ],
                ],
            ],
        ]);

        $factory = Mockery::mock(DistributorFactory::class);
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
        $document = AssetDocument::create([
            'distributor' => [
                'id' => $this->faker->uuid,
            ],
        ]);

        $factory = Mockery::mock(DistributorFactory::class);
        $factory->makePartial();
        $factory->shouldAllowMockingProtectedMethods();
        $factory
            ->shouldReceive('createFromCompany')
            ->once()
            ->with($document->distributor)
            ->andReturn(null);

        $factory->create($document);
    }

    /**
     * @covers ::createFromDocument
     */
    public function testCreateFromDocument(): void {
        $document = Document::create([
            'distributor' => [
                'id' => $this->faker->uuid,
            ],
        ]);

        $factory = Mockery::mock(DistributorFactory::class);
        $factory->makePartial();
        $factory->shouldAllowMockingProtectedMethods();
        $factory
            ->shouldReceive('createFromCompany')
            ->once()
            ->with($document->distributor)
            ->andReturn(null);

        $factory->create($document);
    }

    /**
     * @covers ::create
     * @covers ::createFromCompany
     */
    public function testCreateFromCompany(): void {
        // Prepare
        $factory = $this->app->make(DistributorFactory::class);

        // Test
        $json        = $this->getTestData()->json('~distributor-full.json');
        $company     = Company::create($json);
        $distributor = $factory->create($company);

        $this->assertNotNull($distributor);
        $this->assertTrue($distributor->wasRecentlyCreated);
        $this->assertEquals($company->id, $distributor->getKey());
        $this->assertEquals($company->name, $distributor->name);

        // Distributor should be updated
        $json    = $this->getTestData()->json('~distributor-changed.json');
        $company = Company::create($json);
        $updated = $factory->create($company);

        $this->assertNotNull($updated);
        $this->assertSame($distributor, $updated);
        $this->assertEquals($company->id, $updated->getKey());
        $this->assertEquals($company->name, $updated->name);
    }

    /**
     * @covers ::prefetch
     */
    public function testPrefetch(): void {
        $a          = Company::create([
            'id' => $this->faker->uuid,
        ]);
        $b          = Company::create([
            'id' => $this->faker->uuid,
        ]);
        $resolver   = $this->app->make(DistributorResolver::class);
        $normalizer = $this->app->make(Normalizer::class);

        $factory = new class($normalizer, $resolver) extends DistributorFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(Normalizer $normalizer, DistributorResolver $resolver) {
                $this->normalizer   = $normalizer;
                $this->distributors = $resolver;
            }
        };

        $callback = Mockery::spy(function (EloquentCollection $collection): void {
            $this->assertCount(0, $collection);
        });

        $factory->prefetch([$a, $b], false, Closure::fromCallable($callback));

        $callback->shouldHaveBeenCalled()->once();

        $this->flushQueryLog();

        $factory->find($a);
        $factory->find($b);

        $this->assertCount(0, $this->getQueryLog());
    }
    // </editor-fold>

    // <editor-fold desc='DataProviders'>
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderCreate(): array {
        return [
            AssetDocumentObject::class => ['createFromAssetDocumentObject', new AssetDocumentObject()],
            AssetDocument::class       => ['createFromAssetDocument', new AssetDocument()],
            Document::class            => ['createFromDocument', new Document()],
            Company::class             => ['createFromCompany', new Company()],
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
