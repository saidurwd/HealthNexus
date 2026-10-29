<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** A scheduled report as an attachment. The text says only what the file is and which period it covers. */
class AnalyticsReportMail extends Mailable
{
    public function __construct(public readonly string $reportName, public readonly string $periodFrom, public readonly string $periodTo, private readonly string $body, private readonly string $fileName, private readonly string $mime) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Report: '.$this->reportName);
    }

    public function content(): Content
    {
        return new Content(htmlString: '<p>Your scheduled report <strong>'.e($this->reportName).'</strong> ('.e($this->periodFrom).' to '.e($this->periodTo).') is attached.</p><p>It contains figures from the hospital; keep it inside the hospital.</p>');
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return [Attachment::fromData(fn () => $this->body, $this->fileName)->withMime($this->mime)];
    }
}
