<?php declare(strict_types = 1);

namespace Tests;

use App\Services\Filesystem\Disk;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin \Tests\TestCase
 */
trait FakeDisks {
    /**
     * @var array<string,bool>
     */
    private array $fakeDisks = [];

    protected function setUpFakeDisks(): void {
        $this->app->afterResolving(Disk::class, function (Disk $disk): void {
            if (!isset($this->fakeDisks[$disk->getName()])) {
                $this->fakeDisks[$disk->getName()] = (bool) Storage::fake($disk->getName());
            }
        });
    }

    protected function tearDownFakeDisks(): void {
        $this->fakeDisks = [];
    }
}
