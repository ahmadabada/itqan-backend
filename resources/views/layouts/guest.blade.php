<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'تسجيل الدخول' }} | أكاديمية الإتقان</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=tajawal:400,500,700,900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxStyles
    @livewireStyles
</head>
<body class="bg-primary-50 min-h-screen flex items-center justify-center antialiased">
    <div class="w-full max-w-md px-4">
        {{-- Logo --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-black text-primary-500">أكاديمية الإتقان</h1>
            <p class="text-neutral-500 mt-1">نظام الاختبارات القرآنية</p>
        </div>

        {{ $slot }}
    </div>
    @livewireScripts
    @fluxScripts
</body>
</html>
