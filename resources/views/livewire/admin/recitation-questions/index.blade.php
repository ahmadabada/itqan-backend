<div class="p-4 sm:p-6 lg:p-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5 sm:mb-6 gap-3">
        <div class="min-w-0">
            <h2 class="text-xl sm:text-2xl font-bold text-neutral-900">بنك الأسئلة</h2>
            <p class="text-neutral-500 text-xs sm:text-sm mt-0.5">{{ $items->total() }} سؤال</p>
        </div>
        <div class="flex items-center gap-2">
            <flux:button wire:click="openImportModal" variant="ghost" size="sm">
                استيراد Excel
            </flux:button>
            <flux:button wire:click="openCreateModal" variant="primary" size="sm">
                إضافة سؤال
            </flux:button>
        </div>
    </div>

    {{-- Last import result banner --}}
    @if($lastFailedCount > 0 && $lastErrorsCsvPath)
        <div class="mb-5 p-4 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-between gap-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                </svg>
                <div class="text-sm">
                    <p class="font-medium text-amber-900">
                        فشل {{ $lastFailedCount }} سطر في آخر استيراد
                    </p>
                    <p class="text-amber-700 text-xs mt-0.5">
                        حمّل سجل الأخطاء لمعرفة السبب وأعد رفع الصفوف بعد تصحيحها.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <flux:button wire:click="downloadErrors" variant="primary" size="sm">
                    تحميل سجل الأخطاء
                </flux:button>
                <button wire:click="dismissLastResult" class="text-amber-600 hover:text-amber-800 text-xs">
                    إخفاء
                </button>
            </div>
        </div>
    @endif

    {{-- Filters --}}
    <div class="mb-5 flex items-center gap-3 flex-wrap">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="بحث برقم السؤال أو صفحة..."
            class="max-w-xs"
        />
        <flux:select wire:model.live="groupFilter" placeholder="كل المجموعات" class="max-w-[180px]">
            <flux:select.option value="">كل المجموعات</flux:select.option>
            @foreach($groups as $g)
                <flux:select.option value="{{ $g->value }}">{{ $g->shortLabel() }} — {{ $g->fullLabel() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[720px]">
            <thead class="bg-neutral-50 border-b border-neutral-200">
                <tr>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium w-12">#</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">المجموعة</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">السؤال</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">البداية</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">النهاية</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse($items as $q)
                    <tr class="hover:bg-neutral-50 transition-colors">
                        <td class="px-4 py-3 text-neutral-400 tabular-nums">{{ $loop->iteration + ($items->firstItem() ?? 1) - 1 }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-0.5 rounded-full bg-primary-50 text-primary-700">
                                {{ $q->group_number->shortLabel() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-medium text-neutral-900 tabular-nums">{{ $q->question_number }}</td>
                        <td class="px-4 py-3 text-neutral-700">
                            {{ $surahMap[$q->start_surah] ?? $q->start_surah }}
                            <span class="text-neutral-400">— آية {{ $q->start_ayah }} — ص {{ $q->start_page }}</span>
                        </td>
                        <td class="px-4 py-3 text-neutral-700">
                            {{ $surahMap[$q->end_surah] ?? $q->end_surah }}
                            <span class="text-neutral-400">— آية {{ $q->end_ayah }} — ص {{ $q->end_page }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <button wire:click="openEditModal({{ $q->id }})" class="text-xs text-neutral-500 hover:text-primary-600 transition-colors">
                                    تعديل
                                </button>
                                <button wire:click="confirmDelete({{ $q->id }})" class="text-xs text-neutral-500 hover:text-danger-600 transition-colors">
                                    حذف
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-neutral-400">لا توجد أسئلة. ابدأ بالاستيراد من Excel.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="mt-4">{{ $items->links() }}</div>

    {{-- Create/Edit Modal --}}
    <flux:modal wire:model.self="showFormModal" class="md:w-[600px]">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editId ? 'تعديل سؤال' : 'إضافة سؤال' }}</flux:heading>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <flux:input label="رقم السؤال" wire:model="f_question_number" type="number" />
                <flux:select label="المجموعة" wire:model="f_group_number">
                    <flux:select.option value="">— اختر —</flux:select.option>
                    @foreach($groups as $g)
                        <flux:select.option value="{{ $g->value }}">{{ $g->shortLabel() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <flux:select label="سورة البداية" wire:model="f_start_surah">
                    <flux:select.option value="">—</flux:select.option>
                    @foreach($surahMap as $num => $name)
                        <flux:select.option value="{{ $num }}">{{ $num }}. {{ $name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input label="آية البداية" wire:model="f_start_ayah" type="number" />
                <flux:input label="صفحة البداية" wire:model="f_start_page" type="number" />
            </div>

            <div class="grid grid-cols-3 gap-3">
                <flux:select label="سورة النهاية" wire:model="f_end_surah">
                    <flux:select.option value="">—</flux:select.option>
                    @foreach($surahMap as $num => $name)
                        <flux:select.option value="{{ $num }}">{{ $num }}. {{ $name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input label="آية النهاية" wire:model="f_end_ayah" type="number" />
                <flux:input label="صفحة النهاية" wire:model="f_end_page" type="number" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showFormModal', false)" variant="ghost">إلغاء</flux:button>
                <flux:button wire:click="save" variant="primary">حفظ</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Import Modal --}}
    <flux:modal wire:model.self="showImportModal" class="md:w-[520px]">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">استيراد بنك الأسئلة من Excel</flux:heading>
                <p class="text-sm text-neutral-500 mt-1">
                    الأعمدة المطلوبة: <code>question_number</code>, <code>group_number</code>,
                    <code>start_surah</code>, <code>start_ayah</code>, <code>start_page</code>,
                    <code>end_surah</code>, <code>end_ayah</code>, <code>end_page</code>.
                    عمود السورة يقبل اسم السورة بالعربية أو رقمها (1–114).
                </p>
            </div>

            <flux:select label="وضع الاستيراد" wire:model="importMode">
                <flux:select.option value="replace">استبدال كامل (يحذف الموجود ثم يستورد)</flux:select.option>
                <flux:select.option value="upsert">تحديث المتطابق وإضافة الجديد</flux:select.option>
            </flux:select>

            <div>
                <label class="text-sm text-neutral-700 mb-1 block">ملف Excel (xlsx / xls / csv)</label>
                <input
                    type="file"
                    wire:model="importFile"
                    accept=".xlsx,.xls,.csv"
                    class="w-full text-sm border border-neutral-300 rounded-lg px-3 py-2 file:me-3 file:rounded file:border-0 file:bg-primary-50 file:text-primary-700 file:px-3 file:py-1"
                />
                @error('importFile') <p class="text-xs text-danger-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-2">
                <flux:button wire:click="$set('showImportModal', false)" variant="ghost">إلغاء</flux:button>
                <flux:button wire:click="runImport" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="runImport">استيراد</span>
                    <span wire:loading wire:target="runImport">جارٍ الاستيراد...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Delete confirmation --}}
    @if($deleteId)
        <flux:modal wire:model.self="deleteId" class="md:w-[400px]">
            <div class="space-y-4">
                <flux:heading size="lg">حذف السؤال؟</flux:heading>
                <p class="text-sm text-neutral-600">لا يمكن التراجع عن هذه العملية.</p>
                <div class="flex justify-end gap-2">
                    <flux:button wire:click="$set('deleteId', null)" variant="ghost">إلغاء</flux:button>
                    <flux:button wire:click="deleteItem" variant="danger">حذف</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
