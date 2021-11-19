<?php declare(strict_types = 1);

namespace App\Exceptions\Handlers;

use App\Mail\Error;
use Exception;
use Illuminate\Contracts\Mail\Mailer;
use Monolog\Handler\MailHandler;
use Monolog\Logger;

use function array_column;
use function end;
use function explode;
use function max;

class MailableHandler extends MailHandler {
    /**
     * @param array<string> $recipients
     */
    public function __construct(
        protected Mailer $mailer,
        protected array $recipients,
        protected ?string $channel,
        int $level = Logger::DEBUG,
        bool $bubble = true,
    ) {
        parent::__construct($level, $bubble);
    }

    /**
     * @return array<string>
     */
    public function getRecipients(): array {
        return $this->recipients;
    }

    /**
     * @param array<string> $recipients
     */
    public function setRecipients(array $recipients): static {
        $this->recipients = $recipients;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isHandling(array $record): bool {
        return parent::isHandling($record) && $this->getRecordsRecipients([$record]);
    }

    /**
     * @inheritDoc
     */
    protected function send(string $content, array $records): void {
        $recipients = $this->getRecordsRecipients($records);

        if ($recipients) {
            $this->mailer->bcc($recipients)->send(
                new Error($this->channel, $content, $records),
            );
        }
    }

    /**
     * @param array<array<mixed>> $records
     *
     * @return array<string>
     */
    protected function getRecordsRecipients(array $records): array {
        // Group by recipients by level
        $maxLevel   = max(array_column($records, 'level') ?: Logger::getLevels());
        $recipients = [];

        foreach ($this->getRecipients() as $recipient) {
            if ($maxLevel >= $this->getRecipientLevel($recipient)) {
                $recipients[] = $recipient;
            }
        }

        return $recipients;
    }

    protected function getRecipientLevel(string $recipient): int {
        $level = $this->getLevel();

        try {
            $custom = explode('+', explode('@', $recipient, 2)[0], 2);
            $custom = end($custom) ?: $level;
            $level  = Logger::toMonologLevel($custom);
        } catch (Exception) {
            // no action
        }

        return $level;
    }
}
