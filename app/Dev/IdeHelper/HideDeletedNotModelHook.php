<?php declare(strict_types = 1);

namespace App\Dev\IdeHelper;

use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Barryvdh\LaravelIdeHelper\Contracts\ModelHookInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @see \App\Models\Concerns\HideDeletedNot
 */
class HideDeletedNotModelHook implements ModelHookInterface {
    public function run(ModelsCommand $command, Model $model): void {
        (new class($command) extends ModelsCommand {
            public function __construct(
                protected ModelsCommand $command,
            ) {
                // empty
            }

            public function removeDeletedNotProperty(): void {
                unset($this->command->properties['deleted_not']);
            }
        })->removeDeletedNotProperty();
    }
}
