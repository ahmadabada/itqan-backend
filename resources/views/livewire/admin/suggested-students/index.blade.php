<div class="p-4 sm:p-6 lg:p-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5 sm:mb-6 gap-3">
        <div class="min-w-0">
            <h2 class="text-xl sm:text-2xl font-bold text-neutral-900">الطلاب المقترحون</h2>
            <p class="text-neutral-500 text-xs sm:text-sm mt-0.5">
                {{ $rows->total() }} مقترح — قائمة autocomplete للمختبرين عند بدء اختبار جديد.
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <flux:button wire:click="downloadTemplate" variant="ghost" size="sm">
                تنزيل القالب
            </flux:button>
            <flux:button wire:click="openImportModal" variant="primary" size="sm">
                رفع إكسل
            </flux:button>
            @if($rows->total() > 0)
                <flux:button wire:click="confirmDeleteAll" variant="danger" size="sm">
                    حذف الكل
                </flux:button>
            @endif
        </div>
    </div>

    {{-- Last-import banner --}}
    @if($lastErrorsCsvPath && $lastFailedCount > 0)
        <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 flex items-center justify-between gap-3">
            <p class="text-sm text-rose-800">
                آخر محاولة رفع فيها <span class="font-semibold">{{ $lastFailedCount }}</span> صف فاشل. لم يتم حفظ أي صف (all-or-nothing).
            </p>
            <flux:button wire:click="downloadErrors" variant="ghost" size="sm">
                تنزيل ملف الأخطاء
            </flux:button>
        </div>
    @endif

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
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[700px] whitespace-nowrap">
                <thead class="bg-neutral-50 border-b border-neutral-200">
                    <tr>
                        <th class="text-start px-4 py-3 text-neutral-600 font-medium w-12">#</th>
                        <th class="text-start px-4 py-3 text-neutral-600 font-medium">رقم الهوية</th>
                        <th class="text-start px-4 py-3 text-neutral-600 font-medium">الاسم الكامل</th>
                        <th class="text-start px-4 py-3 text-neutral-600 font-medium">المنطقة</th>
                        <th class="text-start px-4 py-3 text-neutral-600 font-medium">الجنس</th>
                        <th class="text-start px-4 py-3 text-neutral-600 font-medium">سبق التسميع</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse($rows as $row)
                        <tr class="hover:bg-neutral-50 transition-colors">
                            <td class="px-4 py-3 text-neutral-400 tabular-nums">{{ $loop->iteration + ($rows->firstItem() ?? 1) - 1 }}</td>
                            <td class="px-4 py-3 font-mono text-neutral-700">{{ $row->national_id ?? '—' }}</td>
                            <td class="px-4 py-3 font-medium text-neutral-900">{{ $row->fullName() }}</td>
                            <td class="px-4 py-3 text-neutral-600 text-xs">{{ $row->student_zone }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $row->gender->value === 'male' ? 'bg-sky-50 text-sky-700' : 'bg-pink-50 text-pink-700' }}">
                                    {{ $row->gender->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($row->is_recite_before)
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700">نعم</span>
                                @else
                                    <span class="text-xs text-neutral-300">لا</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-neutral-400">
                                @if($search)
                                    لا توجد نتائج للبحث عن "{{ $search }}"
                                @else
                                    لم يتم رفع أي مقترح بعد. حمّل القالب وارفع إكسل لبدء استخدام الميزة.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rows->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200">
                {{ $rows->links() }}
            </div>
        @endif
    </div>

    {{-- ── Import Modal ── --}}
    @if($showImportModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl w-full max-w-md shadow-xl">
                <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
                    <h3 class="text-lg font-bold text-neutral-900">رفع إكسل</h3>
                    <button wire:click="$set('showImportModal', false)" class="text-neutral-400 hover:text-neutral-600">✕</button>
                </div>

                <form wire:submit="runImport" class="px-6 py-5 space-y-4">
                    <div class="text-sm text-neutral-600 space-y-1">
                        <p>• الرفع يستبدل القائمة الحالية بالكامل (truncate + insert).</p>
                        <p>• لو في أي صف خطأ، يتم رفض الملف كاملاً.</p>
                        <p>• الأعمدة المطلوبة: <span class="font-mono">first_name, family_name, student_zone, gender</span>.</p>
                    </div>

                    <flux:field>
                        <flux:label>ملف الإكسل (xlsx / xls / csv — أقل من 5MB)</flux:label>
                        <input
                            type="file"
                            wire:model="importFile"
                            accept=".xlsx,.xls,.csv"
                            class="w-full text-sm text-neutral-700 file:me-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
                        />
                        <flux:error name="importFile" />
                    </flux:field>

                    <div wire:loading wire:target="importFile,runImport" class="text-xs text-neutral-500">
                        جارٍ المعالجة...
                    </div>
                </form>

                <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200">
                    <flux:button wire:click="$set('showImportModal', false)" variant="ghost" size="sm">إلغاء</flux:button>
                    <flux:button wire:click="runImport" variant="primary" size="sm">رفع</flux:button>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Delete-all Modal ── --}}
    @if($showDeleteAllModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl w-full max-w-md shadow-xl">
                <div class="px-6 py-4 border-b border-neutral-200">
                    <h3 class="text-lg font-bold text-neutral-900">حذف كل المقترحين؟</h3>
                </div>
                <div class="px-6 py-5">
                    <p class="text-sm text-neutral-600">
                        سيتم حذف <span class="font-semibold">{{ $rows->total() }}</span> سجل. يمكنك إعادة الرفع من إكسل في أي وقت.
                    </p>
                </div>
                <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200">
                    <flux:button wire:click="$set('showDeleteAllModal', false)" variant="ghost" size="sm">إلغاء</flux:button>
                    <flux:button wire:click="deleteAll" variant="danger" size="sm">حذف الكل</flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
