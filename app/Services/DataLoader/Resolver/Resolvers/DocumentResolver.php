<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Document;
use App\Services\DataLoader\Resolver\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Resolver<Document>
 */
class DocumentResolver extends Resolver {
    /**
     * @param Closure(?Document): Document|null $factory
     *
     * @return ($factory is null ? Document|null : Document)
     */
    public function get(string|int $id, Closure $factory = null): ?Document {
        return $this->resolve($id, $factory);
    }

    protected function getFindQuery(): ?Builder {
        return Document::withTrashed();
    }
}
