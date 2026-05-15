<div class="bg-white rounded-2xl shadow-sm border border-neutral-200 p-8">

    <form wire:submit="login" class="space-y-5">

        <flux:field>
            <flux:label>رقم الهوية</flux:label>
            <flux:input
                wire:model="national_id"
                type="text"
                inputmode="numeric"
                placeholder="أدخل رقم الهوية الوطنية"
                autofocus
                autocomplete="username"
            />
            <flux:error name="national_id" />
        </flux:field>

        <flux:field>
            <flux:label>كلمة المرور</flux:label>
            <flux:input
                wire:model="password"
                type="password"
                placeholder="أدخل كلمة المرور"
                autocomplete="current-password"
                viewable
            />
            <flux:error name="password" />
        </flux:field>

        <div class="flex items-center gap-2">
            <flux:checkbox wire:model="remember" id="remember" />
            <label for="remember" class="text-sm text-neutral-500 cursor-pointer select-none">تذكرني</label>
        </div>

        <flux:button
            type="submit"
            variant="primary"
            class="w-full"
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove wire:target="login">تسجيل الدخول</span>
            <span wire:loading wire:target="login">جارٍ التحقق...</span>
        </flux:button>

    </form>

</div>
