<?php declare(strict_types = 1);

namespace Tests;

use App\Services\Filesystem\Disk;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

use function config;

/**
 * @mixin TestCase
 */
trait FakeDisks {
    /**
     * @var array<string,Filesystem>
     */
    private array $fakeDisks = [];

    /**
     * @before
     */
    public function initFakeDisks(): void {
        $this->afterApplicationCreated(function (): void {
            $this->app->afterResolving(Disk::class, function (Disk $disk): void {
                if (!isset($this->fakeDisks[$disk->getName()])) {
                    $name                              = $disk->getName();
                    $settings                          = config("filesystems.disks.{$name}", []);
                    $this->fakeDisks[$disk->getName()] = Storage::fake($name, $settings);
                }
            });
        });

        $this->beforeApplicationDestroyed(function (): void {
            // Cleanup
            foreach ($this->fakeDisks as $disk) {
                $disk->deleteDirectory('.');
            }

            // Reset
            $this->fakeDisks = [];
        });
    }
}
