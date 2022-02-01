<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Asset as AssetModel;
use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Finders\OemFinder;
use App\Services\DataLoader\Finders\ServiceGroupFinder;
use App\Services\DataLoader\Finders\ServiceLevelFinder;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\OemResolver;
use App\Services\DataLoader\Resolver\Resolvers\ServiceGroupResolver;
use App\Services\DataLoader\Resolver\Resolvers\ServiceLevelResolver;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Schema\ViewDocument;
use App\Utils\Eloquent\Model;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Concerns\WithAssetDocument
 */
class WithAssetDocumentTest extends TestCase {
    /**
     * @covers ::documentOem
     */
    public function testDocumentOem(): void {
        $oem     = $this->faker->word;
        $factory = Mockery::mock(WithAssetDocumentTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();

        $factory
            ->shouldReceive('oem')
            ->with($oem)
            ->twice()
            ->andReturns();

        $factory->documentOem(new Document([
            'vendorSpecificFields' => [
                'vendor' => $oem,
            ],
        ]));
        $factory->documentOem(new ViewDocument([
            'vendorSpecificFields' => [
                'vendor' => $oem,
            ],
        ]));
    }

    /**
     * @covers ::assetDocumentOem
     */
    public function testAssetDocumentOem(): void {
        $oem      = Oem::factory()->make();
        $asset    = AssetModel::factory()->make();
        $document = new ViewAssetDocument([
            'document' => [
                'vendorSpecificFields' => [
                    'vendor' => $this->faker->word,
                ],
            ],
        ]);

        $factory = Mockery::mock(WithAssetDocumentTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('documentOem')
            ->with($document->document)
            ->once()
            ->andReturn($oem);

        $this->assertSame($oem, $factory->assetDocumentOem($asset, $document));
    }

    /**
     * @covers ::assetDocumentOem
     */
    public function testAssetDocumentOemNoDocument(): void {
        $oem        = Oem::factory()->make();
        $asset      = AssetModel::factory()->make();
        $asset->oem = $oem;
        $document   = new ViewAssetDocument([
            'document' => null,
        ]);

        $factory = Mockery::mock(WithAssetDocumentTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('documentOem')
            ->never();

        $this->assertSame($oem, $factory->assetDocumentOem($asset, $document));
    }

    /**
     * @covers ::assetDocumentServiceGroup
     */
    public function testAssetDocumentServiceGroup(): void {
        $oem      = Oem::factory()->make();
        $asset    = AssetModel::factory()->make();
        $group    = ServiceGroup::factory()->make();
        $document = new ViewAssetDocument([
            'document'       => [
                'vendorSpecificFields' => [
                    'vendor' => $this->faker->word,
                ],
            ],
            'supportPackage' => $this->faker->word,
        ]);

        $factory = Mockery::mock(WithAssetDocumentTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('assetDocumentOem')
            ->with($asset, $document)
            ->once()
            ->andReturn($oem);
        $factory
            ->shouldReceive('serviceGroup')
            ->with(
                $oem,
                $document->supportPackage,
            )
            ->once()
            ->andReturns($group);

        $this->assertSame($group, $factory->assetDocumentServiceGroup($asset, $document));
    }

    /**
     * @covers ::assetDocumentServiceLevel
     */
    public function testAssetDocumentServiceLevel(): void {
        $oem      = Oem::factory()->make();
        $asset    = AssetModel::factory()->make();
        $group    = ServiceGroup::factory()->make();
        $level    = ServiceLevel::factory()->make();
        $document = new ViewAssetDocument([
            'document'  => [
                'vendorSpecificFields' => [
                    'vendor' => $this->faker->word,
                ],
            ],
            'skuNumber' => $this->faker->word,
        ]);

        $factory = Mockery::mock(WithAssetDocumentTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('assetDocumentOem')
            ->with($asset, $document)
            ->once()
            ->andReturn($oem);
        $factory
            ->shouldReceive('assetDocumentServiceGroup')
            ->with(
                $asset,
                $document,
            )
            ->once()
            ->andReturns($group);
        $factory
            ->shouldReceive('serviceLevel')
            ->with(
                $oem,
                $group,
                $document->skuNumber,
            )
            ->once()
            ->andReturns($level);

        $this->assertSame($level, $factory->assetDocumentServiceLevel($asset, $document));
    }
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class WithAssetDocumentTest_Factory extends ModelFactory {
    use WithAssetDocument {
        documentOem as public;
        assetDocumentOem as public;
        assetDocumentServiceGroup as public;
        assetDocumentServiceLevel as public;
    }

    // TODO [tests] Remove after https://youtrack.jetbrains.com/issue/WI-25253

    public function __construct(
        ExceptionHandler $exceptionHandler,
        Normalizer $normalizer,
        protected OemResolver $oemResolver,
        protected ServiceGroupResolver $serviceGroupResolver,
        protected ServiceLevelResolver $serviceLevelResolver,
        protected ?ServiceGroupFinder $serviceGroupFinder = null,
        protected ?ServiceLevelFinder $serviceLevelFinder = null,
        protected ?OemFinder $oemFinder = null,
    ) {
        parent::__construct($exceptionHandler, $normalizer);
    }

    public function create(Type $type): ?Model {
        return null;
    }

    protected function getOemResolver(): OemResolver {
        return $this->oemResolver;
    }

    protected function getOemFinder(): ?OemFinder {
        return $this->oemFinder;
    }

    protected function getServiceGroupFinder(): ?ServiceGroupFinder {
        return $this->serviceGroupFinder;
    }

    protected function getServiceGroupResolver(): ServiceGroupResolver {
        return $this->serviceGroupResolver;
    }

    protected function getServiceLevelFinder(): ?ServiceLevelFinder {
        return $this->serviceLevelFinder;
    }

    protected function getServiceLevelResolver(): ServiceLevelResolver {
        return $this->serviceLevelResolver;
    }
}
