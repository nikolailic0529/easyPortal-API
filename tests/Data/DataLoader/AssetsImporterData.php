<?php declare(strict_types = 1);

namespace Tests\Data\DataLoader;

use App\Services\DataLoader\Schema\ViewAsset;
use App\Services\DataLoader\Schema\ViewAssetDocument;
use App\Services\DataLoader\Schema\ViewDocument;
use App\Services\DataLoader\Testing\Data\ClientDumpsIterator;
use App\Services\DataLoader\Testing\Data\Data;
use Illuminate\Console\Command;

use function array_filter;
use function array_unique;
use function array_values;

class AssetsImporterData extends Data {
    public const CONTEXT_DISTRIBUTORS = 'distributors';
    public const CONTEXT_RESELLERS    = 'resellers';
    public const CONTEXT_CUSTOMERS    = 'customers';
    public const LIMIT                = 50;
    public const CHUNK                = 10;

    /**
     * @inheritDoc
     */
    public function generate(string $path): array|bool {
        $result = $this->dumpClientResponses($path, function (): bool {
            $result  = $this->kernel->call('ep:data-loader-import-assets', [
                '--limit' => static::LIMIT,
                '--chunk' => static::CHUNK,
            ]);
            $success = $result === Command::SUCCESS;

            return $success;
        });

        if ($result) {
            $result = $this->getContext($path);
        }

        return $result;
    }

    /**
     * @return array<mixed>
     */
    protected function getContext(string $path): array {
        // Extract
        $distributors = [];
        $resellers    = [];
        $customers    = [];

        foreach ((new ClientDumpsIterator($path))->getResponseIterator() as $object) {
            if ($object instanceof ViewAsset) {
                $resellers[] = $object->resellerId ?? null;
                $resellers[] = $object->reseller->id ?? null;
                $customers[] = $object->customerId ?? null;
                $customers[] = $object->customer->id ?? null;
            } elseif ($object instanceof ViewAssetDocument) {
                $resellers[] = $object->reseller->id ?? null;
                $customers[] = $object->customer->id ?? null;
            } elseif ($object instanceof ViewDocument) {
                $resellers[]    = $object->resellerId ?? null;
                $customers[]    = $object->customerId ?? null;
                $distributors[] = $object->distributorId ?? null;
            } else {
                // empty
            }
        }

        // Cleanup
        $extracted = [
            static::CONTEXT_DISTRIBUTORS => $distributors,
            static::CONTEXT_RESELLERS    => $resellers,
            static::CONTEXT_CUSTOMERS    => $customers,
        ];

        foreach ($extracted as &$data) {
            $data = array_values(array_filter(array_unique($data)));
        }

        return $extracted;
    }
}
