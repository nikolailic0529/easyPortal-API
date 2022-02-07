<?php declare(strict_types = 1);

namespace App\Utils\Iterators\Concerns;

trait Properties {
    use Index;
    use Limit;
    use Offset;
    use ChunkSize;
}
