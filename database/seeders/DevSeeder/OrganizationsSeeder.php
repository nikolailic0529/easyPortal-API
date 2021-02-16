<?php declare(strict_types = 1);

namespace Database\Seeders\DevSeeder;

use App\Models\Organization;
use LastDragon_ru\LaraASP\Migrator\Seeders\RawSeeder;

class OrganizationsSeeder extends RawSeeder {
    protected function getTarget(): ?string {
        return Organization::class;
    }
}
