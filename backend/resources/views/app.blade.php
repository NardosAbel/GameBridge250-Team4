<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <script>
        try {
            if (localStorage.getItem('theme') === 'light') {
                document.documentElement.classList.remove('dark');
            }
        } catch (e) {}
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'Campus Esports Ethiopia') }}</title>

    <!-- Google Fonts: Inter & Noto Sans Ethiopic -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Ethiopic:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    @viteReactRefresh
    @vite(['resources/js/app.tsx'])
    @inertiaHead
</head>
<body class="font-sans antialiased bg-slate-950 text-slate-100 min-h-screen selection:bg-emerald-500 selection:text-white">
    @inertia
</body>
</html>
