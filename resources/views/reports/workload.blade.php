@extends('layouts.app')

@section('title', 'گزارش حجم کاری و ظرفیت همکاران')

@section('content')
<div class="space-y-6 pb-12" x-data="{
    viewMode: localStorage.getItem('reports_workload_view') || 'table',
    searchQuery: '',
    statusFilter: 'ALL',
    nodeFilter: 'ALL',
    setViewMode(mode) {
        this.viewMode = mode;
        localStorage.setItem('reports_workload_view', mode);
    },
    matchesFilter(name, dept, status, hasOverdue) {
        if (this.searchQuery && !name.toLowerCase().includes(this.searchQuery.toLowerCase()) && !dept.toLowerCase().includes(this.searchQuery.toLowerCase())) {
            return false;
        }
        if (this.statusFilter === 'overdue' && !hasOverdue) {
            return false;
        }
        if (this.statusFilter !== 'ALL' && this.statusFilter !== 'overdue' && status !== this.statusFilter) {
            return false;
        }
        if (this.nodeFilter !== 'ALL' && dept !== this.nodeFilter) {
            return false;
        }
        return true;
    }
}">

<script>
function exportWorkloadCSV() {
    var rows = [
        ['نام همکار', 'واحد سازمانی', 'وضعیت کاری', 'کارهای فعال', 'وزن فعال', 'در حال انجام', 'انجام شده', 'دارای تاخیر']
    ];
    document.querySelectorAll('#workloadReportTable tbody tr[data-member]').forEach(function(tr) {
        if (tr.style.display !== 'none') {
            rows.push([
                tr.getAttribute('data-name') || '',
                tr.getAttribute('data-org') || '',
                tr.getAttribute('data-status') || '',
                tr.getAttribute('data-active') || '0',
                tr.getAttribute('data-active-weight') || '0',
                tr.getAttribute('data-inprogress') || '0',
                tr.getAttribute('data-completed') || '0',
                tr.getAttribute('data-overdue') || '0'
            ]);
        }
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
    link.download = 'team_workload_' + new Date().toISOString().slice(0, 10) + '.csv';
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
        <div class="absolute -right-16 -top-16 w-80 h-80 bg-emerald-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute left-1/3 -bottom-16 w-72 h-72 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="space-y-3">
                <div class="flex items-center gap-2 text-indigo-300 text-xs font-semibold">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>پایش و توزیع کار اعضای تیم</span>
                    <span class="text-slate-500">•</span>
                    <span>{{ verta()->format('l، d F Y') }}</span>
                </div>

                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black tracking-tight text-white flex items-center gap-3">
                    <span class="bg-emerald-600/40 p-2 rounded-2xl border border-emerald-400/30">👥</span>
                    <span>حجم کاری و ظرفیت همکاران</span>
                </h1>

                <p class="text-slate-300 text-xs sm:text-sm max-w-2xl leading-relaxed">
                    بررسی نحوه توزیع کارها بین اعضای تیم، سنجش تعادل کاری و شناسایی همکاران دارای ظرفیت یا کار فشرده.
                </p>

                <!-- Navigation Tabs (Projects vs Workload) -->
                <div class="flex flex-wrap items-center gap-2 pt-2">
                    <a href="{{ route('reports.projects') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold bg-white/10 hover:bg-white/20 text-slate-200 backdrop-blur-md border border-white/10 transition-all">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <span>پروژه‌ها</span>
                    </a>

                    <a href="{{ route('reports.workload') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-white text-slate-900 shadow-md shadow-black/10 border border-white transition-all">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span>حجم کاری همکاران</span>
                        <span class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 text-[11px] font-bold">{{ $totalMembers }} عضو</span>
                    </a>
                </div>
            </div>

            <!-- Header Quick Actions -->
            <div class="flex items-center gap-2.5 shrink-0 flex-wrap sm:flex-nowrap">
                <button onclick="exportWorkloadCSV()" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-semibold border border-white/20 backdrop-blur-md transition-all shadow-sm cursor-pointer">
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
    <!-- 2. Team Capacity & Workload KPI Cards      -->
    <!-- ========================================== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        <!-- 1. Total Members -->
        <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">کل اعضای تیم</span>
                <span class="p-2 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </span>
            </div>
            <div class="mt-3 flex items-baseline justify-between">
                <div class="text-2xl font-black text-slate-900 dark:text-white">{{ number_format($totalMembers) }}</div>
                <div class="text-[11px] text-slate-400 font-medium">نیروی انسانی فعال</div>
            </div>
            <div class="mt-3 w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                <div class="bg-blue-600 h-full rounded-full" style="width: 100%"></div>
            </div>
        </div>

        <!-- 2. Overloaded Members -->
        <div @click="statusFilter = statusFilter === 'overloaded' ? 'ALL' : 'overloaded'" class="bg-white dark:bg-slate-800 p-5 rounded-2xl border {{ $overloadedCount > 0 ? 'border-amber-300 dark:border-amber-700/80 bg-amber-50/20' : 'border-slate-200/80 dark:border-slate-700/80' }} shadow-xs cursor-pointer hover:shadow-md transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-amber-700 dark:text-amber-300 flex items-center gap-1.5">
                    @if($overloadedCount > 0)
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    @endif
                    <span>کار فشرده / پرمشغله</span>
                </span>
                <span class="p-2 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400">
                    ⚠️
                </span>
            </div>
            <div class="mt-3 flex items-baseline justify-between">
                <div class="text-2xl font-black text-amber-600 dark:text-amber-400">{{ number_format($overloadedCount) }}</div>
                <div class="text-[11px] font-bold text-amber-600 dark:text-amber-400">
                    {{ $totalMembers > 0 ? round(($overloadedCount / $totalMembers) * 100) : 0 }}% کل تیم
                </div>
            </div>
            <div class="mt-3 w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                <div class="bg-amber-500 h-full rounded-full transition-all duration-500" style="width: {{ $totalMembers > 0 ? round(($overloadedCount / $totalMembers) * 100) : 0 }}%"></div>
            </div>
        </div>

        <!-- 3. Balanced Members -->
        <div @click="statusFilter = statusFilter === 'balanced' ? 'ALL' : 'balanced'" class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 shadow-xs cursor-pointer hover:shadow-md hover:border-emerald-300 dark:hover:border-emerald-700 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-emerald-700 dark:text-emerald-300">وضعیت متعادل</span>
                <span class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                    🟢
                </span>
            </div>
            <div class="mt-3 flex items-baseline justify-between">
                <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ number_format($balancedCount) }}</div>
                <div class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                    {{ $totalMembers > 0 ? round(($balancedCount / $totalMembers) * 100) : 0 }}% تیم
                </div>
            </div>
            <div class="mt-3 w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" style="width: {{ $totalMembers > 0 ? round(($balancedCount / $totalMembers) * 100) : 0 }}%"></div>
            </div>
        </div>

        <!-- 4. Underutilized / Available Capacity -->
        <div @click="statusFilter = statusFilter === 'underutilized' ? 'ALL' : 'underutilized'" class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 shadow-xs cursor-pointer hover:shadow-md hover:border-blue-300 dark:hover:border-blue-700 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-blue-700 dark:text-blue-300">دارای ظرفیت خالی</span>
                <span class="p-2 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400">
                    🔹
                </span>
            </div>
            <div class="mt-3 flex items-baseline justify-between">
                <div class="text-2xl font-black text-blue-600 dark:text-blue-400">{{ number_format($underutilizedCount) }}</div>
                <div class="text-[11px] font-bold text-blue-600 dark:text-blue-400">
                    {{ $totalMembers > 0 ? round(($underutilizedCount / $totalMembers) * 100) : 0 }}% آماده
                </div>
            </div>
            <div class="mt-3 w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                <div class="bg-blue-500 h-full rounded-full transition-all duration-500" style="width: {{ $totalMembers > 0 ? round(($underutilizedCount / $totalMembers) * 100) : 0 }}%"></div>
            </div>
        </div>

        <!-- 5. Total Active Tasks Distributed -->
        <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">کارهای فعال تیم</span>
                <span class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </span>
            </div>
            <div class="mt-3 flex items-baseline justify-between">
                <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400">{{ number_format($totalActiveTasks) }}</div>
                <div class="text-[11px] text-amber-600 dark:text-amber-400 font-bold font-mono">وزن فعال: {{ number_format($totalActiveWeight ?? 0) }}</div>
            </div>
            <div class="mt-3 w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                <div class="bg-indigo-600 h-full rounded-full" style="width: 100%"></div>
            </div>
        </div>

        <!-- 6. Total Overdue Tasks across team -->
        <div @click="statusFilter = statusFilter === 'overdue' ? 'ALL' : 'overdue'" class="bg-white dark:bg-slate-800 p-5 rounded-2xl border {{ $totalOverdueTasks > 0 ? 'border-rose-300 dark:border-rose-800/80 bg-rose-50/20' : 'border-slate-200/80 dark:border-slate-700/80' }} shadow-xs cursor-pointer hover:shadow-md transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-rose-600 dark:text-rose-400 flex items-center gap-1.5">
                    @if($totalOverdueTasks > 0)
                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                    @endif
                    <span>کارهای معوقه تیم</span>
                </span>
                <span class="p-2 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400">
                    🚨
                </span>
            </div>
            <div class="mt-3 flex items-baseline justify-between">
                <div class="text-2xl font-black text-rose-600 dark:text-rose-400">{{ number_format($totalOverdueTasks) }}</div>
                <div class="text-[11px] font-bold text-rose-500">
                    {{ $totalOverdueTasks > 0 ? 'نیازمند بازتوزیع' : 'بدون تاخیر' }}
                </div>
            </div>
            <div class="mt-3 w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                <div class="bg-rose-500 h-full rounded-full transition-all duration-500" style="width: {{ $totalActiveTasks > 0 ? min(100, round(($totalOverdueTasks / $totalActiveTasks) * 100)) : 0 }}%"></div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 3. Visual Capacity Distribution Spectrum   -->
    <!-- ========================================== -->
    <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <span>نمودار تعادل حجم کاری تیم</span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">درصد همکاران با مشغله فشرده، متعادل یا دارای ظرفیت کاری</p>
            </div>

            <!-- Breakdown Legend -->
            <div class="flex items-center gap-4 text-xs font-semibold">
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-md bg-amber-500"></span>
                    <span class="text-slate-700 dark:text-slate-300">فشرده: {{ $overloadedCount }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-md bg-emerald-500"></span>
                    <span class="text-slate-700 dark:text-slate-300">متعادل: {{ $balancedCount }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-md bg-blue-500"></span>
                    <span class="text-slate-700 dark:text-slate-300">دارای ظرفیت: {{ $underutilizedCount }}</span>
                </div>
            </div>
        </div>

        <!-- Visual Multi-segment Progress Bar -->
        <div class="w-full h-4 bg-slate-100 dark:bg-slate-700 rounded-2xl overflow-hidden flex gap-0.5 p-0.5">
            @php
                $overPct = $totalMembers > 0 ? ($overloadedCount / $totalMembers) * 100 : 0;
                $balPct = $totalMembers > 0 ? ($balancedCount / $totalMembers) * 100 : 0;
                $undPct = $totalMembers > 0 ? ($underutilizedCount / $totalMembers) * 100 : 0;
            @endphp
            @if($overPct > 0)
                <div class="bg-amber-500 h-full rounded-l-xl transition-all duration-500" style="width: {{ $overPct }}%" title="پربار: {{ round($overPct) }}%"></div>
            @endif
            @if($balPct > 0)
                <div class="bg-emerald-500 h-full transition-all duration-500" style="width: {{ $balPct }}%" title="متعادل: {{ round($balPct) }}%"></div>
            @endif
            @if($undPct > 0)
                <div class="bg-blue-500 h-full rounded-r-xl transition-all duration-500" style="width: {{ $undPct }}%" title="ظرفیت خالی: {{ round($undPct) }}%"></div>
            @endif
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 4. Interactive Search & Quick Filters      -->
    <!-- ========================================== -->
    <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Search Member -->
            <div class="lg:col-span-2">
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <span>جستجوی نام یا واحد همکار</span>
                </label>
                <div class="relative">
                    <input type="text" x-model="searchQuery" placeholder="نام، نام خانوادگی یا واحد سازمانی..." class="w-full text-xs sm:text-sm rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 pr-10 py-2.5">
                    <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>
            </div>

            <!-- Workload Status Filter -->
            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>وضعیت بار کاری</span>
                </label>
                <select x-model="statusFilter" class="w-full text-xs sm:text-sm rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                    <option value="ALL">همه وضعیت‌ها</option>
                    <option value="overloaded">⚠️ کار فشرده / پرمشغله</option>
                    <option value="balanced">🟢 در وضعیت متعادل</option>
                    <option value="underutilized">🔹 ظرفیت خالی</option>
                    <option value="overdue">🚨 دارای کار معوقه</option>
                </select>
            </div>

            <!-- Organization Filter -->
            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0h4m-4 0H9m4 0V7m0 0h4m-4 0H9"/></svg>
                    <span>واحد سازمانی</span>
                </label>
                <select x-model="nodeFilter" class="w-full text-xs sm:text-sm rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                    <option value="ALL">همه واحدها</option>
                    @foreach($organizationNodes as $node)
                        <option value="{{ $node->name }}">{{ $node->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Quick Status Filter Pills -->
        <div class="flex items-center justify-between gap-4 pt-2 border-t border-slate-100 dark:border-slate-700/60 flex-wrap">
            <div class="flex items-center gap-2 flex-wrap text-xs">
                <span class="text-slate-400 font-semibold">فیلتر سریع:</span>
                <button @click="statusFilter = 'ALL'; nodeFilter = 'ALL'; searchQuery = ''" :class="statusFilter === 'ALL' && nodeFilter === 'ALL' && !searchQuery ? 'bg-indigo-600 text-white font-bold' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300'" class="px-3 py-1.5 rounded-xl transition-all cursor-pointer">
                    همه اعضا ({{ $totalMembers }})
                </button>
                <button @click="statusFilter = 'overloaded'" :class="statusFilter === 'overloaded' ? 'bg-amber-600 text-white font-bold' : 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300'" class="px-3 py-1.5 rounded-xl transition-all cursor-pointer">
                    ⚠️ فشرده ({{ $overloadedCount }})
                </button>
                <button @click="statusFilter = 'balanced'" :class="statusFilter === 'balanced' ? 'bg-emerald-600 text-white font-bold' : 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300'" class="px-3 py-1.5 rounded-xl transition-all cursor-pointer">
                    🟢 متعادل ({{ $balancedCount }})
                </button>
                <button @click="statusFilter = 'underutilized'" :class="statusFilter === 'underutilized' ? 'bg-blue-600 text-white font-bold' : 'bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300'" class="px-3 py-1.5 rounded-xl transition-all cursor-pointer">
                    🔹 ظرفیت خالی ({{ $underutilizedCount }})
                </button>
                <button @click="statusFilter = 'overdue'" :class="statusFilter === 'overdue' ? 'bg-rose-600 text-white font-bold' : 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300'" class="px-3 py-1.5 rounded-xl transition-all cursor-pointer">
                    🚨 دارای تاخیر
                </button>
            </div>

            <!-- View Switcher -->
            <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-2xl border border-slate-200 dark:border-slate-700">
                <button @click="setViewMode('table')" :class="viewMode === 'table' ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 font-bold shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'" class="px-3 py-1.5 rounded-xl text-xs flex items-center gap-1.5 transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    <span>نمای جدولی</span>
                </button>

                <button @click="setViewMode('grid')" :class="viewMode === 'grid' ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 font-bold shadow-xs' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'" class="px-3.5 py-1.5 rounded-xl text-xs flex items-center gap-1.5 transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <span>نمای کارت‌ها</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 5. Workload Matrix: Table View             -->
    <!-- ========================================== -->
    <div x-show="viewMode === 'table'" class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table id="workloadReportTable" class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 text-xs font-bold uppercase tracking-wider">
                        <th class="py-4 px-6">نام همکار و سمت</th>
                        <th class="py-4 px-6 text-center">وضعیت کاری</th>
                        <th class="py-4 px-6 text-center min-w-[160px]">میزان مشغله (وزنی)</th>
                        <th class="py-4 px-6 text-center">کارهای فعال (وزن کل)</th>
                        <th class="py-4 px-6 text-center">در حال انجام</th>
                        <th class="py-4 px-6 text-center">انجام شده</th>
                        <th class="py-4 px-6 text-center">دارای تاخیر</th>
                        <th class="py-4 px-6 text-center">درصد انجام کارها</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/70 dark:divide-slate-700/70 text-sm">
                    @forelse($workloadData as $item)
                        @php
                            $member = $item['user'];
                            $loadStatus = $item['load_status'];
                            $orgName = $member->primaryRoleAssignment?->organizationNode?->name ?? 'بدون واحد';
                            $hasOverdue = $item['overdue_tasks'] > 0;
                            $completionRate = $item['total_tasks'] > 0 ? round(($item['completed_tasks'] / $item['total_tasks']) * 100) : 0;
                        @endphp
                        <tr x-show="matchesFilter('{{ addslashes($member->full_name) }}', '{{ addslashes($orgName) }}', '{{ $loadStatus }}', {{ $hasOverdue ? 'true' : 'false' }})"
                            class="hover:bg-slate-50/90 dark:hover:bg-slate-700/40 transition-colors {{ $hasOverdue ? 'bg-rose-50/15 dark:bg-rose-950/10' : '' }}"
                            data-member="true"
                            data-name="{{ $member->full_name }}"
                            data-org="{{ $orgName }}"
                            data-status="{{ $loadStatus === 'overloaded' ? 'پربار' : ($loadStatus === 'balanced' ? 'متعادل' : 'ظرفیت خالی') }}"
                            data-active="{{ $item['active_tasks'] }}"
                            data-active-weight="{{ $item['active_weight'] }}"
                            data-inprogress="{{ $item['in_progress_tasks'] }}"
                            data-completed="{{ $item['completed_tasks'] }}"
                            data-overdue="{{ $item['overdue_tasks'] }}">

                            <!-- User & Role -->
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl {{ $loadStatus === 'overloaded' ? 'bg-amber-600 text-white' : ($loadStatus === 'balanced' ? 'bg-emerald-600 text-white' : 'bg-blue-600 text-white') }} flex items-center justify-center font-bold text-sm shadow-sm shrink-0">
                                        {{ mb_substr($member->first_name ?? 'ک', 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 dark:text-white">
                                            {{ $member->full_name }}
                                        </div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 flex items-center gap-1.5">
                                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                            <span>{{ $orgName }}</span>
                                            @if($member->primaryRoleAssignment)
                                                <span class="text-slate-400">•</span>
                                                <span class="text-indigo-500 font-semibold">{{ $member->primaryRoleAssignment->role->label() }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Workload Badge -->
                            <td class="py-4 px-6 text-center">
                                @if($loadStatus === 'overloaded')
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-xl bg-amber-50 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 border border-amber-200 dark:border-amber-800 text-xs font-bold shadow-xs">
                                        <span>⚠️</span>
                                        <span>کار فشرده</span>
                                    </span>
                                @elseif($loadStatus === 'underutilized')
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-xl bg-blue-50 text-blue-800 dark:bg-blue-950/80 dark:text-blue-300 border border-blue-200 dark:border-blue-800 text-xs font-semibold">
                                        <span>🔹</span>
                                        <span>ظرفیت خالی</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-xl bg-emerald-50 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-xs font-semibold">
                                        <span>🟢</span>
                                        <span>متعادل</span>
                                    </span>
                                @endif
                            </td>

                            <!-- Capacity Meter Gauge -->
                            <td class="py-4 px-6">
                                <div class="max-w-[150px] mx-auto space-y-1">
                                    <div class="flex items-center justify-between text-[11px] font-bold">
                                        <span class="text-slate-500 dark:text-slate-400">{{ $item['active_tasks'] }} کار فعال</span>
                                        <span class="{{ $loadStatus === 'overloaded' ? 'text-amber-600' : ($loadStatus === 'balanced' ? 'text-emerald-600' : 'text-blue-600') }}">{{ min(100, $item['active_tasks'] * 12) }}%</span>
                                    </div>
                                    <div class="w-full bg-slate-100 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500 {{ $loadStatus === 'overloaded' ? 'bg-amber-500' : ($loadStatus === 'balanced' ? 'bg-emerald-500' : 'bg-blue-500') }}" style="width: {{ min(100, (int) round(($item['active_weight'] / 12) * 100)) }}%"></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Active Tasks & Weight -->
                            <td class="py-4 px-6 text-center">
                                <div class="font-black text-base text-slate-900 dark:text-white font-mono">{{ $item['active_tasks'] }}</div>
                                <div class="text-[11px] font-bold text-amber-700 dark:text-amber-300 font-mono mt-0.5">وزن: {{ $item['active_weight'] }}</div>
                            </td>

                            <!-- In Progress -->
                            <td class="py-4 px-6 text-center text-blue-600 dark:text-blue-400 font-bold">
                                {{ $item['in_progress_tasks'] }}
                            </td>

                            <!-- Completed Tasks -->
                            <td class="py-4 px-6 text-center text-emerald-600 dark:text-emerald-400 font-bold">
                                {{ $item['completed_tasks'] }}
                            </td>

                            <!-- Overdue Tasks -->
                            <td class="py-4 px-6 text-center">
                                @if($item['overdue_tasks'] > 0)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-rose-50 dark:bg-rose-950/80 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 text-xs font-bold animate-pulse">
                                        <span>🚨</span>
                                        <span>{{ $item['overdue_tasks'] }}</span>
                                    </span>
                                @else
                                    <span class="text-slate-400 font-normal text-xs">۰</span>
                                @endif
                            </td>

                            <!-- Completion Rate -->
                            <td class="py-4 px-6 text-center">
                                <div class="font-bold text-xs {{ $completionRate >= 70 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-400' }}">
                                    {{ $completionRate }}٪
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-16 text-center">
                                <div class="max-w-md mx-auto space-y-3">
                                    <div class="w-16 h-16 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-500 flex items-center justify-center text-3xl mx-auto">
                                        👥
                                    </div>
                                    <h3 class="text-base font-bold text-slate-900 dark:text-white">هیچ کاربری در حیطه سازمانی شما یافت نشد</h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">اطمینان حاصل فرمایید که کاربران در چارت سازمانی به واحدهای معتبر اختصاص یافته‌اند.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- 6. Workload Matrix: Member Cards Grid Mode -->
    <!-- ========================================== -->
    <div x-show="viewMode === 'grid'" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @forelse($workloadData as $item)
                @php
                    $member = $item['user'];
                    $loadStatus = $item['load_status'];
                    $orgName = $member->primaryRoleAssignment?->organizationNode?->name ?? 'بدون واحد';
                    $hasOverdue = $item['overdue_tasks'] > 0;
                    $completionRate = $item['total_tasks'] > 0 ? round(($item['completed_tasks'] / $item['total_tasks']) * 100) : 0;
                @endphp
                <div x-show="matchesFilter('{{ addslashes($member->full_name) }}', '{{ addslashes($orgName) }}', '{{ $loadStatus }}', {{ $hasOverdue ? 'true' : 'false' }})"
                     class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm hover:shadow-lg transition-all flex flex-col justify-between overflow-hidden relative group">
                    <!-- Top Status Strip -->
                    <div class="h-1.5 w-full {{ $loadStatus === 'overloaded' ? 'bg-amber-500' : ($loadStatus === 'balanced' ? 'bg-emerald-500' : 'bg-blue-500') }}"></div>

                    <div class="p-6 space-y-4">
                        <!-- Top Row: Avatar, Name & Status Badge -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl {{ $loadStatus === 'overloaded' ? 'bg-amber-600 text-white' : ($loadStatus === 'balanced' ? 'bg-emerald-600 text-white' : 'bg-blue-600 text-white') }} flex items-center justify-center font-bold text-base shadow-sm shrink-0">
                                    {{ mb_substr($member->first_name ?? 'ک', 0, 1) }}
                                </div>
                                <div>
                                    <h3 class="font-bold text-base text-slate-900 dark:text-white">
                                        {{ $member->full_name }}
                                    </h3>
                                    <p class="text-xs text-slate-400 mt-0.5">{{ $orgName }}</p>
                                </div>
                            </div>

                            <div>
                                @if($loadStatus === 'overloaded')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-amber-50 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 text-[11px] font-bold">
                                        ⚠️ پربار
                                    </span>
                                @elseif($loadStatus === 'underutilized')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-blue-50 text-blue-800 dark:bg-blue-950/80 dark:text-blue-300 text-[11px] font-semibold">
                                        🔹 ظرفیت خالی
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 text-[11px] font-semibold">
                                        🟢 متعادل
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Capacity Meter -->
                        <div class="space-y-1.5 pt-1">
                            <div class="flex items-center justify-between text-xs font-bold">
                                <span class="text-slate-600 dark:text-slate-300">میزان مشغله (وزنی)</span>
                                <span class="{{ $loadStatus === 'overloaded' ? 'text-amber-600' : ($loadStatus === 'balanced' ? 'text-emerald-600' : 'text-blue-600') }}">{{ min(100, (int) round(($item['active_weight'] / 12) * 100)) }}%</span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500 {{ $loadStatus === 'overloaded' ? 'bg-amber-500' : ($loadStatus === 'balanced' ? 'bg-emerald-500' : 'bg-blue-500') }}" style="width: {{ min(100, (int) round(($item['active_weight'] / 12) * 100)) }}%"></div>
                            </div>
                        </div>

                        <!-- 4-Stat Metric Boxes -->
                        <div class="grid grid-cols-4 gap-2 pt-2 text-center">
                            <div class="p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-100 dark:border-slate-800">
                                <div class="text-lg font-black text-slate-900 dark:text-white font-mono">{{ $item['active_tasks'] }}</div>
                                <div class="text-[10px] text-amber-700 dark:text-amber-300 font-bold mt-0.5">وزن: {{ $item['active_weight'] }}</div>
                            </div>

                            <div class="p-2.5 rounded-2xl bg-blue-50/60 dark:bg-blue-950/40 border border-blue-100 dark:border-blue-900/60">
                                <div class="text-lg font-black text-blue-600 dark:text-blue-400">{{ $item['in_progress_tasks'] }}</div>
                                <div class="text-[10px] text-blue-500 font-semibold mt-0.5">در جریان</div>
                            </div>

                            <div class="p-2.5 rounded-2xl bg-emerald-50/60 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900/60">
                                <div class="text-lg font-black text-emerald-600 dark:text-emerald-400">{{ $item['completed_tasks'] }}</div>
                                <div class="text-[10px] text-emerald-500 font-semibold mt-0.5">تکمیل</div>
                            </div>

                            <div class="p-2.5 rounded-2xl bg-rose-50/60 dark:bg-rose-950/40 border border-rose-100 dark:border-rose-900/60">
                                <div class="text-lg font-black text-rose-600 dark:text-rose-400">{{ $item['overdue_tasks'] }}</div>
                                <div class="text-[10px] text-rose-500 font-semibold mt-0.5">معوقه</div>
                            </div>
                        </div>

                        <!-- Overdue Notice if any -->
                        @if($hasOverdue)
                            <div class="p-2.5 rounded-2xl bg-rose-50 dark:bg-rose-950/80 border border-rose-200 dark:border-rose-800 text-xs font-bold text-rose-700 dark:text-rose-300 flex items-center justify-between animate-pulse">
                                <span>🚨 {{ $item['overdue_tasks'] }} کار دارای تاخیر</span>
                                <span class="text-[10px] font-normal text-rose-500">نیاز به اقدام</span>
                            </div>
                        @endif
                    </div>

                    <!-- Footer -->
                    <div class="p-4 bg-slate-50 dark:bg-slate-900/40 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                        <span>درصد انجام کارها:</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $completionRate }}٪</span>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-16 text-center bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700">
                    <div class="max-w-md mx-auto space-y-3">
                        <div class="w-16 h-16 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-500 flex items-center justify-center text-3xl mx-auto">
                            👥
                        </div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">همکاری با این مشخصات پیدا نشد</h3>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

</div>
@endsection
