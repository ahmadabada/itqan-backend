<div class="p-4 sm:p-6 lg:p-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5 sm:mb-6">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-neutral-900">الاختبارات</h2>
            <p class="text-neutral-500 text-xs sm:text-sm mt-0.5">{{ $exams->total() }} اختبار</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-neutral-200 p-4 mb-5">
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3">

            <div class="lg:col-span-2">
                <label class="block text-xs text-neutral-500 mb-1">بحث (طالب / هوية)</label>
                <flux:input wire:model.live.debounce.300ms="search" placeholder="اسم الطالب أو رقم الهوية..." size="sm" />
            </div>

            <div>
                <label class="block text-xs text-neutral-500 mb-1">الحالة</label>
                <flux:select wire:model.live="statusFilter" size="sm">
                    <flux:select.option value="">الكل</flux:select.option>
                    @foreach($statuses as $status)
                        <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div>
                <label class="block text-xs text-neutral-500 mb-1">المختبر</label>
                <flux:select wire:model.live="examinerFilter" size="sm">
                    <flux:select.option value="">الكل</flux:select.option>
                    @foreach($examiners as $examiner)
                        <flux:select.option value="{{ $examiner->id }}">{{ $examiner->fullName() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div>
                <label class="block text-xs text-neutral-500 mb-1">الجنس</label>
                <flux:select wire:model.live="genderFilter" size="sm">
                    <flux:select.option value="">الكل</flux:select.option>
                    <flux:select.option value="male">ذكر</flux:select.option>
                    <flux:select.option value="female">أنثى</flux:select.option>
                </flux:select>
            </div>

            <div>
                <label class="block text-xs text-neutral-500 mb-1">من تاريخ</label>
                <flux:input type="date" wire:model.live="dateFrom" size="sm" />
            </div>

            <div>
                <label class="block text-xs text-neutral-500 mb-1">إلى تاريخ</label>
                <flux:input type="date" wire:model.live="dateTo" size="sm" />
            </div>

        </div>

        @if($search || $statusFilter || $examinerFilter || $genderFilter || $dateFrom || $dateTo)
            <div class="mt-3 pt-3 border-t border-neutral-100">
                <button wire:click="clearFilters" class="text-xs text-neutral-500 hover:text-danger-600 transition-colors">
                    مسح الفلاتر
                </button>
            </div>
        @endif
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[800px] whitespace-nowrap">
            <thead class="bg-neutral-50 border-b border-neutral-200">
                <tr>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium w-12">#</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الطالب</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">المختبر</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">النوع</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">
                        <button wire:click="sort('total_score')" class="flex items-center gap-1 hover:text-neutral-900">
                            الدرجة
                            @if($sortBy === 'total_score')
                                <span class="text-primary-500">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </button>
                    </th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الحالة</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">
                        <button wire:click="sort('started_at')" class="flex items-center gap-1 hover:text-neutral-900">
                            التاريخ
                            @if($sortBy === 'started_at')
                                <span class="text-primary-500">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </button>
                    </th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse($exams as $exam)
                    <tr class="hover:bg-neutral-50 transition-colors">
                        <td class="px-4 py-3 text-neutral-400 tabular-nums">{{ $loop->iteration + ($exams->firstItem() ?? 1) - 1 }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-neutral-900">{{ $exam->student?->fullName() }}</div>
                            <div class="text-xs text-neutral-400 font-mono mt-0.5">{{ $exam->student?->national_id ?? '—' }}</div>
                        </td>
                        <td class="px-4 py-3 text-neutral-700">{{ $exam->examiner?->fullName() ?? '—' }}</td>
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
                            {{ $exam->started_at?->format('Y-m-d H:i') }}
                        </td>
                        <td class="px-4 py-3 text-end">
                            <a
                                href="{{ route('admin.exams.show', $exam->id) }}"
                                wire:navigate
                                class="text-xs font-medium text-primary-600 hover:text-primary-700 transition-colors"
                            >
                                التفاصيل ←
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-neutral-400">
                            لا توجد اختبارات مطابقة للفلاتر.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        @if($exams->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200">
                {{ $exams->links() }}
            </div>
        @endif
    </div>

</div>
