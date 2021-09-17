<?php declare(strict_types = 1);

namespace App\Dev\IdeHelper;

use App\Models\Concerns\HideGeneratedAttributes;
use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Barryvdh\LaravelIdeHelper\Contracts\ModelHookInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @see \App\Models\Concerns\HideGeneratedAttributes
 */
class HideGeneratedAttributesModelHook implements ModelHookInterface {
    public function run(ModelsCommand $command, Model $model): void {
        (new class($command) extends ModelsCommand {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected ModelsCommand $command,
            ) {
                // empty
            }

            public function removeGeneratedAttributes(): void {
                $this->command->properties = HideGeneratedAttributes::removeGeneratedAttributes(
                    $this->command->properties,
                );
            }
        })->removeGeneratedAttributes();
    }
}
