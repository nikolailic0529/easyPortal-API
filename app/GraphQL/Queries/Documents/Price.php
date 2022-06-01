<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Documents;

use App\Models\Document;
use App\Utils\Eloquent\Callbacks\GetKey;
use Illuminate\Contracts\Config\Repository;

use function array_intersect;

class Price {
    public function __construct(
        protected Repository $repository,
    ) {
        // empty
    }

    public function __invoke(Document $document): ?string {
        return $document->price !== null && $this->isVisible($document)
            ? $document->price
            : null;
    }

    protected function isVisible(Document $document): bool {
        $statuses = (array) $this->repository->get('ep.document_statuses_no_price');
        $actual   = $document->statuses->map(new GetKey())->all();

        return !array_intersect($statuses, $actual);
    }
}
