<?php

namespace App\Notifications;

use App\Notifications\Channels\ZaloBotChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WorkspaceNotification extends Notification
{
    use Queueable;

    public function __construct(public string $title, public string $message, public string $url, public string $icon = 'bell', public string $color = 'blue', public string $category = 'general') {}

    public function via(object $notifiable): array
    {
        $preferences = $notifiable->notification_preferences ?? [];
        $categoryEnabled = $preferences[$this->category] ?? true;
        if (! $categoryEnabled) {
            return [];
        }

        return array_values(array_filter([
            ($preferences['in_app'] ?? true) ? 'database' : null,
            ($preferences['zalo_personal'] ?? true) ? ZaloBotChannel::class : null,
        ]));
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => $this->title, 'message' => $this->message, 'url' => $this->url, 'icon' => $this->icon, 'color' => $this->color];
    }
}
