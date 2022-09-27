<?php declare(strict_types = 1);

namespace Tests\Data\Services\DataLoader\Synchronizers;

use App\Models\Document;
use App\Services\DataLoader\Exceptions\DocumentNotFound;
use App\Services\DataLoader\Testing\Data\Data;
use Illuminate\Console\Command;

use function array_sum;

class DocumentsSynchronizerData extends Data {
    protected function generateData(string $path): bool {
        return $this->dumpClientResponses($path, function (): bool {
            $results = [
                $this->kernel->call('ep:data-loader-documents-sync', [
                    '--chunk'       => static::CHUNK,
                    '--no-outdated' => true,
                ]),
            ];

            try {
                $this->kernel->call('ep:data-loader-document-sync', [
                    'id' => '00000000-0000-0000-0000-000000000000',
                ]);
            } catch (DocumentNotFound) {
                // expected, we just need a dump
            }

            return array_sum($results) === Command::SUCCESS;
        });
    }

    /**
     * @inheritDoc
     */
    public function restore(string $path, array $context): bool {
        $result = parent::restore($path, $context);

        Document::factory()->create([
            'id' => '00000000-0000-0000-0000-000000000000',
        ]);

        return $result;
    }
}
