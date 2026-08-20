<div class="p-4 sm:p-6 lg:p-8">

    <div class="mb-6 sm:mb-8">
        <h2 class="text-xl sm:text-2xl font-bold text-neutral-900">إعدادات النظام</h2>
        <p class="text-neutral-500 text-xs sm:text-sm mt-0.5">القيم الافتراضية للنظام</p>
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

                <p class="text-neutral-500 text-sm">درجة الإجازة (من 100) — يُعتمد الحد المناسب لجنس الطالب.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>درجة الإجازة (ذكور)</flux:label>
                        <flux:input
                            wire:model="passing_score_male"
                            type="number"
                            min="0"
                            max="100"
                        />
                        <flux:error name="passing_score_male" />
                    </flux:field>

                    <flux:field>
                        <flux:label>درجة الإجازة (إناث)</flux:label>
                        <flux:input
                            wire:model="passing_score_female"
                            type="number"
                            min="0"
                            max="100"
                        />
                        <flux:error name="passing_score_female" />
                    </flux:field>
                </div>
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

            {{-- Exam rounds (super admin only) --}}
            @if($canManageRounds)
                <div class="bg-white rounded-xl border border-neutral-200 p-6 space-y-5">
                    <h3 class="text-base font-semibold text-neutral-900">جولات الاختبارات</h3>

                    <flux:field>
                        <flux:label>الجولة الفعالة لاختبارات الموبايل</flux:label>
                        <flux:select wire:model="mobile_exam_round_id">
                            @foreach($examRounds as $round)
                                <option value="{{ $round->id }}">{{ $round->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="mobile_exam_round_id" />
                        <flux:description>كل اختبار مرفوع من الموبايل سيلتحق بهذه الجولة تلقائياً.</flux:description>
                    </flux:field>

                    <div class="pt-3 border-t border-neutral-100">
                        <h4 class="text-sm font-semibold text-neutral-900 mb-3">إنشاء جولة جديدة</h4>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <flux:input
                                wire:model="new_exam_round_name"
                                type="text"
                                placeholder="مثال: جولة شتاء 2026"
                                class="flex-1"
                            />
                            <flux:button type="button" variant="outline" wire:click="createExamRound">
                                إضافة الجولة
                            </flux:button>
                        </div>
                        <flux:error name="new_exam_round_name" />
                    </div>
                </div>
            @endif

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
