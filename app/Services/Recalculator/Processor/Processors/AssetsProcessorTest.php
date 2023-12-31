<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Processor\Processors;

use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\Data\ServiceGroup;
use App\Models\Data\ServiceLevel;
use App\Models\Data\Type;
use App\Models\Document;
use App\Services\Recalculator\Events\ModelsRecalculated;
use App\Services\Recalculator\Testing\Helper;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @covers \App\Services\Recalculator\Processor\ChunkData
 * @covers \App\Services\Recalculator\Processor\Processors\AssetsChunkData
 * @covers \App\Services\Recalculator\Processor\Processors\AssetsProcessor
 */
class AssetsProcessorTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    public function testProcess(): void {
        // Setup
        $this->overrideDateFactory('2021-08-30T00:00:00.000+00:00');
        $this->overrideUuidFactory('8a0d99f2-543f-4608-a128-808acc5c42cc');
        $this->override(ExceptionHandler::class);

        // Prepare
        $date   = Date::now();
        $type   = Type::factory()->create([
            'id' => Str::uuid()->toString(),
        ]);
        $assetA = Asset::factory()
            ->hasCoverages(1, [
                'id' => Str::uuid()->toString(),
            ])
            ->create([
                'id'                        => Str::uuid()->toString(),
                'coverages_count'           => 123,
                'contacts_count'            => 123,
                'warranty_service_group_id' => ServiceGroup::factory()->create(),
                'warranty_service_level_id' => ServiceLevel::factory()->create(),
            ]);
        $assetB = Asset::factory()
            ->hasContacts(1, [
                'id' => Str::uuid()->toString(),
            ])
            ->create([
                'id'              => Str::uuid()->toString(),
                'coverages_count' => 321,
                'contacts_count'  => 321,
            ]);
        $assetC = Asset::factory()
            ->create([
                'id'              => Str::uuid()->toString(),
                'coverages_count' => 0,
                'contacts_count'  => 0,
            ]);

        AssetWarranty::factory()->create([
            // No Document
            'id'              => Str::uuid()->toString(),
            'end'             => $date->subDay(),
            'asset_id'        => $assetA,
            'document_id'     => null,
            'document_number' => null,
        ]);
        AssetWarranty::factory()->create([
            // Not a Contract
            'id'              => Str::uuid()->toString(),
            'end'             => $date->addDay(),
            'asset_id'        => $assetA,
            'document_id'     => Document::factory()->create([
                'id'          => Str::uuid()->toString(),
                'type_id'     => Type::factory()->create(),
                'is_hidden'   => false,
                'is_contract' => false,
                'is_quote'    => true,
            ]),
            'document_number' => Str::uuid()->toString(),
        ]);
        AssetWarranty::factory()->create([
            // Hidden Contract
            'id'              => Str::uuid()->toString(),
            'end'             => $date->addDay(),
            'asset_id'        => $assetA,
            'document_id'     => Document::factory()->create([
                'id'          => Str::uuid()->toString(),
                'type_id'     => $type,
                'is_hidden'   => true,
                'is_contract' => true,
                'is_quote'    => false,
            ]),
            'document_number' => Str::uuid()->toString(),
        ]);
        AssetWarranty::factory()->create([
            // Visible Contract
            'id'              => Str::uuid()->toString(),
            'end'             => $date,
            'asset_id'        => $assetA,
            'document_id'     => Document::factory()->create([
                'id'          => Str::uuid()->toString(),
                'type_id'     => $type,
                'is_hidden'   => false,
                'is_contract' => true,
                'is_quote'    => false,
            ]),
            'document_number' => Str::uuid()->toString(),
        ]);

        // Test
        $queries = $this->getQueryLog();
        $events  = Event::fake(ModelsRecalculated::class);

        $this->app->make(AssetsProcessor::class)
            ->setKeys([$assetA->getKey(), $assetB->getKey(), $assetC->getKey()])
            ->start();

        self::assertQueryLogEquals('~queries.json', $queries);
        self::assertDispatchedEventsEquals(
            '~events.json',
            $events->dispatched(ModelsRecalculated::class),
        );

        $queries->flush();

        unset($events);

        // Properties
        $aAsset     = $assetA->refresh();
        $bAsset     = $assetB->refresh();
        $attributes = [
            'warranty_end',
        ];

        // A
        self::assertEquals([
            'coverages_count' => 1,
            'contacts_count'  => 0,
            'warranty_end'    => $date->format('Y-m-d'),
        ], $this->getModelCountableProperties($aAsset, $attributes));

        // B
        self::assertEquals([
            'coverages_count' => 0,
            'contacts_count'  => 1,
            'warranty_end'    => null,
        ], $this->getModelCountableProperties($bAsset, $attributes));
    }
}
