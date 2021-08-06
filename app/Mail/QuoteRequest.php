<?php declare(strict_types = 1);

namespace App\Mail;


use App\Models\QuoteRequest as ModelsQuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use function app;

class QuoteRequest extends Mailable {
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected ModelsQuoteRequest $request,
    ) {
        // empty
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build() {
        $mail = $this->subject('Quote Request');

        foreach ($this->request->files as $file) {
            /** @var \App\Models\File $file */
            $mail = $mail->attachFromStorageDisk($file->disk, $file->path, $file->name);
        }

        $email = app()->make(Repository::class)->get('ep.email_address');
        return $mail->to($email)->markdown('quote_request', [
            'request' => $this->request,
        ]);
    }
}
