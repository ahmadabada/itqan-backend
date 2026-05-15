<div>

    <div class="mb-8 text-center lg:text-start">
        <h2 class="text-2xl lg:text-3xl font-black text-neutral-900 tracking-tight">مرحباً بعودتك</h2>
        <p class="text-neutral-500 text-sm mt-2">سجّل دخولك للوصول إلى لوحة التحكم</p>
    </div>

    <div class="bg-white rounded-3xl p-7 lg:p-8 shadow-[0_10px_40px_-12px_rgba(0,0,0,0.08)] ring-1 ring-neutral-100">

        <form wire:submit="login" class="space-y-5" x-data="{ showPassword: false }">

            {{-- ── National ID ── --}}
            <div>
                <label for="national_id" class="block text-sm font-bold text-neutral-800 mb-2">
                    رقم الهوية
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 start-0 flex items-center ps-4 text-neutral-400 pointer-events-none">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z"/>
                        </svg>
                    </span>
                    <input
                        id="national_id"
                        type="text"
                        inputmode="numeric"
                        pattern="\d{9}"
                        maxlength="9"
                        wire:model="national_id"
                        placeholder="9 أرقام"
                        autocomplete="username"
                        autofocus
                        class="w-full ps-12 pe-4 py-3.5 text-base font-medium tabular-nums
                               bg-neutral-50/70 text-neutral-900 placeholder:text-neutral-400
                               border-2 border-neutral-200 rounded-2xl
                               focus:bg-white focus:border-primary-400 focus:ring-4 focus:ring-primary-100
                               focus:outline-none transition-all duration-200"
                    />
                </div>
                @error('national_id')
                    <p class="text-xs text-danger-500 mt-1.5 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- ── Password ── --}}
            <div>
                <label for="password" class="block text-sm font-bold text-neutral-800 mb-2">
                    كلمة المرور
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 start-0 flex items-center ps-4 text-neutral-400 pointer-events-none">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                        </svg>
                    </span>
                    <input
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        wire:model="password"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        class="w-full ps-12 pe-12 py-3.5 text-base font-medium
                               bg-neutral-50/70 text-neutral-900 placeholder:text-neutral-400
                               border-2 border-neutral-200 rounded-2xl
                               focus:bg-white focus:border-primary-400 focus:ring-4 focus:ring-primary-100
                               focus:outline-none transition-all duration-200"
                    />
                    <button type="button" @click="showPassword = !showPassword"
                            class="absolute inset-y-0 end-0 flex items-center pe-4 text-neutral-400 hover:text-primary-600 transition-colors"
                            :title="showPassword ? 'إخفاء' : 'إظهار'">
                        <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                        </svg>
                        <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" style="display:none;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/>
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p class="text-xs text-danger-500 mt-1.5 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- ── Remember me ── --}}
            <label class="flex items-center gap-2.5 cursor-pointer select-none pt-1">
                <input type="checkbox" wire:model="remember"
                       class="w-4 h-4 rounded-md border-2 border-neutral-300 text-primary-500
                              focus:ring-2 focus:ring-primary-200 focus:ring-offset-0 cursor-pointer">
                <span class="text-sm text-neutral-700 font-medium">تذكرني</span>
            </label>

            {{-- ── Submit ── --}}
            <button
                type="submit"
                class="w-full py-3.5 rounded-2xl
                       bg-gradient-to-b from-primary-500 to-primary-600
                       hover:from-primary-600 hover:to-primary-700
                       active:from-primary-700 active:to-primary-700
                       text-white font-bold text-base tracking-tight
                       shadow-[0_8px_24px_-8px_rgba(59,130,246,0.5)]
                       hover:shadow-[0_12px_28px_-8px_rgba(59,130,246,0.6)]
                       active:scale-[0.99]
                       transition-all duration-200
                       disabled:opacity-60 disabled:cursor-wait"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="login" class="inline-flex items-center justify-center gap-2">
                    تسجيل الدخول
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                    </svg>
                </span>
                <span wire:loading wire:target="login" class="inline-flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992V4.356M2.985 19.644v-4.992h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                    </svg>
                    جارٍ التحقق...
                </span>
            </button>

        </form>

    </div>

    <p class="text-center text-xs text-neutral-400 mt-6">
        © {{ date('Y') }} أكاديمية الإتقان لتعليم القرآن — فلسطين / غزة
    </p>

</div>
