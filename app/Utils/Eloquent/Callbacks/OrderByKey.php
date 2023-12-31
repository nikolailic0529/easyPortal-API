<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Callbacks;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OrderByKey {
    /**
     * @template T of Model
     *
     * @param Builder<T> $builder
     *
     * @return Builder<T>
     */
    public function __invoke(Builder $builder): Builder {
        $base        = $builder->toBase();
        $model       = $builder->getModel();
        $orders      = $base->unions ? $base->unionOrders : $base->orders;
        $ordered     = false;
        $keyName     = $model->getKeyName();
        $fullKeyName = $model->getQualifiedKeyName();

        if ($orders) {
            foreach ($orders as $order) {
                if (isset($order['column']) && ($order['column'] === $keyName || $order['column'] === $fullKeyName)) {
                    $ordered = true;
                    break;
                }
            }
        }

        if (!$ordered) {
            $builder->orderBy($base->unions ? $keyName : $fullKeyName);
        }

        return $builder;
    }
}
