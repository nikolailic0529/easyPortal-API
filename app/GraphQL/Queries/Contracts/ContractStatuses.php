<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Contracts;

use App\Models\Data\Status;
use App\Models\Document;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;

class ContractStatuses {
    public function __construct(
        protected Repository $repository,
    ) {
        // empty
    }

    /**
     * @return Builder<Status>
     */
    public function __invoke(): Builder {
        $statuses = (array) $this->repository->get('ep.document_statuses_hidden');

        return Status::query()
            ->where('object_type', '=', (new Document())->getMorphClass())
            ->when($statuses, static function (Builder $builder) use ($statuses): void {
                $builder->whereNotIn($builder->getModel()->getKeyName(), $statuses);
            })
            ->orderByKey();
    }
}
