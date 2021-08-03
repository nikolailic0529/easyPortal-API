<?php declare(strict_types = 1);

// @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

use Database\Seeders\QuoteRequestDurationSeeder;
use Illuminate\Database\Migrations\Migration;

class QuoteRequestDurationsSeed extends Migration {
    public function up(): void {
        app()->make(QuoteRequestDurationSeeder::class)->run();
    }

    public function down(): void {
        // empty
    }
}
