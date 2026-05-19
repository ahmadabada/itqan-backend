<div class="p-4 sm:p-6 lg:p-8">

    {{-- Header --}}
    <div class="mb-5 sm:mb-6">
        <a href="{{ route('admin.merges.history') }}" wire:navigate
           class="inline-flex items-center gap-1.5 text-xs text-neutral-500 hover:text-neutral-700 transition-colors mb-2">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
            <span>سجل الدمج</span>
        </a>
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-xl sm:text-2xl font-bold text-neutral-900">
                    تفاصيل عملية الدمج #{{ $operation->id }}
                </h2>
                <p class="text-neutral-500 text-xs sm:text-sm mt-0.5">
                    {{ $operation->performed_at?->translatedFormat('l، j F Y - g:i A') }}
                </p>
            </div>
            <div>
                @if($operation->undone_at)
                    <span class="text-xs px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 font-medium">تم التراجع</span>
                @else
                    <span class="text-xs px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 font-medium">نشطة</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Summary card --}}
    <div class="bg-white rounded-xl border border-neutral-200 p-5 mb-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <p class="text-[11px] text-neutral-400 mb-1">تنفيذ الدمج</p>
                <p class="text-sm text-neutral-800">
                    {{ $operation->performedBy ? trim(($operation->performedBy->first_name ?? '') . ' ' . ($operation->performedBy->family_name ?? '')) : '—' }}
                </p>
                <p class="text-xs text-neutral-500 mt-0.5">{{ $operation->performed_at?->format('Y-m-d g:i A') }}</p>
            </div>
            <div>
                <p class="text-[11px] text-neutral-400 mb-1">الـ master</p>
                @if($operation->masterStudent)
                    <a href="{{ route('admin.students.show', $operation->masterStudent->id) }}" wire:navigate
                       class="text-sm text-primary-700 hover:underline">
                        #{{ $operation->masterStudent->id }} — {{ $operation->masterStudent->fullName() }}
                    </a>
                    <p class="text-xs text-neutral-500 mt-0.5 font-mono">{{ $operation->masterStudent->national_id ?? '—' }}</p>
                @else
                    <span class="text-sm text-neutral-300">محذوف</span>
                @endif
            </div>
            @if($operation->notes)
                <div class="sm:col-span-2">
                    <p class="text-[11px] text-neutral-400 mb-1">ملاحظات الدمج</p>
                    <p class="text-sm text-neutral-700 whitespace-pre-wrap">{{ $operation->notes }}</p>
                </div>
            @endif
            @if($operation->undone_at)
                <div class="sm:col-span-2 border-t border-neutral-100 pt-4">
                    <p class="text-[11px] text-amber-600 mb-1">تم التراجع</p>
                    <p class="text-sm text-neutral-800">
                        بواسطة
                        {{ $operation->undoneBy ? trim(($operation->undoneBy->first_name ?? '') . ' ' . ($operation->undoneBy->family_name ?? '')) : '—' }}
                        — {{ $operation->undone_at?->format('Y-m-d g:i A') }}
                    </p>
                    @if($operation->undo_notes)
                        <p class="text-sm text-neutral-600 mt-1 whitespace-pre-wrap">{{ $operation->undo_notes }}</p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Students: before / after side by side --}}
    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden mb-5">
        <div class="px-5 py-3 border-b border-neutral-200 bg-neutral-50">
            <h3 class="text-sm font-semibold text-neutral-800">السجلات قبل وبعد الدمج</h3>
            <p class="text-xs text-neutral-500 mt-0.5">المقارنة بين الـ snapshot (وقت الدمج) والحالة الحالية في قاعدة البيانات.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[800px]">
                <thead class="bg-neutral-50/50 border-b border-neutral-200">
                    <tr class="text-xs text-neutral-500">
                        <th class="text-start px-4 py-2 font-medium">#</th>
                        <th class="text-start px-4 py-2 font-medium">الحالة</th>
                        <th class="text-start px-4 py-2 font-medium">رقم الهوية</th>
                        <th class="text-start px-4 py-2 font-medium">الاسم الكامل</th>
                        <th class="text-start px-4 py-2 font-medium">قبل: master_id</th>
                        <th class="text-start px-4 py-2 font-medium">بعد: master_id</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse($snapshot['students'] ?? [] as $row)
                        @php
                            $current  = $currentStudents->get($row['id']);
                            $isMaster = $row['id'] === $operation->master_student_id;
                            $beforeName = trim(implode(' ', array_filter([
                                $row['first_name']  ?? null,
                                $row['second_name'] ?? null,
                                $row['third_name']  ?? null,
                                $row['family_name'] ?? null,
                            ])));
                        @endphp
                        <tr class="{{ $isMaster ? 'bg-primary-50/40' : '' }}">
                            <td class="px-4 py-3 text-neutral-400 tabular-nums">#{{ $row['id'] }}</td>
                            <td class="px-4 py-3">
                                @if($isMaster)
                                    <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-primary-100 text-primary-700 font-bold">MASTER</span>
                                @else
                                    <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-neutral-100 text-neutral-600">مدموج</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-neutral-700">
                                <div>{{ $row['national_id'] ?? '—' }}</div>
                                @if($current && $current->national_id !== ($row['national_id'] ?? null))
                                    <div class="text-[10px] text-amber-600 mt-0.5">
                                        → {{ $current->national_id ?? '—' }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-neutral-800">
                                <div>{{ $beforeName ?: '—' }}</div>
                                @if($current && $current->fullName() !== $beforeName)
                                    <div class="text-xs text-amber-600 mt-0.5">→ {{ $current->fullName() }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-neutral-500 font-mono text-xs">
                                {{ $row['master_id'] ?? '—' }}
                            </td>
                            <td class="px-4 py-3 font-mono text-xs">
                                @if($current)
                                    @if($current->master_id)
                                        <span class="text-neutral-700">#{{ $current->master_id }}</span>
                                    @else
                                        <span class="text-neutral-300">—</span>
                                    @endif
                                @else
                                    <span class="text-rose-500">محذوف</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-neutral-400">لا توجد سجلات في الـ snapshot.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Exams across all merged students --}}
    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden mb-5">
        <div class="px-5 py-3 border-b border-neutral-200 bg-neutral-50">
            <h3 class="text-sm font-semibold text-neutral-800">اختبارات السجلات المدموجة</h3>
            <p class="text-xs text-neutral-500 mt-0.5">
                @if($operation->authoritative_exam_id)
                    الاختبار المعتمد: #{{ $operation->authoritative_exam_id }}
                @else
                    لا يوجد اختبار معتمد.
                @endif
            </p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[700px]">
                <thead class="bg-neutral-50/50 border-b border-neutral-200">
                    <tr class="text-xs text-neutral-500">
                        <th class="text-start px-4 py-2 font-medium">#</th>
                        <th class="text-start px-4 py-2 font-medium">الطالب</th>
                        <th class="text-start px-4 py-2 font-medium">الدرجة</th>
                        <th class="text-start px-4 py-2 font-medium">المختبر</th>
                        <th class="text-start px-4 py-2 font-medium">تاريخ الإنجاز</th>
                        <th class="text-start px-4 py-2 font-medium">معتمد؟</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse($currentExams as $exam)
                        <tr class="{{ $exam->is_authoritative ? 'bg-emerald-50/40' : '' }}">
                            <td class="px-4 py-3 text-neutral-400 tabular-nums">#{{ $exam->id }}</td>
                            <td class="px-4 py-3 text-neutral-700">#{{ $exam->student_id }}</td>
                            <td class="px-4 py-3 tabular-nums text-neutral-800">
                                {{ $exam->total_score !== null ? number_format((float) $exam->total_score, 2) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-neutral-600">
                                {{ $exam->examiner ? trim(($exam->examiner->first_name ?? '') . ' ' . ($exam->examiner->family_name ?? '')) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-neutral-500 text-xs">
                                {{ $exam->completed_at?->format('Y-m-d g:i A') ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($exam->is_authoritative)
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-medium">معتمد</span>
                                @else
                                    <span class="text-xs text-neutral-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-neutral-400">لا توجد اختبارات.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
