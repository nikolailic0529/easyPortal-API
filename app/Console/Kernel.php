<?php declare(strict_types = 1);

namespace App\Console;

use App\Console\Commands\TestCommand;
use App\Dev\IdeHelper\ModelsCommand;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use LastDragon_ru\LaraASP\Queue\Concerns\ConsoleKernelWithSchedule;
use LastDragon_ru\LaraASP\Queue\Contracts\Cronable;

use function base_path;

class Kernel extends ConsoleKernel {
    use ConsoleKernelWithSchedule;

    /**
     * The Artisan commands provided by your application.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $commands = [
        // Dev
        ModelsCommand::class,
        TestCommand::class,
    ];

    /**
     * The application's command schedule.
     *
     * @var array<class-string<Cronable>>
     */
    protected array $schedule = [
        // empty
    ];

    /**
     * Register the commands for the application.
     */
    protected function commands(): void {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
