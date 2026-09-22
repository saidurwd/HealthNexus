<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SystemNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $title,
        public string $message,
        public array $data = []
    ) {}

    public function build(): self
    {
        return $this->subject($this->title)
            ->markdown('mail.notification')
            ->with([
                'title' => $this->title,
                'message' => $this->message,
                'data' => $this->data,
            ]);
    }
}
