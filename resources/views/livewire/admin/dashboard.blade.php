<div class="p-8">

    <div class="mb-8">
        <h2 class="text-2xl font-bold text-neutral-900">مرحباً، {{ $user->first_name }}</h2>
        <p class="text-neutral-500 mt-1">لوحة التحكم — {{ now()->translatedFormat('l، j F Y') }}</p>
    </div>

    {{-- Stats cards --}}
    <div class="grid grid-cols-2 gap-4 mb-8 lg:grid-cols-4">

        <div class="bg-white rounded-xl border border-neutral-200 p-5">
            <p class="text-sm text-neutral-500 mb-1">إجمالي الطلاب</p>
            <p class="text-3xl font-bold text-primary-500">{{ number_format($totalStudents) }}</p>
        </div>

        <div class="bg-white rounded-xl border border-neutral-200 p-5">
            <p class="text-sm text-neutral-500 mb-1">إجمالي الاختبارات</p>
            <p class="text-3xl font-bold text-primary-500">{{ number_format($totalExams) }}</p>
        </div>

        <div class="bg-white rounded-xl border border-neutral-200 p-5">
            <p class="text-sm text-neutral-500 mb-1">بانتظار المراجعة</p>
            <p class="text-3xl font-bold {{ $pendingExams > 0 ? 'text-warning-500' : 'text-neutral-400' }}">
                {{ $pendingExams }}
            </p>
        </div>

        <div class="bg-white rounded-xl border border-neutral-200 p-5">
            <p class="text-sm text-neutral-500 mb-1">المستخدمون</p>
            <p class="text-3xl font-bold text-primary-500">{{ $totalUsers }}</p>
        </div>

    </div>

    {{-- Quick links --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <a href="{{ route('admin.students') }}" wire:navigate class="bg-white rounded-xl border border-neutral-200 p-5 hover:border-primary-300 hover:shadow-sm transition-all group">
            <p class="font-medium text-neutral-900 group-hover:text-primary-600">إدارة الطلاب</p>
            <p class="text-sm text-neutral-500 mt-1">عرض وإدارة سجلات الطلاب</p>
        </a>
        <a href="{{ route('admin.users') }}" wire:navigate class="bg-white rounded-xl border border-neutral-200 p-5 hover:border-primary-300 hover:shadow-sm transition-all group">
            <p class="font-medium text-neutral-900 group-hover:text-primary-600">إدارة المستخدمين</p>
            <p class="text-sm text-neutral-500 mt-1">المختبرون والمديرون</p>
        </a>
        <a href="{{ route('admin.settings') }}" wire:navigate class="bg-white rounded-xl border border-neutral-200 p-5 hover:border-primary-300 hover:shadow-sm transition-all group">
            <p class="font-medium text-neutral-900 group-hover:text-primary-600">إعدادات النظام</p>
            <p class="text-sm text-neutral-500 mt-1">درجة الإجازة وإعدادات أخرى</p>
        </a>
    </div>

</div>
