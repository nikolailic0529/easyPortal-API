<?php declare(strict_types = 1);

namespace App\Services\View;

use App\Utils\Providers\ServiceServiceProvider;
use Illuminate\Support\Facades\Blade;

class Provider extends ServiceServiceProvider {
    public function boot(): void {
        Blade::directive('html', static function (string $expression): string {
            return "<?php echo \\Stevebauman\\Purify\\Facades\\Purify::clean($expression); ?>";
        });
    }
}
