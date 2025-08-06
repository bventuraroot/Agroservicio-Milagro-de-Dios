<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EnviarFacturaOffline extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $numeroFactura;
    public $nombreEmpresa;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $numeroFactura = null, $nombreEmpresa = null)
    {
        $this->data = $data;
        $this->numeroFactura = $numeroFactura;
        $this->nombreEmpresa = $nombreEmpresa;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        $asunto = 'Factura';

        if ($this->numeroFactura) {
            $asunto = "Factura No. {$this->numeroFactura}";
        }

        if ($this->nombreEmpresa) {
            $asunto .= " - {$this->nombreEmpresa}";
        }

        return new Envelope(
            subject: $asunto,
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
            view: 'emails.factura-offline',
            with: [
                'data' => $this->data,
                'numeroFactura' => $this->numeroFactura,
                'nombreEmpresa' => $this->nombreEmpresa,
            ]
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
     * Build the message (método alternativo para compatibilidad)
     *
     * @return $this
     */
    public function build()
    {
        $asunto = 'Factura';

        if ($this->numeroFactura) {
            $asunto = "Factura No. {$this->numeroFactura}";
        }

        if ($this->nombreEmpresa) {
            $asunto .= " - {$this->nombreEmpresa}";
        }

        return $this->subject($asunto)
                    ->view('emails.factura-offline')
                    ->with([
                        'data' => $this->data,
                        'numeroFactura' => $this->numeroFactura,
                        'nombreEmpresa' => $this->nombreEmpresa,
                    ]);
    }
}
