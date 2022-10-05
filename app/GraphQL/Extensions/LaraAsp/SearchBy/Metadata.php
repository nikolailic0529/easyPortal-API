<?php declare(strict_types = 1);

namespace App\GraphQL\Extensions\LaraAsp\SearchBy;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Data\Product;
use App\Models\Document;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

use function str_replace;

class Metadata {
    /**
     * @var array<string,bool>
     */
    protected array $metadata;

    public function __construct() {
        // empty
    }

    /**
     * @template T of \Illuminate\Database\Eloquent\Model
     *
     * @param EloquentBuilder<T>|QueryBuilder $builder
     */
    public function isFulltextIndexExists(EloquentBuilder|QueryBuilder $builder, string $property): bool {
        $property = str_replace('`', '', $property);
        $table    = $builder instanceof EloquentBuilder
            ? $builder->getModel()->getTable()
            : $builder->from;
        $exists   = $this->getMetadata()["{$table}.{$property}"]
            ?? $this->getMetadata()[$property]
            ?? false;

        return $exists;
    }

    /**
     * @return array<string,bool>
     */
    protected function getMetadata(): array {
        // TODO: It should be generated automatically and cached somewhere.
        // TODO: It is also required ngram parser
        if (!isset($this->metadata)) {
            $this->metadata = [
                (new Asset())->getTable().'.serial_number' => true,
                (new Asset())->getTable().'.nickname'      => true,
                (new Document())->getTable().'.number'     => true,
                (new Customer())->getTable().'.name'       => true,
                (new Product())->getTable().'.name'        => true,
            ];
        }

        return $this->metadata;
    }
}
