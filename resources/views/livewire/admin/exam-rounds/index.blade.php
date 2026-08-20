<div class="p-4 sm:p-6 lg:p-8">

    <div class="mb-6 sm:mb-8">
        <h2 class="text-xl sm:text-2xl font-bold text-neutral-900">جولات الاختبارات</h2>
        <p class="text-neutral-500 text-xs sm:text-sm mt-0.5">استعراض سريع لأهم مؤشرات كل جولة</p>
    </div>

    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[980px] whitespace-nowrap">
                <thead class="bg-neutral-50 border-b border-neutral-200">
                    <tr>
                        <th class="text-start px-4 py-3 text-neutral-600 font-medium w-12">#</th>
                        <th class="text-start px-4 py-3 text-neutral-600 font-medium">اسم الجولة</th>
                        <th class="text-start px-4 py-3 text-neutral-600 font-medium">الاختبارات</th>
                        <th class="text-start px-4 py-3 text-neutral-600 font-medium">المعتمدة</th>
                        <th class="text-start px-4 py-3 text-neutral-600 font-medium">الطلاب</th>
                        <th class="text-start px-4 py-3 text-neutral-600 font-medium">متوسط الدرجة</th>
                        <th class="text-start px-4 py-3 text-neutral-600 font-medium">الفترة</th>
                        <th class="text-start px-4 py-3 text-neutral-600 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse($rounds as $round)
                        <tr class="hover:bg-neutral-50 transition-colors">
                            <td class="px-4 py-3 text-neutral-400 tabular-nums">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-neutral-900 flex items-center gap-2">
                                    {{ $round->name }}
                                    @if((int) $round->id === (int) $mobileRoundId)
                                        <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-primary-50 text-primary-700 font-medium">جولة الموبايل الحالية</span>
                                    @endif
                                </div>
                                <div class="text-xs text-neutral-400 mt-0.5">تم الإنشاء: {{ $round->created_at?->format('Y-m-d') }}</div>
                            </td>
                            <td class="px-4 py-3 text-neutral-700 tabular-nums">{{ number_format($round->exams_count) }}</td>
                            <td class="px-4 py-3 text-emerald-700 font-semibold tabular-nums">{{ number_format($round->approved_exams_count) }}</td>
                            <td class="px-4 py-3 text-neutral-700 tabular-nums">{{ number_format((int) $round->students_count) }}</td>
                            <td class="px-4 py-3 text-neutral-700 tabular-nums">
                                {{ $round->average_score !== null ? number_format((float) $round->average_score, 1) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-xs text-neutral-500">
                                @if($round->first_exam_at || $round->last_exam_at)
                                    {{ $round->first_exam_at ? \Illuminate\Support\Carbon::parse($round->first_exam_at)->format('Y-m-d') : '—' }}
                                    <span class="text-neutral-300">→</span>
                                    {{ $round->last_exam_at ? \Illuminate\Support\Carbon::parse($round->last_exam_at)->format('Y-m-d') : '—' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-end">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('admin.exams.index', ['round' => $round->id]) }}" wire:navigate class="text-xs font-medium text-primary-600 hover:text-primary-700 transition-colors">
                                        عرض اختبارات الجولة
                                    </a>
                                    @if($canRenameRounds)
                                        <button type="button" wire:click="startRename({{ $round->id }})" class="text-xs font-medium text-neutral-600 hover:text-neutral-900 transition-colors">
                                            إعادة تسمية
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        @if($editingRoundId === $round->id)
                            <tr class="bg-neutral-50/70">
                                <td colspan="8" class="px-4 py-3">
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                        <flux:input wire:model="editingRoundName" type="text" class="sm:w-96" />
                                        <div class="flex items-center gap-2">
                                            <flux:button type="button" size="sm" variant="primary" wire:click="renameRound">حفظ الاسم</flux:button>
                                            <flux:button type="button" size="sm" variant="outline" wire:click="cancelRename">إلغاء</flux:button>
                                        </div>
                                    </div>
                                    <flux:error name="editingRoundName" />
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-neutral-400">لا توجد جولات بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
