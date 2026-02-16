<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoginCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public ?string $name
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Code de vérification')
            ->view('emails.login_code')
            ->with([
                'code' => $this->code,
                'name' => $this->name,
            ]);
    }
}
