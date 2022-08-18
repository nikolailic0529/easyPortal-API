<?php declare(strict_types = 1);

namespace App\Exceptions\Exceptions;

use App\Exceptions\ApplicationException;
use App\Exceptions\Contracts\TranslatedException;
use Psr\Log\LogLevel;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

use function trans;

class FailedToSendMail extends ApplicationException implements TranslatedException {
    public function __construct(TransportExceptionInterface $previous = null) {
        parent::__construct('Failed to send Mail.', $previous);

        $this->setLevel(LogLevel::CRITICAL);
    }

    public function getErrorMessage(): string {
        return trans('errors.failed_to_send_mail');
    }
}
