<?php declare(strict_types = 1);

namespace App\Mail;


use App\Models\QuoteRequest as ModelsQuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

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

        return $mail->to($this->request->to)->markdown('quote_request', [
            'request' => $this->request,
        ]);
    }
}
