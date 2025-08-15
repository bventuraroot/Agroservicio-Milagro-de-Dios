<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIChatSetting extends Model
{
    use HasFactory;

    protected $table = 'ai_chat_settings';

    protected $fillable = [
        'user_id',
        'background_color',
        'text_color',
        'accent_color',
        'font_size',
        'save_conversations',
        'show_timestamps'
    ];

    protected $casts = [
        'save_conversations' => 'boolean',
        'show_timestamps' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function getDefaultSettings()
    {
        return [
            'background_color' => '#ffffff',
            'text_color' => '#333333',
            'accent_color' => '#667eea',
            'font_size' => '14px',
            'save_conversations' => true,
            'show_timestamps' => true
        ];
    }
}
