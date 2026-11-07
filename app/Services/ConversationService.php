<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\User;
use InvalidArgumentException;

class ConversationService
{
    public function findOrCreate(User $firstUser, User $secondUser): Conversation
    {
        $participants = [
            (string) $firstUser->getAuthIdentifier(),
            (string) $secondUser->getAuthIdentifier(),
        ];

        if ($participants[0] === $participants[1]) {
            throw new InvalidArgumentException(
                'A user cannot start a conversation with themselves.'
            );
        }

        sort($participants);

        $conversationKey = implode('_', $participants);

        return Conversation::firstOrCreate(
            ['conversation_key' => $conversationKey],
            [
                'participants' => $participants,
                'last_message_at' => null,
            ]
        );
    }
}
