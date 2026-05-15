<div class="p-8">

    <div class="mb-8">
        <h2 class="text-2xl font-bold text-neutral-900">إعدادات النظام</h2>
        <p class="text-neutral-500 text-sm mt-0.5">القيم الافتراضية للنظام</p>
    </div>

    <div class="max-w-2xl">
        <form wire:submit="saveSettings" class="space-y-6">

            {{-- Academy name --}}
            <div class="bg-white rounded-xl border border-neutral-200 p-6">
                <h3 class="text-base font-semibold text-neutral-900 mb-4">معلومات الأكاديمية</h3>
                <flux:field>
                    <flux:label>اسم الأكاديمية</flux:label>
                    <flux:input wire:model="academy_name" type="text" />
                    <flux:error name="academy_name" />
                    <flux:description>يظهر في الشهادات والتقارير</flux:description>
                </flux:field>
            </div>

            {{-- Exam settings --}}
            <div class="bg-white rounded-xl border border-neutral-200 p-6 space-y-5">
                <h3 class="text-base font-semibold text-neutral-900">إعدادات الاختبار</h3>

                <flux:field>
                    <flux:label>درجة الإجازة (من 100)</flux:label>
                    <div class="flex items-center gap-3">
                        <flux:input
                            wire:model="passing_score"
                            type="number"
                            min="0"
                            max="100"
                            class="w-32"
                        />
                        <span class="text-neutral-400 text-sm">الطالب مجاز إذا حصل على هذه الدرجة أو أعلى</span>
                    </div>
                    <flux:error name="passing_score" />
                </flux:field>
            </div>

            {{-- Re-exam settings --}}
            <div class="bg-white rounded-xl border border-neutral-200 p-6 space-y-5">
                <h3 class="text-base font-semibold text-neutral-900">إعادة الاختبار</h3>

                <flux:field>
                    <flux:label>صلاحية رمز الإذن (أيام)</flux:label>
                    <div class="flex items-center gap-3">
                        <flux:input
                            wire:model="reexam_permit_ttl_days"
                            type="number"
                            min="1"
                            max="365"
                            class="w-32"
                        />
                        <span class="text-neutral-400 text-sm">مدة صلاحية الرمز منذ إنشائه</span>
                    </div>
                    <flux:error name="reexam_permit_ttl_days" />
                </flux:field>
            </div>

            {{-- Import settings --}}
            <div class="bg-white rounded-xl border border-neutral-200 p-6 space-y-5">
                <h3 class="text-base font-semibold text-neutral-900">استيراد Excel</h3>

                <flux:field>
                    <flux:label>عند تكرار رقم الهوية</flux:label>
                    <flux:select wire:model="excel_import_mode" class="w-48">
                        <option value="skip">تجاهل السطر (skip)</option>
                        <option value="update">تحديث البيانات (update)</option>
                    </flux:select>
                    <flux:error name="excel_import_mode" />
                </flux:field>
            </div>

            {{-- Results query --}}
            <div class="bg-white rounded-xl border border-neutral-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-neutral-900">استعلام الطلاب عن النتائج</p>
                        <p class="text-sm text-neutral-500 mt-0.5">السماح للطلاب بمشاهدة نتائجهم برقم الهوية</p>
                    </div>
                    <flux:switch wire:model="results_query_enabled" />
                </div>
                <div class="mt-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium {{ $results_query_enabled ? 'bg-success-50 text-success-700' : 'bg-neutral-100 text-neutral-500' }}">
                        {{ $results_query_enabled ? 'مفعّل' : 'معطّل' }}
                    </span>
                </div>
            </div>

            {{-- Save button --}}
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>حفظ الإعدادات</span>
                    <span wire:loading>جارٍ الحفظ...</span>
                </flux:button>
            </div>

        </form>
    </div>

</div>
