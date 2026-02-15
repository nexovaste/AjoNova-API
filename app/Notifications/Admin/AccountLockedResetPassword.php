<?php

namespace App\Notifications\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountLockedResetPassword extends Notification implements ShouldQueue
{
    use Queueable;


    public function __construct(
        protected string $fullName,
        protected string $token,
        protected string $device,
        protected string $browser,
        protected string $location,
        protected string $title
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
            ->subject('Account locked - Reset Your Password')
            ->view('emails.admin.account-lock-resetpassword', [
                'url' => $url,
                'fullName' => $this->fullName,
                'device' => $this->device,
                'location' => $this->location,
                'browser' => $this->browser,
                'title' => $this->title
            ]);
    }


    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
