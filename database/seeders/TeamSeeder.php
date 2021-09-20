<?php declare(strict_types = 1);

namespace Database\Seeders;

use App\Models\Team;
use LastDragon_ru\LaraASP\Migrator\Seeders\SmartSeeder;

class TeamSeeder extends SmartSeeder {
    public function seed(): void {
        $names = ['Marketing', 'Finance', 'Operations management', 'Human Resource', 'IT'];
        foreach ($names as $name) {
            $team       = new Team();
            $team->name = $name;
            $team->save();
        }
    }

    protected function getTarget(): ?string {
        return Team::class;
    }
}
