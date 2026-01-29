<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;

class AdminAlert extends Notification
{
    use Queueable;

    public function __construct(
        private string $title,
        private string $message,
        private string $severity = 'warning',
        private array $context = []
    ) {}

    public function via($notifiable): array
    {
        $channels = ['mail', 'database'];
        
        if (config('services.slack.webhook_url')) {
            $channels[] = 'slack';
        }

        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("[{$this->severity}] {$this->title}")
            ->line($this->message);

        if (!empty($this->context)) {
            $mail->line('Details:');
            foreach ($this->context as $key => $value) {
                $mail->line("- {$key}: {$value}");
            }
        }

        $mail->line('Please check the system immediately.');

        if ($this->severity === 'critical') {
            $mail->action('View Dashboard', url('/dashboard'));
        }

        return $mail;
    }

    public function toSlack($notifiable): SlackMessage
    {
        $emoji = match($this->severity) {
            'critical' => ':rotating_light:',
            'error' => ':x:',
            'warning' => ':warning:',
            default => ':information_source:',
        };

        return (new SlackMessage)
            ->from('OmniPost Alerts')
            ->content("{$emoji} **{$this->title}**\n\n{$this->message}")
            ->attachment(function ($attachment) {
                $attachment->fields($this->context);
            });
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'severity' => $this->severity,
            'context' => $this->context,
        ];
    }

    public static function failedPublishes(int $count, string $timeWindow): self
    {
        return new self(
            title: 'Multiple Publishing Failures',
            message: "{$count} publish attempts failed in the last {$timeWindow}",
            severity: 'error',
            context: ['failed_count' => $count, 'time_window' => $timeWindow]
        );
    }

    public static function queueDepthHigh(int $depth): self
    {
        return new self(
            title: 'High Queue Depth',
            message: "Queue has {$depth} pending jobs",
            severity: 'warning',
            context: ['queue_depth' => $depth]
        );
    }

    public static function tokenRefreshFailed(string $platform, string $accountName): self
    {
        return new self(
            title: 'Token Refresh Failed',
            message: "Failed to refresh {$platform} token for {$accountName}",
            severity: 'error',
            context: ['platform' => $platform, 'account' => $accountName]
        );
    }

    public static function rateLimitExceeded(string $platform): self
    {
        return new self(
            title: 'Rate Limit Exceeded',
            message: "Rate limit exceeded for {$platform}",
            severity: 'warning',
            context: ['platform' => $platform]
        );
    }

    public static function diskSpaceLow(float $freeSpaceMB, float $usedPercent): self
    {
        return new self(
            title: 'Low Disk Space',
            message: "Disk space is running low: {$usedPercent}% used",
            severity: 'critical',
            context: [
                'free_space_mb' => $freeSpaceMB,
                'used_percent' => $usedPercent,
            ]
        );
    }

    public static function databaseConnectionFailed(string $error): self
    {
        return new self(
            title: 'Database Connection Failed',
            message: "Cannot connect to database: {$error}",
            severity: 'critical',
            context: ['error' => $error]
        );
    }
}
