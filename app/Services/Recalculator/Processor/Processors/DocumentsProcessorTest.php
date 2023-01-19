<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Processor\Processors;

use App\Models\Data\Status;
use App\Models\Data\Type;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\DocumentStatus;
use App\Services\Recalculator\Events\ModelsRecalculated;
use App\Services\Recalculator\Testing\Helper;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\WithQueryLogs;

/**
 * @internal
 * @covers \App\Services\Recalculator\Processor\ChunkData
 * @covers \App\Services\Recalculator\Processor\Processors\DocumentsChunkData
 * @covers \App\Services\Recalculator\Processor\Processors\DocumentsProcessor
 */
class DocumentsProcessorTest extends TestCase {
    use WithQueryLogs;
    use Helper;

    public function testProcess(): void {
        // Setup
        $this->overrideDateFactory('2022-10-12T00:00:00.000+00:00');
        $this->overrideUuidFactory('284e45c0-7295-4873-bf74-4eef91869798');
        $this->override(ExceptionHandler::class);

        // Prepare
        $statusHidden  = Status::factory()->create([
            'id' => Str::uuid()->toString(),
        ]);
        $statusNoPrice = Status::factory()->create([
            'id' => Str::uuid()->toString(),
        ]);
        $typeContract  = Type::factory()->create([
            'id' => Str::uuid()->toString(),
        ]);
        $typeQuote     = Type::factory()->create([
            'id' => Str::uuid()->toString(),
        ]);

        $this->setSettings([
            'ep.document_statuses_no_price' => [$statusNoPrice->getKey()],
            'ep.document_statuses_hidden'   => [$statusHidden->getKey()],
            'ep.contract_types'             => [$typeContract->getKey()],
            'ep.quote_types'                => [$typeQuote->getKey()],
        ]);

        // Objects
        $documentA = Document::factory()->create([
            'id'           => Str::uuid()->toString(),
            'price'        => '123.45',
            'price_origin' => '543.21',
            'type_id'      => $typeContract,
            'is_hidden'    => false,
            'is_contract'  => false,
            'is_quote'     => false,
        ]);
        $documentB = Document::factory()->create([
            'id'           => Str::uuid()->toString(),
            'price'        => '123.45',
            'price_origin' => '543.21',
            'type_id'      => $typeQuote,
            'is_hidden'    => false,
            'is_contract'  => false,
            'is_quote'     => false,
        ]);
        $entryAA   = DocumentEntry::factory()->create([
            'id'                          => Str::uuid()->toString(),
            'document_id'                 => $documentA,
            'monthly_retail_price'        => '1.11',
            'monthly_retail_price_origin' => '1.11',
            'monthly_list_price'          => '2.22',
            'monthly_list_price_origin'   => '2.22',
            'list_price'                  => '3.33',
            'list_price_origin'           => '3.33',
            'renewal'                     => '4.44',
            'renewal_origin'              => '4.44',
        ]);
        $entryAB   = DocumentEntry::factory()->create([
            'id'                          => Str::uuid()->toString(),
            'document_id'                 => $documentA,
            'monthly_retail_price'        => '1.11',
            'monthly_retail_price_origin' => '1.11',
            'monthly_list_price'          => '2.22',
            'monthly_list_price_origin'   => '2.22',
            'list_price'                  => '3.33',
            'list_price_origin'           => '3.33',
            'renewal'                     => '4.44',
            'renewal_origin'              => '4.44',
        ]);
        $entryBA   = DocumentEntry::factory()->create([
            'id'                          => Str::uuid()->toString(),
            'document_id'                 => $documentB,
            'monthly_retail_price'        => '1.11',
            'monthly_retail_price_origin' => '1.11',
            'monthly_list_price'          => '2.22',
            'monthly_list_price_origin'   => '2.22',
            'list_price'                  => '3.33',
            'list_price_origin'           => '3.33',
            'renewal'                     => '4.44',
            'renewal_origin'              => '4.44',
        ]);

        DocumentStatus::factory()->create([
            'id'          => Str::uuid()->toString(),
            'document_id' => $documentA,
            'status_id'   => $statusNoPrice,
        ]);
        DocumentStatus::factory()->create([
            'id'          => Str::uuid()->toString(),
            'document_id' => $documentA,
            'status_id'   => $statusHidden,
        ]);

        // Test
        $queries = $this->getQueryLog();
        $events  = Event::fake(ModelsRecalculated::class);

        $this->app->make(DocumentsProcessor::class)
            ->setKeys([$documentA->getKey(), $documentB->getKey()])
            ->start();

        self::assertQueryLogEquals('~queries.json', $queries);
        self::assertDispatchedEventsEquals(
            '~events.json',
            $events->dispatched(ModelsRecalculated::class),
        );

        $queries->flush();

        unset($events);

        // A
        $aDocument = $documentA->fresh();
        $aaEntry   = $entryAA->fresh();
        $abEntry   = $entryAB->fresh();

        self::assertNotNull($aDocument);
        self::assertNull($aDocument->price);
        self::assertTrue($aDocument->is_hidden);
        self::assertTrue($aDocument->is_contract);
        self::assertFalse($aDocument->is_quote);
        self::assertNotNull($aaEntry);
        self::assertNull($aaEntry->renewal);
        self::assertNull($aaEntry->list_price);
        self::assertNull($aaEntry->monthly_list_price);
        self::assertNull($aaEntry->monthly_retail_price);
        self::assertNotNull($abEntry);
        self::assertNull($abEntry->renewal);
        self::assertNull($abEntry->list_price);
        self::assertNull($abEntry->monthly_list_price);
        self::assertNull($abEntry->monthly_retail_price);

        // B
        $bDocument = $documentB->fresh();
        $baEntry   = $entryBA->fresh();

        self::assertNotNull($bDocument);
        self::assertEquals($bDocument->price_origin, $bDocument->price);
        self::assertFalse($bDocument->is_hidden);
        self::assertFalse($bDocument->is_contract);
        self::assertTrue($bDocument->is_quote);
        self::assertNotNull($baEntry);
        self::assertEquals($baEntry->renewal_origin, $baEntry->renewal);
        self::assertEquals($baEntry->list_price_origin, $baEntry->list_price);
        self::assertEquals($baEntry->monthly_list_price_origin, $baEntry->monthly_list_price);
        self::assertEquals($baEntry->monthly_retail_price_origin, $baEntry->monthly_retail_price);
    }
}
