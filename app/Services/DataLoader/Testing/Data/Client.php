<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

use App\Services\DataLoader\Client\Client as BaseClient;
use App\Services\DataLoader\Testing\Concerns\WithData;
use App\Services\DataLoader\Testing\Concerns\WithDump;
use App\Services\DataLoader\Testing\Concerns\WithLimit;
use SplFileInfo;

class Client extends BaseClient {
    use WithLimit;
    use WithData;
    use WithDump;

    private ?string            $path    = null;
    private ?ClientDataCleaner $cleaner = null;

    // <editor-fold desc="Getters & Setters">
    // =========================================================================
    public function getPath(): ?string {
        return $this->path;
    }

    public function setPath(?string $path): static {
        $this->path = $path;

        return $this;
    }

    public function getCleaner(): ?ClientDataCleaner {
        return $this->cleaner;
    }

    public function setCleaner(?ClientDataCleaner $cleaner): static {
        $this->cleaner = $cleaner;

        return $this;
    }
    // </editor-fold>

    // <editor-fold desc="Client">
    // =========================================================================
    /**
     * @inheritdoc
     */
    protected function callDump(string $selector, string $graphql, array $variables, mixed $json): mixed {
        // Cleanup
        $cleaner = $this->getCleaner();
        $dump    = new ClientDump([
            'selector'  => $selector,
            'query'     => $graphql,
            'variables' => $variables,
            'response'  => $json,
        ]);

        if ($cleaner) {
            foreach ($dump->getResponseIterator(true) as $object) {
                $cleaner->clean($object);
            }
        }

        // Save
        $base = $this->getPath();

        if ($base) {
            $path = "{$base}/{$this->callDumpPath($selector, $graphql, $variables)}";
            $file = new ClientDumpFile(new SplFileInfo($path));

            $file->setDump($dump);
            $file->save();
        }

        return $dump->response;
    }
    // </editor-fold>
}
