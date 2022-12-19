<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer;

use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\TypeWithKey;
use App\Utils\Eloquent\Model;

class ModelObject extends Type implements TypeWithKey {
    public Model $model;

    public function getKey(): string {
        return $this->model->getKey();
    }

    public function getName(): string {
        return $this->model->getMorphClass();
    }
}
