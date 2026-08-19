<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['chat_id', 'chat_type', 'display_name', 'is_group_target', 'last_seen_at', 'notification_preferences'])]
class ZaloChat extends Model
{
    protected function casts(): array
    {
        return ['is_group_target' => 'boolean', 'last_seen_at' => 'datetime', 'notification_preferences' => 'array'];
    }
}
