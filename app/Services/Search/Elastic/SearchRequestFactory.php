<?php declare(strict_types = 1);

namespace App\Services\Search\Elastic;

use App\Services\Search\Builder as SearchBuilder;
use ElasticScoutDriver\Factories\SearchRequestFactory as BaseSearchRequestFactory;
use Illuminate\Support\Collection;
use Laravel\Scout\Builder as ScoutBuilder;

class SearchRequestFactory extends BaseSearchRequestFactory {
    /**
     * @return array<mixed>|null
     */
    protected function makeFilter(ScoutBuilder $builder): ?array {
        $filter = new Collection(parent::makeFilter($builder));

        if ($builder->whereIns) {
            $filter = $filter->merge($this->getTerms($builder->whereIns));
        }

        if ($builder instanceof SearchBuilder && $builder->whereNotIns) {
            $filter = $filter->merge([
                [
                    'bool' => [
                        'must_not' => $this->getTerms($builder->whereNotIns)->all(),
                    ],
                ],
            ]);
        }

        return $filter->isNotEmpty() ? $filter->all() : null;
    }

    /**
     * @param array<string,array<mixed>> $where
     */
    private function getTerms(array $where): Collection {
        return (new Collection($where))
            ->map(static function (array $value, string $field) {
                return [
                    'terms' => [$field => $value],
                ];
            })
            ->values();
    }
}
