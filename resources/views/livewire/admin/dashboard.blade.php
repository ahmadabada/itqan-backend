<div class="p-4 sm:p-6 lg:p-8">

    <div class="mb-6 sm:mb-8">
        <h2 class="text-xl sm:text-2xl font-bold text-neutral-900">مرحباً، {{ $user->first_name }}</h2>
        <p class="text-neutral-500 text-xs sm:text-sm mt-1">نظرة عامة على نظام الاختبارات</p>
    </div>

    {{-- Quick links --}}
    <div class="grid grid-cols-1 gap-3 sm:gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6 sm:mb-8">
        <a href="{{ route('admin.exams.index') }}" wire:navigate class="bg-white rounded-xl border border-neutral-200 p-4 sm:p-5 hover:border-primary-300 hover:shadow-sm transition-all group">
            <p class="font-medium text-neutral-900 group-hover:text-primary-600">كل الاختبارات</p>
            <p class="text-xs sm:text-sm text-neutral-500 mt-1">تصفح وفلترة جميع الاختبارات</p>
        </a>
        <a href="{{ route('admin.students') }}" wire:navigate class="bg-white rounded-xl border border-neutral-200 p-4 sm:p-5 hover:border-primary-300 hover:shadow-sm transition-all group">
            <p class="font-medium text-neutral-900 group-hover:text-primary-600">إدارة الطلاب</p>
            <p class="text-xs sm:text-sm text-neutral-500 mt-1">عرض وإدارة سجلات الطلاب</p>
        </a>
        <a href="{{ route('admin.users') }}" wire:navigate class="bg-white rounded-xl border border-neutral-200 p-4 sm:p-5 hover:border-primary-300 hover:shadow-sm transition-all group">
            <p class="font-medium text-neutral-900 group-hover:text-primary-600">إدارة المستخدمين</p>
            <p class="text-xs sm:text-sm text-neutral-500 mt-1">المختبرون والمديرون</p>
        </a>
        <a href="{{ route('admin.settings') }}" wire:navigate class="bg-white rounded-xl border border-neutral-200 p-4 sm:p-5 hover:border-primary-300 hover:shadow-sm transition-all group">
            <p class="font-medium text-neutral-900 group-hover:text-primary-600">إعدادات النظام</p>
            <p class="text-xs sm:text-sm text-neutral-500 mt-1">درجة الإجازة وإعدادات أخرى</p>
        </a>
    </div>

    {{-- Stats cards --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-4 mb-6 sm:mb-8 lg:grid-cols-4">

        <div class="bg-white rounded-xl border border-neutral-200 p-4 sm:p-5">
            <p class="text-xs sm:text-sm text-neutral-500 mb-1">إجمالي الطلاب</p>
            <p class="text-2xl sm:text-3xl font-bold text-primary-500">{{ number_format($totalStudents) }}</p>
        </div>

        <div class="bg-white rounded-xl border border-neutral-200 p-4 sm:p-5">
            <p class="text-xs sm:text-sm text-neutral-500 mb-1">إجمالي الاختبارات</p>
            <p class="text-2xl sm:text-3xl font-bold text-primary-500">{{ number_format($totalExams) }}</p>
        </div>

        <div class="bg-white rounded-xl border border-neutral-200 p-4 sm:p-5">
            <p class="text-xs sm:text-sm text-neutral-500 mb-1">الاختبارات المعتمدة</p>
            <p class="text-2xl sm:text-3xl font-bold text-success-500">
                {{ number_format($approvedExams) }}
            </p>
        </div>

        <div class="bg-white rounded-xl border border-neutral-200 p-4 sm:p-5">
            <p class="text-xs sm:text-sm text-neutral-500 mb-1">المستخدمون</p>
            <p class="text-2xl sm:text-3xl font-bold text-primary-500">{{ $totalUsers }}</p>
        </div>

    </div>

    {{-- Charts: Histogram (full width) + 2 pies --}}
    <div class="grid grid-cols-1 gap-3 sm:gap-4 mb-6 sm:mb-8">

        {{-- Score Histogram --}}
        <div
            class="bg-white rounded-xl border border-neutral-200 p-4 sm:p-5"
            x-data="scoreHistogram(@js($scoreDistribution), {{ (int) $passingScoreMale }}, {{ (int) $passingScoreFemale }})"
            x-init="render()"
            wire:ignore
        >
            <div class="flex items-center justify-between mb-3 sm:mb-4">
                <h3 class="text-sm sm:text-base font-semibold text-neutral-900">توزيع درجات الطلاب</h3>
                <span class="text-xs text-neutral-500">حد النجاح: ذكور {{ $passingScoreMale }} / إناث {{ $passingScoreFemale }}</span>
            </div>
            <div x-ref="chart"></div>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:gap-4 md:grid-cols-2 lg:grid-cols-3">
            {{-- Gender Pie --}}
            <div
                class="bg-white rounded-xl border border-neutral-200 p-4 sm:p-5"
                x-data="pieChart(@js($genderDistribution), ['#3b82f6', '#ec4899'])"
                x-init="render()"
                wire:ignore
            >
                <h3 class="text-sm sm:text-base font-semibold text-neutral-900 mb-3 sm:mb-4">توزيع الطلاب حسب الجنس</h3>
                <div x-ref="chart"></div>
            </div>

            {{-- Halaqah Pie --}}
            <div
                class="bg-white rounded-xl border border-neutral-200 p-4 sm:p-5"
                x-data="pieChart(@js($halaqahDistribution), ['#0ea5e9', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#14b8a6', '#94a3b8'])"
                x-init="render()"
                wire:ignore
            >
                <h3 class="text-sm sm:text-base font-semibold text-neutral-900 mb-3 sm:mb-4">توزيع الطلاب حسب الحلقة</h3>
                <div x-ref="chart"></div>
            </div>
        </div>
    </div>

    <script>
        function scoreHistogram(data, passingScoreMale, passingScoreFemale) {
            return {
                render() {
                    const maleBinIndex   = Math.min(9, Math.floor(passingScoreMale / 10));
                    const femaleBinIndex = Math.min(9, Math.floor(passingScoreFemale / 10));

                    const chart = new ApexCharts(this.$refs.chart, {
                        chart: {
                            type: 'bar',
                            stacked: true,
                            height: 320,
                            fontFamily: 'inherit',
                            toolbar: { show: false },
                            animations: { speed: 400 },
                        },
                        series: [
                            { name: 'ذكور',  data: data.male },
                            { name: 'إناث', data: data.female },
                        ],
                        xaxis: {
                            categories: data.labels,
                            title: { text: 'الدرجة', style: { fontSize: '12px', fontWeight: 500 } },
                        },
                        yaxis: {
                            opposite: true,
                            title: { text: 'عدد الطلاب', style: { fontSize: '12px', fontWeight: 500 } },
                            labels: { formatter: (v) => Math.round(v) },
                        },
                        plotOptions: {
                            bar: {
                                borderRadius: 6,
                                columnWidth: '65%',
                            },
                        },
                        colors: ['#3b82f6', '#ec4899'],
                        legend: { position: 'top', fontSize: '12px' },
                        dataLabels: { enabled: false },
                        grid: { borderColor: '#e5e7eb', strokeDashArray: 4 },
                        tooltip: { y: { formatter: (v) => `${v} طالب` } },
                        annotations: {
                            xaxis: [
                                {
                                    x: data.labels[maleBinIndex],
                                    borderColor: '#1d4ed8',
                                    strokeDashArray: 4,
                                    label: {
                                        text: `ذكور (${passingScoreMale})`,
                                        style: { background: '#1d4ed8', color: '#fff', fontSize: '11px' },
                                    },
                                },
                                {
                                    x: data.labels[femaleBinIndex],
                                    borderColor: '#be185d',
                                    strokeDashArray: 4,
                                    label: {
                                        text: `إناث (${passingScoreFemale})`,
                                        offsetY: 20,
                                        style: { background: '#be185d', color: '#fff', fontSize: '11px' },
                                    },
                                },
                            ],
                        },
                    });
                    chart.render();
                },
            };
        }

        function pieChart(data, palette) {
            return {
                render() {
                    if (!data.counts.length) {
                        this.$refs.chart.innerHTML = '<p class="text-center text-neutral-400 text-sm py-12">لا توجد بيانات</p>';
                        return;
                    }
                    const chart = new ApexCharts(this.$refs.chart, {
                        chart: {
                            type: 'donut',
                            height: 300,
                            fontFamily: 'inherit',
                            animations: { speed: 400 },
                        },
                        series: data.counts,
                        labels: data.labels,
                        colors: palette,
                        legend: { position: 'bottom', fontSize: '13px' },
                        dataLabels: {
                            enabled: true,
                            formatter: (val) => `${Math.round(val)}%`,
                            style: { fontSize: '12px' },
                        },
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '60%',
                                    labels: {
                                        show: true,
                                        total: {
                                            show: true,
                                            label: 'الإجمالي',
                                            fontSize: '13px',
                                            color: '#64748b',
                                            formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0),
                                        },
                                    },
                                },
                            },
                        },
                        tooltip: { y: { formatter: (v) => `${v} طالب` } },
                    });
                    chart.render();
                },
            };
        }
    </script>
</div>
