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

                    {{-- BR-SS-5: autocomplete on the suggested-students list. Picking a
                         row pre-fills the form; the examiner can still edit. --}}
                    <div class="relative">
                        <label class="block text-xs text-neutral-500 mb-1">
                            ابحث في القائمة المقترحة (اختياري — لتعبئة الحقول تلقائياً)
                        </label>
                        <flux:input
                            wire:model.live.debounce.300ms="suggestionSearch"
                            type="text"
                            placeholder="ابحث بالاسم أو رقم الهوية..."
                        />
                        @if(count($this->suggestionResults) > 0)
                            <div class="absolute z-10 mt-1 w-full bg-white border border-neutral-200 rounded-lg shadow-lg max-h-72 overflow-y-auto">
                                @foreach($this->suggestionResults as $s)
                                    <button
                                        type="button"
                                        wire:click="selectSuggestion({{ $s['id'] }})"
                                        class="w-full text-start px-4 py-2.5 hover:bg-primary-50 transition-colors border-b border-neutral-100 last:border-0"
                                    >
                                        <div class="font-medium text-neutral-900 text-sm">
                                            {{ trim(implode(' ', array_filter([$s['first_name'], $s['second_name'] ?? null, $s['third_name'] ?? null, $s['family_name']]))) }}
                                        </div>
                                        <div class="text-xs text-neutral-500 mt-0.5 font-mono">
                                            {{ $s['national_id'] ?? 'بدون هوية' }} · {{ $s['student_zone'] }}
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @elseif(mb_strlen($suggestionSearch) >= 2)
                            <div class="absolute z-10 mt-1 w-full bg-white border border-neutral-200 rounded-lg shadow-sm px-4 py-3 text-xs text-neutral-400">
                                لا توجد نتائج
                            </div>
                        @endif
                    </div>

                    <div class="border-t border-neutral-100 pt-4">

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
                        <flux:field>
                            <flux:label>منطقة الطالب</flux:label>
                            <flux:select wire:model="add_student_zone" placeholder="اختر المنطقة">
                                <flux:select.option value="East Gaza">شرق غزة</flux:select.option>
                                <flux:select.option value="West Gaza">غرب غزة</flux:select.option>
                                <flux:select.option value="North Gaza">شمال غزة</flux:select.option>
                                <flux:select.option value="South Gaza">جنوب غزة</flux:select.option>
                            </flux:select>
                            <flux:error name="add_student_zone" />
                        </flux:field>
                        <flux:field>
                            <flux:label>هل سبق له السرد / التسميع؟</flux:label>
                            <flux:select wire:model="add_is_recite_before" placeholder="اختر...">
                                <flux:select.option value="1">نعم، سبق له</flux:select.option>
                                <flux:select.option value="0">لا، لم يسبق له</flux:select.option>
                            </flux:select>
                            <flux:error name="add_is_recite_before" />
                        </flux:field>
                        <flux:button type="submit" variant="primary" class="w-full">إضافة وبدء الاختبار</flux:button>
                    </form>
                    </div> {{-- /border-t wrapper for the manual-entry block --}}
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
                <div class="flex items-center justify-center gap-1.5 flex-wrap mt-2.5">
                    @if($this->selectedStudent?->gender)
                        <span class="text-[10px] px-2 py-0.5 rounded-full {{ $this->selectedStudent->gender->value === 'male' ? 'bg-sky-100 text-sky-800' : 'bg-pink-100 text-pink-800' }} font-medium">
                            {{ $this->selectedStudent->gender->label() }}
                        </span>
                    @endif
                    @if($this->selectedStudent?->student_zone)
                        @php
                            $zones = [
                                'East Gaza' => 'شرق غزة',
                                'West Gaza' => 'غرب غزة',
                                'North Gaza' => 'شمال غزة',
                                'South Gaza' => 'جنوب غزة',
                            ];
                        @endphp
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-neutral-200/80 text-neutral-700 font-medium">
                            {{ $zones[$this->selectedStudent->student_zone] ?? $this->selectedStudent->student_zone }}
                        </span>
                    @endif
                    @if($this->selectedStudent?->is_recite_before)
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-medium">
                            سبق له التسميع
                        </span>
                    @else
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-neutral-200 text-neutral-600 font-medium">
                            لم يسمع بعد
                        </span>
                    @endif
                </div>
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

            <form
                wire:submit="proceedFromSetup"
                class="space-y-5"
                x-data="{ examType: @js($examType) }"
            >

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

                {{-- Visual state is Alpine-driven; wire:model (no .live) syncs to $examType
                     only when the form submits, so toggling doesn't fire a request. --}}
                <flux:field>
                    <flux:label>نوع الاختبار</flux:label>
                    <div class="grid grid-cols-2 gap-3">
                        <label
                            class="flex items-center justify-center border rounded-lg px-4 py-3 cursor-pointer transition-colors"
                            :class="examType === 'full_quran'
                                ? 'border-primary-400 bg-primary-50'
                                : 'border-neutral-200 hover:border-neutral-300'"
                        >
                            <input type="radio" wire:model="examType" x-model="examType" value="full_quran" class="hidden">
                            <span class="font-medium text-sm"
                                  :class="examType === 'full_quran' ? 'text-primary-700' : 'text-neutral-700'">
                                القرآن كاملاً
                            </span>
                        </label>
                        <label
                            class="flex items-center justify-center border rounded-lg px-4 py-3 cursor-pointer transition-colors"
                            :class="examType === 'half_quran'
                                ? 'border-primary-400 bg-primary-50'
                                : 'border-neutral-200 hover:border-neutral-300'"
                        >
                            <input type="radio" wire:model="examType" x-model="examType" value="half_quran" class="hidden">
                            <span class="font-medium text-sm"
                                  :class="examType === 'half_quran' ? 'text-primary-700' : 'text-neutral-700'">
                                نصف القرآن
                            </span>
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
                        متابعة
                    </flux:button>
                </div>

            </form>

        </div>
    </div>

    {{-- ══════════════════════════════════════════ --}}
    {{-- STEP: selecting_groups (half_quran only)   --}}
    {{-- Alpine-managed selection: no request per click. One request on submit. --}}
    {{-- ══════════════════════════════════════════ --}}
    @elseif($step === 'selecting_groups')
    <div
        wire:ignore
        wire:key="step-selecting-groups"
        class="flex-1 flex items-center justify-center p-6"
        x-data="{
            selected: @js($selectedGroups),
            submitting: false,
            toggle(g) {
                const i = this.selected.indexOf(g);
                if (i !== -1) { this.selected.splice(i, 1); return; }
                if (this.selected.length >= 3) return;
                this.selected.push(g);
            },
            isSelected(g) { return this.selected.includes(g) },
            order(g)      { return this.selected.indexOf(g) + 1 },
            disabled(g)   { return !this.isSelected(g) && this.selected.length >= 3 },
            async submit() {
                if (this.selected.length !== 3 || this.submitting) return;
                this.submitting = true;
                try { await $wire.proceedFromGroups(this.selected); }
                finally { this.submitting = false; }
            }
        }"
    >
        <div class="w-full max-w-xl">

            <div class="text-center mb-6">
                <p class="text-xs uppercase tracking-wider text-neutral-400 mb-1.5 font-medium">{{ $this->selectedStudent?->fullName() }}</p>
                <h2 class="text-2xl font-bold text-neutral-900">اختر 3 مجموعات</h2>
                <p class="text-sm text-neutral-500 mt-1">
                    سيُسحب سؤال عشوائي من كل مجموعة. لا يمكن اختيار نفس المجموعة مرتين.
                </p>
                <p class="text-xs text-primary-600 font-semibold mt-2 tabular-nums">
                    <span x-text="selected.length"></span> / 3
                </p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-6">
                @foreach($groups as $g)
                    <button
                        type="button"
                        @click="toggle({{ $g->value }})"
                        :disabled="disabled({{ $g->value }})"
                        class="relative rounded-2xl border-2 px-4 py-5 text-center transition-all"
                        :class="isSelected({{ $g->value }})
                            ? 'border-primary-500 bg-primary-50 shadow-[0_6px_20px_-8px_rgba(59,130,246,0.4)]'
                            : (disabled({{ $g->value }})
                                ? 'border-neutral-200 bg-white opacity-40 cursor-not-allowed'
                                : 'border-neutral-200 bg-white hover:border-neutral-300 cursor-pointer')"
                    >
                        <span
                            x-show="isSelected({{ $g->value }})"
                            class="absolute top-2 end-2 w-6 h-6 rounded-full bg-primary-500 text-white text-xs font-bold flex items-center justify-center"
                            x-text="order({{ $g->value }})"
                        ></span>
                        <div class="text-lg sm:text-xl font-extrabold text-neutral-900 leading-tight">
                            {{ $g->surahRange()[0] }}
                            <span class="text-neutral-400 font-normal mx-0.5">—</span>
                            {{ $g->surahRange()[1] }}
                        </div>
                        <div class="text-[11px] text-neutral-400 mt-2">{{ $g->fullLabel() }}</div>
                    </button>
                @endforeach
            </div>

            <div class="flex gap-3">
                <flux:button type="button" wire:click="$set('step', 'setup')" variant="outline" class="flex-1">
                    رجوع
                </flux:button>
                <button
                    type="button"
                    @click="submit()"
                    :disabled="selected.length !== 3 || submitting"
                    class="flex-1 py-3 rounded-xl font-semibold text-sm transition-all
                           bg-gradient-to-b from-primary-500 to-primary-600 text-white
                           hover:from-primary-600 hover:to-primary-700
                           shadow-[0_8px_24px_-8px_rgba(59,130,246,0.5)]
                           disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:shadow-none"
                >
                    <span x-show="!submitting">معاينة الأسئلة</span>
                    <span x-show="submitting">جارٍ التوليد...</span>
                </button>
            </div>

        </div>
    </div>

    {{-- ══════════════════════════════════════════ --}}
    {{-- STEP: previewing — Alpine-only tabs, no server hits for navigation --}}
    {{-- ══════════════════════════════════════════ --}}
    @elseif($step === 'previewing')
    <div
        wire:ignore
        wire:key="step-previewing"
        class="flex-1 flex items-start justify-center p-4 sm:p-6"
        x-data="{
            tab: 1,
            qs: @js($pickedQuestions),
            get current() { return this.qs[this.tab] || null }
        }"
    >
        <div class="w-full max-w-2xl">

            <div class="text-center mb-5">
                <p class="text-xs uppercase tracking-wider text-neutral-400 mb-1.5 font-medium">{{ $this->selectedStudent?->fullName() }}</p>
                <h2 class="text-xl sm:text-2xl font-bold text-neutral-900">معاينة الأسئلة المختارة</h2>
                <p class="text-xs text-neutral-500 mt-1">راجع الأسئلة الثلاثة قبل بدء الجلسة.</p>
            </div>

            {{-- Tabs --}}
            <div class="border-b border-neutral-200 mb-5">
                <nav class="flex gap-1 justify-center" aria-label="معاينة الأسئلة">
                    <template x-for="n in [1, 2, 3]" :key="n">
                        <button
                            type="button"
                            @click="tab = n"
                            class="px-4 sm:px-5 py-2.5 text-sm font-medium transition-colors -mb-px border-b-2"
                            :class="tab === n
                                ? 'border-primary-600 text-primary-700'
                                : 'border-transparent text-neutral-500 hover:text-neutral-700'"
                        >
                            <span x-text="'السؤال ' + n"></span>
                            <span
                                x-show="qs[n]"
                                class="ms-1.5 text-[10px] px-1.5 py-0.5 rounded-full"
                                :class="tab === n ? 'bg-primary-100 text-primary-700' : 'bg-neutral-100 text-neutral-500'"
                                x-text="qs[n]?.group_label"
                            ></span>
                        </button>
                    </template>
                </nav>
            </div>

            {{-- Active panel --}}
            <div x-show="current" class="bg-white rounded-2xl border border-neutral-200 p-5 sm:p-7 shadow-sm">
                <div class="mb-5">
                    <div class="text-[10px] uppercase tracking-wider text-neutral-400">المجموعة</div>
                    <div class="text-base font-bold text-primary-700 mt-0.5">
                        <span x-text="current?.group_label"></span>
                        <span class="text-neutral-400 font-normal">—</span>
                        <span x-text="current?.group_full_label"></span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="border border-neutral-200 rounded-xl p-4 bg-neutral-50/50">
                        <div class="text-[10px] font-medium text-neutral-500 mb-2">البداية</div>
                        <div class="space-y-1.5 text-sm">
                            <div class="flex items-baseline gap-2">
                                <span class="text-neutral-400 text-xs w-10">السورة:</span>
                                <span class="font-semibold text-neutral-900" x-text="current?.start_surah_name"></span>
                                <span class="text-[10px] text-neutral-400" x-text="'(' + current?.start_surah + ')'"></span>
                            </div>
                            <div class="flex items-baseline gap-2">
                                <span class="text-neutral-400 text-xs w-10">الآية:</span>
                                <span class="text-neutral-700 tabular-nums" x-text="current?.start_ayah"></span>
                            </div>
                            <div class="flex items-baseline gap-2">
                                <span class="text-neutral-400 text-xs w-10">الصفحة:</span>
                                <span class="text-neutral-700 tabular-nums" x-text="current?.start_page"></span>
                            </div>
                        </div>
                    </div>

                    <div class="border border-neutral-200 rounded-xl p-4 bg-neutral-50/50">
                        <div class="text-[10px] font-medium text-neutral-500 mb-2">النهاية</div>
                        <div class="space-y-1.5 text-sm">
                            <div class="flex items-baseline gap-2">
                                <span class="text-neutral-400 text-xs w-10">السورة:</span>
                                <span class="font-semibold text-neutral-900" x-text="current?.end_surah_name"></span>
                                <span class="text-[10px] text-neutral-400" x-text="'(' + current?.end_surah + ')'"></span>
                            </div>
                            <div class="flex items-baseline gap-2">
                                <span class="text-neutral-400 text-xs w-10">الآية:</span>
                                <span class="text-neutral-700 tabular-nums" x-text="current?.end_ayah"></span>
                            </div>
                            <div class="flex items-baseline gap-2">
                                <span class="text-neutral-400 text-xs w-10">الصفحة:</span>
                                <span class="text-neutral-700 tabular-nums" x-text="current?.end_page"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="mt-5 flex items-center justify-end gap-2">
                <flux:button wire:click="backFromPreview" variant="outline" size="sm">
                    رجوع
                </flux:button>
                <flux:button wire:click="confirmAndStart" variant="primary">
                    تأكيد وبدء الاختبار
                </flux:button>
            </div>

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
        data-rq='@json($pickedQuestions)'
        data-cq="{{ $currentQuestion }}"
        data-rs="{{ $rulingsScore }}"
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
            rq: {},                             // recitation question snapshots, keyed by position
            currentQ: 1,
            subStep: 'question',                // 'question' | 'rulings' | 'summary'
            rulingsScore: 0,
            spq: 30, de: 2, dw: 1, dc: 0.5,     // score-per-question + deductions
            saving: false,
            savedAt: null,
            savingExam: false,

            // ── Computed ──
            get q() { return this.qs[this.currentQ] },
            get currentRq() { return this.rq[this.currentQ] || null },
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
                this.rq           = JSON.parse(d.rq || '{}');
                this.currentQ     = +d.cq;
                this.rulingsScore = +d.rs || 0;
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
                <p class="text-xs uppercase tracking-wider text-neutral-400 mb-1 font-medium">{{ $this->selectedStudent?->fullName() }}</p>
                <div class="flex items-center justify-center gap-1.5 flex-wrap mb-2.5">
                    @if($this->selectedStudent?->gender)
                        <span class="text-[9px] px-1.5 py-0.5 rounded-full {{ $this->selectedStudent->gender->value === 'male' ? 'bg-sky-50 text-sky-700' : 'bg-pink-50 text-pink-700' }}">
                            {{ $this->selectedStudent->gender->label() }}
                        </span>
                    @endif
                    @if($this->selectedStudent?->student_zone)
                        @php
                            $zones = [
                                'East Gaza' => 'شرق غزة',
                                'West Gaza' => 'غرب غزة',
                                'North Gaza' => 'شمال غزة',
                                'South Gaza' => 'جنوب غزة',
                            ];
                        @endphp
                        <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-neutral-100 text-neutral-600">
                            {{ $zones[$this->selectedStudent->student_zone] ?? $this->selectedStudent->student_zone }}
                        </span>
                    @endif
                    @if($this->selectedStudent?->is_recite_before)
                        <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-amber-50 text-amber-700 font-medium">
                            سبق له التسميع
                        </span>
                    @endif
                </div>
                <h2 class="text-xl font-bold text-neutral-800 tracking-tight">
                    <span x-text="'السؤال ' + currentQ + ' من 3'"></span>
                    <span x-show="currentRq" class="ms-2 text-sm font-semibold text-primary-600"
                          x-text="currentRq ? '· ' + currentRq.group_label : ''"></span>
                </h2>
            </div>

            {{-- ─── Recitation question card: surah/ayah/page (BR-EXAM-10/11) ─── --}}
            <div x-show="currentRq" class="w-full bg-gradient-to-br from-white via-amber-50/30 to-amber-100/20
                        rounded-2xl border border-amber-200/60 px-5 py-4
                        shadow-[0_4px_20px_-8px_rgba(217,119,6,0.15)]">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <div class="text-[10px] uppercase tracking-wider text-amber-700/70 font-bold mb-1.5">من</div>
                        <div class="font-bold text-neutral-900 text-base" x-text="currentRq?.start_surah_name"></div>
                        <div class="text-xs text-neutral-500 mt-0.5">
                            آية <span class="font-semibold tabular-nums" x-text="currentRq?.start_ayah"></span>
                            <span class="text-neutral-300 mx-1">·</span>
                            صفحة <span class="font-semibold tabular-nums" x-text="currentRq?.start_page"></span>
                        </div>
                    </div>
                    <div class="border-s border-amber-200/60 ps-4">
                        <div class="text-[10px] uppercase tracking-wider text-amber-700/70 font-bold mb-1.5">إلى</div>
                        <div class="font-bold text-neutral-900 text-base" x-text="currentRq?.end_surah_name"></div>
                        <div class="text-xs text-neutral-500 mt-0.5">
                            آية <span class="font-semibold tabular-nums" x-text="currentRq?.end_ayah"></span>
                            <span class="text-neutral-300 mx-1">·</span>
                            صفحة <span class="font-semibold tabular-nums" x-text="currentRq?.end_page"></span>
                        </div>
                    </div>
                </div>
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
                    <span class="text-exam-error/80 text-xs">فتح</span>
                </div>
                <div class="flex items-center gap-1.5 bg-amber-50/70 px-3 py-1.5 rounded-full">
                    <span class="font-bold text-exam-warning tabular-nums" x-text="q.warnings_count"></span>
                    <span class="text-exam-warning/80 text-xs">تنبيه</span>
                </div>
                <div class="flex items-center gap-1.5 bg-cyan-50/70 px-3 py-1.5 rounded-full">
                    <span class="font-bold text-exam-continuation tabular-nums" x-text="q.continuations_count"></span>
                    <span class="text-exam-continuation/80 text-xs">تردد</span>
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
                        <span class="text-base font-bold text-exam-error">فتح على الطالب</span>
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
                        <span class="text-base font-bold text-exam-warning">تنبيه</span>
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
                        <span class="text-base font-bold text-exam-continuation">تردد / استرسال</span>
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
                                  x-text="'(' + qs[n].errors_count + 'ف ' + qs[n].warnings_count + 'ت ' + qs[n].continuations_count + 'س)'"></span>
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
