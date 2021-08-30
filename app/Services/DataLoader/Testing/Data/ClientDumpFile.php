<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

use SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;

use function dirname;
use function file_get_contents;
use function json_decode;
use function json_encode;

use const JSON_PRETTY_PRINT;
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
            $this->dump = new ClientDump(json_decode(file_get_contents($this->file->getPathname()), true));
        }

        return $this->dump;
    }

    public function save(): void {
        if (isset($this->dump)) {
            $fs   = new Filesystem();
            $path = $this->file->getPathname();
            $dump = json_encode($this->getDump(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $fs->mkdir(dirname($path));
            $fs->dumpFile($path, $dump);
        }
    }
}
