<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Services\Logger\LoggerObject;
use Illuminate\Database\Eloquent\Model;

class EloquentObject implements LoggerObject {
    public function __construct(
        protected Model $model,
    ) {
        // empty
    }

    public function getId(): string {
        return $this->model->getKey();
    }

    public function getType(): string {
        return $this->model->getMorphClass();
    }
}
