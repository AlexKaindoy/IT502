
document.addEventListener('alpine:init', () => {

    Alpine.data('messenger', (config) => ({
        onlineUsers: [],

        init() {
            // Online presence
            window.Echo.join('online')
                .here((users) => {
                    this.onlineUsers = users.map(user => String(user.id));
                })
                .joining((user) => {
                    const id = String(user.id);

                    if (!this.onlineUsers.includes(id)) {
                        this.onlineUsers.push(id);
                    }
                })
                .leaving((user) => {
                    const id = String(user.id);

                    this.onlineUsers = this.onlineUsers.filter(
                        onlineId => onlineId !== id
                    );
                });

            // Listen for messages through the personal channel
            window.Echo.private(`App.Models.User.${config.userId}`)
                .listen('.message.sent', (event) => {
                    if (
                        String(event.conversation_id) !==
                        String(config.selectedConversationId())
                    ) {
                        this.$wire.$refresh();
                    }
                });
        },
         destroy() {
            window.Echo.leave('online');

            window.Echo.leave(
                `App.Models.User.${config.userId}`
            );
        }
    }));


    Alpine.data('conversation', (config) => ({
        otherUserTyping: false,
        typingTimer: null,

        init() {
            const channel = window.Echo.private(
                `conversations.${config.conversationId}`
            );

            // Listen for incoming messages
            channel.listen('.message.sent', async (event) => {
                window.dispatchEvent(
                    new CustomEvent('messages-will-update')
                );

                await this.$wire.handleIncomingMessage(
                    event.conversation_id
                );

                await this.$wire.$refresh();

                window.dispatchEvent(
                    new CustomEvent('messages-updated')
                );
            });

            // Listen for deleted messages
            channel.listen('.message.deleted', () => {
                this.$wire.$refresh();
            });

            // Listen for typing indicators
            channel.listenForWhisper('typing', (event) => {
                if (String(event.sender_id) === String(config.userId)) {
                    return;
                }

                this.otherUserTyping = true;

                clearTimeout(this.typingTimer);

                this.typingTimer = setTimeout(() => {
                    this.otherUserTyping = false;
                }, 1800);
            });
        },

        destroy() {
            clearTimeout(this.typingTimer);

            window.Echo.leave(
                `conversations.${config.conversationId}`
            );
        }
    }));


    Alpine.data('messageContainer', () => ({
        shouldAutoScroll: true,
        isLoadingOlder: false,

        init() {
            this.$nextTick(() => {
                this.$el.scrollTop = this.$el.scrollHeight;
            });
        },

         scrollToLatest() {
            this.$nextTick(() => {
                this.$el.scrollTo({
                    top: this.$el.scrollHeight,
                    behavior: 'smooth'
                });
            });
        },

        async loadOlderMessages() {
            if (
                this.$el.scrollTop > 50 ||
                !this.$wire.hasMoreMessages ||
                this.isLoadingOlder
            ) {
                return;
            }

            this.isLoadingOlder = true;

            const previousHeight = this.$el.scrollHeight;
            const previousTop = this.$el.scrollTop;

            try {
                await this.$wire.loadOlderMessages();

                await this.$nextTick();

                this.$el.scrollTop =
                    this.$el.scrollHeight - previousHeight + previousTop;
            } finally {
                this.isLoadingOlder = false;
            }
        },

        beforeMessagesUpdate() {
            this.shouldAutoScroll =
                this.$el.scrollHeight -
                this.$el.scrollTop -
                this.$el.clientHeight < 120;
        },

        afterMessagesUpdate() {
            this.$nextTick(() => {
                if (this.shouldAutoScroll) {
                    this.$el.scrollTo({
                        top: this.$el.scrollHeight,
                        behavior: 'smooth'
                    });
                }
            });
        }
    }));


    Alpine.data('messageComposer', (config) => ({
        messageText: '',
        sendTypingEvent() {
            window.Echo.private(
                `conversations.${config.conversationId}`
            ).whisper('typing', {
                sender_id: config.userId
            });
        }
    }));

});
