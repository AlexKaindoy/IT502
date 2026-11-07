# IT502 Messenger

IT502 Messenger is a Laravel application with authenticated, one-to-one messaging. It stores users, conversations, messages, and queued jobs in MongoDB. Livewire handles chat actions and page updates, while Laravel Reverb and Laravel Echo provide real-time messages, online presence, and typing indicators.

## Requirements

- PHP 8.3 or later, with the MongoDB PHP extension enabled for the PHP CLI and web server
- Composer
- A running MongoDB server (local development uses `127.0.0.1:27017`)
- Node.js and npm

Check that the MongoDB extension is available to the PHP CLI:

```powershell
php -m | Select-String mongodb
```

If it is missing, install the MongoDB extension that matches your PHP version, architecture, and thread-safety build, enable it in the loaded `php.ini`, and restart PHP processes.

## Local setup

Run these commands from the project directory in PowerShell:

```powershell
composer install
if (-not (Test-Path .env)) {
    Copy-Item .env.example .env
    php artisan key:generate
}
npm install
php artisan migrate
```

If `.env` already exists, keep it and check that it has the settings below. Generate an application key only if `APP_KEY` is empty.

The MongoDB connection and local Reverb settings are supplied in `.env.example`. The corresponding database connection is defined in `config/database.php`; the chat models explicitly use that `mongodb` connection. The MongoDB PHP extension is required; installing the Composer packages alone does not install it.

The MongoDB connection in `config/database.php` reads its URI and database name from these environment variables:

```php
'mongodb' => [
    'driver' => 'mongodb',
    'dsn' => env('DB_URI'),
    'database' => env('DB_DATABASE', 'it502'),
],
```

The relevant local environment settings are:

```dotenv
DB_CONNECTION=mongodb
DB_URI=mongodb://127.0.0.1:27017/
DB_DATABASE=it502

BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=database

REVERB_APP_ID=it502-local
REVERB_APP_KEY=it502-local-key
REVERB_APP_SECRET=it502-local-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

The Reverb ID, key, and secret above are local examples. The `VITE_REVERB_APP_KEY` and `REVERB_APP_KEY` must match, as must the client and server host/port/scheme settings. Use unique credentials and secure transport for a deployed environment; do not commit `.env`.

Both chat events (`MessageSent` and `MessageDeleted`) implement `ShouldBroadcast`, so Laravel queues them for broadcasting. The queue connection named `database` in `config/queue.php` uses the MongoDB driver's queue connector and stores pending jobs in the `jobs` collection. Set `QUEUE_CONNECTION=database` and keep a queue worker running so those broadcast jobs are processed. The example uses file-based sessions and cache, so a separate SQL database is not needed for those services. The MongoDB URI can be changed to point at another server or an authenticated MongoDB deployment.

## Run the application

Run migrations, then start each process in its own PowerShell terminal. The Reverb server handles WebSocket connections; the queue worker processes broadcast jobs; and Vite serves the frontend assets.

```powershell
php artisan serve
```

```powershell
php artisan reverb:start
```

```powershell
php artisan queue:work
```

```powershell
npm run dev
```

Open `http://127.0.0.1:8000`, register two user accounts, and sign in to each account in a separate browser or private window to try real-time messaging. `npm run build` can be used instead of the Vite development server to compile frontend assets.

## Chat flow

1. `/chat` is protected by the `auth` middleware. The registration and login routes create and authenticate users stored through the MongoDB-backed `User` model.
2. The chat page lists other registered users. Selecting one calls `startConversation`; `ConversationService` sorts the two user IDs and builds a stable conversation key, then finds or creates one conversation for that pair. The conversation records the participant IDs and latest message time.
3. `sendMessage` validates a non-empty message of at most 5,000 characters and verifies that the signed-in user belongs to the selected conversation. `MessageService` saves the message, marks it read by its sender, and updates the conversation's `last_message_at`.
4. `MessageSent` is broadcast to the private conversation channel and recipient's private user channel. `routes/channels.php` authorizes private conversation subscriptions only for participants. Echo listens for incoming messages and refreshes the Livewire component; messages arriving in the currently open conversation are marked read.
5. Opening a conversation also marks its unread incoming messages as read. The sidebar counts unread messages for other conversations. The interface initially loads the latest 30 messages and can request older messages in additional batches.
6. Only the original sender can delete a message. Deletion is a soft delete (`deleted_at` and `deleted_by` are recorded); the chat displays a deletion notice and broadcasts `MessageDeleted` so the other participant's view refreshes.
7. The `online` presence channel reports connected users. While a conversation is open, Echo client whispers carry typing indicators to the other participant.

## MongoDB collections

The Laravel MongoDB package uses the conventional `users`, `conversations`, and `messages` collections. Collections are populated as the application creates records. The conversation migration creates a unique index on `conversation_key` so each pair has one conversation.

Conversation document fields include `conversation_key`, `participants` (the two user IDs), and `last_message_at`. Message documents include `conversation_id`, `sender_id`, `body`, `read_by`, `deleted_at`, and `deleted_by`, with timestamps. User passwords are stored as hashes.

## Useful commands

```powershell
php artisan migrate:status
php artisan test
```
