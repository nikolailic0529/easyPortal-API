<?php declare(strict_types = 1);

namespace App\Services\Settings\Migrations;

use App\Services\Settings\Settings;
use App\Services\Settings\Storage;
use Illuminate\Container\Container;
use LastDragon_ru\LaraASP\Migrator\Migrations\RawDataMigration;
use RuntimeException;

use function array_flip;
use function array_key_exists;

abstract class SettingsRename extends RawDataMigration {
    protected function runRawUp(): void {
        $this->rename($this->getRenameMap());
    }

    protected function runRawDown(): void {
        $this->rename(array_flip($this->getRenameMap()));
    }

    /**
     * @param array<string, string> $map
     */
    protected function rename(array $map): void {
        $storage = $this->getStorage();
        $data    = $storage->load();

        if (!$data || !$map) {
            return;
        }

        foreach ($map as $from => $to) {
            if (array_key_exists($from, $data)) {
                $data[$to] = $data[$from];

                unset($data[$from]);
            }
        }

        $storage->save($data) or throw new RuntimeException(
            'Failed to rename settings.',
        );

        (new class($this->getSettings()) extends Settings {
            /**
             * @noinspection PhpMissingParentConstructorInspection
             * @phpstan-ignore-next-line
             */
            public function __construct(Settings $settings) {
                $settings->notify();
            }
        });
    }

    protected function getSettings(): Settings {
        return Container::getInstance()->make(Settings::class);
    }

    protected function getStorage(): Storage {
        return Container::getInstance()->make(Storage::class);
    }

    /**
     * @return array<string, string>
     */
    abstract protected function getRenameMap(): array;
}
