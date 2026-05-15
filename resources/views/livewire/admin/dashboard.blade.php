<div class="min-h-screen bg-neutral-50">

    {{-- Top bar --}}
    <header class="bg-white border-b border-neutral-200 px-6 py-4 flex items-center justify-between">
        <h1 class="text-xl font-bold text-primary-500">أكاديمية الإتقان — لوحة التحكم</h1>

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
            <div class="w-16 h-16 rounded-full bg-primary-50 flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-neutral-900 mb-2">مرحباً، {{ $user->first_name }}!</h2>
            <p class="text-neutral-500">لوحة التحكم قيد الإنشاء. ستتوفر قريباً.</p>
        </div>
    </main>

</div>
