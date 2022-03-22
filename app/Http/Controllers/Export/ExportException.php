<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export;

use App\Http\HttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

abstract class ExportException extends HttpException implements HttpExceptionInterface {
    public function getStatusCode(): int {
        return Response::HTTP_BAD_REQUEST;
    }

    /**
     * @return array<string>
     */
    public function getHeaders(): array {
        return [];
    }
}
