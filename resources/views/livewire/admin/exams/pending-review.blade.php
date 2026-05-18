<div class="p-4 sm:p-6 lg:p-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5 sm:mb-6 gap-3">
        <div class="min-w-0">
            <h2 class="text-xl sm:text-2xl font-bold text-neutral-900">مراجعة التعارضات</h2>
            <p class="text-neutral-500 text-xs sm:text-sm mt-0.5">
                {{ $this->totalPending }} اختبار بانتظار المراجعة
            </p>
        </div>
    </div>

    {{-- Filter --}}
    <div class="mb-5">
        <flux:select wire:model.live="filterConflict" class="w-64">
            <option value="">كل أنواع التعارض</option>
            <option value="same_student_multiple_offline">نفس الطالب - نفس الجهاز</option>
            <option value="same_student_multiple_devices">نفس الطالب - جهازان مختلفان</option>
            <option value="existing_approved_no_permit">اختبار معتمد بلا إذن</option>
            <option value="manual_student_exists">طالب يدوي موجود مسبقاً</option>
        </flux:select>
    </div>

    {{-- Empty state --}}
    @if($this->exams->isEmpty())
        <div class="bg-white rounded-xl border border-neutral-200 py-16 text-center">
            <p class="text-neutral-400 text-lg">لا توجد تعارضات بانتظار المراجعة</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($this->exams as $exam)
                <div class="bg-white rounded-xl border border-neutral-200 p-6">
                    <div class="flex items-start justify-between gap-4">

                        {{-- Exam info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="text-base font-bold text-neutral-900">
                                    @if($exam->student)
                                        <a href="{{ route('admin.students.show', $exam->student->id) }}" wire:navigate class="hover:text-primary-600 hover:underline transition-colors">
                                            {{ $exam->student->fullName() }}
                                        </a>
                                    @else
                                        طالب غير معروف
                                    @endif
                                </span>
                                <span class="text-xs text-neutral-400 font-mono">
                                    {{ $exam->student?->national_id }}
                                </span>
                                @php
                                    $conflictLabels = [
                                        'same_student_multiple_offline' => ['label' => 'نفس الطالب - نفس الجهاز', 'color' => 'bg-amber-50 text-amber-700'],
                                        'same_student_multiple_devices' => ['label' => 'نفس الطالب - جهازان', 'color' => 'bg-orange-50 text-orange-700'],
                                        'existing_approved_no_permit'   => ['label' => 'بلا إذن إعادة', 'color' => 'bg-red-50 text-red-700'],
                                        'manual_student_exists'         => ['label' => 'طالب يدوي موجود', 'color' => 'bg-purple-50 text-purple-700'],
                                    ];
                                    $conflict = $conflictLabels[$exam->conflict_reason] ?? ['label' => $exam->conflict_reason, 'color' => 'bg-neutral-100 text-neutral-600'];
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $conflict['color'] }}">
                                    {{ $conflict['label'] }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-x-8 gap-y-1 text-sm text-neutral-600 mb-3">
                                <div>
                                    <span class="text-neutral-400">المختبر:</span>
                                    {{ $exam->examiner?->fullName() }}
                                </div>
                                <div>
                                    <span class="text-neutral-400">نوع الاختبار:</span>
                                    {{ $exam->exam_type->value === 'full_quran' ? 'كامل القرآن' : 'نصف القرآن' }}
                                </div>
                                <div>
                                    <span class="text-neutral-400">الدرجة:</span>
                                    <span class="font-semibold {{ $exam->is_passed ? 'text-success-600' : 'text-danger-500' }}">
                                        {{ number_format($exam->total_score, 1) }} / 100
                                    </span>
                                    ({{ $exam->is_passed ? 'مجاز' : 'غير مجاز' }})
                                </div>
                                <div>
                                    <span class="text-neutral-400">المحاولة:</span>
                                    #{{ $exam->attempt_number }}
                                </div>
                                <div>
                                    <span class="text-neutral-400">الجهاز:</span>
                                    <span class="font-mono text-xs">{{ $exam->device_uuid }}</span>
                                </div>
                                <div>
                                    <span class="text-neutral-400">تاريخ الاختبار:</span>
                                    {{ $exam->completed_at?->format('Y-m-d g:i A') }}
                                </div>
                            </div>

                            {{-- Questions breakdown --}}
                            @if($exam->questions->isNotEmpty())
                                <div class="flex gap-4 text-xs text-neutral-500">
                                    @foreach($exam->questions as $q)
                                        <div class="bg-neutral-50 rounded px-3 py-1.5">
                                            <span class="font-medium text-neutral-700">س{{ $q->question_number }}:</span>
                                            {{ number_format($q->final_score, 1) }}/30
                                            @if($q->errors_count || $q->warnings_count || $q->continuations_count)
                                                <span class="text-neutral-400">
                                                    (فتح: {{ $q->errors_count }}, تنبيهات: {{ $q->warnings_count }}, تردد: {{ $q->continuations_count }})
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                    <div class="bg-neutral-50 rounded px-3 py-1.5">
                                        <span class="font-medium text-neutral-700">الأحكام:</span>
                                        {{ number_format($exam->rulings_score, 1) }}/10
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="flex flex-col gap-2 flex-shrink-0">
                            <flux:button
                                wire:click="approve({{ $exam->id }})"
                                wire:confirm="هل أنت متأكد من اعتماد هذا الاختبار؟"
                                variant="primary"
                                size="sm"
                            >
                                اعتماد
                            </flux:button>
                            <flux:button
                                wire:click="reject({{ $exam->id }})"
                                wire:confirm="هل أنت متأكد من رفض هذا الاختبار؟"
                                variant="danger"
                                size="sm"
                            >
                                رفض
                            </flux:button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
