<?php

namespace App\Notifications;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PostRejected extends Notification
{
    use Queueable;

    public function __construct(
        public Post $post,
        public User $rejector,
        public string $reason
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Post Rejected: ' . $this->post->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($this->rejector->name . ' has rejected your post.')
            ->line('**Post Title:** ' . $this->post->title)
            ->line('**Brand:** ' . $this->post->brand->name)
            ->line('**Reason:** ' . $this->reason)
            ->action('View Post', url('/dashboard/resources/posts/' . $this->post->id))
            ->line('Please review the feedback and make necessary changes.');
    }

    public function toArray($notifiable): array
    {
        return [
            'post_id' => $this->post->id,
            'post_title' => $this->post->title,
            'rejector_id' => $this->rejector->id,
            'rejector_name' => $this->rejector->name,
            'brand_name' => $this->post->brand->name,
            'reason' => $this->reason,
            'message' => "{$this->rejector->name} rejected '{$this->post->title}'",
        ];
    }
}
