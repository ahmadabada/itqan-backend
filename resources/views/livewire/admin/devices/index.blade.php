<div class="p-4 sm:p-6 lg:p-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5 sm:mb-6 gap-3">
        <div class="min-w-0">
            <h2 class="text-xl sm:text-2xl font-bold text-neutral-900">الأجهزة</h2>
            <p class="text-neutral-500 text-xs sm:text-sm mt-0.5">{{ $devices->total() }} جهاز مسجّل</p>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[700px] whitespace-nowrap">
            <thead class="bg-neutral-50 border-b border-neutral-200">
                <tr>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">#</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">device_uuid</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">المنصة</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">آخر مستخدم</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">آخر نشاط</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">أوامر معلّقة</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse($devices as $device)
                    <tr class="hover:bg-neutral-50 transition-colors">
                        <td class="px-4 py-3 text-neutral-400 tabular-nums">{{ $device->id }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-neutral-700">{{ $device->device_uuid }}</td>
                        <td class="px-4 py-3">
                            @if($device->fcm_platform)
                                <span class="text-xs px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-600">
                                    {{ $device->fcm_platform }}
                                </span>
                            @else
                                <span class="text-xs text-neutral-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-neutral-600">
                            {{ $device->lastUser ? trim(($device->lastUser->first_name ?? '') . ' ' . ($device->lastUser->family_name ?? '')) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-neutral-500 text-xs">
                            {{ $device->last_seen_at?->diffForHumans() ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @if($device->pending_commands_count > 0)
                                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-50 text-amber-700">
                                    {{ $device->pending_commands_count }}
                                </span>
                            @else
                                <span class="text-xs text-neutral-300">0</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <button
                                wire:click="openWipeModal({{ $device->id }})"
                                class="text-xs text-neutral-500 hover:text-danger-600 transition-colors"
                            >
                                مسح بيانات الجهاز
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-neutral-400">لا توجد أجهزة مسجّلة بعد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        @if($devices->hasPages())
            <div class="px-4 py-3 border-t border-neutral-200">
                {{ $devices->links() }}
            </div>
        @endif
    </div>

    {{-- ── Wipe Modal ── --}}
    @if($wipeDeviceId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl w-full max-w-md shadow-xl">

                <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
                    <h3 class="text-lg font-bold text-neutral-900">إصدار أمر مسح للجهاز #{{ $wipeDeviceId }}</h3>
                    <button wire:click="closeWipeModal" class="text-neutral-400 hover:text-neutral-600">✕</button>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <p class="text-sm text-neutral-600">
                        سيُرسَل أمر مسح للجهاز. الجهاز نفسه يفحص ويرفض المسح إذا كان عنده بيانات لم تُرفع بعد.
                    </p>
                    <flux:textarea
                        wire:model="wipeReason"
                        label="السبب (اختياري)"
                        placeholder="مثال: انتهاء فترة الاختبارات."
                        rows="2"
                    />
                </div>

                <div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-neutral-200">
                    <flux:button wire:click="closeWipeModal" variant="ghost" size="sm">إلغاء</flux:button>
                    <flux:button wire:click="issueWipe" variant="danger" size="sm">إصدار الأمر</flux:button>
                </div>

            </div>
        </div>
    @endif

</div>
