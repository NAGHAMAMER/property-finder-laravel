<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $userName,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('رمز استعادة كلمة المرور - عقاري')
            ->view('emails.password-reset-code');
    }
}
