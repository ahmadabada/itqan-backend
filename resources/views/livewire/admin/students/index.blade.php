<div class="p-4 sm:p-6 lg:p-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5 sm:mb-6 gap-3">
        <div class="min-w-0">
            <h2 class="text-xl sm:text-2xl font-bold text-neutral-900">الطلاب</h2>
            <p class="text-neutral-500 text-xs sm:text-sm mt-0.5">{{ $students->total() }} طالب مسجّل</p>
        </div>
        <div class="flex items-center gap-2">
            @unless($lockedGender)
                <flux:button wire:click="openImportModal" variant="outline" size="sm">
                    استيراد من إكسل
                </flux:button>
            @endunless
            <flux:button wire:click="openCreateModal" variant="primary" size="sm">
                إضافة طالب
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
        @unless($lockedGender)
            <flux:select wire:model.live="genderFilter" placeholder="كل الأجناس" class="max-w-[160px]">
                <flux:select.option value="">كل الأجناس</flux:select.option>
                <flux:select.option value="male">ذكر</flux:select.option>
                <flux:select.option value="female">أنثى</flux:select.option>
            </flux:select>
        @endunless
        <flux:select wire:model.live="halaqahFilter" placeholder="كل الحلقات" class="max-w-[200px]">
            <flux:select.option value="">كل الحلقات</flux:select.option>
            @foreach($halaqat as $halaqah)
                <flux:select.option value="{{ $halaqah->value }}">{{ $halaqah->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[600px] whitespace-nowrap">
            <thead class="bg-neutral-50 border-b border-neutral-200">
                <tr>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium w-12">#</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">رقم الهوية</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الاسم الكامل</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الجنس</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الحلقة</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الاختبارات</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse($students as $student)
                    <tr class="hover:bg-neutral-50 transition-colors">
                        <td class="px-4 py-3 text-neutral-400 tabular-nums">{{ $loop->iteration + ($students->firstItem() ?? 1) - 1 }}</td>
                        <td class="px-4 py-3 font-mono text-neutral-700">
                            {{ $student->national_id }}
                        </td>
                        <td class="px-4 py-3 font-medium text-neutral-900">
                            <a href="{{ route('admin.students.show', $student->id) }}" wire:navigate class="hover:text-primary-600 hover:underline transition-colors">
                                {{ $student->fullName() }}
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            @if($student->gender)
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $student->gender->value === 'male' ? 'bg-sky-50 text-sky-700' : 'bg-pink-50 text-pink-700' }}">
                                    {{ $student->gender->label() }}
                                </span>
                            @else
                                <span class="text-xs text-neutral-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($student->halaqah)
                                <span class="text-xs px-2 py-0.5 rounded-full bg-primary-50 text-primary-700">
                                    {{ $student->halaqah->label() }}
                                </span>
                            @else
                                <span class="text-xs text-neutral-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-neutral-500">
                            {{ $student->exams()->count() }} اختبار
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <button
                                    wire:click="openEditModal({{ $student->id }})"
                                    class="text-xs text-neutral-500 hover:text-primary-600 transition-colors"
                                >
                                    تعديل
                                </button>
                                <button
                                    wire:click="confirmDelete({{ $student->id }})"
                                    class="text-xs text-neutral-500 hover:text-danger-600 transition-colors"
                                >
                                    حذف
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-neutral-400">
                            @if($search)
                                لا توجد نتائج للبحث عن "{{ $search }}"
                            @else
                                لا يوجد طلاب مسجّلون بعد
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        {{-- Pagination --}}
        @if($students->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200">
                {{ $students->links() }}
            </div>
        @endif
    </div>

    {{-- ── Student Form Modal (Create/Edit) ── --}}
    @if($showFormModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
            <div class="bg-white rounded-2xl w-full max-w-lg mx-4 shadow-xl">

                <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
                    <h3 class="text-lg font-bold text-neutral-900">
                        {{ $editStudentId ? 'تعديل بيانات الطالب' : 'إضافة طالب جديد' }}
                    </h3>
                    <button wire:click="$set('showFormModal', false)" class="text-neutral-400 hover:text-neutral-600">✕</button>
                </div>

                <form wire:submit="saveStudent" class="px-6 py-5 space-y-4">

                    <flux:field>
                        <flux:label>رقم الهوية</flux:label>
                        <flux:input
                            wire:model="form_national_id"
                            type="text"
                            inputmode="numeric"
                            maxlength="9"
                            placeholder="9 أرقام"
                        />
                        <flux:error name="form_national_id" />
                    </flux:field>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>الاسم الأول</flux:label>
                            <flux:input wire:model="form_first_name" type="text" />
                            <flux:error name="form_first_name" />
                        </flux:field>
                        <flux:field>
                            <flux:label>الاسم الثاني <span class="text-neutral-400 text-xs font-normal">(اختياري)</span></flux:label>
                            <flux:input wire:model="form_second_name" type="text" />
                        </flux:field>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>الاسم الثالث <span class="text-neutral-400 text-xs font-normal">(اختياري)</span></flux:label>
                            <flux:input wire:model="form_third_name" type="text" />
                        </flux:field>
                        <flux:field>
                            <flux:label>اسم العائلة</flux:label>
                            <flux:input wire:model="form_family_name" type="text" />
                            <flux:error name="form_family_name" />
                        </flux:field>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>الجنس</flux:label>
                            <flux:select wire:model="form_gender" placeholder="اختر الجنس" :disabled="(bool) $lockedGender">
                                <flux:select.option value="male">ذكر</flux:select.option>
                                <flux:select.option value="female">أنثى</flux:select.option>
                            </flux:select>
                            <flux:error name="form_gender" />
                        </flux:field>

                        <flux:field>
                            <flux:label>الحلقة</flux:label>
                            <flux:select wire:model="form_halaqah" placeholder="اختر الحلقة">
                                @foreach($halaqat as $halaqah)
                                    <flux:select.option value="{{ $halaqah->value }}">{{ $halaqah->label() }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="form_halaqah" />
                        </flux:field>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <flux:button type="button" wire:click="$set('showFormModal', false)" variant="outline">
                            إلغاء
                        </flux:button>
                        <flux:button type="submit" variant="primary">
                            {{ $editStudentId ? 'حفظ التعديلات' : 'إضافة' }}
                        </flux:button>
                    </div>

                </form>

            </div>
        </div>
    @endif

    {{-- ── Excel Import Modal ── --}}
    @if($showImportModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
            <div class="bg-white rounded-2xl w-full max-w-lg mx-4 shadow-xl">
                <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
                    <h3 class="text-lg font-bold text-neutral-900">استيراد الطلاب من إكسل</h3>
                    <button wire:click="$set('showImportModal', false)" class="text-neutral-400 hover:text-neutral-600">✕</button>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <p class="text-sm text-neutral-500">
                        يُحدَّث الطالب الموجود برقم هويته ويُضاف الجديد. لا يُحذف أحد.
                        الأعمدة المطلوبة: رقم الهوية، الاسم الأول، اسم العائلة، الحلقة، الجنس.
                    </p>

                    <button wire:click="downloadTemplate" type="button" class="text-xs text-primary-600 hover:underline">
                        ↓ تنزيل قالب فارغ
                    </button>

                    <flux:field>
                        <flux:label>ملف الإكسل (xlsx / xls / csv)</flux:label>
                        <input type="file" wire:model="importFile" accept=".xlsx,.xls,.csv"
                               class="block w-full text-sm text-neutral-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100" />
                        <flux:error name="importFile" />
                        <div wire:loading wire:target="importFile" class="text-xs text-neutral-400 mt-1">جارٍ الرفع…</div>
                    </flux:field>

                    @if($importResult && !empty($importResult['failed_rows']))
                        <div class="rounded-lg border border-danger-200 bg-danger-50 p-3 max-h-52 overflow-y-auto">
                            <p class="text-sm font-bold text-danger-700 mb-2">
                                {{ count($importResult['failed_rows']) }} صف به أخطاء — لم يُحفظ شيء:
                            </p>
                            <ul class="space-y-1 text-xs text-danger-700">
                                @foreach($importResult['failed_rows'] as $row)
                                    <li>صف {{ $row['row_number'] }}: {{ $row['error'] }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="flex justify-end gap-3 pt-1">
                        <flux:button type="button" wire:click="$set('showImportModal', false)" variant="outline">إغلاق</flux:button>
                        <flux:button type="button" wire:click="runImport" variant="primary" wire:loading.attr="disabled" wire:target="runImport">
                            <span wire:loading.remove wire:target="runImport">استيراد</span>
                            <span wire:loading wire:target="runImport">جارٍ الاستيراد…</span>
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Delete Confirm Modal ── --}}
    @if($deleteStudentId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
            <div class="bg-white rounded-2xl w-full max-w-sm mx-4 shadow-xl p-6 text-center">
                <h3 class="text-lg font-bold text-neutral-900 mb-2">تأكيد الحذف</h3>
                <p class="text-sm text-neutral-500 mb-5">سيتم حذف بيانات الطالب نهائياً. لا يمكن التراجع.</p>
                <div class="flex gap-3">
                    <flux:button wire:click="$set('deleteStudentId', null)" variant="outline" class="flex-1">إلغاء</flux:button>
                    <flux:button wire:click="deleteStudent" variant="danger" class="flex-1">حذف</flux:button>
                </div>
            </div>
        </div>
    @endif

</div>
