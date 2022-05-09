<?php declare(strict_types = 1);

namespace App\Utils\Processor;

use App\Utils\Iterators\Concerns\Limit;
use App\Utils\Iterators\Concerns\Offset;
use App\Utils\Iterators\Contracts\Limitable;
use App\Utils\Iterators\Contracts\Offsetable;

/**
 * The Processor for Iterator.
 *
 * @template TItem
 * @template TChunkData
 * @template TState of State
 *
 * @extends Processor<TItem, TChunkData, TState>
 */
abstract class IteratorProcessor extends Processor implements Limitable, Offsetable {
    use Limit;
    use Offset;
}
