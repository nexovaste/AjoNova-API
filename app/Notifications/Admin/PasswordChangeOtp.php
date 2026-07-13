<?php

namespace App\Notifications\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangeOtp extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $token,
        protected string $adminTitle,
        protected string $fullName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = config('app.frontend_url') . '/change-password?token=' . $this->token . '&email=' .
            urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Reset Your Password')
            ->view('emails.admin.password-change-otp', [
                'url' => $url,
                'title' => $this->adminTitle,
                'fullName' => $this->fullName,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
