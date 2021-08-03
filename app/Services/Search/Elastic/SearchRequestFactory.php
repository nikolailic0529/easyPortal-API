<?php declare(strict_types = 1);

namespace App\Services\Search\Elastic;

use App\Services\Search\Builder as SearchBuilder;
use ElasticScoutDriver\Factories\SearchRequestFactory as BaseSearchRequestFactory;
use Illuminate\Support\Collection;
use Laravel\Scout\Builder as ScoutBuilder;

use function is_array;

class SearchRequestFactory extends BaseSearchRequestFactory {
    /**
     * @return array<mixed>|null
     */
    protected function makeFilter(ScoutBuilder $builder): ?array {
        $filter = new Collection(parent::makeFilter($builder));

        if ($builder->whereIns) {
            $filter = $filter->merge($this->getTerms($builder->whereIns));
        }

        if ($builder instanceof SearchBuilder) {
            $not = new Collection();

            if ($builder->whereNots) {
                $not = $not->merge($this->getTerms($builder->whereNots));
            }

            if ($builder->whereNotIns) {
                $not = $not->merge($this->getTerms($builder->whereNotIns));
            }

            if (!$not->isEmpty()) {
                $filter = $filter->merge([
                    [
                        'bool' => [
                            'must_not' => $not->all(),
                        ],
                    ],
                ]);
            }
        }

        return $filter->isNotEmpty() ? $filter->all() : null;
    }

    /**
     * @param array<string,array<mixed>|mixed> $where
     */
    private function getTerms(array $where): Collection {
        return (new Collection($where))
            ->map(static function (mixed $value, string $field) {
                return is_array($value)
                    ? ['terms' => [$field => $value]]
                    : ['term' => [$field => $value]];
            })
            ->values();
    }
}
