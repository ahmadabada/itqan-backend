<div class="p-6 max-w-6xl mx-auto">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-neutral-900">اختباراتي</h2>
            <p class="text-neutral-500 text-sm mt-0.5">{{ $exams->total() }} اختبار قمت بتنفيذه</p>
        </div>
        <a href="{{ route('examiner.students') }}" wire:navigate
           class="px-4 py-2 rounded-lg bg-primary-500 text-white text-sm font-medium hover:bg-primary-600 transition-colors">
            الطلاب
        </a>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-neutral-200 p-4 mb-5">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">

            <div class="lg:col-span-2">
                <label class="block text-xs text-neutral-500 mb-1">بحث (طالب / هوية)</label>
                <flux:input wire:model.live.debounce.300ms="search" placeholder="الاسم أو رقم الهوية..." size="sm" />
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

        @if($search || $statusFilter || $genderFilter || $dateFrom || $dateTo)
            <div class="mt-3 pt-3 border-t border-neutral-100">
                <button wire:click="clearFilters" class="text-xs text-neutral-500 hover:text-danger-600 transition-colors">
                    مسح الفلاتر
                </button>
            </div>
        @endif
    </div>

    {{-- List --}}
    <div class="space-y-2">
        @forelse($exams as $exam)
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
            <div class="bg-white rounded-xl border border-neutral-200 p-4 hover:border-primary-200 hover:shadow-sm transition-all flex items-center justify-between gap-4">
                <div class="w-9 h-9 rounded-full bg-neutral-50 flex items-center justify-center text-xs font-bold text-neutral-500 tabular-nums flex-shrink-0">
                    {{ $loop->iteration + ($exams->firstItem() ?? 1) - 1 }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="font-semibold text-neutral-900 truncate">{{ $exam->student?->fullName() }}</p>
                        @if($exam->student?->gender)
                            <span class="text-xs px-1.5 py-0.5 rounded {{ $exam->student->gender->value === 'male' ? 'bg-sky-50 text-sky-700' : 'bg-pink-50 text-pink-700' }}">
                                {{ $exam->student->gender->label() }}
                            </span>
                        @endif
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $color }}">
                            {{ $exam->status?->label() }}
                        </span>
                    </div>
                    <p class="text-xs text-neutral-500 mt-1 font-mono">
                        {{ $exam->student?->national_id }} ·
                        {{ $exam->parts_count }} جزء ·
                        {{ $exam->started_at?->format('Y-m-d g:i A') }}
                    </p>
                </div>

                <div class="text-end flex-shrink-0">
                    @if($exam->total_score !== null)
                        <p class="text-2xl font-black tabular-nums text-primary-700">
                            {{ number_format($exam->total_score, 1) }}
                        </p>
                        <p class="text-xs text-neutral-400">/ 100</p>
                    @else
                        <p class="text-neutral-300">—</p>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-neutral-200 p-12 text-center text-neutral-400">
                لا توجد اختبارات مطابقة.
            </div>
        @endforelse
    </div>

    @if($exams->hasPages())
        <div class="mt-5">
            {{ $exams->links() }}
        </div>
    @endif

</div>
