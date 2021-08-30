<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

use Generator;
use IteratorAggregate;
use Symfony\Component\Finder\Finder;

class ClientDumpsIterator implements IteratorAggregate {
    public function __construct(
        protected string $path,
    ) {
        // empty
    }

    /**
     * @return \Generator<\App\Services\DataLoader\Testing\Data\ClientDumpFile>
     */
    public function getIterator(): Generator {
        $files = (new Finder())->in($this->path)->notName(DataGenerator::CONTEXT)->name('*.json')->files();

        foreach ($files as $file) {
            yield new ClientDumpFile($file);
        }
    }

    /**
     * @return \Generator<object>
     */
    public function getResponseIterator(bool $save = false): Generator {
        foreach ($this as $file) {
            /** @var \App\Services\DataLoader\Testing\Data\ClientDumpFile $file */
            yield from $file->getDump()->getResponseIterator($save);

            if ($save) {
                $file->save();
            }
        }
    }
}
