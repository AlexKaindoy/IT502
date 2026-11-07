<div class="min-h-screen bg-slate-100 p-0 sm:p-4 lg:p-6" x-data="messenger({
    userId: @js((string) auth()->user()->getAuthIdentifier()),
    selectedConversationId: () => $wire.selectedConversationId
})">
    <div
        class="mx-auto flex h-screen max-h-[900px] max-w-7xl overflow-hidden bg-white shadow-xl sm:h-[calc(100vh-3rem)] sm:rounded-2xl">

        {{-- Sidebar --}}
        <aside class="flex w-full max-w-sm shrink-0 flex-col border-r border-slate-200 bg-white">

            {{-- Brand --}}
            <div class="border-b border-slate-100 px-6 py-5">
                <h1 class="text-xl font-bold tracking-tight text-slate-900">
                    MSIT <span class="text-indigo-600">Messenger</span>
                </h1>
            </div>

            {{-- Sidebar heading --}}
            <div class="px-5 pb-3 pt-5">
                <h2 class="text-sm font-semibold text-slate-800">
                    IT502
                </h2>
            </div>

            {{-- User list --}}
            <div class="flex-1 overflow-y-auto px-3 pb-4">
                @forelse ($users as $user)
                    <button type="button" wire:key="user-{{ $user->id }}"
                        wire:click="startConversation('{{ $user->id }}')" wire:loading.attr="disabled"
                        wire:target="startConversation"
                        class="group flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left transition-colors duration-150
    hover:bg-slate-50
    {{ $selectedConversation &&
    in_array((string) $user->getAuthIdentifier(), $selectedConversation->participants, true)
        ? 'bg-indigo-50 ring-1 ring-inset ring-indigo-100'
        : '' }}">
                        {{-- Avatar with Online Indicator --}}
                        <div class="relative shrink-0">
                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>

                            <span x-show="onlineUsers.includes(@js((string) $user->getAuthIdentifier()))" x-cloak
                                class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-white bg-emerald-500">
                            </span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="truncate text-[13px] font-semibold tracking-tight text-slate-800">
                                    {{ $user->name }}
                                </h3>

                                @if (($unreadCounts[(string) $user->getAuthIdentifier()] ?? 0) > 0)
                                    <span
                                        class="flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-indigo-600 px-1.5 text-[10px] font-bold text-white">
                                        {{ $unreadCounts[(string) $user->getAuthIdentifier()] }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <span class="text-xs font-medium text-indigo-600 opacity-0 transition group-hover:opacity-100">
                            Chat
                        </span>
                    </button>
                @empty
                    <div class="px-3 py-8 text-center">
                        <p class="text-sm text-slate-500">
                            No other users found.
                        </p>
                    </div>
                @endforelse
            </div>

            {{-- Logged-in user --}}
            <div class="border-t border-slate-200 p-4">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-200 text-sm font-semibold text-slate-700">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-800">
                            {{ auth()->user()->name }}
                        </p>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button type="submit"
                            class="rounded-lg px-3 py-2 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-red-600">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Main Chat Panel --}}
        <section class="hidden min-w-0 flex-1 flex-col bg-slate-50 sm:flex">

            @if ($selectedConversation)

                {{-- Conversation Header --}}
                <div wire:key="conversation-{{ $selectedConversation->id }}" x-data="conversation({
                    conversationId: @js((string) $selectedConversation->id),
                    userId: @js((string) auth()->user()->getAuthIdentifier())
                })"
                    class="flex min-h-0 flex-1 flex-col">
                    <div class="flex items-center gap-3 border-b border-slate-200 bg-white px-6 py-4">
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700">
                            {{ strtoupper(
                                substr(
                                    $users->firstWhere(
                                        '_id',
                                        $selectedConversation->participants[0] === (string) auth()->user()->getAuthIdentifier()
                                            ? $selectedConversation->participants[1]
                                            : $selectedConversation->participants[0],
                                    )?->name ?? 'U',
                                    0,
                                    1,
                                ),
                            ) }}
                        </div>

                        <div class="min-w-0">
                            <h2 class="truncate text-sm font-semibold text-slate-900">
                                @php
                                    $otherParticipantId = collect($selectedConversation->participants)->first(
                                        fn($id) => $id !== (string) auth()->user()->getAuthIdentifier(),
                                    );

                                    $otherParticipant = $users->firstWhere('_id', $otherParticipantId);
                                @endphp

                                {{ $otherParticipant?->name ?? 'Conversation' }}
                            </h2>
                        </div>
                    </div>

                    {{-- Messages --}}
                    <div x-data="messageContainer" x-ref="messageContainer"
                        x-on:scroll.throttle.300ms="loadOlderMessages()"
                        x-on:messages-will-update.window="beforeMessagesUpdate()"
                        x-on:messages-updated.window="afterMessagesUpdate()" x-on:message-sent.window="scrollToLatest()"
                        class="flex-1 space-y-5 overflow-y-auto px-6 py-6">
                        @forelse ($messages as $message)
                            @php
                                $isOwnMessage =
                                    (string) $message->sender_id === (string) auth()->user()->getAuthIdentifier();
                            @endphp

                            <div wire:key="message-{{ $message->id }}"
                                class="flex {{ $isOwnMessage ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[75%]">
                                    <div
                                        class="rounded-2xl px-4 py-3 text-sm leading-relaxed shadow-sm
                                        {{ $isOwnMessage
                                            ? 'rounded-br-md bg-indigo-600 text-white'
                                            : 'rounded-bl-md border border-slate-200 bg-white text-slate-800' }}">
                                        @if ($message->deleted_at)
                                            <span
                                                class="italic {{ $isOwnMessage ? 'text-indigo-200' : 'text-slate-400' }}">
                                                This message was deleted.
                                            </span>
                                        @else
                                            {{ $message->body }}
                                        @endif
                                    </div>
                                    <div
                                        class="mt-1 flex items-center gap-3 px-1 {{ $isOwnMessage ? 'justify-end' : 'justify-start' }}">
                                        <p class="text-[11px] text-slate-400">
                                            {{ $message->created_at?->format('M d, h:i A') }}
                                        </p>

                                        @if ($isOwnMessage && !$message->deleted_at)
                                            <button type="button" wire:click="deleteMessage('{{ $message->id }}')"
                                                wire:confirm="Are you sure you want to delete this message?"
                                                wire:loading.attr="disabled"
                                                class="text-[11px] font-medium text-slate-400 transition hover:text-red-500">
                                                Delete
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="flex h-full items-center justify-center">
                                <div class="text-center">
                                    <div
                                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M8 10h8M8 14h5m-9 6 2.5-3H18a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2v3Z" />
                                        </svg>
                                    </div>

                                    <h3 class="mt-4 text-sm font-semibold text-slate-800">
                                        Start a conversation
                                    </h3>

                                    <p class="mt-1 text-xs text-slate-500">
                                        Send your first message below.
                                    </p>
                                </div>
                            </div>
                        @endforelse
                        <div x-show="otherUserTyping" x-transition.opacity x-cloak
                            class="px-4 py-2 text-xs italic text-slate-400">
                            {{ $otherParticipant?->name ?? 'Someone' }} is typing...
                        </div>
                    </div>

                    {{-- Message Composer --}}
                    <div class="border-t border-slate-200 bg-white px-5 py-4">
                        <form wire:submit="sendMessage" x-data="messageComposer({
                            conversationId: @js((string) $selectedConversation->id),
                            userId: @js((string) auth()->user()->getAuthIdentifier())
                        })" class="flex items-end gap-3">
                            <div class="flex-1">
                                <textarea wire:model="body" x-model="messageText" x-on:input="sendTypingEvent()" rows="1"
                                    placeholder="Write a message..."
                                    class="block w-full resize-none rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-indigo-400 focus:bg-white focus:ring-2 focus:ring-indigo-100"></textarea>
                                @error('body')
                                    <p class="mt-1 text-xs text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <button type="submit" wire:loading.attr="disabled" wire:target="sendMessage"
                                x-bind:disabled="!messageText.trim()" aria-label="Send message" title="Send message"
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white transition
        hover:bg-indigo-700
        disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400">
                                <svg wire:loading.remove wire:target="sendMessage" xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6 12 3 3l18 9-18 9 3-9Zm0 0h7" />
                                </svg>

                                <svg wire:loading wire:target="sendMessage" xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            @elseif ($selectedConversationId)
                <div class="flex flex-1 items-center justify-center">
                    <p class="text-sm text-slate-500">
                        Conversation not found or access denied.
                    </p>
                </div>
            @else
                {{-- Empty Conversation State --}}
                <div class="flex flex-1 flex-col items-center justify-center px-6 text-center">
                    <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-indigo-50 text-indigo-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-9 w-9" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8 10h8M8 14h5m-9 6 2.5-3H18a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2v3Z" />
                        </svg>
                    </div>

                    <h2 class="mt-6 text-xl font-bold tracking-tight text-slate-900">
                        Your conversations start here
                    </h2>

                    <p class="mt-2 max-w-sm text-sm leading-6 text-slate-500">
                        Select someone from the people list to start a private conversation.
                    </p>
                </div>

            @endif
        </section>

    </div>
</div>
