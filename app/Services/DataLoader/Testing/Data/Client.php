<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

use App\Services\DataLoader\Client\Client as BaseClient;
use App\Services\DataLoader\Testing\Concerns\WithDump;
use App\Services\DataLoader\Testing\Concerns\WithLimit;

class Client extends BaseClient {
    use WithLimit;
    use WithDump;

    private ?Context           $context = null;
    private ?ClientDataCleaner $cleaner = null;

    // <editor-fold desc="Getters & Setters">
    // =========================================================================
    public function getContext(): ?Context {
        return $this->context;
    }

    public function setContext(?Context $context): static {
        $this->context = $context;

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
     * @inheritDoc
     */
    protected function callExecute(string $selector, string $graphql, array $variables, array $files): mixed {
        // Load/Call
        $json = null;

        if ($this->hasDump($selector, $graphql, $variables)) {
            $json = $this->getDump($selector, $graphql, $variables);
        } else {
            $json = parent::callExecute($selector, $graphql, $variables, $files);
            $json = $this->cleanup($selector, $graphql, $variables, $json);
        }

        // Context
        $context = $this->getContext();

        if ($context) {
            $context[Context::FILES] = [
                $this->getDumpPath($selector, $graphql, $variables),
            ];
        }

        return $json;
    }

    /**
     * @param array<string, mixed> $variables
     */
    protected function cleanup(string $selector, string $graphql, array $variables, mixed $json): mixed {
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
        $data = $this->getData();

        if ($data) {
            $path = $this->getDumpPath($selector, $graphql, $variables);
            $file = new ClientDumpFile($data->file($path));

            $file->setDump($dump);
            $file->save();
        }

        return $dump->response;
    }
    // </editor-fold>
}
