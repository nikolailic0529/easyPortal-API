<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer;

use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;

class ModelObject extends Type {
    public Model $model;
}
