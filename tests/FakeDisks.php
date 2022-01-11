<?php declare(strict_types = 1);

namespace Tests;

use App\Services\Filesystem\Disk;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin \Tests\TestCase
 */
trait FakeDisks {
    /**
     * @var array<string,bool>
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
                    $config                            = $this->app()->make(Repository::class);
                    $settings                          = $config->get("filesystems.disks.{$name}", []);
                    $this->fakeDisks[$disk->getName()] = (bool) Storage::fake($name, $settings);
                }
            });
        });

        $this->beforeApplicationDestroyed(function (): void {
            $this->fakeDisks = [];
        });
    }
}
