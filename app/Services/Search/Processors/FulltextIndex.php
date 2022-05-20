<?php declare(strict_types = 1);

namespace App\Services\Search\Processors;

use Illuminate\Database\Eloquent\Model;

class FulltextIndex {
    /**
     * @param class-string<Model> $model
     */
    public function __construct(
        protected string $model,
        protected string $name,
        protected string $sql,
    ) {
        // empty
    }

    public function getModel(): string {
        return $this->model;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getSql(): string {
        return $this->sql;
    }
}
