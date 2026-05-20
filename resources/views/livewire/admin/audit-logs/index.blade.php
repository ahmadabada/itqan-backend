<div class="p-4 sm:p-6 lg:p-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5 sm:mb-6 gap-3">
        <div class="min-w-0">
            <h2 class="text-xl sm:text-2xl font-bold text-neutral-900">سجل النشاط</h2>
            <p class="text-neutral-500 text-xs sm:text-sm mt-0.5">{{ number_format($logs->total()) }} عملية مُسجَّلة</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-5 flex items-end gap-3 flex-wrap">
        <flux:select wire:model.live="actionFilter" placeholder="كل الإجراءات" class="max-w-[220px]">
            <flux:select.option value="">كل الإجراءات</flux:select.option>
            @foreach($distinctActions as $action)
                <flux:select.option value="{{ $action }}">
                    {{ $actionLabels[$action] ?? $action }}
                </flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="userFilter" placeholder="كل المستخدمين" class="max-w-[220px]">
            <flux:select.option value="">كل المستخدمين</flux:select.option>
            @foreach($users as $u)
                <flux:select.option value="{{ $u->id }}">{{ $u->fullName() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input type="date" wire:model.live="dateFrom" label="من" class="max-w-[170px]" />
        <flux:input type="date" wire:model.live="dateTo"   label="إلى" class="max-w-[170px]" />

        @if($actionFilter || $userFilter || $dateFrom || $dateTo)
            <flux:button wire:click="clearFilters" variant="ghost" size="sm">إلغاء الفلاتر</flux:button>
        @endif
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[800px] whitespace-nowrap">
            <thead class="bg-neutral-50 border-b border-neutral-200">
                <tr>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium w-12">#</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">
                        <button wire:click="sort('user_id')" class="flex items-center gap-1 hover:text-neutral-900">
                            المستخدم
                            @if($sortBy === 'user_id')
                                <span class="text-primary-500">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </button>
                    </th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">
                        <button wire:click="sort('created_at')" class="flex items-center gap-1 hover:text-neutral-900">
                            تاريخ ووقت
                            @if($sortBy === 'created_at')
                                <span class="text-primary-500">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </button>
                    </th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الإجراء</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">السياق</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">IP</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse($logs as $log)
                    @php $isOpen = in_array($log->id, $expanded, true); @endphp
                    <tr class="hover:bg-neutral-50 transition-colors">
                        <td class="px-4 py-3 text-neutral-400 tabular-nums">{{ $loop->iteration + ($logs->firstItem() ?? 1) - 1 }}</td>
                        <td class="px-4 py-3">
                            @if($log->user)
                                <p class="font-medium text-neutral-900">{{ $log->user->fullName() }}</p>
                                <p class="text-[10px] text-neutral-400">{{ $log->user->role?->label() }}</p>
                            @else
                                <span class="text-neutral-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-neutral-600 text-xs">
                            {{ $log->created_at?->format('Y-m-d g:i A') ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-0.5 rounded-full bg-primary-50 text-primary-700">
                                {{ $actionLabels[$log->action] ?? $log->action }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-neutral-500 text-xs">
                            @if($log->exam_id)
                                <a href="{{ route('admin.exams.show', $log->exam_id) }}" wire:navigate
                                   class="font-mono hover:text-primary-600">
                                    اختبار #{{ $log->exam_id }}
                                </a>
                            @else
                                <span class="text-neutral-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-neutral-500 text-xs font-mono">{{ $log->ip_address ?? '—' }}</td>
                        <td class="px-4 py-3 text-end">
                            @if(! empty($log->old_values) || ! empty($log->new_values))
                                <button
                                    wire:click="toggleExpand({{ $log->id }})"
                                    class="text-xs text-neutral-500 hover:text-primary-600"
                                >
                                    {{ $isOpen ? 'إخفاء' : 'التفاصيل' }}
                                </button>
                            @endif
                        </td>
                    </tr>
                    @if($isOpen)
                        <tr class="bg-neutral-50/60">
                            <td colspan="7" class="px-6 py-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @if(! empty($log->old_values))
                                        <div>
                                            <p class="text-xs font-semibold text-neutral-700 mb-1.5">القيم السابقة</p>
                                            <pre class="text-[11px] bg-white rounded-lg border border-neutral-200 p-3 overflow-x-auto" dir="ltr">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    @endif
                                    @if(! empty($log->new_values))
                                        <div>
                                            <p class="text-xs font-semibold text-neutral-700 mb-1.5">القيم الجديدة</p>
                                            <pre class="text-[11px] bg-white rounded-lg border border-neutral-200 p-3 overflow-x-auto" dir="ltr">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    @endif
                                </div>
                                @if($log->user_agent)
                                    <p class="text-[10px] text-neutral-400 mt-3 truncate" title="{{ $log->user_agent }}">
                                        <span class="text-neutral-500">User-Agent:</span> {{ $log->user_agent }}
                                    </p>
                                @endif
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-neutral-400">
                            لا توجد عمليات مطابقة للفلاتر
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        @if($logs->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

</div>
