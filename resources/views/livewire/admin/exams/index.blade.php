<div class="p-4 sm:p-6 lg:p-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5 sm:mb-6 gap-3 flex-wrap">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-neutral-900">الاختبارات</h2>
            <p class="text-neutral-500 text-xs sm:text-sm mt-0.5">{{ $exams->total() }} اختبار</p>
        </div>
        <flux:button wire:click="openExportModal" size="sm" icon="arrow-down-tray">
            تصدير Excel
        </flux:button>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-neutral-200 p-4 mb-5">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">

            <div>
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
                <label class="block text-xs text-neutral-500 mb-1">المعلم</label>
                <flux:select wire:model.live="halaqahFilter" size="sm">
                    <flux:select.option value="">الكل</flux:select.option>
                    @foreach($halaqat as $halaqah)
                        <flux:select.option value="{{ $halaqah->value }}">{{ $halaqah->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div>
                <label class="block text-xs text-neutral-500 mb-1">الجولة</label>
                <flux:select wire:model.live="roundFilter" size="sm">
                    <flux:select.option value="">الكل</flux:select.option>
                    @foreach($rounds as $round)
                        <flux:select.option value="{{ $round->id }}">{{ $round->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div>
                <label class="block text-xs text-neutral-500 mb-1">من درجة</label>
                <flux:input type="number" min="0" max="100" step="0.5" wire:model.live.debounce.400ms="minScore" placeholder="0" size="sm" />
            </div>

            <div>
                <label class="block text-xs text-neutral-500 mb-1">إلى درجة</label>
                <flux:input type="number" min="0" max="100" step="0.5" wire:model.live.debounce.400ms="maxScore" placeholder="100" size="sm" />
            </div>

            <div>
                <label class="block text-xs text-neutral-500 mb-1">الإجازة (ذكور ≥ {{ $passingScoreMale }} / إناث ≥ {{ $passingScoreFemale }})</label>
                <flux:select wire:model.live="passedFilter" size="sm">
                    <flux:select.option value="">الكل</flux:select.option>
                    <flux:select.option value="passed">مجاز فقط</flux:select.option>
                    <flux:select.option value="failed">غير مجاز فقط</flux:select.option>
                </flux:select>
            </div>

        </div>

        @if($search || $statusFilter !== 'approved' || $examinerFilter || $genderFilter || $halaqahFilter || $roundFilter || $minScore !== '' || $maxScore !== '' || $passedFilter)
            <div class="mt-3 pt-3 border-t border-neutral-100">
                <button wire:click="clearFilters" class="text-xs text-neutral-500 hover:text-danger-600 transition-colors">
                    مسح الفلاتر
                </button>
            </div>
        @endif
    </div>

    {{-- Counter card — reflects the active filter set --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
        <div class="bg-white rounded-xl border border-neutral-200 p-4">
            <p class="text-xs text-neutral-500 mb-1">المجموع</p>
            <p class="text-2xl font-bold text-neutral-900 tabular-nums">{{ number_format($totalCount) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-success-100 p-4">
            <p class="text-xs text-success-600 mb-1">المجازون</p>
            <p class="text-2xl font-bold text-success-700 tabular-nums">{{ number_format($passedCount) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-danger-100 p-4">
            <p class="text-xs text-danger-600 mb-1">غير مجازين</p>
            <p class="text-2xl font-bold text-danger-600 tabular-nums">{{ number_format($failedCount) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-primary-100 p-4">
            <p class="text-xs text-primary-600 mb-1">متوسط الدرجة</p>
            <p class="text-2xl font-bold text-primary-700 tabular-nums">
                {{ $avgScore !== null ? number_format($avgScore, 1) : '—' }}
            </p>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[800px] whitespace-nowrap">
            <thead class="bg-neutral-50 border-b border-neutral-200">
                <tr>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium w-12">#</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الطالب</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">المعلم</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">المختبر</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الأجزاء</th>
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
                    @php $effective = $exam->student; @endphp
                    <tr class="hover:bg-neutral-50 transition-colors">
                        <td class="px-4 py-3 text-neutral-400 tabular-nums">{{ $loop->iteration + ($exams->firstItem() ?? 1) - 1 }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-neutral-900 flex items-center gap-2 flex-wrap">
                                @if($effective)
                                    <a href="{{ route('admin.students.show', $effective->id) }}" wire:navigate class="hover:text-primary-600 hover:underline transition-colors">
                                        {{ $effective->fullName() }}
                                    </a>
                                @else
                                    —
                                @endif
                                @if($exam->is_authoritative)
                                    <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-medium">النتيجة المعتمدة</span>
                                @endif
                                @if($exam->status?->value === 'excluded')
                                    <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-neutral-100 text-neutral-500 font-medium">مستبعد</span>
                                @endif
                            </div>
                            <div class="text-xs text-neutral-400 font-mono mt-0.5">
                                <span>{{ $effective?->national_id ?? '—' }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 flex-wrap mt-1">
                                @if($effective?->gender)
                                    <span class="text-[10px] px-1.5 py-0.5 rounded {{ $effective->gender->value === 'male' ? 'bg-sky-50 text-sky-700' : 'bg-pink-50 text-pink-700' }}">
                                        {{ $effective->gender->label() }}
                                    </span>
                                @endif
                                @if($exam->round)
                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-50 text-amber-700">
                                        {{ $exam->round->name }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-neutral-700">{{ $effective?->halaqah?->label() ?? '—' }}</td>
                        <td class="px-4 py-3 text-neutral-700">{{ $exam->examiner?->fullName() ?? '—' }}</td>
                        <td class="px-4 py-3 text-neutral-600 text-xs whitespace-nowrap">
                            {{ $exam->parts_count }} جزء
                            <span class="text-neutral-400">({{ $exam->new_memorization_parts }} جديد)</span>
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
                            <div class="flex items-center justify-end gap-2 flex-wrap">
                                {{-- Show only the opposite action of the current status. In-progress exams get no action --}}
                                @if($exam->status?->value === 'approved')
                                    <button
                                        type="button"
                                        wire:click="exclude({{ $exam->id }})"
                                        wire:confirm="استبعاد هذا الاختبار؟"
                                        class="text-xs font-medium text-neutral-600 hover:text-rose-600 transition-colors"
                                    >
                                        استبعاد
                                    </button>
                                @elseif($exam->status?->value === 'excluded')
                                    <button
                                        type="button"
                                        wire:click="approve({{ $exam->id }})"
                                        wire:confirm="اعتماد هذا الاختبار؟"
                                        class="text-xs font-medium text-emerald-600 hover:text-emerald-700 transition-colors"
                                    >
                                        اعتماد
                                    </button>
                                @endif
                                {{-- Deep-links into the details page with the edit form already open --}}
                                <a
                                    href="{{ route('admin.exams.show', ['exam' => $exam->id, 'edit' => 1]) }}"
                                    wire:navigate
                                    class="text-xs font-medium text-neutral-600 hover:text-primary-600 transition-colors"
                                >
                                    تعديل
                                </a>
                                <a
                                    href="{{ route('admin.exams.show', $exam->id) }}"
                                    wire:navigate
                                    class="text-xs font-medium text-primary-600 hover:text-primary-700 transition-colors"
                                >
                                    التفاصيل ←
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center text-neutral-400">
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

    {{-- ── Export columns modal ──────────────────────────────────────────
         Alpine `open` is two-way entangled with Livewire's $showExportModal so
         that clicking "تصدير" can set open=false locally (instant visual close)
         AND piggyback the same state change to the server inside the export()
         request payload — necessary because export() returns a download Response,
         which does NOT propagate state updates back to the client. --}}
    @if($showExportModal)
        @php
            $columnLabels = \App\Livewire\Admin\Exams\Index::EXPORT_COLUMN_LABELS;
        @endphp
        <div
            x-data="{ open: $wire.entangle('showExportModal') }"
            x-show="open"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
        >
            <div class="bg-white rounded-2xl w-full max-w-md shadow-xl" @click.outside="open = false">

                <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
                    <h3 class="text-lg font-bold text-neutral-900">اختر أعمدة التصدير</h3>
                    <button @click="open = false" class="text-neutral-400 hover:text-neutral-600">✕</button>
                </div>

                <div class="px-6 py-5">
                    <div class="flex items-center gap-3 mb-4 text-xs">
                        <button
                            type="button"
                            x-on:click="Object.keys($wire.exportColumns).forEach(k => $wire.set('exportColumns.' + k, true))"
                            class="text-primary-600 hover:text-primary-700 font-medium"
                        >
                            تحديد الكل
                        </button>
                        <span class="text-neutral-300">·</span>
                        <button
                            type="button"
                            x-on:click="Object.keys($wire.exportColumns).forEach(k => $wire.set('exportColumns.' + k, false))"
                            class="text-neutral-500 hover:text-neutral-700 font-medium"
                        >
                            إلغاء التحديد
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        @foreach($columnLabels as $key => $label)
                            <label class="flex items-center gap-2 px-3 py-2 rounded-lg border border-neutral-200 hover:bg-neutral-50 cursor-pointer">
                                <input
                                    type="checkbox"
                                    wire:model.live="exportColumns.{{ $key }}"
                                    class="rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
                                />
                                <span class="text-sm text-neutral-700">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <p class="text-xs text-neutral-500 mt-4">
                        التصدير سيشمل كل الاختبارات المطابقة للفلاتر الحالية ({{ number_format($totalCount) }} اختبار) بنفس ترتيب العرض.
                    </p>
                </div>

                <div class="flex justify-end gap-3 px-6 py-4 border-t border-neutral-100 bg-neutral-50/50 rounded-b-2xl">
                    <flux:button type="button" @click="open = false" variant="outline">
                        إلغاء
                    </flux:button>
                    <flux:button type="button" @click="open = false; $wire.export()" variant="primary" icon="arrow-down-tray">
                        تصدير
                    </flux:button>
                </div>

            </div>
        </div>
    @endif

</div>
