<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader;

use App\Utils\Processor\CompositeState;
use DateTimeInterface;

class LoaderState extends CompositeState {
    public string            $objectId;
    public DateTimeInterface $started;
}
