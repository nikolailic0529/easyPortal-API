<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use Illuminate\Database\Eloquent\Model;

interface ResolverFinder {
    public function find(mixed $key): ?Model;
}
