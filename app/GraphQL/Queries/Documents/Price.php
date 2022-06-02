<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Documents;

use App\Models\Document;
use App\Models\DocumentEntry;
use App\Utils\Eloquent\Callbacks\GetKey;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Config\Repository;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

use function array_intersect;
use function assert;
use function is_null;
use function is_string;

class Price {
    public function __construct(
        protected Repository $repository,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     */
    public function __invoke(
        Document|DocumentEntry $object,
        array $args,
        GraphQLContext $context,
        ResolveInfo $resolveInfo,
    ): ?string {
        $value = $object->getAttribute($resolveInfo->fieldName);
        $value = $value !== null && $this->isVisible($object)
            ? $value
            : null;

        assert(is_null($value) || is_string($value));

        return $value;
    }

    protected function isVisible(Document|DocumentEntry $object): bool {
        $document = $object instanceof DocumentEntry ? $object->document : $object;
        $statuses = (array) $this->repository->get('ep.document_statuses_no_price');
        $actual   = $document->statuses->map(new GetKey())->all();

        return !array_intersect($statuses, $actual);
    }
}
