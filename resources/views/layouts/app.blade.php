<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
        <script src="/vendor/flasher/flasher.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        @flasher_render
        <script>
            (function(){
                if (!window.fetch) return;
                const origFetch = window.fetch;
                window.fetch = async function(input, init){
                    const res = await origFetch(input, init);
                    try {
                        if (window.flasher && res && res.headers) {
                            const h1 = res.headers.get('X-Flasher');
                            const h2 = res.headers.get('X-Flash');
                            const payload = h1 || h2;
                            if (payload) {
                                const data = JSON.parse(payload);
                                if (Array.isArray(data) || typeof data === 'object') {
                                    window.flasher.render(data);
                                }
                            }
                        }
                    } catch (e) { /* ignore */ }
                    return res;
                };
            })();
        </script>
        @if (session('success') || session('error') || session('info') || session('warning'))
        <script>
            (function(){
                var s = @json(session('success'));
                var e = @json(session('error'));
                var i = @json(session('info'));
                var w = @json(session('warning'));
                function show(type, msg){
                    if (!msg) return;
                    if (window.flasher && typeof window.flasher[type] === 'function') {
                        window.flasher[type](msg);
                        return;
                    }
                    if (window.Swal && typeof window.Swal.fire === 'function') {
                        window.Swal.fire({toast:true,position:'top-end',timer:3000,showConfirmButton:false,icon:type,title:msg});
                        return;
                    }
                    try { alert(msg); } catch(_) {}
                }
                show('success', s);
                show('error', e);
                show('info', i);
                show('warning', w);
            })();
        </script>
        @endif
    </body>
</html>
