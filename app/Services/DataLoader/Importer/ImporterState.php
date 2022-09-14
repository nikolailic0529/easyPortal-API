<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer;

use App\Utils\Processor\State;
use DateTimeInterface;

class ImporterState extends State {
    public ?DateTimeInterface $from    = null;
    public bool               $update  = true;
    public int                $updated = 0;
    public int                $created = 0;
    public int                $ignored = 0;
    public int                $deleted = 0;
}
