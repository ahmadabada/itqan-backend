<div class="p-4 sm:p-6 lg:p-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5 sm:mb-6 gap-3">
        <div class="min-w-0">
            <h2 class="text-xl sm:text-2xl font-bold text-neutral-900">سجل عمليات الدمج</h2>
            <p class="text-neutral-500 text-xs sm:text-sm mt-0.5">{{ $operations->total() }} عملية</p>
        </div>
        <flux:select wire:model.live="statusFilter" class="max-w-[160px]">
            <flux:select.option value="active">النشطة</flux:select.option>
            <flux:select.option value="undone">المُتراجَع عنها</flux:select.option>
            <flux:select.option value="all">الكل</flux:select.option>
        </flux:select>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[700px] whitespace-nowrap">
            <thead class="bg-neutral-50 border-b border-neutral-200">
                <tr>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">#</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الـ master</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">السجلات المدموجة</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الأدمن</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">التاريخ</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الحالة</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الإجراء</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse($operations as $op)
                    <tr class="hover:bg-neutral-50 transition-colors">
                        <td class="px-4 py-3 text-neutral-400 tabular-nums">{{ $op->id }}</td>
                        <td class="px-4 py-3">
                            @if($op->masterStudent)
                                <p class="font-medium text-neutral-900">#{{ $op->masterStudent->id }} — {{ trim(($op->masterStudent->first_name ?? '') . ' ' . ($op->masterStudent->family_name ?? '')) }}</p>
                                <p class="text-xs text-neutral-400 font-mono">{{ $op->masterStudent->national_id ?? '—' }}</p>
                            @else
                                <span class="text-neutral-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-neutral-600 font-mono text-xs">
                            {{ implode(', ', array_map(fn($id) => '#' . $id, $op->merged_student_ids ?? [])) }}
                        </td>
                        <td class="px-4 py-3 text-neutral-600">
                            {{ $op->performedBy ? trim(($op->performedBy->first_name ?? '') . ' ' . ($op->performedBy->family_name ?? '')) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-neutral-500 text-xs">
                            {{ $op->performed_at?->format('Y-m-d g:i A') ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @if($op->undone_at)
                                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-50 text-amber-700">تم التراجع</span>
                            @else
                                <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700">نشطة</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($op->undone_at)
                                <span class="text-xs text-neutral-300">—</span>
                            @else
                                <button
                                    wire:click="openUndoModal({{ $op->id }})"
                                    class="text-xs text-neutral-500 hover:text-danger-600 transition-colors"
                                >
                                    تراجع
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-neutral-400">لا توجد عمليات</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        @if($operations->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200">
                {{ $operations->links() }}
            </div>
        @endif
    </div>

    {{-- ── Undo Modal ── --}}
    @if($undoOperationId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl w-full max-w-md shadow-xl">

                <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
                    <h3 class="text-lg font-bold text-neutral-900">تراجع عن الدمج #{{ $undoOperationId }}</h3>
                    <button wire:click="closeUndoModal" class="text-neutral-400 hover:text-neutral-600">✕</button>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <p class="text-sm text-neutral-600">
                        سيُعاد كل الطلاب والاختبارات لحالتها قبل الدمج (حسب الـ snapshot المحفوظ).
                    </p>
                    <flux:textarea
                        wire:model="undoNotes"
                        label="سبب التراجع (اختياري)"
                        rows="2"
                    />
                </div>

                <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200">
                    <flux:button wire:click="closeUndoModal" variant="ghost" size="sm">إلغاء</flux:button>
                    <flux:button wire:click="performUndo" variant="danger" size="sm">تأكيد التراجع</flux:button>
                </div>

            </div>
        </div>
    @endif

</div>
