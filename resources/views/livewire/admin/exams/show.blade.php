<div class="p-4 sm:p-6 lg:p-8 max-w-5xl mx-auto">

    {{-- Breadcrumb / back --}}
    <div class="mb-6">
        <a href="{{ route('admin.exams.index') }}" wire:navigate class="text-sm text-neutral-500 hover:text-primary-600 transition-colors">
            ← العودة لقائمة الاختبارات
        </a>
    </div>

    {{-- Header card --}}
    <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden mb-6">

        @php
            $statusColors = [
                'in_progress'    => ['bg' => 'bg-sky-50',     'text' => 'text-sky-700',     'border' => 'border-sky-200'],
                'completed'      => ['bg' => 'bg-neutral-50', 'text' => 'text-neutral-700', 'border' => 'border-neutral-200'],
                'pending_review' => ['bg' => 'bg-amber-50',   'text' => 'text-amber-700',   'border' => 'border-amber-200'],
                'approved'       => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200'],
                'rejected'       => ['bg' => 'bg-rose-50',    'text' => 'text-rose-700',    'border' => 'border-rose-200'],
            ];
            $sc = $statusColors[$exam->status?->value] ?? $statusColors['completed'];
        @endphp

        <div class="px-6 py-5 border-b border-neutral-200 flex items-start justify-between flex-wrap gap-3">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <h1 class="text-xl font-bold text-neutral-900">اختبار #{{ $exam->id }}</h1>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $sc['bg'] }} {{ $sc['text'] }} border {{ $sc['border'] }}">
                        {{ $exam->status?->label() }}
                    </span>
                    @if($exam->is_approved)
                        <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">معتمد</span>
                    @endif
                </div>
                <p class="text-sm text-neutral-500">
                    {{ $exam->exam_type?->label() }} ·
                    المحاولة رقم {{ $exam->attempt_number }} ·
                    {{ $exam->source?->value === 'web' ? 'من المتصفح' : 'من التطبيق' }}
                </p>
            </div>

            @if($exam->total_score !== null)
                <div class="text-end">
                    <p class="text-4xl font-black {{ $exam->is_passed ? 'text-success-600' : 'text-danger-500' }}">
                        {{ number_format($exam->total_score, 1) }}
                        <span class="text-sm font-normal text-neutral-400">/ 100</span>
                    </p>
                    <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-full {{ $exam->is_passed ? 'bg-success-50 text-success-700' : 'bg-danger-50 text-danger-600' }}">
                        {{ $exam->is_passed ? '✓ مجاز' : '✗ غير مجاز' }}
                    </span>
                </div>
            @endif
        </div>

        {{-- Student / examiner info --}}
        <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x md:divide-x-reverse divide-neutral-100">
            <div class="px-6 py-4">
                <p class="text-xs text-neutral-400 mb-1">الطالب</p>
                <p class="font-semibold text-neutral-900">{{ $exam->student?->fullName() }}</p>
                <p class="text-xs text-neutral-500 font-mono mt-0.5">{{ $exam->student?->national_id }}</p>
                @if($exam->student?->gender)
                    <span class="inline-block mt-1.5 text-xs px-2 py-0.5 rounded-full {{ $exam->student->gender->value === 'male' ? 'bg-sky-50 text-sky-700' : 'bg-pink-50 text-pink-700' }}">
                        {{ $exam->student->gender->label() }}
                    </span>
                @endif
            </div>
            <div class="px-6 py-4">
                <p class="text-xs text-neutral-400 mb-1">المختبر</p>
                <p class="font-semibold text-neutral-900">{{ $exam->examiner?->fullName() ?? '—' }}</p>
                <p class="text-xs text-neutral-500 font-mono mt-0.5">{{ $exam->examiner?->national_id }}</p>
            </div>
        </div>

        {{-- Timestamps --}}
        <div class="grid grid-cols-2 md:grid-cols-3 divide-x divide-x-reverse divide-neutral-100 border-t border-neutral-100 bg-neutral-50/50">
            <div class="px-6 py-3">
                <p class="text-xs text-neutral-400">بدأ</p>
                <p class="text-sm text-neutral-700 mt-0.5">{{ $exam->started_at?->format('Y-m-d H:i') ?? '—' }}</p>
            </div>
            <div class="px-6 py-3">
                <p class="text-xs text-neutral-400">انتهى</p>
                <p class="text-sm text-neutral-700 mt-0.5">{{ $exam->completed_at?->format('Y-m-d H:i') ?? '—' }}</p>
            </div>
            <div class="px-6 py-3">
                <p class="text-xs text-neutral-400">المدة</p>
                <p class="text-sm text-neutral-700 mt-0.5">
                    @if($exam->started_at && $exam->completed_at)
                        {{ $exam->started_at->diffInMinutes($exam->completed_at) }} دقيقة
                    @else
                        —
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- Score breakdown --}}
    <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-neutral-200">
            <h2 class="font-bold text-neutral-900">تفاصيل الدرجات</h2>
        </div>

        <div class="divide-y divide-neutral-100">
            @foreach($exam->questions as $q)
                <div class="px-6 py-4">
                    <div class="flex items-start justify-between mb-2">
                        <h3 class="font-semibold text-neutral-900">السؤال {{ $q->question_number }}</h3>
                        <p class="text-2xl font-black {{ $q->final_score >= 25 ? 'text-success-600' : ($q->final_score >= 15 ? 'text-warning-600' : 'text-danger-500') }}">
                            {{ number_format($q->final_score, 1) }}
                            <span class="text-xs font-normal text-neutral-400">/ 30</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-4 text-sm">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-exam-error"></span>
                            <span class="text-neutral-500">أخطاء:</span>
                            <span class="font-bold text-exam-error">{{ $q->errors_count }}</span>
                            <span class="text-xs text-neutral-400">(−{{ $q->errors_count * 2 }})</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-exam-warning"></span>
                            <span class="text-neutral-500">تنبيهات:</span>
                            <span class="font-bold text-exam-warning">{{ $q->warnings_count }}</span>
                            <span class="text-xs text-neutral-400">(−{{ $q->warnings_count }})</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-exam-continuation"></span>
                            <span class="text-neutral-500">استرسالات:</span>
                            <span class="font-bold text-exam-continuation">{{ $q->continuations_count }}</span>
                            <span class="text-xs text-neutral-400">(−{{ $q->continuations_count * 0.5 }})</span>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Rulings --}}
            <div class="px-6 py-4 bg-neutral-50/50 flex items-center justify-between">
                <h3 class="font-semibold text-neutral-900">الأحكام</h3>
                <p class="text-2xl font-black text-primary-600">
                    {{ $exam->rulings_score !== null ? number_format($exam->rulings_score, 1) : '—' }}
                    <span class="text-xs font-normal text-neutral-400">/ 10</span>
                </p>
            </div>

            {{-- Total --}}
            <div class="px-6 py-4 bg-primary-50/30 flex items-center justify-between">
                <h3 class="font-bold text-neutral-900">المجموع</h3>
                <p class="text-3xl font-black {{ $exam->is_passed ? 'text-success-600' : 'text-danger-500' }}">
                    {{ $exam->total_score !== null ? number_format($exam->total_score, 1) : '—' }}
                    <span class="text-sm font-normal text-neutral-400">/ 100</span>
                </p>
            </div>
        </div>
    </div>

    {{-- Conflict info if any --}}
    @if($exam->conflict_reason)
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
            <p class="text-sm font-bold text-amber-800 mb-1">سبب التعارض</p>
            <p class="text-sm text-amber-700">{{ $exam->conflict_reason }}</p>
        </div>
    @endif

    {{-- Re-exam permit info --}}
    @if($exam->reexamPermit)
        <div class="bg-white rounded-2xl border border-neutral-200 p-5 mb-6">
            <p class="text-sm font-bold text-neutral-700 mb-2">إذن إعادة الاختبار</p>
            <p class="text-xs text-neutral-500">
                الرمز: <span class="font-mono">{{ $exam->reexamPermit->permit_code }}</span>
            </p>
        </div>
    @endif

</div>
