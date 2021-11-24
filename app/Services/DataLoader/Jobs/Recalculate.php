<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Model;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Services\Queue\Queues;
use App\Utils\Eloquent\Callbacks\GetKey;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Queue\Contracts\Initializable;

use function sprintf;

/**
 * @template T of \App\Models\Model
 */
abstract class Recalculate extends Job implements Initializable {
    /**
     * @var array<string>
     */
    protected array $keys;

    /**
     * @return array<mixed>
     */
    public function getQueueConfig(): array {
        return [
                'queue' => Queues::DATA_LOADER_RECALCULATE,
            ] + parent::getQueueConfig();
    }

    /**
     * @return array<string>
     */
    public function getKeys(): array {
        return $this->keys;
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Models\Model> $models
     */
    public function setModels(Collection $models): static {
        // Valid?
        if ($models->isEmpty()) {
            throw new InvalidArgumentException('The `$models` cannot be empty.');
        }

        $expected = $this->getModel();
        $actual   = $models->first();

        if (!($actual instanceof $expected)) {
            throw new InvalidArgumentException(sprintf(
                'The `$models` must contain `%s` models, but it is contain `%s`.',
                $expected::class,
                $actual::class,
            ));
        }

        // Initialize
        $this->keys = (new Collection($models))->map(new GetKey())->unique()->values()->all();

        $this->initialized();

        // Return
        return $this;
    }

    /**
     * @return T
     */
    abstract public function getModel(): Model;

    abstract protected function process(): void;

    public function __invoke(): void {
        GlobalScopes::callWithoutGlobalScope(OwnedByOrganizationScope::class, function (): void {
            $this->process();
        });
    }
}
