<?php declare(strict_types = 1);

namespace App\Services\Logger;

interface LoggerObject {
    public function getId(): ?string;
    public function getType(): string;
}
