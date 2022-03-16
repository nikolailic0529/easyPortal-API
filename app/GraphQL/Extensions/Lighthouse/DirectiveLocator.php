<?php declare(strict_types = 1);

namespace App\GraphQL\Extensions\Lighthouse;

use GraphQL\Language\AST\Node;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use Nuwave\Lighthouse\Events\ManipulateAST;
use Nuwave\Lighthouse\Events\StartExecution;
use Nuwave\Lighthouse\Schema\DirectiveLocator as LighthouseDirectiveLocator;
use Nuwave\Lighthouse\Support\Contracts\Directive;
use WeakMap;

/**
 * @see https://github.com/nuwave/lighthouse/issues/2041
 */
class DirectiveLocator extends LighthouseDirectiveLocator {
    /**
     * @var WeakMap<Node,Collection<int, Directive>>
     */
    private WeakMap $directives;

    private bool $execution = false;

    public function __construct(Dispatcher $eventsDispatcher) {
        parent::__construct($eventsDispatcher);

        $this->reset();
    }

    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(ManipulateAST::class, function (): void {
            $this->reset();
        });
        $dispatcher->listen(StartExecution::class, function (): void {
            $this->execution = true;
        });
    }

    protected function reset(): void {
        $this->directives = new WeakMap();
        $this->execution  = false;
    }

    public function associated(Node $node): Collection {
        // While AST Manipulation phase the node/directive/args can be changed
        // so we must not cache anything.
        if (!$this->execution) {
            return parent::associated($node);
        }

        // While Execution phase nothing can be changed (?), so
        if (!isset($this->directives[$node])) {
            $this->directives[$node] = parent::associated($node);
        }

        return $this->directives[$node];
    }
}
