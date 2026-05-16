<div class="min-h-screen bg-gradient-to-b from-neutral-50 via-white to-neutral-50 flex flex-col">

    {{-- ─── Top bar (clean, mobile-friendly) ─── --}}
    <header class="bg-white/80 backdrop-blur-md border-b border-neutral-100 flex-shrink-0 sticky top-0 z-20">
        <div class="px-4 sm:px-6 h-14 flex items-center justify-between max-w-2xl mx-auto">

            {{-- Brand: logo dot + name --}}
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700
                            flex items-center justify-center text-white font-black text-sm
                            shadow-sm shadow-primary-500/30 flex-shrink-0">
                    إ
                </div>
                <div class="leading-tight min-w-0">
                    <p class="text-sm font-bold text-neutral-800 truncate">أكاديمية الإتقان</p>
                    <p class="text-[10px] text-neutral-400 -mt-0.5">واجهة المختبر</p>
                </div>
            </div>

            {{-- User: avatar + short name + logout --}}
            <div class="flex items-center gap-2 flex-shrink-0">
                <div class="hidden sm:flex items-center gap-2 ps-3 pe-3 py-1 rounded-full bg-neutral-50 ring-1 ring-neutral-100">
                    <div class="w-6 h-6 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-xs font-bold">
                        {{ mb_substr($examiner->first_name, 0, 1) }}
                    </div>
                    <span class="text-xs font-semibold text-neutral-700">{{ $examiner->shortName() }}</span>
                </div>
                {{-- Mobile: avatar only --}}
                <div class="sm:hidden w-8 h-8 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-xs font-bold ring-1 ring-primary-200/40"
                     title="{{ $examiner->shortName() }}">
                    {{ mb_substr($examiner->first_name, 0, 1) }}
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            title="تسجيل الخروج"
                            class="w-9 h-9 rounded-full flex items-center justify-center
                                   text-neutral-400 hover:text-danger-500 hover:bg-danger-50
                                   transition-all duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"/>
                        </svg>
                    </button>
                </form>
            </div>
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
                            <flux:input wire:model="add_national_id" type="text" inputmode="numeric" maxlength="9" placeholder="9 أرقام" />
                            <flux:error name="add_national_id" />
                        </flux:field>
                        <div class="grid grid-cols-2 gap-3">
                            <flux:field>
                                <flux:label>الاسم الأول</flux:label>
                                <flux:input wire:model="add_first_name" type="text" />
                                <flux:error name="add_first_name" />
                            </flux:field>
                            <flux:field>
                                <flux:label>الاسم الثاني <span class="text-neutral-400 text-xs font-normal">(اختياري)</span></flux:label>
                                <flux:input wire:model="add_second_name" type="text" />
                            </flux:field>
                            <flux:field>
                                <flux:label>الاسم الثالث <span class="text-neutral-400 text-xs font-normal">(اختياري)</span></flux:label>
                                <flux:input wire:model="add_third_name" type="text" />
                            </flux:field>
                            <flux:field>
                                <flux:label>اسم العائلة</flux:label>
                                <flux:input wire:model="add_family_name" type="text" />
                                <flux:error name="add_family_name" />
                            </flux:field>
                        </div>
                        @php $examinerGender = auth()->user()->gender?->value; @endphp
                        <flux:field>
                            <flux:label>الجنس</flux:label>
                            <flux:select wire:model="add_gender" placeholder="اختر الجنس">
                                @if($examinerGender === 'male')
                                    <flux:select.option value="male">ذكر</flux:select.option>
                                @elseif($examinerGender === 'female')
                                    <flux:select.option value="female">أنثى</flux:select.option>
                                @endif
                            </flux:select>
                            <flux:error name="add_gender" />
                            <p class="text-[10px] text-neutral-400 mt-1">
                                الإضافة محصورة بـ {{ $examinerGender === 'male' ? 'الذكور' : 'الإناث' }} لأنك مختبر{{ $examinerGender === 'female' ? 'ة' : '' }}.
                            </p>
                        </flux:field>
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
                <p class="text-sm text-primary-500 mt-0.5 font-mono">
                    {{ $this->selectedStudent?->national_id ?? 'بدون رقم هوية' }}
                </p>
            </div>

            @if($this->selectedStudent && empty($this->selectedStudent->national_id))
                <div class="bg-amber-50 border border-amber-200 rounded-xl px-5 py-4 mb-5">
                    <p class="text-sm font-bold text-amber-800 mb-1">⚠️ هذا الطالب بلا رقم هوية</p>
                    <p class="text-xs text-amber-700">أدخل رقم هويته أدناه ليتم حفظه على بياناته قبل البدء.</p>
                </div>
            @endif

            @if($needsPermit)
                <div class="bg-warning-50 border border-warning-200 rounded-xl px-5 py-4 mb-6">
                    <p class="text-sm font-medium text-warning-700 mb-1">الطالب لديه اختبار معتمد مسبقاً</p>
                    <p class="text-xs text-warning-600">أدخل رمز إذن إعادة الاختبار الممنوح من الأدمن</p>
                </div>
            @endif

            <form wire:submit="startExam" class="space-y-5">

                @if($this->selectedStudent && empty($this->selectedStudent->national_id))
                    <flux:field>
                        <flux:label>رقم الهوية</flux:label>
                        <flux:input
                            wire:model="inlineNationalId"
                            type="text"
                            inputmode="numeric"
                            maxlength="9"
                            placeholder="9 أرقام"
                        />
                        <flux:error name="inlineNationalId" />
                    </flux:field>
                @endif

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
                    <flux:button type="button" wire:click="$set('step', 'search')" variant="outline" class="flex-1">
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
    {{-- STEP: active — Alpine handles Q1/Q2/Q3 + rulings + summary entirely client-side --}}
    {{-- Server only receives one combined sync every 20s (BR-EXAM-08) and the final saveExam --}}
    {{-- ══════════════════════════════════════════ --}}
    @elseif($step === 'active')
    <div class="flex-1 flex flex-col" wire:key="step-active">

    {{-- Data bridge: single-quoted attribute safely holds JSON (double-quoted).
         Livewire updates this element; Alpine reads it once in init(). --}}
    <div class="hidden" id="_exam-data"
        data-qs='@json($questions)'
        data-cq="{{ $currentQuestion }}"
        data-rs="{{ $rulingsScore }}"
        data-ps="{{ $passingScore }}"
        data-spq="{{ $scorePerQ }}"
        data-de="{{ $errorDeduction }}"
        data-dw="{{ $warnDeduction }}"
        data-dc="{{ $contDeduction }}"
    ></div>

    <div
        wire:ignore
        wire:key="alpine-exam-active"
        class="flex-1 flex flex-col"
        x-data="{
            // ── State ──
            qs: {},
            currentQ: 1,
            subStep: 'question',                // 'question' | 'rulings' | 'summary'
            rulingsScore: 0,
            passingScore: 60,
            spq: 30, de: 2, dw: 1, dc: 0.5,     // score-per-question + deductions
            saving: false,
            savedAt: null,
            savingExam: false,

            // ── Computed ──
            get q() { return this.qs[this.currentQ] },
            score(n) {
                const q = this.qs[n];
                return +Math.max(0, this.spq - q.errors_count*this.de - q.warnings_count*this.dw - q.continuations_count*this.dc).toFixed(1);
            },
            get cs() { return this.score(this.currentQ) },
            sc(s) { return s >= 25 ? 'text-primary-500' : s >= 15 ? 'text-warning-500' : 'text-exam-error' },
            get total() {
                const sum = this.score(1) + this.score(2) + this.score(3);
                return +(sum + Number(this.rulingsScore || 0)).toFixed(1);
            },
            get isPassing() { return this.total >= this.passingScore },
            get rulingsValid() {
                const v = Number(this.rulingsScore);
                return !isNaN(v) && v >= 0 && v <= 10;
            },

            // ── Actions ──
            press(t) {
                const a = {error:this.de, warning:this.dw, continuation:this.dc};
                const k = {error:'errors_count', warning:'warnings_count', continuation:'continuations_count'};
                if (this.cs - a[t] < 0) return;
                this.q[k[t]]++;
                this.q.history.push(t);
            },
            undo(t) {
                const k = {error:'errors_count', warning:'warnings_count', continuation:'continuations_count'};
                if (this.q[k[t]] === 0) return;
                const h = this.q.history;
                for (let i = h.length-1; i >= 0; i--) { if (h[i] === t) { h.splice(i, 1); break; } }
                this.q[k[t]] = Math.max(0, this.q[k[t]] - 1);
            },
            // Pure client-side navigation between sub-steps — NO server requests
            goTo(target, n) {
                if (target === 'question') { this.currentQ = n || 1; this.subStep = 'question'; return; }
                if (target === 'summary' && !this.rulingsValid) { this.subStep = 'rulings'; return; }
                this.subStep = target;
            },
            clampRulings() {
                const v = Number(this.rulingsScore);
                if (isNaN(v)) { this.rulingsScore = 0; return; }
                this.rulingsScore = Math.max(0, Math.min(10, v));
            },
            // Background sync every 20s — saves questions + rulings together
            async doSync() {
                this.saving = true;
                try {
                    await $wire.syncExam(this.qs, Number(this.rulingsScore || 0));
                    this.savedAt = new Date().toLocaleTimeString('ar-SA', {hour:'2-digit', minute:'2-digit'});
                } finally {
                    this.saving = false;
                }
            },
            // Final save — only server call from inside the active step
            async saveExam() {
                if (!confirm('تأكيد حفظ الاختبار؟ لا يمكن التراجع.')) return;
                this.savingExam = true;
                try {
                    await this.doSync();
                    await $wire.saveExam();
                } catch (e) { this.savingExam = false; }
            },
            init() {
                const d = document.getElementById('_exam-data').dataset;
                this.qs           = JSON.parse(d.qs);
                this.currentQ     = +d.cq;
                this.rulingsScore = +d.rs || 0;
                this.passingScore = +d.ps;
                this.spq = +d.spq; this.de = +d.de; this.dw = +d.dw; this.dc = +d.dc;
                setInterval(() => this.doSync(), 20000);
            }
        }"
    >

        {{-- ─── Floating pill nav tabs (detached from header) ─── --}}
        <div class="px-4 pt-5 pb-2 flex justify-center">
            <div class="bg-white rounded-full shadow-[0_4px_20px_-6px_rgba(0,0,0,0.08)] ring-1 ring-neutral-100 px-1.5 py-1.5 flex items-center justify-center gap-0.5 flex-wrap">
                <template x-for="n in [1, 2, 3]" :key="n">
                    <button
                        @click="goTo('question', n)"
                        class="px-3.5 py-1.5 rounded-full text-sm transition-all duration-200"
                        :class="(subStep === 'question' && currentQ === n)
                            ? 'bg-primary-500 text-white font-bold shadow-sm shadow-primary-500/30'
                            : 'text-neutral-500 hover:text-neutral-800 hover:bg-neutral-50 font-medium'"
                        x-text="'السؤال ' + n"
                    ></button>
                </template>
                <span class="w-px h-4 bg-neutral-200 mx-1 select-none"></span>
                <button
                    @click="goTo('rulings')"
                    class="px-3.5 py-1.5 rounded-full text-sm transition-all duration-200"
                    :class="(subStep === 'rulings' || subStep === 'summary')
                        ? 'bg-primary-500 text-white font-bold shadow-sm shadow-primary-500/30'
                        : 'text-neutral-500 hover:text-neutral-800 hover:bg-neutral-50 font-medium'"
                >الأحكام</button>
            </div>
        </div>

        {{-- ═══════════════════════════ SUB-STEP: QUESTION ═══════════════════════════ --}}
        <div x-show="subStep === 'question'" class="flex-1 flex flex-col items-center justify-start p-6 sm:p-8 gap-7 max-w-md mx-auto w-full">

            {{-- ─── Student + question indicator ─── --}}
            <div class="text-center mt-2">
                <p class="text-xs uppercase tracking-wider text-neutral-400 mb-1.5 font-medium">{{ $this->selectedStudent?->fullName() }}</p>
                <h2 class="text-xl font-bold text-neutral-800 tracking-tight" x-text="'السؤال ' + currentQ + ' من 3'"></h2>
            </div>

            {{-- ─── Score card (soft UI, gradient bg, large radius, prominent number) ─── --}}
            <div class="relative w-56 h-56 rounded-[2rem] flex flex-col items-center justify-center
                        bg-gradient-to-br from-white via-primary-50/40 to-primary-100/30
                        shadow-[0_10px_40px_-12px_rgba(0,0,0,0.08)]
                        ring-1 ring-white">
                {{-- Subtle inner highlight --}}
                <div class="absolute inset-0 rounded-[2rem] bg-gradient-to-b from-white/60 to-transparent pointer-events-none"></div>

                <div class="relative flex flex-col items-center justify-center gap-1">
                    <p class="text-[11px] uppercase tracking-widest text-neutral-400 font-semibold">الدرجة الحالية</p>
                    <p
                        class="text-7xl font-black leading-none tracking-tight tabular-nums transition-colors duration-300"
                        :class="sc(cs)"
                        x-text="cs.toFixed(1)"
                    ></p>
                    <p class="text-xs text-neutral-400 font-medium mt-0.5">من 30</p>
                </div>
            </div>

            {{-- ─── Deduction counts (clean inline pills) ─── --}}
            <div class="flex items-center gap-2 sm:gap-3 text-sm">
                <div class="flex items-center gap-1.5 bg-red-50/70 px-3 py-1.5 rounded-full">
                    <span class="font-bold text-exam-error tabular-nums" x-text="q.errors_count"></span>
                    <span class="text-exam-error/80 text-xs">خطأ</span>
                </div>
                <div class="flex items-center gap-1.5 bg-amber-50/70 px-3 py-1.5 rounded-full">
                    <span class="font-bold text-exam-warning tabular-nums" x-text="q.warnings_count"></span>
                    <span class="text-exam-warning/80 text-xs">تنبيه</span>
                </div>
                <div class="flex items-center gap-1.5 bg-cyan-50/70 px-3 py-1.5 rounded-full">
                    <span class="font-bold text-exam-continuation tabular-nums" x-text="q.continuations_count"></span>
                    <span class="text-exam-continuation/80 text-xs">استرسال</span>
                </div>
            </div>

            {{-- ─── Deduction buttons (soft tinted bg + colored border + soft shadow) ─── --}}
            <div class="flex flex-col gap-3.5 w-full">

                {{-- Error --}}
                <div class="flex items-stretch gap-2.5">
                    <button
                        @click="press('error')"
                        class="flex-1 flex items-center justify-between px-6 py-4 rounded-2xl
                               bg-red-50/60 border border-exam-error/30
                               hover:bg-red-50 hover:border-exam-error/60 hover:shadow-[0_4px_20px_-6px_rgba(239,68,68,0.25)]
                               active:scale-[0.98]
                               transition-all duration-200"
                    >
                        <span class="text-2xl font-black text-exam-error tabular-nums">−2</span>
                        <span class="text-base font-bold text-exam-error">خطأ جلي</span>
                    </button>
                    <button
                        @click="undo('error')"
                        :disabled="q.errors_count === 0"
                        class="w-13 h-auto px-3 rounded-2xl border flex items-center justify-center text-lg font-bold transition-all duration-200"
                        :class="q.errors_count > 0
                            ? 'border-exam-error/30 text-exam-error bg-red-50/60 hover:bg-red-50 hover:border-exam-error/60 active:scale-95'
                            : 'border-neutral-200 text-neutral-300 cursor-not-allowed'"
                        title="تراجع"
                    >↩</button>
                </div>

                {{-- Warning --}}
                <div class="flex items-stretch gap-2.5">
                    <button
                        @click="press('warning')"
                        class="flex-1 flex items-center justify-between px-6 py-4 rounded-2xl
                               bg-amber-50/60 border border-exam-warning/30
                               hover:bg-amber-50 hover:border-exam-warning/60 hover:shadow-[0_4px_20px_-6px_rgba(245,158,11,0.25)]
                               active:scale-[0.98]
                               transition-all duration-200"
                    >
                        <span class="text-2xl font-black text-exam-warning tabular-nums">−1</span>
                        <span class="text-base font-bold text-exam-warning">تنبيه / تردد</span>
                    </button>
                    <button
                        @click="undo('warning')"
                        :disabled="q.warnings_count === 0"
                        class="w-13 h-auto px-3 rounded-2xl border flex items-center justify-center text-lg font-bold transition-all duration-200"
                        :class="q.warnings_count > 0
                            ? 'border-exam-warning/30 text-exam-warning bg-amber-50/60 hover:bg-amber-50 hover:border-exam-warning/60 active:scale-95'
                            : 'border-neutral-200 text-neutral-300 cursor-not-allowed'"
                        title="تراجع"
                    >↩</button>
                </div>

                {{-- Continuation --}}
                <div class="flex items-stretch gap-2.5">
                    <button
                        @click="press('continuation')"
                        class="flex-1 flex items-center justify-between px-6 py-4 rounded-2xl
                               bg-cyan-50/60 border border-exam-continuation/30
                               hover:bg-cyan-50 hover:border-exam-continuation/60 hover:shadow-[0_4px_20px_-6px_rgba(6,182,212,0.25)]
                               active:scale-[0.98]
                               transition-all duration-200"
                    >
                        <span class="text-2xl font-black text-exam-continuation tabular-nums">−0.5</span>
                        <span class="text-base font-bold text-exam-continuation">استرسال خاطئ</span>
                    </button>
                    <button
                        @click="undo('continuation')"
                        :disabled="q.continuations_count === 0"
                        class="w-13 h-auto px-3 rounded-2xl border flex items-center justify-center text-lg font-bold transition-all duration-200"
                        :class="q.continuations_count > 0
                            ? 'border-exam-continuation/30 text-exam-continuation bg-cyan-50/60 hover:bg-cyan-50 hover:border-exam-continuation/60 active:scale-95'
                            : 'border-neutral-200 text-neutral-300 cursor-not-allowed'"
                        title="تراجع"
                    >↩</button>
                </div>

            </div>

            {{-- ─── Next / proceed button (prominent, soft shadow) ─── --}}
            <button
                @click="currentQ < 3 ? goTo('question', currentQ + 1) : goTo('rulings')"
                class="w-full py-4 rounded-2xl
                       bg-gradient-to-b from-primary-500 to-primary-600
                       hover:from-primary-600 hover:to-primary-700
                       active:from-primary-700 active:to-primary-700
                       text-white font-bold text-base tracking-tight
                       shadow-[0_8px_24px_-8px_rgba(59,130,246,0.5)]
                       hover:shadow-[0_12px_28px_-8px_rgba(59,130,246,0.6)]
                       active:scale-[0.99]
                       transition-all duration-200"
                x-text="currentQ < 3 ? 'السؤال التالي  ←' : 'الانتقال للأحكام  ←'"
            ></button>

        </div>

        {{-- ═══════════════════════════ SUB-STEP: RULINGS ═══════════════════════════ --}}
        <div x-show="subStep === 'rulings'" class="flex-1 flex flex-col items-center justify-start p-6 sm:p-8 gap-7 max-w-md mx-auto w-full">

            <div class="text-center mt-2">
                <p class="text-xs uppercase tracking-wider text-neutral-400 mb-1.5 font-medium">{{ $this->selectedStudent?->fullName() }}</p>
                <h2 class="text-xl font-bold text-neutral-800 tracking-tight">درجة الأحكام</h2>
            </div>

            {{-- Rulings input card (Soft UI) --}}
            <div class="w-full bg-gradient-to-br from-white via-primary-50/30 to-primary-100/20
                        rounded-[2rem] p-7 ring-1 ring-white
                        shadow-[0_10px_40px_-12px_rgba(0,0,0,0.08)] text-center">
                <p class="text-[11px] uppercase tracking-widest text-neutral-400 font-semibold mb-3">أدخل الدرجة (0 – 10)</p>
                <input
                    type="number"
                    min="0"
                    max="10"
                    step="0.5"
                    inputmode="decimal"
                    x-model.number="rulingsScore"
                    @blur="clampRulings()"
                    class="w-32 text-center text-6xl font-black tabular-nums
                           bg-white/70 border-2 border-neutral-200 rounded-2xl py-3 mx-auto block
                           text-primary-600
                           focus:border-primary-400 focus:outline-none focus:ring-4 focus:ring-primary-100
                           transition-all"
                />
                <p x-show="!rulingsValid" class="text-xs text-exam-error mt-3">الدرجة يجب أن تكون بين 0 و 10</p>
            </div>

            {{-- Partial scores recap (live from Alpine) --}}
            <div class="w-full bg-white/70 rounded-2xl p-4 ring-1 ring-neutral-100 space-y-2">
                <p class="text-[11px] uppercase tracking-widest text-neutral-400 font-semibold mb-2">الدرجات حتى الآن</p>
                <template x-for="n in [1, 2, 3]" :key="n">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-neutral-600" x-text="'السؤال ' + n"></span>
                        <span class="font-bold tabular-nums text-neutral-800" x-text="score(n).toFixed(1) + ' / 30'"></span>
                    </div>
                </template>
            </div>

            <button
                @click="goTo('summary')"
                :disabled="!rulingsValid"
                class="w-full py-4 rounded-2xl
                       bg-gradient-to-b from-primary-500 to-primary-600
                       hover:from-primary-600 hover:to-primary-700
                       text-white font-bold text-base tracking-tight
                       shadow-[0_8px_24px_-8px_rgba(59,130,246,0.5)]
                       hover:shadow-[0_12px_28px_-8px_rgba(59,130,246,0.6)]
                       active:scale-[0.99]
                       transition-all duration-200
                       disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:shadow-none"
            >عرض الملخص  ←</button>

        </div>

        {{-- ═══════════════════════════ SUB-STEP: SUMMARY ═══════════════════════════ --}}
        <div x-show="subStep === 'summary'" class="flex-1 flex flex-col items-center justify-start p-6 sm:p-8 gap-6 max-w-md mx-auto w-full">

            <div class="text-center mt-2">
                <p class="text-xs uppercase tracking-wider text-neutral-400 mb-1.5 font-medium">{{ $this->selectedStudent?->fullName() }}</p>
                <h2 class="text-xl font-bold text-neutral-800 tracking-tight">ملخص الاختبار</h2>
            </div>

            {{-- Total score card (live computed — neutral colors, no pass/fail indication) --}}
            <div class="w-full rounded-[2rem] overflow-hidden ring-1 ring-white
                        shadow-[0_10px_40px_-12px_rgba(0,0,0,0.1)]
                        bg-gradient-to-br from-primary-50/70 via-white to-primary-100/40">
                <div class="py-7 text-center relative">
                    <div class="absolute inset-0 bg-gradient-to-b from-white/40 to-transparent pointer-events-none"></div>
                    <div class="relative">
                        <p class="text-[11px] uppercase tracking-widest font-semibold mb-2 text-primary-700/70">المجموع</p>
                        <p class="text-7xl font-black tabular-nums leading-none mb-1 text-primary-700"
                           x-text="total.toFixed(1)"></p>
                        <p class="text-xs font-medium mt-1 text-primary-600/80">من 100</p>
                    </div>
                </div>
            </div>

            {{-- Per-question breakdown --}}
            <div class="w-full bg-white/70 rounded-2xl ring-1 ring-neutral-100 overflow-hidden">
                <template x-for="n in [1, 2, 3]" :key="n">
                    <div class="flex items-center justify-between px-5 py-3 text-sm border-b border-neutral-100 last:border-0">
                        <div class="text-neutral-700">
                            <span x-text="'السؤال ' + n"></span>
                            <span class="text-xs text-neutral-400 ms-2 tabular-nums"
                                  x-text="'(' + qs[n].errors_count + 'خ ' + qs[n].warnings_count + 'ت ' + qs[n].continuations_count + 'س)'"></span>
                        </div>
                        <span class="font-bold tabular-nums text-neutral-900" x-text="score(n).toFixed(1)"></span>
                    </div>
                </template>
                <div class="flex items-center justify-between px-5 py-3 text-sm bg-neutral-50/50">
                    <span class="text-neutral-700">الأحكام</span>
                    <span class="font-bold tabular-nums text-neutral-900" x-text="Number(rulingsScore || 0).toFixed(1)"></span>
                </div>
            </div>

            <p class="text-xs text-neutral-400 text-center -mt-2">
                {{ $this->selectedStudent?->fullName() }} · {{ $this->selectedStudent?->national_id }}
            </p>

            {{-- Actions --}}
            <div class="flex gap-3 w-full">
                <button
                    @click="goTo('rulings')"
                    class="flex-1 py-3 rounded-2xl bg-white border border-neutral-200 text-neutral-700 font-semibold text-sm hover:bg-neutral-50 active:scale-[0.99] transition-all"
                >تعديل الأحكام</button>
                <button
                    @click="saveExam()"
                    :disabled="savingExam"
                    class="flex-1 py-3 rounded-2xl
                           bg-gradient-to-b from-primary-500 to-primary-600
                           hover:from-primary-600 hover:to-primary-700
                           text-white font-bold text-sm tracking-tight
                           shadow-[0_8px_24px_-8px_rgba(59,130,246,0.5)]
                           hover:shadow-[0_12px_28px_-8px_rgba(59,130,246,0.6)]
                           active:scale-[0.99]
                           transition-all duration-200
                           disabled:opacity-50 disabled:cursor-wait"
                >
                    <span x-show="!savingExam">حفظ الاختبار</span>
                    <span x-show="savingExam">جارٍ الحفظ...</span>
                </button>
            </div>

        </div>

        {{-- ─── Sync indicator (shared across all sub-steps) ─── --}}
        <div class="h-5 text-center pb-3">
            <span x-show="saving" class="text-xs text-neutral-400 animate-pulse inline-flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-primary-400 animate-ping"></span>
                جارٍ الحفظ...
            </span>
            <span x-show="!saving && savedAt !== null" x-text="'تم الحفظ ✓ ' + savedAt" class="text-xs text-neutral-400"></span>
        </div>

    </div>
    </div>

    {{-- ══════════════════════════════════════════ --}}
    {{-- STEP: saved                                --}}
    {{-- ══════════════════════════════════════════ --}}
    @elseif($step === 'saved')
    <div class="flex-1 flex items-center justify-center p-6">
        <div class="text-center max-w-sm">

            <div class="w-20 h-20 rounded-full bg-primary-50 flex items-center justify-center mx-auto mb-4">
                <span class="text-3xl text-primary-500">✓</span>
            </div>

            <h2 class="text-2xl font-bold text-neutral-900 mb-1">تم حفظ الاختبار</h2>
            <p class="text-neutral-500 mb-2">{{ $this->selectedStudent?->fullName() }}</p>
            <p class="text-3xl font-black text-primary-600 mb-8">
                {{ number_format($this->totalScore, 1) }} / 100
            </p>

            <flux:button wire:click="resetSession" variant="primary" size="base">
                اختبار طالب آخر
            </flux:button>

        </div>
    </div>
    @endif

</div>
