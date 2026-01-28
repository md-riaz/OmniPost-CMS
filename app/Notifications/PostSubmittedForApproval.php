<?php

namespace App\Notifications;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PostSubmittedForApproval extends Notification
{
    use Queueable;

    public function __construct(
        public Post $post,
        public User $submitter
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Post Submitted for Approval: ' . $this->post->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($this->submitter->name . ' has submitted a post for approval.')
            ->line('**Post Title:** ' . $this->post->title)
            ->line('**Brand:** ' . $this->post->brand->name)
            ->action('Review Post', url('/dashboard/resources/posts/' . $this->post->id))
            ->line('Please review and approve or reject this post.');
    }

    public function toArray($notifiable): array
    {
        return [
            'post_id' => $this->post->id,
            'post_title' => $this->post->title,
            'submitter_id' => $this->submitter->id,
            'submitter_name' => $this->submitter->name,
            'brand_name' => $this->post->brand->name,
            'message' => "{$this->submitter->name} submitted '{$this->post->title}' for approval",
        ];
    }
}
