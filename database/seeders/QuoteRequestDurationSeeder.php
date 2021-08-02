<?php declare(strict_types = 1);

namespace Database\Seeders;

use App\Models\QuoteRequestDuration;
use LastDragon_ru\LaraASP\Migrator\Seeders\SmartSeeder;

use function range;

class QuoteRequestDurationSeeder extends SmartSeeder {
    public function seed(): void {
        foreach (range(1, 20) as $number) {
            $duration       = new QuoteRequestDuration();
            $value          = "$number Years";
            $duration->key  = $value;
            $duration->name = $value;
            $duration->save();
        }
    }

    protected function getTarget(): ?string {
        return QuoteRequestDuration::class;
    }
}
