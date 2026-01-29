<?php

namespace App\Notifications;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PostApproved extends Notification
{
    use Queueable;

    public function __construct(
        public Post $post,
        public User $approver
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Post Approved: ' . $this->post->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($this->approver->name . ' has approved your post.')
            ->line('**Post Title:** ' . $this->post->title)
            ->line('**Brand:** ' . $this->post->brand->name)
            ->action('View Post', url('/dashboard/resources/posts/' . $this->post->id))
            ->line('You can now schedule this post for publication.');
    }

    public function toArray($notifiable): array
    {
        return [
            'post_id' => $this->post->id,
            'post_title' => $this->post->title,
            'approver_id' => $this->approver->id,
            'approver_name' => $this->approver->name,
            'brand_name' => $this->post->brand->name,
            'message' => "{$this->approver->name} approved '{$this->post->title}'",
        ];
    }
}
