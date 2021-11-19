<?php declare(strict_types = 1);

namespace App\Exceptions\Handlers;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Monolog\Handler\GroupHandler;
use Throwable;

use function array_map;

/**
 * @see \Monolog\Handler\WhatFailureGroupHandler
 */
class SafeHandler extends GroupHandler {
    /**
     * @inheritDoc
     */
    public function __construct(
        protected ExceptionHandler $handler,
        array $handlers,
        bool $bubble = true,
    ) {
        parent::__construct($handlers, $bubble);
    }

    /**
     * {@inheritDoc}
     */
    public function handle(array $record): bool {
        if ($this->processors) {
            $record = $this->processRecord($record);
        }

        foreach ($this->handlers as $handler) {
            try {
                $handler->handle($record);
            } catch (Throwable $exception) {
                $this->handler->report(new HandlerException($handler, $exception));
            }
        }

        return false === $this->bubble;
    }

    /**
     * {@inheritDoc}
     */
    public function handleBatch(array $records): void {
        if ($this->processors) {
            $records = array_map(
                fn(mixed $record): mixed => $this->processRecord($record),
                $records,
            );
        }

        foreach ($this->handlers as $handler) {
            try {
                $handler->handleBatch($records);
            } catch (Throwable $exception) {
                $this->handler->report(new HandlerException($handler, $exception));
            }
        }
    }
}
