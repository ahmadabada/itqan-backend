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
            @elseif(! $exam)
                <div class="bg-neutral-50 rounded-xl px-5 py-4 text-center">
                    <p class="font-medium text-neutral-900">{{ $student->fullName() }}</p>
                    <p class="text-neutral-500 text-sm mt-2">لا توجد نتيجة معتمدة لهذا الطالب بعد.</p>
                </div>
            @else
                {{-- Result card --}}
                <div class="border border-neutral-200 rounded-xl overflow-hidden">

                    {{-- Pass/Fail banner --}}
                    <div class="py-6 text-center {{ $exam->is_passed ? 'bg-success-50' : 'bg-danger-50' }}">
                        <p class="text-sm font-medium {{ $exam->is_passed ? 'text-success-600' : 'text-danger-500' }} mb-1">
                            {{ $exam->exam_type->value === 'full_quran' ? 'القرآن الكريم كاملاً' : 'نصف القرآن الكريم' }}
                        </p>
                        <p class="text-5xl font-black {{ $exam->is_passed ? 'text-success-700' : 'text-danger-600' }}">
                            {{ number_format($exam->total_score, 1) }}
                        </p>
                        <p class="text-sm {{ $exam->is_passed ? 'text-success-600' : 'text-danger-500' }} mt-0.5">من 100</p>
                        <div class="mt-3">
                            <span class="inline-block px-4 py-1 rounded-full text-sm font-bold {{ $exam->is_passed ? 'bg-success-100 text-success-700' : 'bg-danger-100 text-danger-600' }}">
                                {{ $exam->is_passed ? '✓ مجاز' : '✗ غير مجاز' }}
                            </span>
                        </div>
                    </div>

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

                    {{-- PDF download (BR-QUERY-04) --}}
                    <div class="p-5 border-t border-neutral-100">
                        <a
                            href="{{ route('public.result-pdf', $exam->id) }}"
                            target="_blank"
                            class="flex items-center justify-center gap-2 w-full bg-primary-500 hover:bg-primary-600 text-white font-medium py-3 px-5 rounded-lg transition-colors text-sm"
                        >
                            تحميل وثيقة النتيجة (PDF)
                        </a>
                    </div>

                </div>
            @endif
        @endif

    @endif

</div>
