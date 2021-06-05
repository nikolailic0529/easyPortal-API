<?php declare(strict_types = 1);

namespace App\Services\Logger\Listeners;

use App\Services\Logger\Models\Enums\Category;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Events\QueryExecuted;

use function ltrim;
use function preg_match;

class DatabaseListener extends Listener {
    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(QueryExecuted::class, $this->getSafeListener(function (QueryExecuted $event): void {
            $this->query($event);
        }));
    }

    protected function query(QueryExecuted $event): void {
        $this->logger->count([
            "{$this->getCategory()}.total"                                 => 1,
            "{$this->getCategory()}.queries.{$this->getType($event->sql)}" => 1,
        ]);
    }

    protected function getCategory(): Category {
        return Category::database();
    }

    protected function getType(string $sql): string {
        $sql  = ltrim($sql);
        $type = 'other';

        if (preg_match('/^select\s/i', $sql)) {
            $type = 'select';
        } elseif (preg_match('/^insert\s/i', $sql)) {
            $type = 'insert';
        } elseif (preg_match('/^update\s/i', $sql)) {
            $type = 'update';
        } elseif (preg_match('/^delete\s/i', $sql)) {
            $type = 'delete';
        } else {
            // empty
        }

        return $type;
    }
}
