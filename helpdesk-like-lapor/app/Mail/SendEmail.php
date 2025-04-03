<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The data passed to the email.
     *
     * @var array
     */
    protected $dari_laravel;

    /**
     * Create a new message instance.
     */
    public function __construct($dari_laravel)
    {
        $this->dari_laravel = $dari_laravel;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Email Masuk dari Laravel'
        
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.template_email',
            with: [
                'nama' => $this->dari_laravel['nama'],
                'imail' => $this->dari_laravel['imail'],
                'ini_pesannya' => $this->dari_laravel['ini_pesannya'],
            ],
            // text: 'emails.template_email_plain',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
