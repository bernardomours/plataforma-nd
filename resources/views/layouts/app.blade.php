<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Plataforma ND</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/tom-select.complete.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    </head>
    <body class="font-sans antialiased bg-gray-100">
        <div>
            <livewire:layout.navigation />

            <div class="md:pl-64 pt-16 min-h-screen flex flex-col">
                
                @if (isset($header))
                    <header class="bg-white border-b border-gray-200 shrink-0">
                        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endif

                <main class="flex-1 p-6">
                    {{ $slot }}
                </main>
                
            </div>
        </div>
    </body>
</html>