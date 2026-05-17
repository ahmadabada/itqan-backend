<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'لوحة التحكم' }} | أكاديمية الإتقان</title>

    <link rel="icon" type="image/png" href="{{ asset('images/photoshop-light.png') }}" media="(prefers-color-scheme: light)">
    <link rel="icon" type="image/png" href="{{ asset('images/photoshop-dark.png') }}" media="(prefers-color-scheme: dark)">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=tajawal:400,500,700,900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    @livewireStyles
</head>
<body class="bg-neutral-100 text-neutral-900 antialiased h-[100dvh] overflow-hidden" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
    <flux:toast />

    @php
        $navGroups = [
            [
                'title' => 'عام',
                'items' => [
                    ['route' => 'admin.dashboard', 'label' => 'لوحة التحكم', 'pattern' => 'admin/dashboard', 'icon' => 'home'],
                ],
            ],
            [
                'title' => 'الاختبارات',
                'items' => [
                    ['route' => 'admin.exams.index',   'label' => 'كل الاختبارات', 'pattern' => 'admin/exams', 'icon' => 'list'],
                    ['route' => 'admin.exams.pending', 'label' => 'مراجعة التعارضات', 'pattern' => 'admin/exams/pending*', 'icon' => 'alert', 'badge_route' => 'pending'],
                ],
            ],
            [
                'title' => 'البيانات',
                'items' => [
                    ['route' => 'admin.students',             'label' => 'الطلاب', 'pattern' => 'admin/students*', 'icon' => 'users'],
                    ['route' => 'admin.recitation-questions', 'label' => 'بنك الأسئلة', 'pattern' => 'admin/recitation-questions*', 'icon' => 'book'],
                    ['route' => 'admin.users',                'label' => 'المستخدمون', 'pattern' => 'admin/users*', 'icon' => 'shield'],
                    ['route' => 'admin.permits',              'label' => 'أذونات الإعادة', 'pattern' => 'admin/permits*', 'icon' => 'key'],
                ],
            ],
            [
                'title' => 'النظام',
                'items' => [
                    ['route' => 'admin.settings', 'label' => 'الإعدادات', 'pattern' => 'admin/settings', 'icon' => 'cog'],
                ],
            ],
        ];

        $pendingCount = \App\Models\Exam::where('status', 'pending_review')->count();

        $iconPaths = [
            'home'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12 12 2.25 21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>',
            'list'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/>',
            'alert'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>',
            'users'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>',
            'shield' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z"/>',
            'key'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z"/>',
            'cog'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>',
            'book'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>',
        ];

        // Determine current page title from route
        $currentPath  = request()->path();
        $pageTitle    = 'لوحة التحكم';
        foreach ($navGroups as $group) {
            foreach ($group['items'] as $item) {
                if (request()->is($item['pattern'])) {
                    $pageTitle = $item['label'];
                    break 2;
                }
            }
        }
    @endphp

    {{-- Mobile backdrop --}}
    <div
        x-show="sidebarOpen"
        x-transition.opacity
        @click="sidebarOpen = false"
        class="fixed inset-0 bg-black/40 z-40 lg:hidden"
        style="display: none;"
    ></div>

    {{-- ════════════════════ Sidebar (always fixed on the right in RTL) ════════════════════ --}}
    <aside
        class="fixed top-0 bottom-0 right-0 w-64 bg-white border-l border-neutral-200 flex flex-col z-50 transition-transform duration-300 ease-out lg:translate-x-0"
        :class="sidebarOpen ? 'translate-x-0 shadow-2xl' : 'translate-x-full lg:translate-x-0 lg:shadow-none'"
    >

            {{-- Brand --}}
            <div class="px-5 py-5 border-b border-neutral-200 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary-500 flex items-center justify-center text-white font-black text-base shadow-sm shadow-primary-500/30">
                    إ
                </div>
                <div class="leading-tight flex-1 min-w-0">
                    <p class="text-sm font-bold text-neutral-900 truncate">أكاديمية الإتقان</p>
                    <p class="text-[10px] text-neutral-400 mt-0.5">نظام الاختبارات القرآنية</p>
                </div>
                {{-- Close button (mobile only) --}}
                <button
                    @click="sidebarOpen = false"
                    class="lg:hidden p-1 text-neutral-400 hover:text-neutral-700 transition-colors"
                    aria-label="إغلاق القائمة"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Navigation (grouped) --}}
            <nav class="flex-1 overflow-y-auto py-3">
                @foreach($navGroups as $group)
                    <div class="px-5 mt-4 first:mt-0 mb-2">
                        <p class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">{{ $group['title'] }}</p>
                    </div>
                    <div class="px-3 space-y-0.5">
                        @foreach($group['items'] as $item)
                            @php $active = request()->is($item['pattern']); @endphp
                            <a href="{{ route($item['route']) }}" wire:navigate
                               @click="sidebarOpen = false"
                               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all relative {{ $active ? 'bg-primary-50 text-primary-700 font-semibold' : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900' }}">
                                @if($active)
                                    <span class="absolute end-0 top-1/2 -translate-y-1/2 w-1 h-6 bg-primary-500 rounded-s"></span>
                                @endif
                                <svg class="w-5 h-5 flex-shrink-0 {{ $active ? 'text-primary-600' : 'text-neutral-400' }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    {!! $iconPaths[$item['icon']] ?? '' !!}
                                </svg>
                                <span class="flex-1">{{ $item['label'] }}</span>
                                @if(($item['badge_route'] ?? null) === 'pending' && $pendingCount > 0)
                                    <span class="text-[10px] bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full font-bold">
                                        {{ $pendingCount }}
                                    </span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </nav>

            {{-- User footer --}}
            <div class="p-3 border-t border-neutral-200 flex-shrink-0">
                @auth
                    <div class="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-neutral-50 transition-colors">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                            {{ mb_substr(auth()->user()->first_name, 0, 1) }}
                        </div>
                        <div class="min-w-0 flex-1 leading-tight">
                            <p class="text-sm font-medium text-neutral-800 truncate">{{ auth()->user()->fullName() }}</p>
                            <p class="text-[10px] text-neutral-400">{{ auth()->user()->role->label() }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" title="تسجيل الخروج" class="text-neutral-400 hover:text-danger-500 transition-colors p-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                @endauth
            </div>

    </aside>

    {{-- ════════════════════ Main (offset on lg by sidebar width on the right) ════════════════════ --}}
    <div class="h-[100dvh] flex flex-col lg:mr-64 min-h-0">

        {{-- Top bar / header --}}
        <header class="bg-white border-b border-neutral-200 z-20 flex-shrink-0">
            <div class="px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-3">

                <div class="flex items-center gap-3 min-w-0">
                    {{-- Hamburger (mobile only) --}}
                    <button
                        @click="sidebarOpen = true"
                        class="lg:hidden p-2 -ms-2 rounded-lg text-neutral-600 hover:bg-neutral-100 transition-colors"
                        aria-label="فتح القائمة"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/>
                        </svg>
                    </button>

                    <div class="min-w-0">
                        <h1 class="text-sm sm:text-base font-bold text-neutral-900 truncate">{{ $pageTitle }}</h1>
                        <p class="text-[10px] sm:text-xs text-neutral-400 truncate">{{ now()->translatedFormat('l، j F Y') }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
                    @if($pendingCount > 0)
                        <a href="{{ route('admin.exams.pending') }}" wire:navigate
                           class="flex items-center gap-2 px-2.5 sm:px-3 py-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 text-xs font-medium transition-colors"
                           title="{{ $pendingCount }} اختبار بانتظار المراجعة"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                            <span class="hidden sm:inline">{{ $pendingCount }} اختبار بانتظار المراجعة</span>
                            <span class="sm:hidden font-bold">{{ $pendingCount }}</span>
                        </a>
                    @endif

                    @auth
                        <span class="text-xs text-neutral-400 hidden md:block">
                            مرحباً، <span class="text-neutral-700 font-medium">{{ auth()->user()->first_name }}</span>
                        </span>
                    @endauth
                </div>
            </div>
        </header>

        <main class="flex-1 min-h-0 overflow-y-auto overscroll-contain">
            {{ $slot }}
        </main>

    </div>

    @livewireScripts
    @fluxScripts
</body>
</html>
