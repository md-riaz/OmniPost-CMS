<?php

namespace App\Notifications;

use App\Models\Post;
use App\Models\User;
use App\Models\PostComment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PostCommentAdded extends Notification
{
    use Queueable;

    public function __construct(
        public Post $post,
        public User $commenter,
        public PostComment $comment
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Comment on: ' . $this->post->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($this->commenter->name . ' commented on your post.')
            ->line('**Post Title:** ' . $this->post->title)
            ->line('**Comment:** ' . $this->comment->comment_text)
            ->action('View Post', url('/dashboard/resources/posts/' . $this->post->id))
            ->line('Reply to keep the conversation going.');
    }

    public function toArray($notifiable): array
    {
        return [
            'post_id' => $this->post->id,
            'post_title' => $this->post->title,
            'commenter_id' => $this->commenter->id,
            'commenter_name' => $this->commenter->name,
            'comment_id' => $this->comment->id,
            'comment_text' => $this->comment->comment_text,
            'message' => "{$this->commenter->name} commented on '{$this->post->title}'",
        ];
    }
}
