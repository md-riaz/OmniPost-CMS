<?php

namespace App\Notifications;

use App\Models\OAuthToken;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TokenExpiringNotification extends Notification
{
    use Queueable;

    public function __construct(
        public OAuthToken $token
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $daysRemaining = now()->diffInDays($this->token->expires_at);
        
        return (new MailMessage)
            ->warning()
            ->subject('OAuth Token Expiring Soon: ' . ucfirst($this->token->platform))
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('An OAuth token is expiring soon.')
            ->line('**Platform:** ' . ucfirst($this->token->platform))
            ->line('**Expires In:** ' . $daysRemaining . ' days')
            ->line('**Expires At:** ' . $this->token->expires_at->format('Y-m-d H:i:s'))
            ->action('Reconnect Account', url('/oauth/' . $this->token->platform . '/redirect'))
            ->line('Please reconnect your account to continue publishing.');
    }

    public function toArray($notifiable): array
    {
        return [
            'token_id' => $this->token->id,
            'platform' => $this->token->platform,
            'expires_at' => $this->token->expires_at->toDateTimeString(),
            'days_remaining' => now()->diffInDays($this->token->expires_at),
            'message' => ucfirst($this->token->platform) . ' token expires in ' . now()->diffInDays($this->token->expires_at) . ' days',
        ];
    }
}
