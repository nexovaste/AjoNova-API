<?php

namespace App\Notifications\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffRegistration extends Notification implements ShouldQueue
{
    use Queueable;


    public function __construct(
        protected string $fullName,
        protected string $password,
        protected string $title
    )
    {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
       return (new MailMessage)
            ->subject('Your Unity Co-op Login Credentials')
            ->view('emails.admin.staff-registration', [
                'password' => $this->password,
                'fullName' => $this->fullName,
                'title' => $this->title,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
