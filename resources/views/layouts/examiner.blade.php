<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'واجهة المختبر' }} | أكاديمية الإتقان</title>

    <link rel="icon" type="image/png" href="{{ asset('images/photoshop-light.png') }}" media="(prefers-color-scheme: light)">
    <link rel="icon" type="image/png" href="{{ asset('images/photoshop-dark.png') }}" media="(prefers-color-scheme: dark)">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=tajawal:400,500,700,900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    @livewireStyles
</head>
<body class="bg-neutral-50 text-neutral-900 antialiased min-h-screen flex flex-col">
    <flux:toast />

    {{-- Top bar --}}
    <header class="bg-white border-b border-neutral-200 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">

            <div class="flex items-center gap-8">
                {{-- Brand --}}
                <a href="{{ route('examiner.exam') }}" wire:navigate class="flex items-center gap-2">
                    <div class="w-9 h-9 rounded-xl bg-primary-500 flex items-center justify-center text-white font-black text-sm">إ</div>
                    <div class="leading-tight">
                        <p class="text-sm font-bold text-neutral-900">أكاديمية الإتقان</p>
                        <p class="text-[10px] text-neutral-400">واجهة المختبر</p>
                    </div>
                </a>

                {{-- Nav --}}
                <nav class="hidden md:flex items-center gap-1">
                    @php
                        $navItems = [
                            ['route' => 'examiner.exam',      'label' => 'جلسة اختبار', 'pattern' => 'examiner/exam'],
                            ['route' => 'examiner.exams',     'label' => 'اختباراتي',   'pattern' => 'examiner/exams*'],
                        ];
                    @endphp
                    @foreach($navItems as $item)
                        @php $active = request()->is($item['pattern']); @endphp
                        <a href="{{ route($item['route']) }}" wire:navigate
                           class="px-3 py-1.5 rounded-lg text-sm transition-colors {{ $active ? 'bg-primary-50 text-primary-700 font-semibold' : 'text-neutral-600 hover:bg-neutral-100' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
            </div>

            {{-- User --}}
            <div class="flex items-center gap-3">
                @auth
                    <div class="text-end leading-tight hidden sm:block">
                        <p class="text-sm font-medium text-neutral-800">{{ auth()->user()->fullName() }}</p>
                        <p class="text-[10px] text-neutral-400">{{ auth()->user()->role->label() }}</p>
                    </div>
                    <div class="w-9 h-9 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center font-bold text-sm">
                        {{ mb_substr(auth()->user()->first_name, 0, 1) }}
                    </div>
                @endauth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-xs text-neutral-500 hover:text-danger-500 transition-colors px-2">
                        خروج
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="flex-1">
        {{ $slot }}
    </main>

    @livewireScripts
    @fluxScripts
</body>
</html>
