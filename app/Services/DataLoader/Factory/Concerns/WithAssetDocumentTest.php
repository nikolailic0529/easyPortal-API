<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Asset as AssetModel;
use App\Models\Data\Oem;
use App\Models\Data\ServiceGroup;
use App\Models\Data\ServiceLevel;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Resolver\Resolvers\OemResolver;
use App\Services\DataLoader\Resolver\Resolvers\ServiceGroupResolver;
use App\Services\DataLoader\Resolver\Resolvers\ServiceLevelResolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\Types\Document;
use App\Services\DataLoader\Schema\Types\ViewAssetDocument;
use App\Services\DataLoader\Schema\Types\ViewDocument;
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
        $oem     = $this->faker->word();
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
                    'vendor' => $this->faker->word(),
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

        self::assertSame($oem, $factory->assetDocumentOem($asset, $document));
    }

    /**
     * @covers ::assetDocumentOem
     */
    public function testAssetDocumentOemNoDocument(): void {
        $oem         = Oem::factory()->make();
        $asset       = AssetModel::factory()->make();
        $asset->oem  = $oem;
        $document    = new ViewAssetDocument([
            'document' => null,
        ]);
        $oemResolver = Mockery::mock(OemResolver::class);
        $oemResolver
            ->shouldReceive('getByKey')
            ->with($asset->oem_id)
            ->once()
            ->andReturn($oem);

        $factory = Mockery::mock(WithAssetDocumentTest_Factory::class);
        $factory->shouldAllowMockingProtectedMethods();
        $factory->makePartial();
        $factory
            ->shouldReceive('getOemResolver')
            ->once()
            ->andReturns($oemResolver);
        $factory
            ->shouldReceive('documentOem')
            ->never();

        self::assertSame($oem, $factory->assetDocumentOem($asset, $document));
    }

    /**
     * @covers ::assetDocumentServiceGroup
     */
    public function testAssetDocumentServiceGroup(): void {
        $oem      = Oem::factory()->make();
        $asset    = AssetModel::factory()->make();
        $group    = ServiceGroup::factory()->make();
        $document = new ViewAssetDocument([
            'document'                   => [
                'vendorSpecificFields' => [
                    'vendor' => $this->faker->word(),
                ],
            ],
            'serviceGroupSku'            => $this->faker->word(),
            'serviceGroupSkuDescription' => $this->faker->word(),
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
                $document->serviceGroupSku,
                $document->serviceGroupSkuDescription,
            )
            ->once()
            ->andReturns($group);

        self::assertSame($group, $factory->assetDocumentServiceGroup($asset, $document));
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
            'document'                   => [
                'vendorSpecificFields' => [
                    'vendor' => $this->faker->word(),
                ],
            ],
            'serviceLevelSku'            => $this->faker->word(),
            'serviceLevelSkuDescription' => $this->faker->word(),
            'serviceFullDescription'     => $this->faker->sentence(),
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
                $document->serviceLevelSku,
                $document->serviceLevelSkuDescription,
                $document->serviceFullDescription,
            )
            ->once()
            ->andReturns($level);

        self::assertSame($level, $factory->assetDocumentServiceLevel($asset, $document));
    }
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 *
 * @extends ModelFactory<Model>
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
        protected OemResolver $oemResolver,
        protected ServiceGroupResolver $serviceGroupResolver,
        protected ServiceLevelResolver $serviceLevelResolver,
    ) {
        parent::__construct($exceptionHandler);
    }

    public function getModel(): string {
        return Model::class;
    }

    public function create(Type $type): ?Model {
        return null;
    }

    protected function getOemResolver(): OemResolver {
        return $this->oemResolver;
    }

    protected function getServiceGroupResolver(): ServiceGroupResolver {
        return $this->serviceGroupResolver;
    }

    protected function getServiceLevelResolver(): ServiceLevelResolver {
        return $this->serviceLevelResolver;
    }
}
