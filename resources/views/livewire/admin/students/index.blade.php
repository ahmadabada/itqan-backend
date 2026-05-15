<div class="p-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-neutral-900">الطلاب</h2>
            <p class="text-neutral-500 text-sm mt-0.5">{{ $students->total() }} طالب مسجّل</p>
        </div>
        <flux:button wire:click="openCreateModal" variant="primary" size="sm">
            إضافة طالب
        </flux:button>
    </div>

    {{-- Search --}}
    <div class="mb-5">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="بحث بالاسم أو رقم الهوية..."
            class="max-w-xs"
        />
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 border-b border-neutral-200">
                <tr>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">رقم الهوية</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الاسم الكامل</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الاختبارات</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse($students as $student)
                    <tr class="hover:bg-neutral-50 transition-colors">
                        <td class="px-4 py-3 font-mono text-neutral-700">{{ $student->national_id }}</td>
                        <td class="px-4 py-3 font-medium text-neutral-900">{{ $student->fullName() }}</td>
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
                        <td colspan="4" class="px-4 py-8 text-center text-neutral-400">
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
                            :readonly="(bool)$editStudentId"
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
                            <flux:label>الاسم الثاني</flux:label>
                            <flux:input wire:model="form_second_name" type="text" />
                        </flux:field>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>الاسم الثالث</flux:label>
                            <flux:input wire:model="form_third_name" type="text" />
                        </flux:field>
                        <flux:field>
                            <flux:label>اسم العائلة</flux:label>
                            <flux:input wire:model="form_family_name" type="text" />
                            <flux:error name="form_family_name" />
                        </flux:field>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <flux:button type="button" wire:click="$set('showFormModal', false)" variant="ghost">
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
                    <flux:button wire:click="$set('deleteStudentId', null)" variant="ghost" class="flex-1">إلغاء</flux:button>
                    <flux:button wire:click="deleteStudent" variant="danger" class="flex-1">حذف</flux:button>
                </div>
            </div>
        </div>
    @endif

</div>
