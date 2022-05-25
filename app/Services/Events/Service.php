<?php declare(strict_types = 1);

namespace App\Services\Events;

use App\Services\Service as BaseService;

/**
 * Laravel doesn't provide any way to remove the concrete listener, but it is
 * required e.g. for Processors while process huge amount of objects when
 * listeners should be reset between chunks. This service designed specially
 * to solve this problem, so you can relax and don't worry about unsubscribing.
 */
class Service extends BaseService {
    // empty
}
