<?php

namespace App\Notifications\Member;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginOtpMail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $otp,
        protected string $device,
        protected string $location,
        protected string $fullName,
        protected string $title
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }


    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify Your New Device - Unity Co-op')
            ->view('emails.member.verify-login-otp', [
                'device' => $this->device,
                'otp' => $this->otp,
                'location' => $this->location,
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
