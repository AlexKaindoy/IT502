<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\BelongsTo;

class Message extends Model
{
    protected $connection = 'mongodb';

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'deleted_at',
        'deleted_by',
        'read_by',
    ];

    protected function casts(): array
    {
        return [
            'conversation_id' => 'string',
            'sender_id' => 'string',
            'deleted_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id', 'id');
    }
}
