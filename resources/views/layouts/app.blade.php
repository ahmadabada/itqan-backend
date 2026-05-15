<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'أكاديمية الإتقان' }} | نظام الاختبارات القرآنية</title>

    {{-- Tajawal font from bunny.net (GDPR-friendly Google Fonts mirror) --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=tajawal:400,500,700,900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    @livewireStyles
</head>
<body class="bg-neutral-50 text-neutral-900 antialiased">
    <flux:toast />
    {{ $slot }}
    @livewireScripts
    @fluxScripts
</body>
</html>
