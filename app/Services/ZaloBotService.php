<?php

namespace App\Services;

use App\Models\ZaloChat;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZaloBotService
{
    public function configured(): bool
    {
        return filled(config('services.zalo_bot.token'));
    }

    public function sendMessage(string $chatId, string $text): bool
    {
        if (! $this->configured() || blank($chatId)) {
            return false;
        }

        try {
            $response = Http::timeout(10)->acceptJson()->post($this->endpoint('sendMessage'), [
                'chat_id' => $chatId,
                'text' => mb_substr($text, 0, 2000),
                'parse_mode' => 'markdown',
            ]);
            if (! $response->successful() || ! $response->json('ok')) {
                Log::warning('Zalo Bot sendMessage failed', ['status' => $response->status(), 'response' => $response->json()]);

                return false;
            }

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Zalo Bot request failed', ['message' => $exception->getMessage()]);

            return false;
        }
    }

    public function sendNotification(?string $privateChatId, string $title, string $message, string $url): void
    {
        $text = "**{$title}**\n{$message}\n{$url}";
        $targets = collect([$privateChatId, ZaloChat::where('is_group_target', true)->value('chat_id')])->filter()->unique();
        $targets->each(fn (string $chatId) => $this->sendMessage($chatId, $text));
    }

    public function endpoint(string $method): string
    {
        return 'https://bot-api.zaloplatforms.com/bot'.config('services.zalo_bot.token').'/'.$method;
    }
}
