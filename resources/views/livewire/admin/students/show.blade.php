<div class="p-4 sm:p-6 lg:p-8">

    {{-- Merged record banner: this row was merged into another student, so it
         should not be treated as an independent identity outside the merge UI. --}}
    @if($student->master_id)
        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 flex items-center gap-3">
            <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
            </svg>
            <div class="flex-1 min-w-0 text-sm">
                <p class="font-semibold text-amber-900">هذا السجل مدموج في سجل آخر</p>
                <p class="text-amber-700 mt-0.5">
                    السجل الأساسي:
                    <a href="{{ route('admin.students.show', $student->master_id) }}" wire:navigate class="font-medium underline hover:text-amber-900">
                        #{{ $student->master_id }}{{ $student->master ? ' — ' . $student->master->fullName() : '' }}
                    </a>
                </p>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="mb-6 sm:mb-8 flex items-center justify-between">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-neutral-900">{{ $student->fullName() }}</h2>
            <p class="text-neutral-500 text-sm mt-0.5 font-mono">{{ $student->national_id ?? 'بدون هوية' }}</p>
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
                                <dt class="text-xs font-medium text-neutral-500">منطقة الطالب</dt>
                                <dd class="mt-1 text-sm text-neutral-900">
                                    @php
                                        $zones = [
                                            'East Gaza' => 'شرق غزة',
                                            'West Gaza' => 'غرب غزة',
                                            'North Gaza' => 'شمال غزة',
                                            'South Gaza' => 'جنوب غزة',
                                        ];
                                    @endphp
                                    {{ $student->student_zone ? ($zones[$student->student_zone] ?? $student->student_zone) : '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-neutral-500">هل سبق له التسميع؟</dt>
                                <dd class="mt-1 text-sm text-neutral-900">
                                    @if($student->is_recite_before)
                                        <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">نعم</span>
                                    @else
                                        <span class="inline-flex items-center rounded-md bg-neutral-50 px-2 py-1 text-xs font-medium text-neutral-600 ring-1 ring-inset ring-neutral-500/10">لا</span>
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>
                    <div class="p-5 sm:p-6">
                        <h3 class="text-sm font-semibold text-neutral-900 mb-4">معلومات النظام</h3>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-xs font-medium text-neutral-500">مصدر الإنشاء</dt>
                                <dd class="mt-1 text-sm text-neutral-900">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-neutral-100 text-neutral-800">
                                        {{ $student->created_via?->label() ?? '—' }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-neutral-500">تم الإنشاء بواسطة</dt>
                                <dd class="mt-1 text-sm text-neutral-900">{{ $student->createdBy?->fullName() ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-neutral-500">تاريخ الإضافة</dt>
                                <dd class="mt-1 text-sm text-neutral-900">{{ $student->created_at?->format('Y-m-d g:i A') ?? '—' }}</dd>
                            </div>
                            
                            @if($student->master_id)
                                <div class="pt-4 border-t border-neutral-100">
                                    <dt class="text-xs font-medium text-amber-600">حالة الدمج</dt>
                                    <dd class="mt-1 text-sm text-neutral-900">
                                        تم دمج هذا الحساب في الحساب الأساسي 
                                        <a href="{{ route('admin.students.show', $student->master_id) }}" class="text-primary-600 hover:underline">#{{ $student->master_id }}</a>
                                        بواسطة {{ $student->mergedBy?->fullName() ?? '—' }}
                                        بتاريخ {{ $student->merged_at?->format('Y-m-d g:i A') ?? '—' }}
                                    </dd>
                                </div>
                            @endif
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
                                <th class="text-start px-4 py-3 text-neutral-600 font-medium">المختبر</th>
                                <th class="text-start px-4 py-3 text-neutral-600 font-medium">النوع</th>
                                <th class="text-start px-4 py-3 text-neutral-600 font-medium">الدرجة</th>
                                <th class="text-start px-4 py-3 text-neutral-600 font-medium">الحالة</th>
                                <th class="text-start px-4 py-3 text-neutral-600 font-medium">التاريخ</th>
                                <th class="text-start px-4 py-3 text-neutral-600 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @forelse($exams as $exam)
                                @php $fromMerged = $exam->student_id !== $student->id; @endphp
                                @php $isApproved = $exam->status?->value === 'approved'; @endphp
                                <tr class="hover:bg-neutral-50 transition-colors {{ $isApproved ? 'bg-emerald-50/30' : '' }}">
                                    <td class="px-4 py-3 text-neutral-400 tabular-nums">{{ $loop->iteration + ($exams->firstItem() ?? 1) - 1 }}</td>
                                    <td class="px-4 py-3 text-neutral-700">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span>{{ $exam->examiner?->fullName() ?? '—' }}</span>
                                            @if($isApproved)
                                                <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-medium">معتمد</span>
                                            @elseif($exam->status?->value === 'excluded')
                                                <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-neutral-100 text-neutral-500 font-medium">مستبعد</span>
                                            @endif
                                            @if($fromMerged)
                                                <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-neutral-100 text-neutral-500" title="من سجل مدموج #{{ $exam->student_id }}">
                                                    مدموج #{{ $exam->student_id }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-neutral-600 text-xs">{{ $exam->exam_type?->label() }}</td>
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
