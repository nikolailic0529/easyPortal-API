<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Loader;

use App\Utils\Processor\CompositeState;
use DateTimeInterface;

class LoaderState extends CompositeState {
    public string             $objectId;
    public DateTimeInterface  $started;
    public ?DateTimeInterface $from  = null;
    public bool               $force = false;
}
