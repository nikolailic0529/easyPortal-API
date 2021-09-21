<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;

use function dirname;
use function file_get_contents;
use function json_decode;
use function json_encode;

use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_LINE_TERMINATORS;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class ClientDumpFile {
    protected ClientDump $dump;

    public function __construct(
        protected SplFileInfo $file,
    ) {
        // empty
    }

    public function setDump(ClientDump $dump): static {
        $this->dump = $dump;

        return $this;
    }

    public function getDump(): ClientDump {
        if (!isset($this->dump)) {
            $this->dump = new ClientDump(json_decode(
                file_get_contents($this->file->getPathname()),
                true,
                JSON_THROW_ON_ERROR,
            ));
        }

        return $this->dump;
    }

    public function save(): void {
        if (isset($this->dump)) {
            $fs   = new Filesystem();
            $path = $this->file->getPathname();
            $dump = json_encode(
                $this->getDump(),
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_LINE_TERMINATORS
                | JSON_PRESERVE_ZERO_FRACTION
                | JSON_THROW_ON_ERROR,
            );

            $fs->mkdir(dirname($path));
            $fs->dumpFile($path, $dump);
        }
    }
}
