<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $customSubject;

    /**
     * Create a new message instance.
     *
     * @param array $data
     * @param string|null $subject
     * @return void
     */
    public function __construct($data, $subject = null)
    {
        $this->data = $data;
        $this->customSubject = $subject;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        $subject = $this->customSubject ?? 'Cotización ' . ($this->data['quote_number'] ?? '');

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'emails.quotation',
            with: ['data' => $this->data]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }

    /**
     * Build the message (for Laravel compatibility)
     */
    public function build()
    {
        $subject = $this->customSubject ?? 'Cotización ' . ($this->data['quote_number'] ?? '');

        return $this->subject($subject)
                    ->view('emails.quotation')
                    ->with('data', $this->data);
    }
}
