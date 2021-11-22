<?php declare(strict_types = 1);

namespace App\Utils\Iterators;

use App\Utils\Iterators\Concerns\ChunkSize;
use App\Utils\Iterators\Concerns\Index;
use App\Utils\Iterators\Concerns\Limit;
use App\Utils\Iterators\Concerns\Offset;

trait ObjectIteratorProperties {
    use Index;
    use Limit;
    use Offset;
    use ChunkSize;
}
