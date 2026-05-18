<div class="p-4 sm:p-6 lg:p-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5 sm:mb-6 gap-3">
        <div class="min-w-0">
            <h2 class="text-xl sm:text-2xl font-bold text-neutral-900">الطلاب</h2>
            <p class="text-neutral-500 text-xs sm:text-sm mt-0.5">{{ $students->total() }} طالب مسجّل</p>
        </div>
        <flux:button wire:click="openCreateModal" variant="primary" size="sm">
            إضافة طالب
        </flux:button>
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
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الاختبارات</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse($students as $student)
                    <tr class="hover:bg-neutral-50 transition-colors">
                        <td class="px-4 py-3 text-neutral-400 tabular-nums">{{ $loop->iteration + ($students->firstItem() ?? 1) - 1 }}</td>
                        <td class="px-4 py-3 font-mono text-neutral-700">
                            @if($student->national_id)
                                {{ $student->national_id }}
                            @else
                                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-50 text-amber-700">بدون هوية</span>
                            @endif
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
                        <td colspan="6" class="px-4 py-8 text-center text-neutral-400">
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
                        <flux:label>رقم الهوية <span class="text-xs text-neutral-400 font-normal">(اختياري — مطلوب فقط لإجراء اختبار)</span></flux:label>
                        <flux:input
                            wire:model="form_national_id"
                            type="text"
                            inputmode="numeric"
                            maxlength="9"
                            placeholder="9 أرقام — أو اتركه فارغاً"
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

                    <flux:field>
                        <flux:label>الجنس</flux:label>
                        <flux:select wire:model="form_gender" placeholder="اختر الجنس">
                            <flux:select.option value="male">ذكر</flux:select.option>
                            <flux:select.option value="female">أنثى</flux:select.option>
                        </flux:select>
                        <flux:error name="form_gender" />
                    </flux:field>

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
