<?php declare(strict_types = 1);

use App\Models\Customer;
use App\Models\Reseller;
use App\Models\Type;
use LastDragon_ru\LaraASP\Migrator\Migrations\RawMigration;

return new class() extends RawMigration {
    // Please see the associated SQL files

    public function up(): void {
        // Update DB
        parent::up();

        // Remove types
        Type::query()
            ->whereIn('object_type', [
                (new Reseller())->getMorphClass(),
                (new Customer())->getMorphClass(),
            ])
            ->delete();
    }
};
