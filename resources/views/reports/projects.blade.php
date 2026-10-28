@extends('layouts.app')

@section('title', 'گزارش و تحلیل پیشرفت پروژه‌ها')

@section('content')
<div class="space-y-6 pb-12" x-data="{
    viewMode: localStorage.getItem('reports_projects_view') || 'table',
    setViewMode(mode) {
        this.viewMode = mode;
        localStorage.setItem('reports_projects_view', mode);
    }
}">

<script>
function exportProjectsCSV() {
    var rows = [
        ['کد پروژه', 'عنوان پروژه', 'واحد سازمانی', 'اعضای تیم', 'اولویت', 'وضعیت', 'درصد پیشرفت', 'تعداد کارها', 'مهلت تحویل']
    ];
    document.querySelectorAll('#projectsReportTable tbody tr[data-project]').forEach(function(tr) {
        rows.push([
            tr.getAttribute('data-key') || '',
            tr.getAttribute('data-title') || '',
            tr.getAttribute('data-org') || '',
            tr.getAttribute('data-members') || '',
            tr.getAttribute('data-priority') || '',
            tr.getAttribute('data-status') || '',
            (tr.getAttribute('data-progress') || '0') + '%',
            tr.getAttribute('data-tasks') || '0',
            tr.getAttribute('data-deadline') || 'تعیین‌نشده'
        ]);
    });
    var csvText = '\uFEFF' + rows.map(function(r) {
        return r.map(function(cell) {
            return '"' + String(cell || '').replace(/"/g, '""') + '"';
        }).join(',');
    }).join('\r\n');

    var blob = new Blob([csvText], { type: 'text/csv;charset=utf-8;' });
    var url = URL.createObjectURL(blob);
    var link = document.createElement('a');
    link.href = url;
    link.download = 'projects_report_' + new Date().toISOString().slice(0, 10) + '.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}
</script>

    <!-- ========================================== -->
    <!-- 1. Executive Hero Header & Tab Navigation  -->
    <!-- ========================================== -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-6 sm:p-8 text-white shadow-xl shadow-indigo-950/20 border border-slate-700/60">
        <!-- Ambient Glow Blobs -->
        <div class="absolute -right-16 -top-16 w-80 h-80 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute left-1/3 -bottom-16 w-72 h-72 bg-violet-600/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="space-y-3">
                <div class="flex items-center gap-2 text-indigo-300 text-xs font-semibold">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>گزارش‌ها و تحلیل پروژه‌ها</span>
                    <span class="text-slate-500">•</span>
                    <span>{{ verta()->format('l، d F Y') }}</span>
                </div>

                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black tracking-tight text-white flex items-center gap-3">
                    <span class="bg-indigo-600/40 p-2 rounded-2xl border border-indigo-400/30">📊</span>
                    <span>گزارش و وضعیت پروژه‌ها</span>
                </h1>

                <p class="text-slate-300 text-xs sm:text-sm max-w-2xl leading-relaxed">
                    بررسی روند پیشرفت پروژه‌ها، وضعیت زمان‌بندی و کارهای نیازمند پیگیری.
                </p>

                <!-- Navigation Tabs (Projects vs Workload) -->
                <div class="flex flex-wrap items-center gap-2 pt-2">
                    <a href="{{ route('reports.projects') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-white text-slate-900 shadow-md shadow-black/10 border border-white transition-all">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <span>پروژه‌ها</span>
                        <span class="px-2 py-0.5 rounded-md bg-indigo-100 text-indigo-800 text-[11px] font-bold">{{ $totalProjects }}</span>
                    </a>

                    <a href="{{ route('reports.workload') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold bg-white/10 hover:bg-white/20 text-slate-200 backdrop-blur-md border border-white/10 transition-all">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span>حجم کاری همکاران</span>
                    </a>
                </div>
            </div>

            <!-- Header Quick Actions -->
            <div class="flex items-center gap-2.5 shrink-0 flex-wrap sm:flex-nowrap">
                <button onclick="exportProjectsCSV()" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-semibold border border-white/20 backdrop-blur-md transition-all shadow-sm cursor-pointer">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>خروجی اکسل (CSV)</span>
                </button>

                <button onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-semibold border border-white/20 backdrop-blur-md transition-all shadow-sm cursor-pointer">
                    <svg class="w-4 h-4 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>چاپ گزارش</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 2. Primary Portfolio KPI Metrics Grid      -->
    <!-- ========================================== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        <!-- 1. Total Projects -->
        <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">کل پروژه‌ها</span>
                <span class="p-2 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                </span>
            </div>
            <div class="mt-3 flex items-baseline justify-between">
                <div class="text-2xl font-black text-slate-900 dark:text-white">{{ number_format($totalProjects) }}</div>
                <div class="text-[11px] text-slate-400 font-medium">
                    {{ $rootProjectsCount }} اصلی / {{ $subProjectsCount }} زیرپروژه
                </div>
            </div>
            <div class="mt-3 w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                <div class="bg-blue-600 h-full rounded-full" style="width: 100%"></div>
            </div>
        </div>

        <!-- 2. Active Projects -->
        <a href="{{ route('reports.projects', ['status' => 'ACTIVE']) }}" class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 shadow-xs hover:shadow-md hover:border-emerald-300 dark:hover:border-emerald-700 transition-all group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">پروژه‌های فعال</span>
                <span class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </span>
            </div>
            <div class="mt-3 flex items-baseline justify-between">
                <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ number_format($activeProjects) }}</div>
                <div class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                    {{ $totalProjects > 0 ? round(($activeProjects / $totalProjects) * 100) : 0 }}% کل
                </div>
            </div>
            <div class="mt-3 w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" style="width: {{ $totalProjects > 0 ? round(($activeProjects / $totalProjects) * 100) : 0 }}%"></div>
            </div>
        </a>

        <!-- 3. Completed Projects -->
        <a href="{{ route('reports.projects', ['status' => 'COMPLETED']) }}" class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 shadow-xs hover:shadow-md hover:border-indigo-300 dark:hover:border-indigo-700 transition-all group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">پروژه‌های تکمیل‌شده</span>
                <span class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="mt-3 flex items-baseline justify-between">
                <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400">{{ number_format($completedProjects) }}</div>
                <div class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400">
                    {{ $totalProjects > 0 ? round(($completedProjects / $totalProjects) * 100) : 0 }}% تحقق
                </div>
            </div>
            <div class="mt-3 w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                <div class="bg-indigo-600 h-full rounded-full transition-all duration-500" style="width: {{ $totalProjects > 0 ? round(($completedProjects / $totalProjects) * 100) : 0 }}%"></div>
            </div>
        </a>

        <!-- 4. Critical & Overdue Projects -->
        <a href="{{ route('reports.projects', ['health' => 'overdue']) }}" class="bg-white dark:bg-slate-800 p-5 rounded-2xl border {{ $overdueProjects > 0 ? 'border-rose-300 dark:border-rose-800/80 bg-rose-50/20' : 'border-slate-200/80 dark:border-slate-700/80' }} shadow-xs hover:shadow-md transition-all group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-rose-600 dark:text-rose-400 flex items-center gap-1.5">
                    @if($overdueProjects > 0)
                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
                    @endif
                    <span>دارای تاخیر / معوقه</span>
                </span>
                <span class="p-2 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="mt-3 flex items-baseline justify-between">
                <div class="text-2xl font-black text-rose-600 dark:text-rose-400">{{ number_format($overdueProjects) }}</div>
                <div class="text-[11px] font-bold text-rose-500">
                    {{ $overdueProjects > 0 ? 'نیازمند اقدام فوری' : 'بدون تاخیر' }}
                </div>
            </div>
            <div class="mt-3 w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                <div class="bg-rose-500 h-full rounded-full transition-all duration-500" style="width: {{ $totalProjects > 0 ? min(100, round(($overdueProjects / $totalProjects) * 100)) : 0 }}%"></div>
            </div>
        </a>

        <!-- 5. Force Majeure & High Priority -->
        <a href="{{ route('reports.projects', ['priority' => 'FORCE_MAJEURE']) }}" class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 shadow-xs hover:shadow-md hover:border-amber-300 dark:hover:border-amber-700 transition-all group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">فورس‌ماژور و بالا</span>
                <span class="p-2 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </span>
            </div>
            <div class="mt-3 flex items-baseline justify-between">
                <div class="text-2xl font-black text-amber-600 dark:text-amber-400">{{ number_format($forceMajeureProjects + $highPriorityProjects) }}</div>
                <div class="text-[11px] text-slate-400 font-medium">
                    {{ $forceMajeureProjects }} فورس / {{ $highPriorityProjects }} بالا
                </div>
            </div>
            <div class="mt-3 w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                <div class="bg-amber-500 h-full rounded-full transition-all duration-500" style="width: {{ $totalProjects > 0 ? min(100, round((($forceMajeureProjects + $highPriorityProjects) / $totalProjects) * 100)) : 0 }}%"></div>
            </div>
        </a>

        <!-- 6. On Hold & Other Statuses -->
        <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">در انتظار / متوقف</span>
                <span class="p-2 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="mt-3 flex items-baseline justify-between">
                <div class="text-2xl font-black text-slate-700 dark:text-slate-300">{{ number_format($onHoldProjects) }}</div>
                <div class="text-[11px] text-slate-400 font-medium">
                    {{ $cancelledProjects }} لغو شده
                </div>
            </div>
            <div class="mt-3 w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                <div class="bg-slate-400 h-full rounded-full transition-all duration-500" style="width: {{ $totalProjects > 0 ? round(($onHoldProjects / $totalProjects) * 100) : 0 }}%"></div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 3. Visual Analytics & Distribution Panels  -->
    <!-- ========================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <!-- Panel 1: Status Distribution -->
        <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                        <span>توزیع وضعیت پروژه‌ها</span>
                    </h3>
                    <span class="text-xs text-slate-400 font-medium">{{ $totalProjects }} پروژه کل</span>
                </div>

                <div class="space-y-3.5 pt-1">
                    <!-- Active -->
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1 font-semibold">
                            <span class="text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span>فعال و در جریان</span>
                            </span>
                            <span class="text-slate-700 dark:text-slate-300 font-bold">{{ $activeProjects }} <span class="text-[11px] text-slate-400 font-normal">({{ $totalProjects > 0 ? round(($activeProjects/$totalProjects)*100) : 0 }}%)</span></span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                            <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" style="width: {{ $totalProjects > 0 ? round(($activeProjects/$totalProjects)*100) : 0 }}%"></div>
                        </div>
                    </div>

                    <!-- Completed -->
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1 font-semibold">
                            <span class="text-indigo-600 dark:text-indigo-400 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                <span>تکمیل شده</span>
                            </span>
                            <span class="text-slate-700 dark:text-slate-300 font-bold">{{ $completedProjects }} <span class="text-[11px] text-slate-400 font-normal">({{ $totalProjects > 0 ? round(($completedProjects/$totalProjects)*100) : 0 }}%)</span></span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                            <div class="bg-indigo-600 h-full rounded-full transition-all duration-500" style="width: {{ $totalProjects > 0 ? round(($completedProjects/$totalProjects)*100) : 0 }}%"></div>
                        </div>
                    </div>

                    <!-- On Hold -->
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1 font-semibold">
                            <span class="text-amber-600 dark:text-amber-400 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                <span>در انتظار / تعلیق</span>
                            </span>
                            <span class="text-slate-700 dark:text-slate-300 font-bold">{{ $onHoldProjects }} <span class="text-[11px] text-slate-400 font-normal">({{ $totalProjects > 0 ? round(($onHoldProjects/$totalProjects)*100) : 0 }}%)</span></span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                            <div class="bg-amber-500 h-full rounded-full transition-all duration-500" style="width: {{ $totalProjects > 0 ? round(($onHoldProjects/$totalProjects)*100) : 0 }}%"></div>
                        </div>
                    </div>

                    <!-- Cancelled -->
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1 font-semibold">
                            <span class="text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                <span>لغو / متوقف</span>
                            </span>
                            <span class="text-slate-700 dark:text-slate-300 font-bold">{{ $cancelledProjects }} <span class="text-[11px] text-slate-400 font-normal">({{ $totalProjects > 0 ? round(($cancelledProjects/$totalProjects)*100) : 0 }}%)</span></span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                            <div class="bg-slate-400 h-full rounded-full transition-all duration-500" style="width: {{ $totalProjects > 0 ? round(($cancelledProjects/$totalProjects)*100) : 0 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                <span>نرخ موفقیت تحقق اهداف:</span>
                <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $totalProjects > 0 ? round(($completedProjects / $totalProjects) * 100) : 0 }}٪</span>
            </div>
        </div>

        <!-- Panel 2: Priority Spectrum Breakdown -->
        <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        <span>تفکیک بر اساس اولویت</span>
                    </h3>
                    <span class="text-xs text-slate-400 font-medium">اهمیت پروژه‌ها</span>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-1">
                    <!-- Force Majeure -->
                    <div class="p-3.5 rounded-2xl bg-rose-50/80 dark:bg-rose-950/40 border border-rose-200/80 dark:border-rose-900/60">
                        <div class="flex items-center gap-2 text-rose-700 dark:text-rose-300 text-xs font-bold">
                            <span class="w-2 h-2 rounded-full bg-rose-600 animate-pulse"></span>
                            <span>فورس‌ماژور</span>
                        </div>
                        <div class="text-2xl font-black text-rose-700 dark:text-rose-300 mt-2">{{ $forceMajeureProjects }}</div>
                        <div class="text-[11px] text-rose-600/80 dark:text-rose-400 mt-0.5">اقدام بلافاصله</div>
                    </div>

                    <!-- High -->
                    <div class="p-3.5 rounded-2xl bg-amber-50/80 dark:bg-amber-950/40 border border-amber-200/80 dark:border-amber-900/60">
                        <div class="flex items-center gap-2 text-amber-700 dark:text-amber-300 text-xs font-bold">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            <span>اولویت بالا</span>
                        </div>
                        <div class="text-2xl font-black text-amber-700 dark:text-amber-300 mt-2">{{ $highPriorityProjects }}</div>
                        <div class="text-[11px] text-amber-600/80 dark:text-amber-400 mt-0.5">درجه اهمیت ویژه</div>
                    </div>

                    <!-- Medium -->
                    <div class="p-3.5 rounded-2xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200/80 dark:border-blue-900/60">
                        <div class="flex items-center gap-2 text-blue-700 dark:text-blue-300 text-xs font-bold">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            <span>اولویت متوسط</span>
                        </div>
                        <div class="text-2xl font-black text-blue-700 dark:text-blue-300 mt-2">{{ $mediumPriorityProjects }}</div>
                        <div class="text-[11px] text-blue-600/80 dark:text-blue-400 mt-0.5">روند نرمال</div>
                    </div>

                    <!-- Low -->
                    <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700">
                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400 text-xs font-bold">
                            <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                            <span>اولویت پایین</span>
                        </div>
                        <div class="text-2xl font-black text-slate-800 dark:text-slate-200 mt-2">{{ $lowPriorityProjects }}</div>
                        <div class="text-[11px] text-slate-400 mt-0.5">اولویت منعطف</div>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                <span>سهم پروژه‌های حساس:</span>
                <span class="font-bold text-amber-600 dark:text-amber-400">{{ $totalProjects > 0 ? round((($forceMajeureProjects + $highPriorityProjects) / $totalProjects) * 100) : 0 }}٪</span>
            </div>
        </div>

        <!-- Panel 3: Department / Organization Distribution -->
        <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <span>توزیع در واحدهای سازمانی</span>
                    </h3>
                    <span class="text-xs text-slate-400 font-medium">{{ count($orgDistribution) }} واحد فعال</span>
                </div>

                <div class="space-y-3 pt-1 max-h-[160px] overflow-y-auto pr-1">
                    @forelse($orgDistribution as $org)
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1 font-semibold">
                                <span class="text-slate-700 dark:text-slate-300 truncate max-w-[180px]">{{ $org['name'] }}</span>
                                <span class="text-slate-600 dark:text-slate-400 font-bold">{{ $org['count'] }} <span class="text-[11px] text-slate-400 font-normal">({{ $org['percentage'] }}%)</span></span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-indigo-500 h-full rounded-full transition-all duration-500" style="width: {{ $org['percentage'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-xs text-slate-400">
                            پروژه‌ها به واحد سازمانی خاصی متصل نیستند یا توزیعی ثبت نشده است.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                <span>پروژه‌های دارای واحد سازمانی:</span>
                <span class="font-bold text-slate-700 dark:text-slate-300">{{ array_sum(array_column($orgDistribution, 'count')) }} پروژه</span>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 4. Advanced Interactive Search & Filter Bar-->
    <!-- ========================================== -->
    <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm space-y-4">
        <form method="GET" action="{{ route('reports.projects') }}" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Search Input -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <span>جستجوی متنی</span>
                    </label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ $search }}" placeholder="جستجو در عنوان یا شناسه پروژه..." class="w-full text-xs sm:text-sm rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 pr-10 py-2.5">
                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                    </div>
                </div>

                <!-- Status Selector -->
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>وضعیت پروژه</span>
                    </label>
                    <select name="status" class="w-full text-xs sm:text-sm rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                        <option value="">همه وضعیت‌ها</option>
                        @foreach(\App\Enums\ProjectStatus::cases() as $st)
                            <option value="{{ $st->value }}" {{ $status === $st->value ? 'selected' : '' }}>{{ $st->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Priority Selector -->
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>سطح اولویت</span>
                    </label>
                    <select name="priority" class="w-full text-xs sm:text-sm rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                        <option value="">همه اولویت‌ها</option>
                        @foreach(\App\Enums\ProjectPriority::cases() as $pr)
                            <option value="{{ $pr->value }}" {{ $priority === $pr->value ? 'selected' : '' }}>{{ $pr->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Organization Node Selector -->
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0h4m-4 0H9m4 0V7m0 0h4m-4 0H9"/></svg>
                        <span>واحد سازمانی</span>
                    </label>
                    <select name="organization_node_id" class="w-full text-xs sm:text-sm rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                        <option value="">همه واحدها</option>
                        @foreach($organizationNodes as $node)
                            <option value="{{ $node->id }}" {{ (string)$organizationNodeId === (string)$node->id ? 'selected' : '' }}>{{ $node->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Quick Filter Presets & Actions Row -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2 border-t border-slate-100 dark:border-slate-700/60">
                <!-- Preset Badges -->
                <div class="flex items-center gap-2 flex-wrap text-xs">
                    <span class="text-slate-400 font-semibold">فیلترهای سریع:</span>
                    <a href="{{ route('reports.projects', ['health' => 'overdue']) }}" class="px-3 py-1.5 rounded-xl {{ $health === 'overdue' ? 'bg-rose-600 text-white font-bold' : 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 hover:bg-rose-100' }} transition-all flex items-center gap-1">
                        <span>🚨 دارای تاخیر</span>
                        <span class="px-1.5 py-0.2 rounded-full bg-rose-200/60 dark:bg-rose-800 text-[10px]">{{ $overdueProjects }}</span>
                    </a>

                    <a href="{{ route('reports.projects', ['priority' => 'FORCE_MAJEURE']) }}" class="px-3 py-1.5 rounded-xl {{ $priority === 'FORCE_MAJEURE' ? 'bg-amber-600 text-white font-bold' : 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 hover:bg-amber-100' }} transition-all flex items-center gap-1">
                        <span>⚡ فورس ماژور</span>
                        <span class="px-1.5 py-0.2 rounded-full bg-amber-200/60 dark:bg-amber-800 text-[10px]">{{ $forceMajeureProjects }}</span>
                    </a>

                    <a href="{{ route('reports.projects', ['status' => 'ACTIVE']) }}" class="px-3 py-1.5 rounded-xl {{ $status === 'ACTIVE' ? 'bg-emerald-600 text-white font-bold' : 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100' }} transition-all flex items-center gap-1">
                        <span>🟢 پروژه‌های فعال</span>
                    </a>
                </div>

                <!-- Submit / Clear Buttons -->
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button type="submit" class="w-full sm:w-auto px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs sm:text-sm rounded-xl shadow-sm transition-all hover:shadow-indigo-500/25 cursor-pointer">
                        اعمال فیلترها
                    </button>
                    @if($search || $status || $priority || $organizationNodeId || $health)
                        <a href="{{ route('reports.projects') }}" class="px-4 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs sm:text-sm font-semibold rounded-xl transition-all">
                            پاکسازی فیلترها
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- ========================================== -->
    <!-- 5. Projects Listing Header & View Controls -->
    <!-- ========================================== -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-black text-slate-900 dark:text-white flex items-center gap-2">
                <span>فهرست پروژه‌ها</span>
                <span class="text-xs font-bold px-2.5 py-1 rounded-xl bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                    {{ $projects->total() }} پروژه
                </span>
            </h2>
        </div>

        <!-- View Mode Switcher (Table vs Grid) -->
        <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-2xl border border-slate-200 dark:border-slate-700">
            <button @click="setViewMode('table')" :class="viewMode === 'table' ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 font-bold shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'" class="px-3.5 py-2 rounded-xl text-xs flex items-center gap-1.5 transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                <span>نمای جدولی</span>
            </button>

            <button @click="setViewMode('grid')" :class="viewMode === 'grid' ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 font-bold shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'" class="px-3.5 py-2 rounded-xl text-xs flex items-center gap-1.5 transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                <span>نمای کارت‌ها</span>
            </button>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 6. Projects View: Table Mode               -->
    <!-- ========================================== -->
    <div x-show="viewMode === 'table'" class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table id="projectsReportTable" class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wider">
                        <th class="py-4 px-6">عنوان و شناسه پروژه</th>
                        <th class="py-4 px-6">واحد سازمانی</th>
                        <th class="py-4 px-6">اعضای تیم</th>
                        <th class="py-4 px-6 text-center">اولویت</th>
                        <th class="py-4 px-6 text-center">وضعیت</th>
                        <th class="py-4 px-6 text-center min-w-[180px]">پیشرفت کارها</th>
                        <th class="py-4 px-6">مهلت تحویل</th>
                        <th class="py-4 px-6 text-left">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/70 dark:divide-slate-700/70 text-sm">
                    @forelse($projects as $p)
                        @php
                            $progress = $p->tasks_progress;
                            $deadlineAlert = $p->deadline_alert;
                            $isOverdue = $p->deadline && $p->deadline->isPast() && !in_array($p->status, [\App\Enums\ProjectStatus::COMPLETED, \App\Enums\ProjectStatus::ARCHIVED]);

                            // Prepare members list for display & CSV
                            $pms = $p->projectManagers->map(fn($m) => $m->user?->full_name)->filter()->values();
                            $allMembers = $p->members->map(fn($m) => $m->user?->full_name)->filter()->unique()->values();
                            if ($p->creator && !$allMembers->contains($p->creator->full_name)) {
                                $allMembers->prepend($p->creator->full_name . ' (ایجادکننده)');
                            }
                            $membersString = $allMembers->isNotEmpty() ? $allMembers->join('، ') : ($pms->isNotEmpty() ? $pms->join('، ') : 'بدون عضو');
                        @endphp
                        <tr class="hover:bg-slate-50/90 dark:hover:bg-slate-700/40 transition-colors {{ $isOverdue ? 'bg-rose-50/20 dark:bg-rose-950/10' : '' }}"
                            data-project="true"
                            data-key="{{ $p->project_key }}"
                            data-title="{{ $p->title }}"
                            data-org="{{ $p->organizationNode?->name ?? 'کل سازمان' }}"
                            data-members="{{ $membersString }}"
                            data-priority="{{ $p->priority?->label() ?? 'تعیین‌نشده' }}"
                            data-status="{{ $p->status?->label() ?? 'نامشخص' }}"
                            data-progress="{{ $progress }}"
                            data-tasks="{{ $p->tasks_count }}"
                            data-deadline="{{ $p->deadline ? verta($p->deadline)->format('Y/m/d') : 'تعیین‌نشده' }}">

                            <!-- Project Title & Key -->
                            <td class="py-4 px-6">
                                <div class="flex items-start gap-3">
                                    <div class="w-2.5 h-2.5 rounded-full mt-1.5 shrink-0 {{ $isOverdue ? 'bg-rose-500 animate-pulse' : ($progress >= 100 ? 'bg-indigo-500' : 'bg-emerald-500') }}"></div>
                                    <div>
                                        <div class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                            <a href="{{ route('projects.show', $p) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                                {{ $p->title }}
                                            </a>
                                            @if($p->parent)
                                                <span class="text-[11px] px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-medium">
                                                    زیرپروژه {{ $p->parent->title }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-slate-400 font-mono mt-0.5">
                                            <span>#{{ $p->project_key }}</span>
                                            @if($p->children_count > 0)
                                                <span class="text-[11px] text-indigo-500 font-sans font-semibold">({{ $p->children_count }} زیرپروژه)</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Org Node -->
                            <td class="py-4 px-6 text-slate-600 dark:text-slate-300 text-xs">
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0h4m-4 0H9m4 0V7m0 0h4m-4 0H9"/></svg>
                                    <span>{{ $p->organizationNode?->name ?? 'کل سازمان' }}</span>
                                </div>
                            </td>

                            <!-- Members & Team Avatars -->
                            <td class="py-4 px-6 text-xs">
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center -space-x-2 space-x-reverse">
                                        @php
                                            $shownMembers = $p->members->take(3);
                                        @endphp
                                        @forelse($shownMembers as $mem)
                                            <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-indigo-600 to-violet-600 text-white flex items-center justify-center font-bold text-[10px] border-2 border-white dark:border-slate-800 shadow-xs" title="{{ $mem->user?->full_name }} ({{ $mem->project_role ?? 'عضو' }})">
                                                {{ mb_substr($mem->user?->first_name ?? ($mem->user?->full_name ?? 'ع'), 0, 1) }}
                                            </div>
                                        @empty
                                            @if($p->creator)
                                                <div class="w-7 h-7 rounded-full bg-slate-600 text-white flex items-center justify-center font-bold text-[10px] border-2 border-white dark:border-slate-800 shadow-xs" title="{{ $p->creator->full_name }} (ایجادکننده)">
                                                    {{ mb_substr($p->creator->first_name ?? 'م', 0, 1) }}
                                                </div>
                                            @else
                                                <span class="text-slate-400 text-xs">بدون عضو</span>
                                            @endif
                                        @endforelse

                                        @if($p->members_count > 3)
                                            <div class="w-7 h-7 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 flex items-center justify-center font-bold text-[10px] border-2 border-white dark:border-slate-800 shadow-xs" title="{{ $p->members_count - 3 }} عضو دیگر">
                                                +{{ $p->members_count - 3 }}
                                            </div>
                                        @endif
                                    </div>

                                    @if($p->members_count > 0)
                                        <span class="text-[11px] text-slate-500 dark:text-slate-400 font-semibold">
                                            ({{ $p->members_count }} نفر)
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Priority -->
                            <td class="py-4 px-6 text-center">
                                @if($p->priority)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-bold {{ $p->priority->badgeClass() }}">
                                        {{ $p->priority->label() }}
                                    </span>
                                @else
                                    <span class="text-slate-400 text-xs">—</span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-bold
                                    @if($p->status === \App\Enums\ProjectStatus::ACTIVE) bg-emerald-50 text-emerald-700 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800
                                    @elseif($p->status === \App\Enums\ProjectStatus::COMPLETED) bg-indigo-50 text-indigo-700 dark:bg-indigo-950/80 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800
                                    @elseif($p->status === \App\Enums\ProjectStatus::ON_HOLD) bg-amber-50 text-amber-700 dark:bg-amber-950/80 dark:text-amber-300 border border-amber-200 dark:border-amber-800
                                    @else bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300 @endif">
                                    {{ $p->status?->label() ?? 'نامشخص' }}
                                </span>
                            </td>

                            <!-- Progress Bar -->
                            <td class="py-4 px-6">
                                <div class="max-w-[220px] mx-auto space-y-1.5">
                                    <div class="flex items-center justify-between text-xs font-bold">
                                        <span class="text-indigo-600 dark:text-indigo-400">{{ $progress }}٪</span>
                                        <span class="text-slate-400 font-normal text-[11px]">({{ $p->tasks_count }} کار)</span>
                                    </div>
                                    <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-2 overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500 {{ $progress >= 100 ? 'bg-indigo-600' : ($progress > 50 ? 'bg-emerald-500' : 'bg-blue-500') }}" style="width: {{ $progress }}%"></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Deadline & Health -->
                            <td class="py-4 px-6 text-xs">
                                @if($p->deadline)
                                    <div class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1">
                                        <span>📅</span>
                                        <span>{{ verta($p->deadline)->format('Y/m/d') }}</span>
                                    </div>
                                    @if($deadlineAlert)
                                        <div class="text-[11px] font-bold mt-1 inline-flex items-center gap-1 px-2 py-0.5 rounded-lg {{ $deadlineAlert['class'] }}">
                                            {{ $deadlineAlert['label'] }}
                                        </div>
                                    @endif
                                @else
                                    <span class="text-slate-400 text-xs">تعیین نشده</span>
                                @endif
                            </td>

                            <!-- Quick Actions -->
                            <td class="py-4 px-6 text-left whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('projects.gantt', $p) }}" class="p-2 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-indigo-50 hover:text-indigo-600 dark:hover:bg-indigo-950 transition-all" title="گانت‌چارت پروژه">
                                        📈
                                    </a>
                                    <a href="{{ route('projects.show', $p) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-all shadow-xs">
                                        <span>مشاهده</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-16 text-center">
                                <div class="max-w-md mx-auto space-y-3">
                                    <div class="w-16 h-16 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-500 flex items-center justify-center text-3xl mx-auto">
                                        📁
                                    </div>
                                    <h3 class="text-base font-bold text-slate-900 dark:text-white">پروژه‌ای با این مشخصات پیدا نشد</h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">می‌توانید با حذف فیلترها، همه پروژه‌ها را مشاهده کنید.</p>
                                    @if($search || $status || $priority || $organizationNodeId || $health)
                                        <div class="pt-2">
                                            <a href="{{ route('reports.projects') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700 transition-all">
                                                پاکسازی فیلترها
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($projects->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/30">
                {{ $projects->links() }}
            </div>
        @endif
    </div>

    <!-- ========================================== -->
    <!-- 7. Projects View: Grid Cards Mode          -->
    <!-- ========================================== -->
    <div x-show="viewMode === 'grid'" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @forelse($projects as $p)
                @php
                    $progress = $p->tasks_progress;
                    $deadlineAlert = $p->deadline_alert;
                    $isOverdue = $p->deadline && $p->deadline->isPast() && !in_array($p->status, [\App\Enums\ProjectStatus::COMPLETED, \App\Enums\ProjectStatus::ARCHIVED]);
                @endphp
                <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-lg transition-all flex flex-col justify-between overflow-hidden relative group">
                    <!-- Top Health Indicator Strip -->
                    <div class="h-1.5 w-full {{ $isOverdue ? 'bg-rose-500' : ($progress >= 100 ? 'bg-indigo-600' : ($progress > 50 ? 'bg-emerald-500' : 'bg-blue-500')) }}"></div>

                    <div class="p-6 space-y-4">
                        <!-- Top Row: Key & Priority & Status -->
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-mono font-bold px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                                #{{ $p->project_key }}
                            </span>

                            <div class="flex items-center gap-1.5">
                                @if($p->priority)
                                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold {{ $p->priority->badgeClass() }}">
                                        {{ $p->priority->label() }}
                                    </span>
                                @endif

                                <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold
                                    @if($p->status === \App\Enums\ProjectStatus::ACTIVE) bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300
                                    @elseif($p->status === \App\Enums\ProjectStatus::COMPLETED) bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300
                                    @elseif($p->status === \App\Enums\ProjectStatus::ON_HOLD) bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300
                                    @else bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300 @endif">
                                    {{ $p->status?->label() ?? 'نامشخص' }}
                                </span>
                            </div>
                        </div>

                        <!-- Project Title & Subproject info -->
                        <div>
                            <h3 class="font-bold text-base text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                <a href="{{ route('projects.show', $p) }}">
                                    {{ $p->title }}
                                </a>
                            </h3>
                            @if($p->parent)
                                <p class="text-xs text-slate-400 mt-1 flex items-center gap-1">
                                    <span>زیرمجموعه:</span>
                                    <span class="font-semibold text-slate-600 dark:text-slate-300">{{ $p->parent->title }}</span>
                                </p>
                            @endif
                        </div>

                        <!-- Progress Bar & Tasks Metric -->
                        <div class="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 space-y-2">
                            <div class="flex items-center justify-between text-xs font-bold">
                                <span class="text-slate-600 dark:text-slate-300">پیشرفت کارها</span>
                                <span class="text-indigo-600 dark:text-indigo-400 font-black text-sm">{{ $progress }}٪</span>
                            </div>
                            <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500 {{ $progress >= 100 ? 'bg-indigo-600' : ($progress > 50 ? 'bg-emerald-500' : 'bg-blue-500') }}" style="width: {{ $progress }}%"></div>
                            </div>
                            <div class="flex items-center justify-between text-[11px] text-slate-400 pt-1">
                                <span>{{ $p->tasks_count }} کار ثبت‌شده</span>
                                <span>{{ $p->children_count }} زیرپروژه</span>
                            </div>
                        </div>

                        <!-- Organization & Deadline Alerts -->
                        <div class="space-y-2 text-xs">
                            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0h4m-4 0H9m4 0V7m0 0h4m-4 0H9"/></svg>
                                    <span>واحد:</span>
                                </span>
                                <span class="font-bold text-slate-700 dark:text-slate-300">{{ $p->organizationNode?->name ?? 'کل سازمان' }}</span>
                            </div>

                            <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                                <span>مهلت تحویل:</span>
                                <span class="font-bold {{ $isOverdue ? 'text-rose-600 dark:text-rose-400' : 'text-slate-700 dark:text-slate-300' }}">
                                    {{ $p->deadline ? verta($p->deadline)->format('Y/m/d') : 'تعیین‌نشده' }}
                                </span>
                            </div>

                            @if($deadlineAlert)
                                <div class="text-[11px] font-bold p-2 rounded-xl {{ $deadlineAlert['class'] }} text-center">
                                    {{ $deadlineAlert['label'] }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Footer Action Buttons -->
                    <div class="p-4 bg-slate-50 dark:bg-slate-900/40 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                        <!-- Member Avatars -->
                        <div class="flex items-center -space-x-2 space-x-reverse">
                            @forelse($p->members->take(3) as $mem)
                                <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-indigo-600 to-violet-600 text-white flex items-center justify-center font-bold text-[10px] border-2 border-white dark:border-slate-800 shadow-xs" title="{{ $mem->user?->full_name }}">
                                    {{ mb_substr($mem->user?->first_name ?? 'ع', 0, 1) }}
                                </div>
                            @empty
                                @if($p->creator)
                                    <div class="w-7 h-7 rounded-full bg-slate-600 text-white flex items-center justify-center font-bold text-[10px] border-2 border-white dark:border-slate-800 shadow-xs" title="{{ $p->creator->full_name }}">
                                        {{ mb_substr($p->creator->first_name ?? 'م', 0, 1) }}
                                    </div>
                                @else
                                    <span class="text-[11px] text-slate-400">بدون عضو</span>
                                @endif
                            @endforelse

                            @if($p->members_count > 3)
                                <div class="w-7 h-7 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 flex items-center justify-center font-bold text-[10px] border-2 border-white dark:border-slate-800 shadow-xs" title="{{ $p->members_count - 3 }} عضو دیگر">
                                    +{{ $p->members_count - 3 }}
                                </div>
                            @endif
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2">
                            <a href="{{ route('projects.gantt', $p) }}" class="p-2 rounded-xl bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-indigo-600 border border-slate-200 dark:border-slate-700 text-xs font-bold transition-all shadow-xs" title="گانت‌چارت">
                                📈
                            </a>
                            <a href="{{ route('projects.show', $p) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-all shadow-xs">
                                <span>جزئیات</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-16 text-center bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700">
                    <div class="max-w-md mx-auto space-y-3">
                        <div class="w-16 h-16 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-500 flex items-center justify-center text-3xl mx-auto">
                            📁
                        </div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">پروژه‌ای با این مشخصات پیدا نشد</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">می‌توانید با حذف فیلترها، همه پروژه‌ها را مشاهده کنید.</p>
                    </div>
                </div>
            @endforelse
        </div>

        @if($projects->hasPages())
            <div class="p-4 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700">
                {{ $projects->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
