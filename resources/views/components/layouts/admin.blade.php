<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{{ $title ?? 'Admin Panel' }} - {{ config('app.name', 'RshopRefills') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased">
        <div class="flex h-screen overflow-hidden">
            {{-- Sidebar Placeholder --}}
            <aside class="hidden w-64 flex-col border-r border-zinc-200 bg-white md:flex">
                <div class="flex h-16 items-center px-6">
                    <span class="text-lg font-bold text-blue-600">Rshop Admin</span>
                </div>
                <nav class="flex-1 space-y-1 px-4 py-4">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center rounded-md bg-zinc-100 px-3 py-2 text-sm font-medium text-zinc-900">
                        Dashboard
                    </a>
                    {{-- Add more admin links here in the future --}}
                </nav>
            </aside>

            <div class="flex flex-1 flex-col overflow-hidden">
                {{-- Top Navbar Placeholder --}}
                <header class="flex h-16 shrink-0 items-center justify-between border-b border-zinc-200 bg-white px-6">
                    <div class="flex items-center md:hidden">
                        <span class="text-lg font-bold text-blue-600">Rshop Admin</span>
                    </div>
                    <div class="flex flex-1 items-center justify-end">
                        <div class="flex items-center gap-4">
                            <span class="text-sm font-medium text-zinc-700">{{ Auth::guard('admin')->user()?->name }}</span>
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" class="text-sm text-zinc-500 hover:text-zinc-700">Logout</button>
                            </form>
                        </div>
                    </div>
                </header>

                {{-- Main Content --}}
                <main class="flex-1 overflow-y-auto p-6">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @fluxScripts
    </body>
</html>
