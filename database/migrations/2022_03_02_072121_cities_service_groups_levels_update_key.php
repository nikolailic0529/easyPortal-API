<?php declare(strict_types = 1);

use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use Illuminate\Database\Migrations\Migration;

return new class() extends Migration {
    public function up(): void {
        $this->updateServiceGroups();
        $this->updateServiceLevels();
    }

    public function down(): void {
        // empty
    }

    protected function updateServiceGroups(): void {
        $groups = ServiceGroup::query()
            ->with('oem')
            ->where('key', '=', '')
            ->get();

        foreach ($groups as $group) {
            $group->key = "{$group->oem->getTranslatableKey()}/{$group->sku}";

            $group->save();
        }
    }

    protected function updateServiceLevels(): void {
        $levels = ServiceLevel::query()
            ->with('serviceGroup')
            ->where('key', '=', '')
            ->get();

        foreach ($levels as $level) {
            $level->key = "{$level->serviceGroup->getTranslatableKey()}/{$level->sku}";

            $level->save();
        }
    }
};
