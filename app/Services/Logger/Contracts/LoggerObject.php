<?php declare(strict_types = 1);

namespace App\Services\Logger\Contracts;

interface LoggerObject {
    public function getId(): ?string;
    public function getType(): string;
}
