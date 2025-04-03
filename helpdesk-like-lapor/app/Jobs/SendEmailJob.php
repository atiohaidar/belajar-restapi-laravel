<?php

namespace App\Jobs;

use App\Mail\SendEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Mail;

class SendEmailJob implements ShouldQueue
{
    use Queueable;
    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $email = new SendEmail([
            'nama' => $this->data['nama'],
            'imail' => $this->data['imail'],
            'ini_pesannya' => $this->data['ini_pesannya'],
        ]);
        Mail::to($this->data['imail'])->send($email);
    }
}
