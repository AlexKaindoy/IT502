<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Events\MessageDeleted;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use InvalidArgumentException;

class MessageService
{
    public function send(
        Conversation $conversation,
        User $sender,
        string $body
    ): Message {
        $senderId = (string) $sender->getAuthIdentifier();

        if (! in_array($senderId, $conversation->participants, true)) {
            throw new InvalidArgumentException(
                'You are not a participant in this conversation.'
            );
        }

        $body = trim($body);

        if ($body === '') {
            throw new InvalidArgumentException(
                'Message cannot be empty.'
            );
        }

        $message = $conversation->messages()->create([
            'sender_id' => $senderId,
            'body' => $body,
            'read_by' => [$senderId],
        ]);

        $conversation->last_message_at = $message->created_at;
        $conversation->save();
        broadcast(new MessageSent($message))->toOthers();

        return $message;
    }

    public function delete(Message $message, User $user): void
    {
        // Only the original sender can delete their message.
        if ((string) $message->sender_id !== (string) $user->getAuthIdentifier()) {
            throw new InvalidArgumentException(
                'You can only delete your own messages.'
            );
        }

        // Prevent deleting an already deleted message.
        if ($message->deleted_at !== null) {
            return;
        }

        $message->update([
            'deleted_at' => now(),
            'deleted_by' => (string) $user->getAuthIdentifier(),
        ]);
        broadcast(new MessageDeleted($message))->toOthers();
    }

    public function markAsRead(Conversation $conversation, User $user): void
    {
        $userId = (string) $user->getAuthIdentifier();

        if (! in_array($userId, $conversation->participants, true)) {
            throw new InvalidArgumentException(
                'You are not a participant in this conversation.'
            );
        }

        Message::query()
            ->where('conversation_id', (string) $conversation->id)
            ->where('sender_id', '!=', $userId)
            ->whereNull('deleted_at')
            ->whereNotIn('read_by', [$userId])
            ->each(function (Message $message) use ($userId) {
                $readBy = $message->read_by ?? [];

                if (! in_array($userId, $readBy, true)) {
                    $readBy[] = $userId;

                    $message->update([
                        'read_by' => $readBy,
                    ]);
                }
            });
    }
}
