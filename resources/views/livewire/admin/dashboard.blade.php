<div class="p-4 sm:p-6 lg:p-8">

    <div class="mb-6 sm:mb-8">
        <h2 class="text-xl sm:text-2xl font-bold text-neutral-900">مرحباً، {{ $user->first_name }}</h2>
        <p class="text-neutral-500 text-xs sm:text-sm mt-1">نظرة عامة على نظام الاختبارات</p>
    </div>

    {{-- Stats cards --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-4 mb-6 sm:mb-8 lg:grid-cols-4">

        <div class="bg-white rounded-xl border border-neutral-200 p-4 sm:p-5">
            <p class="text-xs sm:text-sm text-neutral-500 mb-1">إجمالي الطلاب</p>
            <p class="text-2xl sm:text-3xl font-bold text-primary-500">{{ number_format($totalStudents) }}</p>
        </div>

        <div class="bg-white rounded-xl border border-neutral-200 p-4 sm:p-5">
            <p class="text-xs sm:text-sm text-neutral-500 mb-1">إجمالي الاختبارات</p>
            <p class="text-2xl sm:text-3xl font-bold text-primary-500">{{ number_format($totalExams) }}</p>
        </div>

        <div class="bg-white rounded-xl border border-neutral-200 p-4 sm:p-5">
            <p class="text-xs sm:text-sm text-neutral-500 mb-1">الاختبارات المعتمدة</p>
            <p class="text-2xl sm:text-3xl font-bold text-success-500">
                {{ number_format($approvedExams) }}
            </p>
        </div>

        <div class="bg-white rounded-xl border border-neutral-200 p-4 sm:p-5">
            <p class="text-xs sm:text-sm text-neutral-500 mb-1">المستخدمون</p>
            <p class="text-2xl sm:text-3xl font-bold text-primary-500">{{ $totalUsers }}</p>
        </div>

    </div>

    {{-- Quick links --}}
    <div class="grid grid-cols-1 gap-3 sm:gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <a href="{{ route('admin.exams.index') }}" wire:navigate class="bg-white rounded-xl border border-neutral-200 p-4 sm:p-5 hover:border-primary-300 hover:shadow-sm transition-all group">
            <p class="font-medium text-neutral-900 group-hover:text-primary-600">كل الاختبارات</p>
            <p class="text-xs sm:text-sm text-neutral-500 mt-1">تصفح وفلترة جميع الاختبارات</p>
        </a>
        <a href="{{ route('admin.students') }}" wire:navigate class="bg-white rounded-xl border border-neutral-200 p-4 sm:p-5 hover:border-primary-300 hover:shadow-sm transition-all group">
            <p class="font-medium text-neutral-900 group-hover:text-primary-600">إدارة الطلاب</p>
            <p class="text-xs sm:text-sm text-neutral-500 mt-1">عرض وإدارة سجلات الطلاب</p>
        </a>
        <a href="{{ route('admin.users') }}" wire:navigate class="bg-white rounded-xl border border-neutral-200 p-4 sm:p-5 hover:border-primary-300 hover:shadow-sm transition-all group">
            <p class="font-medium text-neutral-900 group-hover:text-primary-600">إدارة المستخدمين</p>
            <p class="text-xs sm:text-sm text-neutral-500 mt-1">المختبرون والمديرون</p>
        </a>
        <a href="{{ route('admin.settings') }}" wire:navigate class="bg-white rounded-xl border border-neutral-200 p-4 sm:p-5 hover:border-primary-300 hover:shadow-sm transition-all group">
            <p class="font-medium text-neutral-900 group-hover:text-primary-600">إعدادات النظام</p>
            <p class="text-xs sm:text-sm text-neutral-500 mt-1">درجة الإجازة وإعدادات أخرى</p>
        </a>
    </div>

</div>
