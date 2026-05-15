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
    @fluxAppearance
    @livewireStyles
</head>
<body class="bg-neutral-50 min-h-screen antialiased">

    <div class="min-h-screen flex flex-col lg:flex-row">

        {{-- ═══════════════ Brand panel (right on RTL desktop, top on mobile) ═══════════════ --}}
        <aside class="relative lg:w-1/2 flex flex-col items-center justify-center p-8 lg:p-12
                      bg-gradient-to-br from-primary-700 via-primary-600 to-primary-800
                      text-white overflow-hidden">

            {{-- Decorative shapes --}}
            <div class="absolute -top-24 -end-24 w-72 h-72 rounded-full bg-white/5 blur-2xl"></div>
            <div class="absolute -bottom-24 -start-24 w-96 h-96 rounded-full bg-white/5 blur-3xl"></div>
            <div class="absolute inset-0 opacity-[0.03]"
                 style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 24px 24px;"></div>

            <div class="relative text-center max-w-md">

                {{-- Logo --}}
                <div class="bg-white/95 rounded-3xl p-6 mb-8 inline-block shadow-2xl shadow-primary-900/40 ring-1 ring-white/20">
                    <img src="{{ asset('images/logo.png') }}"
                         alt="أكاديمية الإتقان"
                         class="w-44 h-44 lg:w-56 lg:h-56 object-contain mx-auto"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                    {{-- Fallback if logo file missing --}}
                    <div class="w-44 h-44 lg:w-56 lg:h-56 mx-auto items-center justify-center text-primary-700 font-black text-6xl"
                         style="display:none;">إتقان</div>
                </div>

                <h1 class="text-3xl lg:text-4xl font-black mb-3 tracking-tight">أكاديمية الإتقان</h1>
                <p class="text-primary-100 text-sm lg:text-base font-medium mb-1">لتعليم القرآن الكريم</p>
                <p class="text-primary-200/80 text-xs lg:text-sm">فلسطين — غـزة</p>

                <div class="mt-8 pt-8 border-t border-white/15 max-w-xs mx-auto">
                    <p class="text-primary-100/90 text-xs lg:text-sm leading-relaxed">
                        نظام إدارة الاختبارات القرآنية — بوابة المختبرين والإداريين
                    </p>
                </div>

            </div>
        </aside>

        {{-- ═══════════════ Form panel (left on RTL desktop, bottom on mobile) ═══════════════ --}}
        <main class="flex-1 flex items-center justify-center p-6 lg:p-12">
            <div class="w-full max-w-md">
                {{ $slot }}
            </div>
        </main>

    </div>

    @livewireScripts
    @fluxScripts
</body>
</html>
