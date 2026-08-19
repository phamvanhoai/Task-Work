<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WorkspaceNotification extends Notification
{
    use Queueable;

    public function __construct(public string $title, public string $message, public string $url, public string $icon = 'bell', public string $color = 'blue') {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => $this->title, 'message' => $this->message, 'url' => $this->url, 'icon' => $this->icon, 'color' => $this->color];
    }
}
