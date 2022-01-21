<?php declare(strict_types = 1);

namespace App\Exceptions\Contracts;

/**
 * Marks that exception is a message (or well-known exception) and there are no
 * reasons to dump the trace into logs.
 *
 * Is can be used if
 * - Exception happens very often
 * - Stack trace is useless
 */
interface ApplicationMessage {
    // empty
}
