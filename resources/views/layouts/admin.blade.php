<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'لوحة التحكم' }} | أكاديمية الإتقان</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=tajawal:400,500,700,900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxStyles
    @livewireStyles
</head>
<body class="bg-neutral-50 text-neutral-900 antialiased">
    <flux:toast />

    <div class="flex min-h-screen">

        {{-- Sidebar (appears on right in RTL flex) --}}
        <aside class="w-64 bg-white border-e border-neutral-200 flex flex-col flex-shrink-0 sticky top-0 h-screen overflow-y-auto">

            {{-- Brand --}}
            <div class="px-5 py-5 border-b border-neutral-200">
                <p class="text-base font-black text-primary-500 leading-tight">أكاديمية الإتقان</p>
                <p class="text-xs text-neutral-400 mt-0.5">نظام الاختبارات القرآنية</p>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 p-3 space-y-0.5">
                @php
                    $navItems = [
                        ['route' => 'admin.dashboard',      'label' => 'لوحة التحكم',      'pattern' => 'admin/dashboard'],
                        ['route' => 'admin.users',          'label' => 'المستخدمون',        'pattern' => 'admin/users*'],
                        ['route' => 'admin.students',       'label' => 'الطلاب',            'pattern' => 'admin/students*'],
                        ['route' => 'admin.permits',        'label' => 'أذونات الإعادة',    'pattern' => 'admin/permits*'],
                        ['route' => 'admin.exams.pending',  'label' => 'مراجعة التعارضات', 'pattern' => 'admin/exams/pending*'],
                        ['route' => 'admin.settings',       'label' => 'الإعدادات',         'pattern' => 'admin/settings'],
                    ];
                @endphp

                @foreach($navItems as $item)
                    @php $active = request()->is($item['pattern']); @endphp
                    <a
                        href="{{ route($item['route']) }}"
                        wire:navigate
                        class="flex items-center px-3 py-2 rounded-lg text-sm transition-colors {{ $active ? 'bg-primary-50 text-primary-700 font-medium' : 'text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900' }}"
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            {{-- User footer --}}
            <div class="p-4 border-t border-neutral-200">
                @auth
                    <p class="text-sm font-medium text-neutral-800 truncate">{{ auth()->user()->fullName() }}</p>
                    <p class="text-xs text-neutral-400 mb-3">{{ auth()->user()->role->label() }}</p>
                @endauth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-neutral-500 hover:text-danger-500 transition-colors">
                        تسجيل الخروج
                    </button>
                </form>
            </div>

        </aside>

        {{-- Main content --}}
        <main class="flex-1 min-w-0 overflow-auto">
            {{ $slot }}
        </main>

    </div>

    @livewireScripts
    @fluxScripts
</body>
</html>
