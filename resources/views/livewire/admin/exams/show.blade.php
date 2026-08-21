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
                <div class="flex items-center gap-3 mb-1 flex-wrap">
                    <h1 class="text-xl font-bold text-neutral-900">اختبار #{{ $exam->id }}</h1>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $sc['bg'] }} {{ $sc['text'] }} border {{ $sc['border'] }}">
                        {{ $exam->status?->label() }}
                    </span>
                    @if($exam->is_authoritative)
                        <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">النتيجة المعتمدة</span>
                    @endif
                    @if($exam->authoritative_decision_by)
                        <span class="text-xs px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200" title="ثبّتها الأدمن يدوياً">مثبّتة يدوياً</span>
                    @endif
                </div>
                <p class="text-sm text-neutral-500">
                    {{ $exam->parts_count }} جزء ({{ $exam->new_memorization_parts }} حفظ جديد) ·
                    المحاولة رقم {{ $exam->attempt_number }} ·
                    {{ $exam->round?->name ?? 'بدون جولة' }} ·
                    {{ $exam->source?->value === 'web' ? 'من المتصفح' : 'من التطبيق' }}
                </p>
            </div>

            <div class="flex items-start gap-4 flex-wrap">
                {{-- Admin override actions. The counted result normally follows
                     newest-wins; an admin can pin a specific approved exam or set
                     one aside. In-progress exams get no button. --}}
                <div class="flex items-center gap-2 flex-wrap">
                    @unless($editing)
                        <flux:button variant="outline" size="sm" icon="pencil-square" wire:click="startEdit">
                            تعديل الاختبار
                        </flux:button>
                    @endunless
                    @if($exam->status?->value === 'approved')
                        @if($exam->authoritative_decision_by)
                            <flux:button variant="outline" size="sm" wire:click="unpin"
                                wire:confirm="إلغاء التثبيت والعودة لاعتماد الأحدث تلقائياً؟">
                                إلغاء التثبيت
                            </flux:button>
                        @elseif(! $exam->is_authoritative)
                            <flux:button variant="primary" size="sm" wire:click="pin"
                                wire:confirm="تثبيت هذا الاختبار كنتيجة معتمدة للطالب؟">
                                تثبيت كنتيجة معتمدة
                            </flux:button>
                        @endif
                        <flux:button variant="danger" size="sm" wire:click="exclude"
                            wire:confirm="استبعاد هذا الاختبار؟">
                            استبعاد الاختبار
                        </flux:button>
                    @elseif($exam->status?->value === 'excluded')
                        <flux:button variant="primary" size="sm" wire:click="approve"
                            wire:confirm="اعتماد هذا الاختبار؟">
                            اعتماد الاختبار
                        </flux:button>
                    @endif
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
        </div>

        {{-- Student / examiner info --}}
        <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x md:divide-x-reverse divide-neutral-100">
            <div class="px-6 py-4">
                <p class="text-xs text-neutral-400 mb-1">الطالب</p>
                <p class="font-semibold text-neutral-900">{{ $exam->student?->fullName() }}</p>
                <p class="text-xs text-neutral-500 font-mono mt-0.5">{{ $exam->student?->national_id }}</p>
                <div class="flex items-center gap-2 flex-wrap mt-2">
                    @if($exam->student?->gender)
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $exam->student->gender->value === 'male' ? 'bg-sky-50 text-sky-700' : 'bg-pink-50 text-pink-700' }}">
                            {{ $exam->student->gender->label() }}
                        </span>
                    @endif
                    @if($exam->student?->halaqah)
                        <span class="text-xs px-2 py-0.5 rounded-full bg-primary-50 text-primary-700 border border-primary-200">
                            حلقة: {{ $exam->student->halaqah->label() }}
                        </span>
                    @endif
                </div>
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
                <p class="text-sm text-neutral-700 mt-0.5">{{ $exam->started_at?->format('Y-m-d g:i A') ?? '—' }}</p>
            </div>
            <div class="px-6 py-3">
                <p class="text-xs text-neutral-400">انتهى</p>
                <p class="text-sm text-neutral-700 mt-0.5">{{ $exam->completed_at?->format('Y-m-d g:i A') ?? '—' }}</p>
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

    {{-- ── Edit form ────────────────────────────────────────────────────────
         Replaces the read-only score breakdown while editing. Question scores and
         the total are derived from the counts (never typed directly), so what the
         admin sees here is exactly what ScoreCalculator will store. --}}
    @if($editing)
        <form wire:submit="saveEdit" class="bg-white rounded-2xl border border-primary-200 overflow-hidden mb-6">

            <div class="px-6 py-4 border-b border-neutral-200 bg-primary-50/40">
                <h2 class="font-bold text-neutral-900">تعديل الاختبار</h2>
                <p class="text-xs text-neutral-500 mt-0.5">
                    تُحتسب درجة كل سؤال والمجموع تلقائياً من عدد الفتحات والتنبيهات والحركات.
                </p>
            </div>

            {{-- Exam-level fields --}}
            <div class="px-6 py-5 grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label class="block text-xs text-neutral-500 mb-1">المختبر</label>
                    <flux:select wire:model="editExaminerId" size="sm">
                        <flux:select.option value="">— اختر المختبر —</flux:select.option>
                        @foreach($examiners as $examiner)
                            <flux:select.option value="{{ $examiner->id }}">{{ $examiner->fullName() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @error('editExaminerId') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs text-neutral-500 mb-1">الجولة</label>
                    <flux:select wire:model="editRoundId" size="sm">
                        <flux:select.option value="">بدون جولة</flux:select.option>
                        @foreach($rounds as $round)
                            <flux:select.option value="{{ $round->id }}">{{ $round->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @error('editRoundId') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs text-neutral-500 mb-1">عدد الأجزاء</label>
                    <flux:input type="number" min="0" max="30" step="0.5" wire:model="editPartsCount" size="sm" />
                    @error('editPartsCount') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs text-neutral-500 mb-1">أجزاء الحفظ الجديد</label>
                    <flux:input type="number" min="0" max="30" step="0.5" wire:model="editNewParts" size="sm" />
                    @error('editNewParts') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs text-neutral-500 mb-1">تاريخ البدء</label>
                    <flux:input type="datetime-local" wire:model="editStartedAt" size="sm" />
                    @error('editStartedAt') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs text-neutral-500 mb-1">تاريخ الانتهاء</label>
                    <flux:input type="datetime-local" wire:model="editCompletedAt" size="sm" />
                    @error('editCompletedAt') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                </div>

            </div>

            {{-- Per-question counts --}}
            <div class="border-t border-neutral-100 divide-y divide-neutral-100">
                @foreach($exam->questions as $q)
                    @php
                        $qErrors        = (int) ($editQuestions[$q->id]['errors_count'] ?? 0);
                        $qWarnings      = (int) ($editQuestions[$q->id]['warnings_count'] ?? 0);
                        $qContinuations = (int) ($editQuestions[$q->id]['continuations_count'] ?? 0);
                        $qScore         = \App\Services\ScoreCalculator::questionScore($qErrors, $qWarnings, $qContinuations);
                    @endphp
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between mb-3 gap-3">
                            <h3 class="font-semibold text-neutral-900">السؤال {{ $q->question_number }}</h3>
                            <p class="text-xl font-black {{ $qScore >= 25 ? 'text-success-600' : ($qScore >= 15 ? 'text-warning-600' : 'text-danger-500') }} whitespace-nowrap">
                                {{ number_format($qScore, 1) }}
                                <span class="text-xs font-normal text-neutral-400">/ 30</span>
                            </p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs text-neutral-500 mb-1">فتح (−{{ config('exam.deductions.error') }} لكل واحدة)</label>
                                <flux:input type="number" min="0" step="1" size="sm"
                                    wire:model.live.debounce.400ms="editQuestions.{{ $q->id }}.errors_count" />
                                @error('editQuestions.'.$q->id.'.errors_count') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs text-neutral-500 mb-1">تنبيهات (−{{ config('exam.deductions.warning') }})</label>
                                <flux:input type="number" min="0" step="1" size="sm"
                                    wire:model.live.debounce.400ms="editQuestions.{{ $q->id }}.warnings_count" />
                                @error('editQuestions.'.$q->id.'.warnings_count') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs text-neutral-500 mb-1">حركة (−{{ config('exam.deductions.continuation') }})</label>
                                <flux:input type="number" min="0" step="1" size="sm"
                                    wire:model.live.debounce.400ms="editQuestions.{{ $q->id }}.continuations_count" />
                                @error('editQuestions.'.$q->id.'.continuations_count') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- Rulings --}}
                <div class="px-6 py-4 bg-neutral-50/50">
                    <div class="flex items-center justify-between gap-4 flex-wrap">
                        <div>
                            <h3 class="font-semibold text-neutral-900">الأحكام</h3>
                            <p class="text-xs text-neutral-500 mt-0.5">من 0 إلى 10 — اتركها فارغة إن لم تُرصد بعد.</p>
                        </div>
                        <div class="w-32">
                            <flux:input type="number" min="0" max="10" step="0.5" size="sm"
                                wire:model.live.debounce.400ms="editRulingsScore" placeholder="—" />
                        </div>
                    </div>
                    @error('editRulingsScore') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Live total --}}
                <div class="px-6 py-4 bg-primary-50/30 flex items-center justify-between gap-3 flex-wrap">
                    <div>
                        <h3 class="font-bold text-neutral-900">المجموع بعد التعديل</h3>
                        @if($previewTotal === null)
                            <p class="text-xs text-neutral-500 mt-0.5">الاختبار ما زال جارياً — لن تُحتسب درجة نهائية قبل إكماله.</p>
                        @endif
                    </div>
                    <p class="text-3xl font-black text-neutral-900">
                        {{ $previewTotal !== null ? number_format($previewTotal, 1) : '—' }}
                        <span class="text-sm font-normal text-neutral-400">/ 100</span>
                    </p>
                </div>
            </div>

            <div class="flex justify-end gap-3 px-6 py-4 border-t border-neutral-100 bg-neutral-50/50">
                <flux:button type="button" variant="outline" wire:click="cancelEdit">إلغاء</flux:button>
                <flux:button type="submit" variant="primary">حفظ التعديلات</flux:button>
            </div>

        </form>
    @endif

    {{-- Score breakdown --}}
    <div @class(['bg-white rounded-2xl border border-neutral-200 overflow-hidden mb-6', 'hidden' => $editing])>
        <div class="px-6 py-4 border-b border-neutral-200">
            <h2 class="font-bold text-neutral-900">تفاصيل الدرجات</h2>
        </div>

        <div class="divide-y divide-neutral-100">
            @foreach($exam->questions as $q)
                <div class="px-6 py-4">
                    <div class="flex items-start justify-between mb-2 gap-3">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-semibold text-neutral-900">السؤال {{ $q->question_number }}</h3>
                        </div>
                        <p class="text-2xl font-black {{ $q->final_score >= 25 ? 'text-success-600' : ($q->final_score >= 15 ? 'text-warning-600' : 'text-danger-500') }} whitespace-nowrap">
                            {{ number_format($q->final_score, 1) }}
                            <span class="text-xs font-normal text-neutral-400">/ 30</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-4 text-sm">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-exam-error"></span>
                            <span class="text-neutral-500">فتح:</span>
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
                            <span class="text-neutral-500">حركة:</span>
                            <span class="font-bold text-exam-continuation">{{ $q->continuations_count }}</span>
                            <span class="text-xs text-neutral-400">(−{{ $q->continuations_count * config('exam.deductions.continuation') }})</span>
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


</div>
