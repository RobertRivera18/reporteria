<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    {{-- Icons & Alerts --}}
    <script src="https://kit.fontawesome.com/f2ff89425f.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])


    {{-- Livewire --}}
    @livewireStyles
</head>

<body class="font-sans antialiased bg-gray-100 text-gray-800">

    {{-- Banner --}}
  

    <div class="min-h-screen flex flex-col">

        {{-- Navigation --}}

        {{-- Header --}}
        @if (isset($header))
            <header class="bg-white border-b">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
                    <div class="flex items-center justify-between">
                        <div class="text-xl font-semibold text-gray-800">
                            {{ $header }}
                        </div>
                    </div>
                </div>
            </header>
        @endif

        {{-- Main Content --}}
        <main class="flex-1">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                {{ $slot }}
            </div>
        </main>

        {{-- Footer --}}
        <footer class="bg-white border-t text-sm text-gray-500">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 text-center">
                © {{ date('Y') }} {{ config('app.name') }} — Todos los derechos reservados
            </div>
        </footer>

    </div>

    {{-- Modals --}}
    @stack('modals')

    {{-- Livewire --}}
    @livewireScripts
    @if (session('swal'))
        <script>
            Swal.fire(@json(session('swal')));
        </script>
    @endif
    {{-- Custom JS --}}
    @stack('js')
</body>

</html>
