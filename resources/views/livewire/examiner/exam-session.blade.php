<div class="min-h-screen bg-neutral-50 flex flex-col">

    {{-- Top bar --}}
    <header class="bg-white border-b border-neutral-200 px-6 py-4 flex items-center justify-between flex-shrink-0">
        <h1 class="text-base font-bold text-primary-500">أكاديمية الإتقان — واجهة المختبر</h1>
        <div class="flex items-center gap-4">
            <span class="text-sm text-neutral-500">{{ $examiner->fullName() }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:button type="submit" variant="ghost" size="sm">خروج</flux:button>
            </form>
        </div>
    </header>

    {{-- ══════════════════════════════════════════ --}}
    {{-- STEP: search                               --}}
    {{-- ══════════════════════════════════════════ --}}
    @if($step === 'search')
    <div class="flex-1 flex items-center justify-center p-6">
        <div class="w-full max-w-lg">

            <h2 class="text-2xl font-bold text-neutral-900 text-center mb-8">ابحث عن الطالب</h2>

            <div class="relative mb-4">
                <flux:input
                    wire:model.live.debounce.300ms="studentSearch"
                    placeholder="أدخل الاسم أو رقم الهوية..."
                    class="w-full text-lg"
                    autofocus
                />
            </div>

            {{-- Search results --}}
            @if(strlen($studentSearch) >= 2)
                <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden mb-4">
                    @forelse($this->searchResults as $student)
                        <button
                            wire:click="selectStudent({{ $student['id'] }})"
                            class="w-full text-start px-4 py-3 hover:bg-primary-50 transition-colors border-b border-neutral-100 last:border-0"
                        >
                            <span class="font-medium text-neutral-900">
                                {{ implode(' ', array_filter([$student['first_name'], $student['second_name'], $student['third_name'], $student['family_name']])) }}
                            </span>
                            <span class="text-neutral-400 text-sm me-3">{{ $student['national_id'] }}</span>
                        </button>
                    @empty
                        <div class="px-4 py-4 text-center text-neutral-400 text-sm">
                            لا توجد نتائج — يمكنك إضافة الطالب يدوياً
                        </div>
                    @endforelse
                </div>
            @endif

            {{-- Quick add student (BR-STD-04) --}}
            <div class="text-center">
                <button
                    wire:click="$toggle('showAddStudent')"
                    class="text-sm text-primary-500 hover:text-primary-700 transition-colors"
                >
                    {{ $showAddStudent ? '← إلغاء' : '+ إضافة طالب جديد' }}
                </button>
            </div>

            @if($showAddStudent)
                <div class="mt-4 bg-white rounded-xl border border-neutral-200 p-5 space-y-4">
                    <h3 class="font-semibold text-neutral-900">إضافة طالب جديد</h3>
                    <form wire:submit="quickAddStudent" class="space-y-3">
                        <flux:field>
                            <flux:label>رقم الهوية</flux:label>
                            <flux:input wire:model="add_national_id" type="text" inputmode="numeric" />
                            <flux:error name="add_national_id" />
                        </flux:field>
                        <div class="grid grid-cols-2 gap-3">
                            <flux:field>
                                <flux:label>الاسم الأول</flux:label>
                                <flux:input wire:model="add_first_name" type="text" />
                                <flux:error name="add_first_name" />
                            </flux:field>
                            <flux:field>
                                <flux:label>الاسم الثاني</flux:label>
                                <flux:input wire:model="add_second_name" type="text" />
                            </flux:field>
                            <flux:field>
                                <flux:label>الاسم الثالث</flux:label>
                                <flux:input wire:model="add_third_name" type="text" />
                            </flux:field>
                            <flux:field>
                                <flux:label>اسم العائلة</flux:label>
                                <flux:input wire:model="add_family_name" type="text" />
                                <flux:error name="add_family_name" />
                            </flux:field>
                        </div>
                        <flux:button type="submit" variant="primary" class="w-full">إضافة وبدء الاختبار</flux:button>
                    </form>
                </div>
            @endif

        </div>
    </div>

    {{-- ══════════════════════════════════════════ --}}
    {{-- STEP: setup                                --}}
    {{-- ══════════════════════════════════════════ --}}
    @elseif($step === 'setup')
    <div class="flex-1 flex items-center justify-center p-6">
        <div class="w-full max-w-md">

            <div class="bg-primary-50 rounded-xl px-5 py-4 mb-6 text-center">
                <p class="text-xl font-bold text-primary-800">{{ $this->selectedStudent?->fullName() }}</p>
                <p class="text-sm text-primary-500 mt-0.5">{{ $this->selectedStudent?->national_id }}</p>
            </div>

            @if($needsPermit)
                <div class="bg-warning-50 border border-warning-200 rounded-xl px-5 py-4 mb-6">
                    <p class="text-sm font-medium text-warning-700 mb-1">الطالب لديه اختبار معتمد مسبقاً</p>
                    <p class="text-xs text-warning-600">أدخل رمز إذن إعادة الاختبار الممنوح من الأدمن</p>
                </div>
            @endif

            <form wire:submit="startExam" class="space-y-5">

                <flux:field>
                    <flux:label>نوع الاختبار</flux:label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center justify-center border rounded-lg px-4 py-3 cursor-pointer transition-colors {{ $examType === 'full_quran' ? 'border-primary-400 bg-primary-50' : 'border-neutral-200 hover:border-neutral-300' }}">
                            <input type="radio" wire:model.live="examType" value="full_quran" class="hidden">
                            <span class="font-medium text-sm {{ $examType === 'full_quran' ? 'text-primary-700' : 'text-neutral-700' }}">القرآن كاملاً</span>
                        </label>
                        <label class="flex items-center justify-center border rounded-lg px-4 py-3 cursor-pointer transition-colors {{ $examType === 'half_quran' ? 'border-primary-400 bg-primary-50' : 'border-neutral-200 hover:border-neutral-300' }}">
                            <input type="radio" wire:model.live="examType" value="half_quran" class="hidden">
                            <span class="font-medium text-sm {{ $examType === 'half_quran' ? 'text-primary-700' : 'text-neutral-700' }}">نصف القرآن</span>
                        </label>
                    </div>
                    <flux:error name="examType" />
                </flux:field>

                @if($needsPermit)
                    <flux:field>
                        <flux:label>رمز إذن إعادة الاختبار</flux:label>
                        <flux:input wire:model="permitCode" type="text" placeholder="RT-XXXX-XX" class="font-mono" />
                        <flux:error name="permitCode" />
                    </flux:field>
                @endif

                <div class="flex gap-3 pt-2">
                    <flux:button type="button" wire:click="$set('step', 'search')" variant="ghost" class="flex-1">
                        رجوع
                    </flux:button>
                    <flux:button type="submit" variant="primary" class="flex-1">
                        بدء الاختبار
                    </flux:button>
                </div>

            </form>

        </div>
    </div>

    {{-- ══════════════════════════════════════════ --}}
    {{-- STEP: question                             --}}
    {{-- ══════════════════════════════════════════ --}}
    @elseif($step === 'question')

    {{-- Auto-save every 10 seconds (BR-EXAM-08) --}}
    <div wire:poll.10000ms="autosave" class="hidden"></div>

    <div class="flex-1 flex flex-col items-center justify-center p-6 gap-6">

        <div class="text-center">
            <p class="text-sm text-neutral-400 mb-1">{{ $this->selectedStudent?->fullName() }}</p>
            <p class="text-lg font-semibold text-neutral-700">السؤال {{ $currentQuestion }} من 3</p>
        </div>

        {{-- Score counter --}}
        @php
            $q      = $questions[$currentQuestion];
            $qScore = $this->currentQuestionScore;
        @endphp

        <div class="bg-white rounded-3xl shadow-sm border border-neutral-200 w-48 h-48 flex flex-col items-center justify-center gap-1">
            <p class="text-xs text-neutral-400 font-medium">الدرجة الحالية</p>
            <p class="text-6xl font-black leading-none
                {{ $qScore >= 25 ? 'text-primary-500' : ($qScore >= 15 ? 'text-warning-500' : 'text-exam-error') }}">
                {{ number_format($qScore, 1) }}
            </p>
            <p class="text-xs text-neutral-400">من 30</p>
        </div>

        {{-- Deduction counts --}}
        <div class="flex items-center gap-6 text-center text-sm text-neutral-500">
            <div>
                <span class="font-bold text-exam-error">{{ $q['errors_count'] }}</span>
                <span class="ms-1">خطأ</span>
            </div>
            <div>
                <span class="font-bold text-exam-warning">{{ $q['warnings_count'] }}</span>
                <span class="ms-1">تنبيه</span>
            </div>
            <div>
                <span class="font-bold text-exam-continuation">{{ $q['continuations_count'] }}</span>
                <span class="ms-1">استرسال</span>
            </div>
        </div>

        {{-- Deduction buttons with per-type undo --}}
        <div class="flex flex-col gap-3 w-full max-w-sm">

            {{-- Error --}}
            <div class="flex items-center gap-2">
                <button
                    wire:click="pressDeduction('error')"
                    class="flex-1 flex items-center justify-between px-5 py-4 rounded-2xl border-2 border-exam-error bg-white hover:bg-red-50 active:scale-[0.98] transition-all"
                >
                    <span class="text-2xl font-black text-exam-error">-2</span>
                    <span class="text-lg font-bold text-exam-error">خطأ جلي</span>
                </button>
                <button
                    wire:click="pressUndoType('error')"
                    @disabled($q['errors_count'] === 0)
                    class="w-12 h-12 rounded-xl border-2 flex items-center justify-center text-lg font-bold transition-all
                        {{ $q['errors_count'] > 0 ? 'border-exam-error text-exam-error hover:bg-red-50 active:scale-95' : 'border-neutral-200 text-neutral-300 cursor-not-allowed' }}"
                >↩</button>
            </div>

            {{-- Warning --}}
            <div class="flex items-center gap-2">
                <button
                    wire:click="pressDeduction('warning')"
                    class="flex-1 flex items-center justify-between px-5 py-4 rounded-2xl border-2 border-exam-warning bg-white hover:bg-amber-50 active:scale-[0.98] transition-all"
                >
                    <span class="text-2xl font-black text-exam-warning">-1</span>
                    <span class="text-lg font-bold text-exam-warning">تنبيه / تردد</span>
                </button>
                <button
                    wire:click="pressUndoType('warning')"
                    @disabled($q['warnings_count'] === 0)
                    class="w-12 h-12 rounded-xl border-2 flex items-center justify-center text-lg font-bold transition-all
                        {{ $q['warnings_count'] > 0 ? 'border-exam-warning text-exam-warning hover:bg-amber-50 active:scale-95' : 'border-neutral-200 text-neutral-300 cursor-not-allowed' }}"
                >↩</button>
            </div>

            {{-- Continuation --}}
            <div class="flex items-center gap-2">
                <button
                    wire:click="pressDeduction('continuation')"
                    class="flex-1 flex items-center justify-between px-5 py-4 rounded-2xl border-2 border-exam-continuation bg-white hover:bg-cyan-50 active:scale-[0.98] transition-all"
                >
                    <span class="text-2xl font-black text-exam-continuation">-0.5</span>
                    <span class="text-lg font-bold text-exam-continuation">استرسال خاطئ</span>
                </button>
                <button
                    wire:click="pressUndoType('continuation')"
                    @disabled($q['continuations_count'] === 0)
                    class="w-12 h-12 rounded-xl border-2 flex items-center justify-center text-lg font-bold transition-all
                        {{ $q['continuations_count'] > 0 ? 'border-exam-continuation text-exam-continuation hover:bg-cyan-50 active:scale-95' : 'border-neutral-200 text-neutral-300 cursor-not-allowed' }}"
                >↩</button>
            </div>

        </div>

        <flux:button wire:click="nextQuestion" variant="primary" size="base" class="w-full max-w-sm">
            {{ $currentQuestion < 3 ? 'السؤال التالي ←' : 'الانتقال للأحكام ←' }}
        </flux:button>

    </div>

    {{-- ══════════════════════════════════════════ --}}
    {{-- STEP: rulings                              --}}
    {{-- ══════════════════════════════════════════ --}}
    @elseif($step === 'rulings')
    <div class="flex-1 flex items-center justify-center p-6">
        <div class="w-full max-w-sm text-center">

            <h2 class="text-xl font-bold text-neutral-900 mb-2">درجة الأحكام</h2>
            <p class="text-neutral-500 text-sm mb-8">{{ $this->selectedStudent?->fullName() }}</p>

            <form wire:submit="submitRulings" class="space-y-6">

                <div>
                    <label class="block text-sm text-neutral-600 mb-2">الدرجة (0 – 10)</label>
                    <input
                        wire:model="rulingsScore"
                        type="number"
                        min="0"
                        max="10"
                        class="w-32 text-center text-5xl font-black border-2 border-neutral-200 rounded-2xl py-4 focus:border-primary-400 focus:outline-none mx-auto block"
                    />
                    @error('rulingsScore')
                        <p class="text-sm text-danger-500 mt-2">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Partial scores recap --}}
                <div class="bg-neutral-50 rounded-xl p-4 text-sm text-neutral-600 space-y-1">
                    @foreach([1, 2, 3] as $qNum)
                        @php $qData = $questions[$qNum]; @endphp
                        <div class="flex justify-between">
                            <span>السؤال {{ $qNum }}</span>
                            <span class="font-medium">
                                {{ number_format(\App\Services\ScoreCalculator::questionScore($qData['errors_count'], $qData['warnings_count'], $qData['continuations_count']), 1) }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <flux:button type="submit" variant="primary" class="w-full">
                    عرض الملخص ←
                </flux:button>

            </form>

        </div>
    </div>

    {{-- ══════════════════════════════════════════ --}}
    {{-- STEP: summary                              --}}
    {{-- ══════════════════════════════════════════ --}}
    @elseif($step === 'summary')
    <div class="flex-1 flex items-center justify-center p-6">
        <div class="w-full max-w-md">

            <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden shadow-sm mb-6">

                <div class="py-6 text-center {{ $this->isPassing ? 'bg-success-50' : 'bg-danger-50' }}">
                    <p class="text-6xl font-black mb-1 {{ $this->isPassing ? 'text-success-700' : 'text-danger-600' }}">
                        {{ number_format($this->totalScore, 1) }}
                    </p>
                    <p class="text-sm {{ $this->isPassing ? 'text-success-600' : 'text-danger-500' }}">من 100</p>
                    <div class="mt-3">
                        <span class="inline-block px-4 py-1 rounded-full text-sm font-bold {{ $this->isPassing ? 'bg-success-100 text-success-700' : 'bg-danger-100 text-danger-600' }}">
                            {{ $this->isPassing ? '✓ مجاز' : '✗ غير مجاز' }}
                        </span>
                    </div>
                </div>

                <div class="divide-y divide-neutral-100">
                    @foreach([1, 2, 3] as $qNum)
                        @php $qData = $questions[$qNum]; @endphp
                        <div class="flex justify-between items-center px-5 py-3 text-sm">
                            <div class="text-neutral-600">
                                السؤال {{ $qNum }}
                                <span class="text-xs text-neutral-400 ms-2">
                                    ({{ $qData['errors_count'] }}خ {{ $qData['warnings_count'] }}ت {{ $qData['continuations_count'] }}س)
                                </span>
                            </div>
                            <span class="font-bold text-neutral-900">
                                {{ number_format(\App\Services\ScoreCalculator::questionScore($qData['errors_count'], $qData['warnings_count'], $qData['continuations_count']), 1) }}
                            </span>
                        </div>
                    @endforeach
                    <div class="flex justify-between items-center px-5 py-3 text-sm">
                        <span class="text-neutral-600">الأحكام</span>
                        <span class="font-bold text-neutral-900">{{ $rulingsScore }}</span>
                    </div>
                </div>

            </div>

            <div class="text-center text-sm text-neutral-500 mb-6">
                {{ $this->selectedStudent?->fullName() }} · {{ $this->selectedStudent?->national_id }}
            </div>

            <div class="flex gap-3">
                <flux:button wire:click="$set('step', 'rulings')" variant="ghost" class="flex-1">
                    تعديل الأحكام
                </flux:button>
                <flux:button
                    wire:click="saveExam"
                    variant="primary"
                    class="flex-1"
                    wire:confirm="تأكيد حفظ الاختبار؟ لا يمكن التراجع."
                >
                    حفظ الاختبار
                </flux:button>
            </div>

        </div>
    </div>

    {{-- ══════════════════════════════════════════ --}}
    {{-- STEP: saved                                --}}
    {{-- ══════════════════════════════════════════ --}}
    @elseif($step === 'saved')
    <div class="flex-1 flex items-center justify-center p-6">
        <div class="text-center max-w-sm">

            <div class="w-20 h-20 rounded-full {{ $this->isPassing ? 'bg-success-50' : 'bg-danger-50' }} flex items-center justify-center mx-auto mb-4">
                <span class="text-3xl {{ $this->isPassing ? 'text-success-500' : 'text-danger-500' }}">
                    {{ $this->isPassing ? '✓' : '✗' }}
                </span>
            </div>

            <h2 class="text-2xl font-bold text-neutral-900 mb-1">تم حفظ الاختبار</h2>
            <p class="text-neutral-500 mb-2">{{ $this->selectedStudent?->fullName() }}</p>
            <p class="text-3xl font-black {{ $this->isPassing ? 'text-success-600' : 'text-danger-500' }} mb-8">
                {{ number_format($this->totalScore, 1) }} / 100
            </p>

            <flux:button wire:click="resetSession" variant="primary" size="base">
                اختبار طالب آخر
            </flux:button>

        </div>
    </div>
    @endif

</div>
