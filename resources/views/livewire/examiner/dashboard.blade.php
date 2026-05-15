<div class="min-h-screen bg-neutral-50">

    {{-- Top bar --}}
    <header class="bg-white border-b border-neutral-200 px-6 py-4 flex items-center justify-between">
        <h1 class="text-xl font-bold text-primary-500">أكاديمية الإتقان — واجهة المختبر</h1>

        <div class="flex items-center gap-4">
            <div class="text-end">
                <p class="text-sm font-medium text-neutral-900">{{ $user->fullName() }}</p>
                <p class="text-xs text-neutral-500">{{ $user->role->label() }}</p>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:button type="submit" variant="ghost" size="sm">
                    تسجيل الخروج
                </flux:button>
            </form>
        </div>
    </header>

    {{-- Placeholder content --}}
    <main class="p-8">
        <div class="max-w-lg mx-auto text-center mt-16">
            <div class="w-16 h-16 rounded-full bg-secondary-50 flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-secondary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-neutral-900 mb-2">مرحباً، {{ $user->first_name }}!</h2>
            <p class="text-neutral-500">واجهة الاختبار قيد الإنشاء. ستتوفر قريباً.</p>
        </div>
    </main>

</div>
