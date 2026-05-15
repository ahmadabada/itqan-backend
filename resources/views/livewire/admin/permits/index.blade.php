<div class="p-4 sm:p-6 lg:p-8">

    <div class="mb-6 sm:mb-8">
        <h2 class="text-xl sm:text-2xl font-bold text-neutral-900">أذونات إعادة الاختبار</h2>
        <p class="text-neutral-500 text-xs sm:text-sm mt-0.5">منح أذونات لإعادة الاختبار</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:gap-8 lg:grid-cols-2">

        {{-- Grant form --}}
        <div class="bg-white rounded-xl border border-neutral-200 p-6">
            <h3 class="text-base font-semibold text-neutral-900 mb-4">منح إذن جديد</h3>

            @if(! $selectedStudent)
                {{-- Student search --}}
                <div class="mb-3">
                    <flux:input
                        wire:model.live.debounce.300ms="searchStudent"
                        placeholder="ابحث عن الطالب..."
                    />
                </div>

                @if($studentResults->isNotEmpty())
                    <div class="border border-neutral-200 rounded-lg overflow-hidden">
                        @foreach($studentResults as $s)
                            <button
                                wire:click="selectStudentForPermit({{ $s->id }})"
                                class="w-full text-start px-4 py-3 hover:bg-primary-50 transition-colors border-b border-neutral-100 last:border-0 text-sm"
                            >
                                <span class="font-medium text-neutral-900">{{ $s->fullName() }}</span>
                                <span class="text-neutral-400 ms-2">{{ $s->national_id }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif

            @elseif(! $generatedCode)
                {{-- Confirm grant --}}
                <div class="bg-primary-50 rounded-xl px-5 py-4 mb-5">
                    <p class="font-medium text-primary-800">{{ $selectedStudent->fullName() }}</p>
                    <p class="text-sm text-primary-500">{{ $selectedStudent->national_id }}</p>
                </div>

                <div class="flex gap-3">
                    <flux:button wire:click="clearGrant" variant="outline" class="flex-1">إلغاء</flux:button>
                    <flux:button wire:click="grantPermit" variant="primary" class="flex-1">منح الإذن</flux:button>
                </div>

            @else
                {{-- Show the generated code --}}
                <div class="bg-success-50 border border-success-200 rounded-xl p-5 text-center mb-4">
                    <p class="text-sm text-success-700 mb-2">تم إنشاء رمز الإذن بنجاح</p>
                    <p class="text-2xl font-black font-mono text-success-800 tracking-widest">{{ $generatedCode }}</p>
                    <p class="text-xs text-success-600 mt-2">أرسل هذا الرمز للمختبر</p>
                </div>
                <flux:button wire:click="clearGrant" variant="primary" class="w-full">إذن جديد</flux:button>
            @endif
        </div>

        {{-- Recent permits --}}
        <div>
            <h3 class="text-base font-semibold text-neutral-900 mb-4">الأذونات الأخيرة</h3>
            <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-neutral-50 border-b border-neutral-200">
                        <tr>
                            <th class="text-start px-4 py-3 text-neutral-600 font-medium w-12">#</th>
                            <th class="text-start px-4 py-3 text-neutral-600 font-medium">الطالب</th>
                            <th class="text-start px-4 py-3 text-neutral-600 font-medium">الرمز</th>
                            <th class="text-start px-4 py-3 text-neutral-600 font-medium">الحالة</th>
                            <th class="text-start px-4 py-3 text-neutral-600 font-medium">ينتهي</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse($permits as $permit)
                            <tr class="hover:bg-neutral-50">
                                <td class="px-4 py-3 text-neutral-400 tabular-nums">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-neutral-900">{{ $permit->student->fullName() }}</p>
                                </td>
                                <td class="px-4 py-3 font-mono text-sm text-neutral-700">{{ $permit->permit_code }}</td>
                                <td class="px-4 py-3">
                                    @if($permit->is_used)
                                        <span class="text-xs px-2 py-0.5 rounded bg-neutral-100 text-neutral-500">مستخدم</span>
                                    @elseif($permit->expires_at->isPast())
                                        <span class="text-xs px-2 py-0.5 rounded bg-danger-50 text-danger-500">منتهي</span>
                                    @else
                                        <span class="text-xs px-2 py-0.5 rounded bg-success-50 text-success-700">صالح</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-neutral-500 text-xs">{{ $permit->expires_at->format('Y/m/d') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-neutral-400">لا توجد أذونات</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if($permits->hasPages())
                    <div class="px-4 py-3 border-t border-neutral-200">{{ $permits->links() }}</div>
                @endif
            </div>
        </div>

    </div>

</div>
