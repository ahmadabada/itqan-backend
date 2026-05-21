<div class="bg-white rounded-2xl shadow-sm border border-neutral-200 p-8">

    <div class="text-center mb-6">
        <h2 class="text-xl font-bold text-neutral-900">الاستعلام عن النتيجة</h2>
    </div>

    {{-- Query disabled notice --}}
    @if(! $queryEnabled)
        <div class="bg-neutral-50 border border-neutral-200 rounded-xl px-5 py-6 text-center">
            <p class="font-medium text-neutral-700">الاستعلام عن النتائج غير متاح حالياً</p>
            <p class="text-sm text-neutral-500 mt-1">تواصل مع أكاديمية الإتقان للمزيد من المعلومات</p>
        </div>
    @else

        {{-- Search form --}}
        <form wire:submit="search" class="space-y-4 mb-6">
            <flux:field>
                <flux:label>رقم الهوية</flux:label>
                <flux:input
                    wire:model="national_id"
                    type="text"
                    inputmode="numeric"
                    maxlength="9"
                    placeholder="9 أرقام"
                    autofocus
                />
                <flux:error name="national_id" />
            </flux:field>
            <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled">
                <span wire:loading.remove>استعلام</span>
                <span wire:loading>جارٍ البحث...</span>
            </flux:button>
        </form>

        {{-- Results --}}
        @if($searched)
            @if(! $student)
                <div class="bg-neutral-50 rounded-xl px-5 py-4 text-center">
                    <p class="text-neutral-500">لا يوجد طالب بهذا الرقم في قاعدة البيانات.</p>
                </div>
            @else
                @if($wasMerged)
                    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        رقم الهوية الذي أدخلته يخص سجلاً تم دمجه. النتيجة المعروضة من السجل الموحَّد.
                    </div>
                @endif

                @if(! $exam)
                    <div class="bg-neutral-50 rounded-xl px-5 py-4 text-center">
                        <p class="font-medium text-neutral-900">{{ $student->fullName() }}</p>
                        <p class="text-neutral-500 text-sm mt-2">لا توجد نتيجة معتمدة لهذا الطالب بعد.</p>
                    </div>
                @else
                {{-- Result card --}}
                <div class="border border-neutral-200 rounded-xl overflow-hidden">

                    {{-- Pass/Fail banner --}}
                    @php
                        $isFemale = $student->gender?->value === 'female';
                        $youPron  = $isFemale ? 'أنتِ' : 'أنت';
                        $passWord = $isFemale ? 'مجازة' : 'مجاز';
                        $kSuffix  = $isFemale ? 'كِ' : 'ك'; // ضمير المخاطب
                    @endphp
                    @if($exam->is_passed)
                        <div class="py-8 px-5 text-center bg-success-50">
                            <p class="text-sm text-success-700 mb-2">نبارك ل{{ $kSuffix }} اجتياز{{ $kSuffix }} اختبارات التصفية ضمن مشروع</p>
                            <p class="text-base font-bold text-success-800 mb-4">صفوة الساردين على خطى أبي صلاح الدين</p>
                            <div class="flex items-baseline justify-center gap-2">
                                <span class="text-base font-medium text-success-700">النتيجة:</span>
                                <span class="text-5xl font-black text-success-700 leading-none">
                                    {{ number_format($exam->total_score, 1) }}
                                </span>
                                <span class="text-sm text-success-600">/ 100</span>
                            </div>
                            <p class="text-sm font-medium text-success-700 mt-4">ننتظر{{ $kSuffix }} في السرد يوم عرفة</p>
                        </div>
                    @else
                        <div class="py-8 px-5 text-center bg-danger-50">
                            <p class="text-lg font-bold text-danger-700 mb-2">نعتذر من{{ $kSuffix }} {{ $youPron }} غير {{ $passWord }}</p>
                            @if($exam->total_score > 60)
                                <div class="flex items-baseline justify-center gap-2 mt-3 mb-3">
                                    <span class="text-base font-medium text-danger-700">النتيجة:</span>
                                    <span class="text-4xl font-black text-danger-700 leading-none">
                                        {{ number_format($exam->total_score, 1) }}
                                    </span>
                                    <span class="text-sm text-danger-600">/ 100</span>
                                </div>
                            @endif
                            <p class="text-sm text-danger-600">نتمنى ل{{ $kSuffix }} التوفيق والسداد في المرات القادمة</p>
                        </div>
                    @endif

                    {{-- Details --}}
                    <div class="divide-y divide-neutral-100 text-sm">
                        <div class="flex justify-between px-5 py-3">
                            <span class="text-neutral-500">الاسم</span>
                            <span class="font-medium text-neutral-900">{{ $student->fullName() }}</span>
                        </div>
                        <div class="flex justify-between px-5 py-3">
                            <span class="text-neutral-500">رقم الهوية</span>
                            <span class="font-mono text-neutral-700">{{ $student->national_id }}</span>
                        </div>
                        <div class="flex justify-between px-5 py-3">
                            <span class="text-neutral-500">تاريخ الاختبار</span>
                            <span class="text-neutral-700">{{ $exam->completed_at?->format('Y/m/d') ?? '—' }}</span>
                        </div>
                    </div>

                    {{-- PDF download (BR-QUERY-04) — مخفي حالياً. الكنترولر والـ route ما زالا موجودين --}}
                    {{--
                    <div class="p-5 border-t border-neutral-100">
                        <a
                            href="{{ route('public.result-pdf', $exam->id) }}"
                            target="_blank"
                            class="flex items-center justify-center gap-2 w-full bg-primary-500 hover:bg-primary-600 text-white font-medium py-3 px-5 rounded-lg transition-colors text-sm"
                        >
                            تحميل وثيقة النتيجة (PDF)
                        </a>
                    </div>
                    --}}

                </div>
                @endif {{-- end @if(! $exam) --}}
            @endif {{-- end @if(! $student) --}}
        @endif {{-- end @if($searched) --}}

    @endif {{-- end @if(! $queryEnabled) --}}

</div>
