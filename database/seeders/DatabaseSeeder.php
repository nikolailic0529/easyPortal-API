<?php declare(strict_types = 1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {
    public function run(): void {
        $this->call(OrganizationSeeder::class);
        $this->call(QuoteRequestDurationSeeder::class);
        $this->call(TeamSeeder::class);
    }
}
