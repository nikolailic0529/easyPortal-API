<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Listeners;

use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\Data\Status;
use App\Models\Data\Type;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\DocumentStatus;
use App\Services\Recalculator\Recalculator;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Date;
use Mockery\MockInterface;
use stdClass;
use Tests\TestCase;
use Tests\WithoutGlobalScopes;

/**
 * @internal
 * @covers \App\Services\Recalculator\Listeners\DocumentDeleted
 */
class DocumentDeletedTest extends TestCase {
    use WithoutGlobalScopes;

    public function testSubscribe(): void {
        $this->override(DocumentDeleted::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('__invoke')
                ->once()
                ->andReturns();
        });

        $this->app->make(Dispatcher::class)
            ->dispatch('eloquent.deleted: '.Document::class, new Document());
        $this->app->make(Dispatcher::class)
            ->dispatch('eloquent.deleted: '.stdClass::class, new stdClass());
    }

    public function testInvoke(): void {
        $type              = Type::factory()->create();
        $status            = Status::factory()->create();
        $contractVisible   = Document::factory()->create([
            'type_id' => $type,
            'price'   => null,
            'end'     => Date::now(),
        ]);
        $contractInvisible = Document::factory()->create([
            'type_id' => $type,
            'price'   => null,
            'end'     => Date::now()->subDay(),
        ]);
        $quote             = Document::factory()->create();
        $assetA            = Asset::factory()->create([
            'warranty_end' => $contractVisible->end,
        ]);
        $assetB            = Asset::factory()->create([
            'warranty_end' => $contractInvisible->end,
        ]);

        DocumentEntry::factory()->create([
            'document_id' => $contractVisible,
            'asset_id'    => $assetA,
        ]);
        DocumentEntry::factory()->create([
            'document_id' => $contractVisible,
            'asset_id'    => $assetB,
        ]);
        DocumentStatus::factory()->create([
            'document_id' => $contractInvisible,
            'status_id'   => $status,
        ]);
        AssetWarranty::factory()->create([
            'document_id' => $contractVisible,
            'asset_id'    => $assetA,
        ]);
        AssetWarranty::factory()->create([
            'document_id' => $contractVisible,
            'asset_id'    => $assetB,
        ]);

        $this->setSettings([
            'ep.document_statuses_hidden' => [$status->getKey()],
            'ep.contract_types'           => [$type->getKey()],
        ]);

        $this->override(Recalculator::class, static function (MockInterface $mock) use ($assetA): void {
            $mock
                ->shouldReceive('dispatch')
                ->with([
                    'model' => Asset::class,
                    'keys'  => [
                        $assetA->getKey(),
                    ],
                ])
                ->once();
        });

        $listener = $this->app->make(DocumentDeleted::class);

        $listener($quote);
        $listener($contractVisible);
        $listener($contractInvisible);
    }
}
