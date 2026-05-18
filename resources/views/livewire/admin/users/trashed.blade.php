<div class="p-4 sm:p-6 lg:p-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-neutral-900">المستخدمون المحذوفون</h2>
            <p class="text-neutral-500 text-sm mt-0.5">المستخدمون اللي تم حذفهم — يمكن استعادتهم</p>
        </div>
        <flux:button :href="route('admin.users')" variant="outline" size="sm" wire:navigate>
            رجوع للمستخدمين
        </flux:button>
    </div>

    {{-- Filters --}}
    <div class="flex gap-3 mb-5">
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
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium w-12">#</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">رقم الهوية</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الاسم</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الجوال</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الدور</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">تاريخ الحذف</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse($users as $user)
                    <tr class="hover:bg-neutral-50 transition-colors">
                        <td class="px-4 py-3 text-neutral-400 tabular-nums">{{ $loop->iteration }}</td>
                        <td class="px-4 py-3 font-mono text-neutral-700">{{ $user->national_id }}</td>
                        <td class="px-4 py-3 font-medium text-neutral-900">{{ $user->fullName() }}</td>
                        <td class="px-4 py-3 font-mono text-neutral-700">{{ $user->phone ?: '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                {{ $user->role === \App\Enums\UserRole::Admin ? 'bg-primary-50 text-primary-700' : 'bg-neutral-100 text-neutral-600' }}">
                                {{ $user->role->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-neutral-500">
                            {{ $user->deleted_at?->diffForHumans() }}
                        </td>
                        <td class="px-4 py-3">
                            @if($currentUser->isSuperAdmin() || $user->role === \App\Enums\UserRole::Examiner)
                                <button
                                    wire:click="confirmRestore({{ $user->id }})"
                                    class="text-xs text-neutral-500 hover:text-success-700 transition-colors"
                                >
                                    استعادة
                                </button>
                            @else
                                <span class="text-xs text-neutral-300">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-neutral-400">
                            لا يوجد مستخدمون محذوفون
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── Restore Confirm Modal ── --}}
    @if($restoreUserId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
            <div class="bg-white rounded-2xl w-full max-w-sm mx-4 shadow-xl">

                <div class="px-6 py-5 text-center">
                    <div class="w-12 h-12 bg-success-50 rounded-full flex items-center justify-center mx-auto mb-3">
                        <span class="text-success-700 text-xl">↺</span>
                    </div>
                    <h3 class="text-lg font-bold text-neutral-900 mb-1">تأكيد الاستعادة</h3>
                    <p class="text-sm text-neutral-500">سيعود المستخدم للقائمة النشطة بنفس الدور والصلاحيات.</p>
                </div>

                <div class="flex gap-3 px-6 pb-5">
                    <flux:button wire:click="$set('restoreUserId', null)" variant="outline" class="flex-1">
                        إلغاء
                    </flux:button>
                    <flux:button wire:click="restoreUser" variant="primary" class="flex-1">
                        استعادة
                    </flux:button>
                </div>

            </div>
        </div>
    @endif

</div>
