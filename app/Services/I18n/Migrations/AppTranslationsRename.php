<?php declare(strict_types = 1);

namespace App\Services\I18n\Migrations;

use App\Services\Filesystem\Disks\AppDisk;
use App\Services\I18n\Storages\AppTranslations;
use Illuminate\Container\Container;
use LastDragon_ru\LaraASP\Migrator\Migrations\RawDataMigration;
use RuntimeException;

use function sprintf;

abstract class AppTranslationsRename extends RawDataMigration {
    protected function runRawUp(): void {
        foreach ($this->getRenameMap() as $from => $to) {
            $this->rename($from, $to);
        }
    }

    protected function runRawDown(): void {
        foreach ($this->getRenameMap() as $to => $from) {
            $this->rename($from, $to);
        }
    }

    protected function rename(string $from, string $to): void {
        // No to?
        $toStorage = $this->getStorage($to);

        if ($toStorage->load()) {
            return;
        }

        // No from?
        $fromStorage = $this->getStorage($from);
        $fromStrings = $fromStorage->load();

        if (!$fromStrings) {
            $fromStorage->delete(true);

            return;
        }

        // Update
        $toStorage->save($fromStrings) or throw new RuntimeException(sprintf(
            'Failed to rename locale `%s` into `%s`.',
            $to,
            $from,
        ));

        $fromStorage->delete(true);
    }

    protected function getStorage(string $locale): AppTranslations {
        return new AppTranslations(Container::getInstance()->make(AppDisk::class), $locale);
    }

    /**
     * @return array<string, string>
     */
    abstract protected function getRenameMap(): array;
}
