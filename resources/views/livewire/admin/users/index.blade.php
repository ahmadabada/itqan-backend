<div class="p-4 sm:p-6 lg:p-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-neutral-900">المستخدمون</h2>
            <p class="text-neutral-500 text-sm mt-0.5">الأدمنز والمختبرون</p>
        </div>
        <flux:button wire:click="openCreateModal" variant="primary" size="sm">
            إضافة مستخدم
        </flux:button>
    </div>

    {{-- Filters --}}
    <div class="flex gap-3 mb-5">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="بحث بالاسم أو رقم الهوية..."
            class="max-w-xs"
        />
        <flux:select wire:model.live="filterRole" class="w-40">
            <option value="">كل الأدوار</option>
            <option value="super_admin">سوبر أدمن</option>
            <option value="admin">أدمن</option>
            <option value="examiner">مختبر</option>
        </flux:select>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 border-b border-neutral-200">
                <tr>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium w-12">#</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">رقم الهوية</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الاسم</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الجنس</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الدور</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الحالة</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">آخر دخول</th>
                    <th class="text-start px-4 py-3 text-neutral-600 font-medium">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse($users as $user)
                    <tr class="hover:bg-neutral-50 transition-colors">
                        <td class="px-4 py-3 text-neutral-400 tabular-nums">{{ $loop->iteration }}</td>
                        <td class="px-4 py-3 font-mono text-neutral-700">{{ $user->national_id }}</td>
                        <td class="px-4 py-3 font-medium text-neutral-900">{{ $user->fullName() }}</td>
                        <td class="px-4 py-3">
                            @if($user->gender)
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $user->gender->value === 'male' ? 'bg-sky-50 text-sky-700' : 'bg-pink-50 text-pink-700' }}">
                                    {{ $user->gender->label() }}
                                </span>
                            @else
                                <span class="text-xs text-neutral-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                {{ $user->is_super_admin ? 'bg-secondary-100 text-secondary-700' : ($user->role === \App\Enums\UserRole::Admin ? 'bg-primary-50 text-primary-700' : 'bg-neutral-100 text-neutral-600') }}">
                                {{ $user->role->label() }}
                                @if($user->is_super_admin) ★ @endif
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                {{ $user->is_active ? 'bg-success-50 text-success-700' : 'bg-danger-50 text-danger-600' }}">
                                {{ $user->is_active ? 'نشط' : 'معطّل' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-neutral-500">
                            {{ $user->last_login_at?->diffForHumans() ?? 'لم يسجل بعد' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                {{-- Toggle active (not for super admin) --}}
                                @if(! $user->is_super_admin && ($currentUser->isSuperAdmin() || $user->role === \App\Enums\UserRole::Examiner))
                                    <button
                                        wire:click="toggleActive({{ $user->id }})"
                                        wire:confirm="{{ $user->is_active ? 'تعطيل هذا المستخدم؟' : 'تفعيل هذا المستخدم؟' }}"
                                        class="text-xs text-neutral-500 hover:text-primary-600 transition-colors"
                                    >
                                        {{ $user->is_active ? 'تعطيل' : 'تفعيل' }}
                                    </button>
                                @endif

                                {{-- Reset password (super admin only — BR-USR-03) --}}
                                @if($currentUser->isSuperAdmin() && ! $user->is_super_admin)
                                    <button
                                        wire:click="openResetModal({{ $user->id }})"
                                        class="text-xs text-neutral-500 hover:text-warning-600 transition-colors"
                                    >
                                        إعادة ضبط
                                    </button>
                                @endif

                                {{-- Delete (BR-USR-01, BR-USR-02) --}}
                                @if(! $user->is_super_admin && ($currentUser->isSuperAdmin() || $user->role === \App\Enums\UserRole::Examiner))
                                    <button
                                        wire:click="confirmDelete({{ $user->id }})"
                                        class="text-xs text-neutral-500 hover:text-danger-600 transition-colors"
                                    >
                                        حذف
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-neutral-400">
                            لا يوجد مستخدمون
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── Create User Modal ── --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
            <div class="bg-white rounded-2xl w-full max-w-lg mx-4 shadow-xl">

                <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-200">
                    <h3 class="text-lg font-bold text-neutral-900">إضافة مستخدم جديد</h3>
                    <button wire:click="$set('showCreateModal', false)" class="text-neutral-400 hover:text-neutral-600">✕</button>
                </div>

                <form wire:submit="createUser" class="px-6 py-5 space-y-4">

                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>رقم الهوية</flux:label>
                            <flux:input wire:model="form_national_id" type="text" inputmode="numeric" maxlength="9" placeholder="9 أرقام" />
                            <flux:error name="form_national_id" />
                        </flux:field>

                        <flux:field>
                            <flux:label>الدور</flux:label>
                            <flux:select wire:model="form_role">
                                <option value="">اختر الدور</option>
                                @foreach($roleOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </flux:select>
                            <flux:error name="form_role" />
                        </flux:field>
                    </div>

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

                    <flux:field>
                        <flux:label>كلمة المرور المؤقتة</flux:label>
                        <flux:input wire:model="form_password" type="text" viewable />
                        <flux:error name="form_password" />
                    </flux:field>

                    <div class="flex justify-end gap-3 pt-2">
                        <flux:button type="button" wire:click="$set('showCreateModal', false)" variant="outline">
                            إلغاء
                        </flux:button>
                        <flux:button type="submit" variant="primary">
                            إضافة
                        </flux:button>
                    </div>

                </form>

            </div>
        </div>
    @endif

    {{-- ── Reset Password Modal ── --}}
    @if($showResetModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
            <div class="bg-white rounded-2xl w-full max-w-sm mx-4 shadow-xl">

                <div class="px-6 py-4 border-b border-neutral-200">
                    <h3 class="text-lg font-bold text-neutral-900">إعادة ضبط كلمة المرور</h3>
                </div>

                <div class="px-6 py-5">
                    <p class="text-sm text-neutral-600 mb-3">
                        سيتم ضبط كلمة المرور لهذه القيمة. احفظها وسلّمها للمستخدم.
                    </p>
                    <div class="bg-neutral-100 rounded-lg px-4 py-3 font-mono text-lg font-bold text-neutral-900 text-center tracking-wider mb-5">
                        {{ $generatedPassword }}
                    </div>
                    <div class="flex justify-end gap-3">
                        <flux:button wire:click="$set('showResetModal', false)" variant="outline">
                            إلغاء
                        </flux:button>
                        <flux:button wire:click="confirmResetPassword" variant="primary">
                            تأكيد الضبط
                        </flux:button>
                    </div>
                </div>

            </div>
        </div>
    @endif

    {{-- ── Delete Confirm Modal ── --}}
    @if($deleteUserId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
            <div class="bg-white rounded-2xl w-full max-w-sm mx-4 shadow-xl">

                <div class="px-6 py-5 text-center">
                    <div class="w-12 h-12 bg-danger-50 rounded-full flex items-center justify-center mx-auto mb-3">
                        <span class="text-danger-500 text-xl">!</span>
                    </div>
                    <h3 class="text-lg font-bold text-neutral-900 mb-1">تأكيد الحذف</h3>
                    <p class="text-sm text-neutral-500">هل أنت متأكد من حذف هذا المستخدم؟ لا يمكن التراجع.</p>
                </div>

                <div class="flex gap-3 px-6 pb-5">
                    <flux:button wire:click="$set('deleteUserId', null)" variant="outline" class="flex-1">
                        إلغاء
                    </flux:button>
                    <flux:button wire:click="deleteUser" variant="danger" class="flex-1">
                        حذف
                    </flux:button>
                </div>

            </div>
        </div>
    @endif

</div>
