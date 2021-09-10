<?php declare(strict_types = 1);

namespace App\Exceptions;

use App\Mail\Error;
use Illuminate\Contracts\Mail\Mailer;
use Monolog\Handler\MailHandler;
use Monolog\Logger;

class MailableHandler extends MailHandler {
    /**
     * @param array<string> $recipients
     */
    public function __construct(
        protected Mailer $mailer,
        protected array $recipients,
        protected string $channel,
        int $level = Logger::DEBUG,
        bool $bubble = true,
    ) {
        parent::__construct($level, $bubble);
    }

    /**
     * @inheritDoc
     */
    public function isHandling(array $record): bool {
        return $this->recipients && parent::isHandling($record);
    }

    /**
     * @inheritDoc
     */
    protected function send(string $content, array $records): void {
        $this->mailer->bcc($this->recipients)->send(new Error($this->channel, $content, $records));
    }
}
