<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\SearchBy;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class Metadata {
    /**
     * @var array<string,bool>
     */
    protected array $metadata;

    public function __construct() {
        // empty
    }

    public function isFulltextIndexExists(EloquentBuilder|QueryBuilder $builder, string $property): bool {
        $table  = $builder instanceof EloquentBuilder
            ? $builder->getModel()->getTable()
            : $builder->from;
        $exists = $this->getMetadata()["{$table}.{$property}"]
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
                (new Document())->getTable().'.number'     => true,
                (new Customer())->getTable().'.name'       => true,
                (new Product())->getTable().'.name'        => true,
            ];
        }

        return $this->metadata;
    }
}
