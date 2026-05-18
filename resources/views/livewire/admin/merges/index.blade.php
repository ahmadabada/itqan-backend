<div class="p-4 sm:p-6 lg:p-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5 sm:mb-6 gap-3">
        <div class="min-w-0">
            <h2 class="text-xl sm:text-2xl font-bold text-neutral-900">دمج الطلاب</h2>
            <p class="text-neutral-500 text-xs sm:text-sm mt-0.5">حدد سجلات الطالب المكرّر ثم ادمجها في سجل واحد.</p>
        </div>
        <div class="flex items-center gap-2">
            @if(count($selectedIds) > 0)
                <flux:button wire:click="clearSelection" variant="ghost" size="sm">
                    إلغاء التحديد ({{ count($selectedIds) }})
                </flux:button>
            @endif
            <flux:button
                wire:click="openMergeModal"
                variant="primary"
                size="sm"
                :disabled="count($selectedIds) < 2"
            >
                دمج المحدد ({{ count($selectedIds) }})
            </flux:button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-5 flex items-center gap-3 flex-wrap">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="بحث بالاسم أو رقم الهوية..."
            class="max-w-xs"
        />
        <flux:select wire:model.live="genderFilter" placeholder="كل الأجناس" class="max-w-[160px]">
            <flux:select.option value="">كل الأجناس</flux:select.option>
            <flux:select.option value="male">ذكر</flux:select.option>
            <flux:select.option value="female">أنثى</flux:select.option>
        </flux:select>
        <flux:select wire:model.live="statusFilter" class="max-w-[180px]">
            <flux:select.option value="not_merged">غير المدموجين</flux:select.option>
            <flux:select.option value="merged">المدموجين</flux:select.option>
            <flux:select.option value="all">الكل</flux:select.option>
        </flux:select>
    </div>

    {{-- Hint --}}
    <p class="text-xs text-neutral-500 mb-3">
        النتائج مرتّبة حسب <span class="font-mono">national_id</span> — السجلات المكرّرة تظهر متجاورة.
    </p>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[700px] whitespace-nowrap">
            <thead class="bg-neutral-50 border-b border-neutral-200">
                <tr>
                    <th class="text-start px-4 py-3 w-10"></th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">رقم الهوية</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الاسم الكامل</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الجنس</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الاختبارات</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">المصدر</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الحالة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse($students as $student)
                    @php $checked = in_array($student->id, $selectedIds, true); @endphp
                    <tr class="hover:bg-neutral-50 transition-colors {{ $checked ? 'bg-primary-50/40' : '' }}">
                        <td class="px-4 py-3">
                            <input
                                type="checkbox"
                                wire:click="toggleSelect({{ $student->id }})"
                                @checked($checked)
                                class="w-4 h-4 rounded border-neutral-300 text-primary-600 focus:ring-primary-500"
                                {{ $student->master_id ? 'disabled' : '' }}
                            >
                        </td>
                        <td class="px-4 py-3 font-mono text-neutral-700">
                            {{ $student->national_id ?? '—' }}
                        </td>
                        <td class="px-4 py-3 font-medium text-neutral-900">{{ $student->fullName() }}</td>
                        <td class="px-4 py-3">
                            @if($student->gender)
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $student->gender->value === 'male' ? 'bg-sky-50 text-sky-700' : 'bg-pink-50 text-pink-700' }}">
                                    {{ $student->gender->label() }}
                                </span>
                            @else
                                <span class="text-xs text-neutral-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-neutral-500 tabular-nums">{{ $student->exams_count }}</td>
                        <td class="px-4 py-3">
                            @if($student->created_via?->value === 'flutter')
                                <span class="text-xs px-2 py-0.5 rounded-full bg-violet-50 text-violet-700">موبايل</span>
                            @else
                                <span class="text-xs px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-600">ويب</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($student->master_id)
                                <span class="text-xs px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-500">
                                    مدموج في #{{ $student->master_id }}
                                </span>
                            @else
                                <span class="text-xs text-neutral-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-neutral-400">
                            @if($search)
                                لا توجد نتائج للبحث عن "{{ $search }}"
                            @else
                                لا يوجد طلاب
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        @if($students->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200">
                {{ $students->links() }}
            </div>
        @endif
    </div>

    {{-- ── Merge Modal ── --}}
    @if($showMergeModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl w-full max-w-2xl shadow-xl max-h-[90vh] flex flex-col">

                <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200 flex-shrink-0">
                    <h3 class="text-lg font-bold text-neutral-900">
                        دمج {{ count($mergeStudents) }} من الطلاب
                    </h3>
                    <button wire:click="$set('showMergeModal', false)" class="text-neutral-400 hover:text-neutral-600">✕</button>
                </div>

                <form wire:submit="performMerge" class="px-6 py-5 space-y-5 overflow-y-auto">

                    {{-- Step 1: Choose master --}}
                    <div>
                        <p class="text-sm font-semibold text-neutral-800 mb-2">1. اختر السجل الأساسي (master):</p>
                        <div class="space-y-2">
                            @foreach($mergeStudents as $s)
                                <label class="flex items-start gap-3 p-3 rounded-lg border border-neutral-200 hover:bg-neutral-50 cursor-pointer transition-colors">
                                    <input type="radio" wire:model.live="masterId" value="{{ $s['id'] }}" class="mt-1 text-primary-600">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-neutral-900">#{{ $s['id'] }} — {{ $s['full_name'] }}</p>
                                        <p class="text-xs text-neutral-500 mt-0.5">
                                            <span class="font-mono">{{ $s['national_id'] ?? '—' }}</span>
                                            <span class="mx-1">·</span>
                                            {{ $s['created_at'] }}
                                        </p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Step 2: Edit master data --}}
                    <div>
                        <p class="text-sm font-semibold text-neutral-800 mb-2">2. بيانات الـ master النهائية:</p>
                        <div class="grid grid-cols-2 gap-3">
                            <flux:input wire:model="masterFirstName"  label="الاسم الأول" />
                            <flux:input wire:model="masterSecondName" label="الاسم الثاني (اختياري)" />
                            <flux:input wire:model="masterThirdName"  label="الاسم الثالث (اختياري)" />
                            <flux:input wire:model="masterFamilyName" label="اسم العائلة" />
                            <flux:input wire:model="masterNationalId" label="رقم الهوية" class="col-span-2" />
                        </div>
                    </div>

                    {{-- Step 3: Choose authoritative exam --}}
                    @if(count($mergeExams) > 0)
                        <div>
                            <p class="text-sm font-semibold text-neutral-800 mb-2">3. اختر الاختبار المعتمد:</p>
                            <div class="space-y-2">
                                @foreach($mergeExams as $e)
                                    <label class="flex items-start gap-3 p-3 rounded-lg border border-neutral-200 hover:bg-neutral-50 cursor-pointer transition-colors">
                                        <input type="radio" wire:model.live="authoritativeExamId" value="{{ $e['id'] }}" class="mt-1 text-primary-600">
                                        <div class="flex-1 min-w-0 text-sm">
                                            <p class="font-medium text-neutral-900">
                                                اختبار #{{ $e['id'] }}
                                                <span class="text-neutral-500">— درجة</span>
                                                <span class="tabular-nums">{{ $e['total_score'] ?? '—' }}</span>
                                                <span class="text-neutral-400 mx-1">(طالب #{{ $e['student_id'] }})</span>
                                            </p>
                                            <p class="text-xs text-neutral-500 mt-0.5">{{ $e['examiner_name'] }} · {{ $e['completed_at'] }}</p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Notes --}}
                    <div>
                        <flux:textarea
                            wire:model="notes"
                            label="ملاحظات (اختياري)"
                            placeholder="مثال: نفس الشخص، فرق إملاء بسيط"
                            rows="2"
                        />
                    </div>

                </form>

                <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200 flex-shrink-0">
                    <flux:button wire:click="$set('showMergeModal', false)" variant="ghost" size="sm">إلغاء</flux:button>
                    <flux:button wire:click="performMerge" variant="primary" size="sm">تأكيد الدمج</flux:button>
                </div>

            </div>
        </div>
    @endif

</div>
