<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

    <title inertia>{{ config('app.name', 'Kasir') }}</title>
     <!-- <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon"> -->
    <link rel="icon" type="image/png" href="/assets/logo/LogoPopsnap.png" />

    <!-- Fonts - Preconnect for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Scripts -->
    @routes
    <script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2.4/qz-tray.js"></script>
    @viteReactRefresh
    @vite('resources/js/app.jsx')
    @inertiaHead
    <script>
        // Auto-refresh CSRF token setiap 30 menit agar tidak token mismatch
        setInterval(function() {
            fetch('/csrf-token', { credentials: 'same-origin' })
                .then(r => r.json())
                .then(data => {
                    if (data.token) {
                        document.querySelector('meta[name="csrf-token"]').setAttribute('content', data.token);
                    }
                }).catch(function(){});
        }, 30 * 60 * 1000);
    </script>
    <style>
        body.dark {
            background-color: rgb(2 6 23);
        }

        body.light {
            background-color: rgb(248 250 252);
        }
    </style>
</head>

<body class="font-sans antialiased bg-slate-50 transition-colors duration-200" onload="setInitialTheme()">

    @inertia
    <script>
        function setInitialTheme() {
            const darkMode = localStorage.getItem('darkMode') === 'true';
            if (darkMode) {
                document.body.classList.add('dark');
                document.body.classList.remove('light');
            } else {
                document.body.classList.add('light');
                document.body.classList.remove('dark');
            }
        }
    </script>
</body>

</html>