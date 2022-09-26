<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Document;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @extends Resolver<Document>
 */
class DocumentResolver extends Resolver {
    /**
     * @param Closure(Normalizer=): Document|null $factory
     *
     * @return ($factory is null ? Document|null : Document)
     */
    public function get(string|int $id, Closure $factory = null): ?Document {
        return $this->resolve($id, $factory);
    }

    public function put(Model|Collection|array $object): void {
        parent::put($object);
    }

    protected function getFindQuery(): ?Builder {
        return Document::withTrashed();
    }
}
