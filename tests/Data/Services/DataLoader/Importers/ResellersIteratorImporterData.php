<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Models\Reseller;
use App\Services\DataLoader\Processors\Importer\Importers\Resellers\IteratorImporter;
use App\Services\DataLoader\Testing\Data\Context;
use App\Services\DataLoader\Testing\Data\Data;
use App\Utils\Iterators\Contracts\ObjectIterator;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Utils\TestData;

class ResellersIteratorImporterData extends Data {
    public const RESELLERS = [
        '54f7dd6b-5234-4760-947d-c95e10751e18',
        'bd0eec0c-4d48-4840-9ce0-237316c727e1',
        '0917f385-4b97-4313-b255-043ad94683a7',
        'd152bb2f-deae-4106-aac2-6d1aa1517232',
        '6a3f5b75-5dc9-4517-aa18-a410644d33fb',
        'd5306966-ab8b-4893-bc85-d7722998b900',
        'a77f6f96-9c33-4e08-9838-ea291d572138',
        '3c01bf74-b2b8-437d-8492-75a061282101',
        '2d1c8169-8f80-41bd-8d37-e10936d25546',
        'c017d376-0055-42a9-80d8-ac90cef3f0c5',
        '00000000-0000-0000-0000-000000000000',
    ];

    protected function generateData(TestData $root, Context $context): bool {
        return $this->app->make(IteratorImporter::class)
            ->setIterator(static::getIterator())
            ->setChunkSize(static::CHUNK)
            ->setLimit(static::LIMIT)
            ->start();
    }

    /**
     * @inheritdoc
     */
    protected function getSupporterContext(): array {
        return [
            Context::RESELLERS,
        ];
    }

    public function restore(TestData $root, Context $context): bool {
        $result = parent::restore($root, $context);

        Reseller::factory()->create([
            'id'        => '00000000-0000-0000-0000-000000000000',
            'synced_at' => Date::now(),
        ]);

        return $result;
    }

    /**
     * @return ObjectIterator<Reseller|string>
     */
    public static function getIterator(): ObjectIterator {
        return static::getModelsIterator(Reseller::class, static::RESELLERS);
    }
}
