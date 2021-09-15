<?php declare(strict_types = 1);

namespace App\Mail;

use Illuminate\Mail\Mailable;

use function count;

class Error extends Mailable {
    /**
     * @param array<mixed> $records
     */
    public function __construct(
        protected ?string $channel,
        protected string $content,
        protected array $records,
    ) {
        // empty
    }

    public function build(): static {
        $count   = count($this->records);
        $channel = $this->channel ? "{$this->channel} " : '';
        $subject = $count === 1
            ? "EAP {$channel}{$this->records[0]['level_name']}: {$this->records[0]['message']}"
            : "EAP {$channel}Error Report ({$count})";

        return $this
            ->subject($subject)
            ->html($this->content);
    }
}
