<?php

namespace App\Notifications\member;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class signupMail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $fullName,
        protected string $title,
        protected string $email,
        protected string $surname,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome - Unity Co-op')
            ->view('emails.member.signup', [
                'fullName' => $this->fullName,
                'title' => $this->title,
                'email'=>$this->email,
                'surname'=>$this->surname
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
