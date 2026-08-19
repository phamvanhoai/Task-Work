<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ZaloChat;
use App\Services\ZaloBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZaloBotWebhookController extends Controller
{
    public function __invoke(Request $request, ZaloBotService $bot): JsonResponse
    {
        $secret = (string) config('services.zalo_bot.webhook_secret');
        if ($secret === '' || ! hash_equals($secret, (string) $request->header('X-Bot-Api-Secret-Token'))) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $message = $request->input('result.message', []);
        $chatId = (string) data_get($message, 'chat.id', '');
        $chatType = strtoupper((string) data_get($message, 'chat.chat_type', ''));
        if ($chatId === '' || ! in_array($chatType, ['PRIVATE', 'GROUP'], true)) {
            return response()->json(['message' => 'Ignored']);
        }

        ZaloChat::updateOrCreate(['chat_id' => $chatId], [
            'chat_type' => $chatType,
            'display_name' => data_get($message, 'from.display_name'),
            'last_seen_at' => now(),
        ]);

        $text = trim((string) data_get($message, 'text', ''));
        if ($chatType === 'PRIVATE' && preg_match('/^\/link\s+([A-Za-z0-9]+)$/i', $text, $matches)) {
            $user = User::where('zalo_link_code', strtoupper($matches[1]))->first();
            if ($user) {
                User::where('zalo_chat_id', $chatId)->whereKeyNot($user->id)->update(['zalo_chat_id' => null]);
                $user->update(['zalo_chat_id' => $chatId, 'zalo_link_code' => null]);
                $bot->sendMessage($chatId, '**Liên kết thành công**'."\nThông báo TaskWork sẽ được gửi riêng cho {$user->name} tại đây.");
            } else {
                $bot->sendMessage($chatId, 'Mã liên kết không hợp lệ hoặc đã được sử dụng.');
            }
        }

        return response()->json(['message' => 'Success']);
    }
}
