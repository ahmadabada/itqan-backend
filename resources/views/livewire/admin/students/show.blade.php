<div class="p-4 sm:p-6 lg:p-8">

    {{-- Header --}}
    <div class="mb-6 sm:mb-8 flex items-center justify-between">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-neutral-900">{{ $student->fullName() }}</h2>
            <p class="text-neutral-500 text-sm mt-0.5 font-mono">{{ $student->national_id }}</p>
        </div>
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('admin.students') }}" wire:navigate class="text-sm font-medium text-neutral-500 hover:text-neutral-900 transition-colors">
            &larr; العودة
        </a>
    </div>

    {{-- Tabs Navigation --}}
    <div class="border-b border-neutral-200 mb-6">
        <nav class="-mb-px flex space-x-6 space-x-reverse" aria-label="Tabs">
            <button
                wire:click="setTab('profile')"
                class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'profile' ? 'border-primary-500 text-primary-600' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300' }}"
            >
                البيانات الشخصية
            </button>
            <button
                wire:click="setTab('exams')"
                class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'exams' ? 'border-primary-500 text-primary-600' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300' }}"
            >
                الاختبارات
            </button>
        </nav>
    </div>

    {{-- Tabs Content --}}
    <div>
        @if($activeTab === 'profile')
            <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x md:divide-x-reverse divide-neutral-200">
                    <div class="p-5 sm:p-6">
                        <h3 class="text-sm font-semibold text-neutral-900 mb-4">المعلومات الأساسية</h3>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-xs font-medium text-neutral-500">الاسم الأول</dt>
                                <dd class="mt-1 text-sm text-neutral-900">{{ $student->first_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-neutral-500">اسم الأب</dt>
                                <dd class="mt-1 text-sm text-neutral-900">{{ $student->second_name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-neutral-500">اسم الجد</dt>
                                <dd class="mt-1 text-sm text-neutral-900">{{ $student->third_name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-neutral-500">اسم العائلة</dt>
                                <dd class="mt-1 text-sm text-neutral-900">{{ $student->family_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-neutral-500">الجنس</dt>
                                <dd class="mt-1 text-sm text-neutral-900">{{ $student->gender?->label() ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-neutral-500">الحلقة</dt>
                                <dd class="mt-1 text-sm text-neutral-900">{{ $student->halaqah?->label() ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                    <div class="p-5 sm:p-6">
                        <h3 class="text-sm font-semibold text-neutral-900 mb-4">معلومات النظام</h3>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-xs font-medium text-neutral-500">تم الإنشاء بواسطة</dt>
                                <dd class="mt-1 text-sm text-neutral-900">{{ $student->createdBy?->fullName() ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-neutral-500">تاريخ الإضافة</dt>
                                <dd class="mt-1 text-sm text-neutral-900">{{ $student->created_at?->format('Y-m-d g:i A') ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        @endif

        @if($activeTab === 'exams')
            <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[800px] whitespace-nowrap">
                        <thead class="bg-neutral-50 border-b border-neutral-200">
                            <tr>
                                <th class="text-start px-4 py-3 text-neutral-600 font-medium w-12">#</th>
                                <th class="text-start px-4 py-3 text-neutral-600 font-medium">المعلم</th>
                                <th class="text-start px-4 py-3 text-neutral-600 font-medium">الأجزاء</th>
                                <th class="text-start px-4 py-3 text-neutral-600 font-medium">الدرجة</th>
                                <th class="text-start px-4 py-3 text-neutral-600 font-medium">الحالة</th>
                                <th class="text-start px-4 py-3 text-neutral-600 font-medium">التاريخ</th>
                                <th class="text-start px-4 py-3 text-neutral-600 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @forelse($exams as $exam)
                                @php $isAuthoritative = (bool) $exam->is_authoritative; @endphp
                                <tr class="hover:bg-neutral-50 transition-colors {{ $isAuthoritative ? 'bg-emerald-50/30' : '' }}">
                                    <td class="px-4 py-3 text-neutral-400 tabular-nums">{{ $loop->iteration + ($exams->firstItem() ?? 1) - 1 }}</td>
                                    <td class="px-4 py-3 text-neutral-700">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span>{{ $exam->instructor?->fullName() ?? '—' }}</span>
                                            @if($isAuthoritative)
                                                <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-medium">النتيجة المعتمدة</span>
                                            @endif
                                            @if($exam->status?->value === 'excluded')
                                                <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-neutral-100 text-neutral-500 font-medium">مستبعد</span>
                                            @endif
                                            @if($exam->authoritative_decision_by)
                                                <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700 font-medium" title="ثبّتها الأدمن يدوياً">مثبّتة</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-neutral-600 text-xs whitespace-nowrap">
                                        {{ $exam->parts_count }} جزء
                                        <span class="text-neutral-400">({{ $exam->new_memorization_parts }} حفظ جديد)</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($exam->total_score !== null)
                                            <span class="font-bold {{ $exam->is_passed ? 'text-success-600' : 'text-danger-500' }}">
                                                {{ number_format($exam->total_score, 1) }}
                                            </span>
                                            <span class="text-xs text-neutral-400">/ 100</span>
                                        @else
                                            <span class="text-neutral-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @php
                                            $statusColors = [
                                                'in_progress'    => 'bg-sky-50 text-sky-700',
                                                'completed'      => 'bg-neutral-100 text-neutral-700',
                                                'pending_review' => 'bg-amber-50 text-amber-700',
                                                'approved'       => 'bg-emerald-50 text-emerald-700',
                                                'rejected'       => 'bg-rose-50 text-rose-700',
                                            ];
                                            $color = $statusColors[$exam->status?->value] ?? 'bg-neutral-100 text-neutral-700';
                                        @endphp
                                        <span class="text-xs px-2 py-0.5 rounded-full {{ $color }}">
                                            {{ $exam->status?->label() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-neutral-500 whitespace-nowrap">
                                        {{ $exam->started_at?->format('Y-m-d g:i A') }}
                                    </td>
                                    <td class="px-4 py-3 text-end">
                                        <a href="{{ route('admin.exams.show', $exam->id) }}" wire:navigate class="text-xs font-medium text-primary-600 hover:text-primary-700 transition-colors">
                                            التفاصيل &larr;
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-12 text-center text-neutral-400">
                                        لا توجد اختبارات لهذا الطالب.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($exams && $exams->hasPages())
                    <div class="px-4 py-3 border-t border-neutral-200">
                        {{ $exams->links() }}
                    </div>
                @endif
            </div>
        @endif
    </div>

</div>
