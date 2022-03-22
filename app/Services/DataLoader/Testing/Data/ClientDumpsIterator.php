<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

use Generator;
use IteratorAggregate;
use Symfony\Component\Finder\Finder;

/**
 * @implements IteratorAggregate<int, ClientDumpFile>
 */
class ClientDumpsIterator implements IteratorAggregate {
    public function __construct(
        protected string $path,
    ) {
        // empty
    }

    /**
     * @return Generator<ClientDumpFile>
     */
    public function getIterator(): Generator {
        $files = (new Finder())->in("{$this->path}/*")->name('*.json')->files();

        foreach ($files as $file) {
            yield new ClientDumpFile($file);
        }
    }

    /**
     * @return Generator<object>
     */
    public function getResponseIterator(bool $save = false): Generator {
        foreach ($this as $file) {
            /** @var ClientDumpFile $file */
            yield from $file->getDump()->getResponseIterator($save);

            if ($save) {
                $file->save();
            }
        }
    }
}
