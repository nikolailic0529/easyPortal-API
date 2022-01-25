<?php declare(strict_types = 1);

namespace App\Services\View;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class Provider extends ServiceProvider {
    public function boot(): void {
        Blade::directive('html', static function (string $expression): string {
            return "<?php echo \\Stevebauman\\Purify\\Facades\\Purify::clean($expression); ?>";
        });
    }
}
