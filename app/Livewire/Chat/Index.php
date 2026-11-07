<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\ConversationService;
use App\Services\MessageService;
use Livewire\Component;

class Index extends Component
{
    public ?string $selectedConversationId = null;

    public string $body = '';
    public int $messageLimit = 30;
    public bool $hasMoreMessages = false;
    public function startConversation(string $userId): void
    {
        $otherUser = User::findOrFail($userId);

        $conversation = app(ConversationService::class)->findOrCreate(
            auth()->user(),
            $otherUser
        );

        $this->selectedConversationId = (string) $conversation->id;
        $this->messageLimit = 30;
        $this->hasMoreMessages = false;
        app(MessageService::class)->markAsRead(
            $conversation,
            auth()->user()
        );
    }

    public function handleIncomingMessage(string $conversationId): void
    {
        // Only handle messages for the conversation currently open.
        if ($this->selectedConversationId !== $conversationId) {
            return;
        }

        $conversation = Conversation::query()
            ->whereKey($conversationId)
            ->where('participants', (string) auth()->user()->getAuthIdentifier())
            ->first();

        if (! $conversation) {
            return;
        }

        app(MessageService::class)->markAsRead(
            $conversation,
            auth()->user()
        );
    }

    public function sendMessage(): void
    {
        $validated = $this->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $conversation = Conversation::query()
            ->whereKey($this->selectedConversationId)
            ->where('participants', (string) auth()->user()->getAuthIdentifier())
            ->firstOrFail();

        app(MessageService::class)->send(
            $conversation,
            auth()->user(),
            $validated['body']
        );

        $this->reset('body');
        $this->dispatch('message-sent');
    }

    public function deleteMessage(string $messageId): void
    {
        $conversation = Conversation::query()
            ->whereKey($this->selectedConversationId)
            ->where('participants', (string) auth()->user()->getAuthIdentifier())
            ->firstOrFail();

        $message = Message::query()
            ->whereKey($messageId)
            ->where('conversation_id', (string) $conversation->id)
            ->firstOrFail();

        app(MessageService::class)->delete(
            $message,
            auth()->user()
        );
    }

    public function loadOlderMessages(): void
    {
        if (! $this->selectedConversationId || ! $this->hasMoreMessages) {
            return;
        }

        // Verify that the authenticated user belongs to this conversation.
        $conversation = Conversation::query()
            ->whereKey($this->selectedConversationId)
            ->where('participants', (string) auth()->user()->getAuthIdentifier())
            ->firstOrFail();

        // Load the next batch of older messages.
        $this->messageLimit += 30;
    }

    public function render()
    {
        $currentUserId = (string) auth()->user()->getAuthIdentifier();
        // Get all conversations belonging to the authenticated user.
        $conversations = Conversation::query()
            ->where('participants', $currentUserId)
            ->get(['_id', 'participants']);

        // Map conversation IDs to the other participant's ID.
        $conversationUserIds = [];

        foreach ($conversations as $conversation) {
            $otherUserId = collect($conversation->participants)
                ->first(fn($id) => (string) $id !== $currentUserId);

            if ($otherUserId) {
                $conversationUserIds[(string) $conversation->id] = (string) $otherUserId;
            }
        }

        // Find unread messages across those conversations.
        $unreadMessages = Message::query()
            ->whereIn('conversation_id', array_keys($conversationUserIds))
            ->where('sender_id', '!=', $currentUserId)
            ->whereNull('deleted_at')
            ->whereNotIn('read_by', [$currentUserId])
            ->get(['conversation_id']);

        // Count unread messages for each other participant.
        $unreadCounts = [];

        foreach ($unreadMessages->groupBy('conversation_id') as $conversationId => $messages) {
            $otherUserId = $conversationUserIds[(string) $conversationId] ?? null;

            if ($otherUserId) {
                $unreadCounts[$otherUserId] = $messages->count();
            }
        }
        $users = User::query()
            ->where('_id', '!=', $currentUserId)
            ->orderBy('name')
            ->get(['_id', 'name', 'email']);

        $selectedConversation = null;
        $messages = collect();

        if ($this->selectedConversationId) {
            $selectedConversation = Conversation::query()
                ->whereKey($this->selectedConversationId)
                ->where('participants', $currentUserId)
                ->first();

            if ($selectedConversation) {
                $latestMessages = Message::query()
                    ->where('conversation_id', $this->selectedConversationId)
                    ->orderByDesc('_id')
                    ->limit($this->messageLimit + 1)
                    ->get();

                $this->hasMoreMessages = $latestMessages->count() > $this->messageLimit;

                $messages = $latestMessages
                    ->take($this->messageLimit)
                    ->reverse()
                    ->values();
            }
        }

        return view('livewire.chat.index', [
            'users' => $users,
            'selectedConversation' => $selectedConversation,
            'messages' => $messages,
            'unreadCounts' => $unreadCounts,
        ]);
    }
}
