<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Messenger' }} - MSIT</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">

    @unless (request()->routeIs('chat.index'))
        <header class="border-b border-gray-200 bg-white">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">

                <a href="{{ url('/') }}" class="text-xl font-bold tracking-tight text-gray-900">
                    IT502
                </a>

                <nav class="flex items-center gap-4">
                    @auth
                        <span class="hidden text-sm text-gray-600 sm:inline">
                            {{ auth()->user()->name }}
                        </span>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <button type="submit"
                                class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900">
                                Logout
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 hover:text-indigo-600">
                            Login
                        </a>
                    @endauth
                </nav>

            </div>
        </header>
    @endunless

    <main class="{{ request()->routeIs('chat.index') ? 'min-h-screen' : '' }}">
        {{ $slot }}
    </main>

    @livewireScripts
</body>

</html>
