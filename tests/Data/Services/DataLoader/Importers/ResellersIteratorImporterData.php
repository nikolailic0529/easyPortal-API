<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Importers;

use App\Models\Reseller;
use App\Services\DataLoader\Importer\Importers\Resellers\IteratorImporter;
use App\Services\DataLoader\Testing\Data\ClientDumpContext;
use App\Services\DataLoader\Testing\Data\Data;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Iterators\ObjectsIterator;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery;

use function array_fill_keys;
use function array_flip;
use function array_rand;
use function count;
use function round;

class ResellersIteratorImporterData extends Data {
    public const LIMIT     = null;
    public const CHUNK     = 10;
    public const RESELLERS = [
        '5a09d4c0-a8fa-4e11-a4f4-3adc5df7800d',
        '8d4d4b7b-47d5-48a0-9c3d-01597b564ece',
        '7a1771c3-5173-4cf4-8ea6-7c8290c91130',
        '08c0401d-ec80-4a69-bbd0-26d2aac6b2e3',
        'ecd39ab3-7044-4de6-89cc-0df2e034dbc1',
        '02cbdc87-beef-4f76-83a8-9abad4a1599d',
        '155f3d69-cf12-4c0e-b8c3-dc5a39020c25',
        '765eb750-64b9-493d-ab95-b1f902ddd795',
        '84c4936b-38cb-4dc5-a5a5-01bb19d02127',
        'b8b3e24a-3f7b-42d4-9c13-60dd92459bb1',
        '00000000-0000-0000-0000-000000000000',
    ];

    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $this->app->make(IteratorImporter::class)
                ->setIterator(static::getIterator())
                ->setChunkSize(static::CHUNK)
                ->setLimit(static::LIMIT)
                ->start();

            return true;
        });
    }

    /**
     * @inheritDoc
     */
    protected function generateContext(string $path): array {
        return $this->app->make(ClientDumpContext::class)->get($path, [
            ClientDumpContext::RESELLERS,
        ]);
    }

    /**
     * @return ObjectIterator<Reseller|string>
     */
    public static function getIterator(): ObjectIterator {
        $resellers = static::RESELLERS;
        $models    = array_fill_keys(
            (array) array_rand(array_flip($resellers), (int) round(count($resellers) / 2)),
            true,
        );
        $model     = new Reseller();

        foreach ($resellers as $key => $id) {
            $resellers[$key] = isset($models[$id])
                ? (clone $model)->forceFill([$model->getKeyName() => $id])
                : $id;
        }

        return new ObjectsIterator(
            Mockery::mock(ExceptionHandler::class),
            $resellers,
        );
    }
}
