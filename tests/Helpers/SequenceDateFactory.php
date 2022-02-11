<?php declare(strict_types = 1);

namespace Tests\Helpers;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\Date;

class SequenceDateFactory {
    protected DateTimeInterface $now;
    protected DateInterval      $interval;

    public function __construct(DateTimeInterface|string $now) {
        $this->now      = Date::make($now)->toMutable();
        $this->interval = new DateInterval('PT1S');
    }

    public function __invoke(DateTimeImmutable $now): DateTimeInterface {
        return Date::make($this->now->add($this->interval));
    }
}
