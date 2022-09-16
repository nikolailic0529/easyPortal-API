<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Synchronizer;

use App\Utils\Processor\CompositeState;
use DateTimeInterface;

class SynchronizerState extends CompositeState {
    public DateTimeInterface  $started;
    public ?DateTimeInterface $from          = null;
    public bool               $withOutdated   = true;
    public ?DateTimeInterface $outdatedExpire = null;
    public ?int               $outdatedLimit  = null;
}
