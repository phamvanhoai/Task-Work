<?php

namespace App\Notifications\Channels;

use App\Notifications\WorkspaceNotification;
use App\Services\ZaloBotService;

class ZaloBotChannel
{
    public function __construct(private ZaloBotService $bot) {}

    public function send(object $notifiable, WorkspaceNotification $notification): void
    {
        $this->bot->sendNotification($notifiable->zalo_chat_id, $notification->title, $notification->message, $notification->url);
    }
}
