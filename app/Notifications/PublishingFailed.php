<?php

namespace App\Notifications;

use App\Models\PostVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PublishingFailed extends Notification
{
    use Queueable;

    public function __construct(
        public PostVariant $variant,
        public string $errorMessage
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject('Publishing Failed: ' . $this->variant->post->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A post variant failed to publish.')
            ->line('**Post Title:** ' . $this->variant->post->title)
            ->line('**Platform:** ' . ucfirst($this->variant->platform))
            ->line('**Error:** ' . $this->errorMessage)
            ->action('View Variant', url('/dashboard/resources/post-variants/' . $this->variant->id))
            ->line('Please review the error and try again.');
    }

    public function toArray($notifiable): array
    {
        return [
            'post_variant_id' => $this->variant->id,
            'post_id' => $this->variant->post_id,
            'post_title' => $this->variant->post->title,
            'platform' => $this->variant->platform,
            'error_message' => $this->errorMessage,
            'message' => "Publishing failed for '{$this->variant->post->title}' on {$this->variant->platform}",
        ];
    }
}
