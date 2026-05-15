<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * AC-25: Branded password reset email.
 *
 * Extends Laravel's built-in notification to customise the email content,
 * subject, and branding while keeping all the security mechanics intact
 * (time-limited token, signed URL, one-time use).
 */
class ResetPasswordNotification extends BaseResetPassword
{
    /**
     * Build the mail representation of the notification.
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $resetUrl = $this->resetUrl($notifiable);
        $appName  = config('app.name', 'SmartProposal');
        $expiry   = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject("Reset your {$appName} password")
            ->greeting("Hello, {$notifiable->name}!")
            ->line("We received a request to reset the password for your **{$appName}** account associated with this email address.")
            ->line('If you made this request, click the button below to choose a new password:')
            ->action('Reset My Password', $resetUrl)
            ->line("This link will expire in **{$expiry} minutes** and can only be used once.")
            ->line('If you did not request a password reset, no action is needed — your account is safe and your current password has not been changed.')
            ->line("If you have any concerns about your account security, please contact our support team immediately.")
            ->salutation("— The {$appName} Team");
    }
}
