<x-app-layout title="داشبورد" header="داشبورد کاری">
    <div class="space-y-8 pb-12" x-data="{
        activeTab: 'my-tasks',
        reviewModalOpen: false,
        reviewTask: null,
        reviewAction: 'APPROVE',
        reviewComments: '',
        reviewUrl: '',
        statusModalOpen: false,
        statusTask: null,
        statusValue: 'IN_PROGRESS',
        statusComments: '',
        statusUrl: '',
        openReview(task, action, url) {
            this.reviewTask = task;
            this.reviewAction = action;
            this.reviewUrl = url;
            this.reviewComments = '';
            this.reviewModalOpen = true;
        },
        openStatus(task, status, url) {
            this.statusTask = task;
            this.statusValue = status;
            this.statusUrl = url;
            this.statusComments = '';
            this.statusModalOpen = true;
        }
    }">

        <!-- ========================================== -->
        <!-- 1. Executive Welcome & Hero Header Banner  -->
        <!-- ========================================== -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-indigo-700 via-indigo-600 to-violet-700 p-6 sm:p-8 text-white shadow-xl shadow-indigo-900/10 border border-indigo-500/30">
            <!-- Background Glow Blobs -->
            <div class="absolute -left-12 -bottom-12 w-72 h-72 bg-white/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute right-1/4 -top-12 w-64 h-64 bg-violet-400/20 rounded-full blur-2xl pointer-events-none"></div>

            <div class="relative flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div class="space-y-3">
                    <div class="flex items-center gap-2 text-indigo-200 text-xs font-semibold">
                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>{{ verta()->format('l، d F Y') }}</span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black tracking-tight">
                        خوش آمدید، {{ $user->full_name }} 👋
                    </h1>

                    <p class="text-indigo-100 text-xs sm:text-sm max-w-2xl leading-relaxed">
                        سلام! وضعیت پروژه‌ها، کارهای در دست اقدام و اولویت‌های امروز شما اینجاست.
                    </p>

                    <!-- Role & Organization Badges -->
                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        @if($user->primaryRoleAssignment)
                            <div class="bg-white/15 backdrop-blur-md px-3 py-1.5 rounded-xl text-xs font-bold border border-white/20 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                <span>نقش: {{ $user->primaryRoleAssignment->role->label() }}</span>
                            </div>
                            <div class="bg-white/15 backdrop-blur-md px-3 py-1.5 rounded-xl text-xs font-medium border border-white/20 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0h4m-4 0H9m4 0V7m0 0h4m-4 0H9"/></svg>
                                <span>واحد: <strong>{{ $user->primaryRoleAssignment->organizationNode ? $user->primaryRoleAssignment->organizationNode->name : 'کل سازمان' }}</strong></span>
                            </div>
                        @else
                            <div class="bg-white/15 backdrop-blur-md px-3 py-1.5 rounded-xl text-xs font-medium border border-white/20">
                                <span>محدوده: کل سازمان</span>
                            </div>
                        @endif

                        @if($myPendingApprovalsCount > 0)
                            <div class="bg-amber-400/30 backdrop-blur-md px-3 py-1.5 rounded-xl text-xs font-bold border border-amber-300/40 text-amber-100 flex items-center gap-1.5 animate-pulse">
                                <span>⚡ {{ $myPendingApprovalsCount }} مورد در کارتابل تایید شما</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Progress Metric Cards -->
                <div class="flex items-center gap-3 shrink-0 flex-wrap sm:flex-nowrap">
                    <!-- Project Completion Rate -->
                    <div class="bg-white/10 backdrop-blur-md p-4 rounded-2xl border border-white/20 min-w-[140px] text-center flex-1 sm:flex-initial">
                        <p class="text-[11px] text-indigo-100 font-semibold">پیشرفت پروژه‌ها</p>
                        <div class="text-2xl sm:text-3xl font-black text-white mt-1">{{ $overallProjectProgress }}%</div>
                        <div class="w-full bg-white/20 h-1.5 rounded-full mt-2 overflow-hidden">
                            <div class="bg-emerald-400 h-full rounded-full transition-all duration-500" style="width: {{ $overallProjectProgress }}%"></div>
                        </div>
                    </div>

                    <!-- Task Completion Rate -->
                    <div class="bg-white/10 backdrop-blur-md p-4 rounded-2xl border border-white/20 min-w-[140px] text-center flex-1 sm:flex-initial">
                        <p class="text-[11px] text-indigo-100 font-semibold">پیشرفت کل کارها</p>
                        <div class="text-2xl sm:text-3xl font-black text-emerald-300 mt-1">{{ $overallTaskProgress }}%</div>
                        <div class="w-full bg-white/20 h-1.5 rounded-full mt-2 overflow-hidden">
                            <div class="bg-emerald-300 h-full rounded-full transition-all duration-500" style="width: {{ $overallTaskProgress }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- 2. Quick Operational Action Shortcuts Bar  -->
        <!-- ========================================== -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-thin">
            @if($user->canCreateProjects())
                <a href="{{ route('projects.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-sm transition-all shrink-0 hover:shadow-indigo-500/20 hover:shadow-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>تعریف پروژه جدید</span>
                </a>
            @endif

            <a href="{{ route('reports.projects') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold text-xs border border-slate-200 dark:border-slate-800 shadow-xs transition-all shrink-0">
                <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span>گزارش پروژه‌ها</span>
            </a>

            <a href="{{ route('reports.workload') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold text-xs border border-slate-200 dark:border-slate-800 shadow-xs transition-all shrink-0">
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span>بررسی تقسیم کار</span>
            </a>

            <a href="{{ route('project-templates.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold text-xs border border-slate-200 dark:border-slate-800 shadow-xs transition-all shrink-0">
                <svg class="w-4 h-4 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                <span>الگوهای آماده</span>
            </a>

            <a href="{{ route('organization.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold text-xs border border-slate-200 dark:border-slate-800 shadow-xs transition-all shrink-0">
                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0h4m-4 0H9m4 0V7m0 0h4m-4 0H9"/></svg>
                <span>چارت سازمانی</span>
            </a>
        </div>

        <!-- ========================================== -->
        <!-- 3. Primary KPI Stat Highlights Grid (6 Cards) -->
        <!-- ========================================== -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
            <!-- 1. Total Accessible Projects -->
            <x-dashboard.stat-card
                title="پروژه‌های شما"
                :value="number_format($totalProjects)"
                :subtext="$activeProjects . ' پروژه در حال انجام'"
                :badge="$activeProjects . ' فعال'"
                badgeType="success"
                color="indigo"
                :link="route('projects.index')">
                <x-slot:icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                </x-slot:icon>
            </x-dashboard.stat-card>

            <!-- 2. Active Tasks in System -->
            <x-dashboard.stat-card
                title="کارهای در جریان"
                :value="number_format($activeTasksCount)"
                :subtext="$taskStatusCounts['IN_PROGRESS'] . ' در دست اقدام • ' . $taskStatusCounts['TODO'] . ' در انتظار'"
                :badge="number_format($totalTasks) . ' کل کارها'"
                badgeType="neutral"
                color="blue"
                :link="route('reports.projects')">
                <x-slot:icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </x-slot:icon>
            </x-dashboard.stat-card>

            <!-- 3. My Assigned Active Tasks -->
            <x-dashboard.stat-card
                title="کارهای من"
                :value="number_format($myActiveTasksCount)"
                :subtext="$myOverdueTasksCount > 0 ? $myOverdueTasksCount . ' کار عقب‌افتاده' : 'همه کارها به‌موقع'"
                :badge="$myOverdueTasksCount > 0 ? '🚨 ' . $myOverdueTasksCount . ' تاخیردار' : '🟢 به‌موقع'"
                :badgeType="$myOverdueTasksCount > 0 ? 'danger' : 'success'"
                color="emerald">
                <x-slot:icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </x-slot:icon>
            </x-dashboard.stat-card>

            <!-- 4. My Pending Supervisor Approvals -->
            <x-dashboard.stat-card
                title="منتظر تایید شما"
                :value="number_format($myPendingApprovalsCount)"
                subtext="کارهای در انتظار تایید شما"
                :badge="$myPendingApprovalsCount > 0 ? 'اقدام فوری' : 'تکمیل'"
                :badgeType="$myPendingApprovalsCount > 0 ? 'warning' : 'neutral'"
                color="amber">
                <x-slot:icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </x-slot:icon>
            </x-dashboard.stat-card>

            <!-- 5. Overdue Tasks & Risk Alarm -->
            <x-dashboard.stat-card
                title="کارهای عقب‌افتاده"
                :value="number_format($overdueTasksCount)"
                :subtext="$overdueProjects . ' پروژه با مهلت گذشته'"
                :badge="$overdueTasksCount > 0 ? 'نیازمند پیگیری' : 'عالی'"
                :badgeType="$overdueTasksCount > 0 ? 'danger' : 'success'"
                color="rose">
                <x-slot:icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </x-slot:icon>
            </x-dashboard.stat-card>

            <!-- 6. Completed Deliverables -->
            <x-dashboard.stat-card
                title="کارهای تکمیل‌شده"
                :value="number_format($taskStatusCounts['COMPLETED'])"
                :subtext="$completedProjects . ' پروژه تکمیل‌شده'"
                badge="موفق"
                badgeType="success"
                color="violet">
                <x-slot:icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </x-slot:icon>
            </x-dashboard.stat-card>
        </div>

        <!-- ========================================== -->
        <!-- 4. Visual Analytics & Interactive Charts   -->
        <!-- ========================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Project Status Distribution Chart Card -->
            <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm space-y-4 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                            <span>توزیع وضعیت پروژه‌ها</span>
                        </h2>
                        <span class="text-xs font-semibold text-slate-400">{{ $totalProjects }} پروژه</span>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">تفکیک کلیه پروژه‌های تحت مدیریت بر اساس وضعیت جاری</p>
                </div>

                <!-- Visual Stacked Bar -->
                <div class="space-y-3 pt-2">
                    @php
                        $activePct = $totalProjects > 0 ? round(($activeProjects / $totalProjects) * 100, 1) : 0;
                        $compPct = $totalProjects > 0 ? round(($completedProjects / $totalProjects) * 100, 1) : 0;
                        $holdPct = $totalProjects > 0 ? round(($onHoldProjects / $totalProjects) * 100, 1) : 0;
                        $otherPct = max(0, 100 - ($activePct + $compPct + $holdPct));
                    @endphp

                    <div class="w-full h-3 rounded-full bg-slate-100 dark:bg-slate-800 flex overflow-hidden">
                        <div style="width: {{ $activePct }}%" class="bg-emerald-500 transition-all duration-500" title="فعال: {{ $activeProjects }}"></div>
                        <div style="width: {{ $compPct }}%" class="bg-blue-500 transition-all duration-500" title="تکمیل شده: {{ $completedProjects }}"></div>
                        <div style="width: {{ $holdPct }}%" class="bg-amber-500 transition-all duration-500" title="متوقف: {{ $onHoldProjects }}"></div>
                        <div style="width: {{ $otherPct }}%" class="bg-rose-400 transition-all duration-500" title="سایر: {{ $cancelledProjects + $archivedProjects }}"></div>
                    </div>

                    <!-- Legend Items Grid -->
                    <div class="grid grid-cols-2 gap-2.5 pt-2 text-xs">
                        <div class="p-2 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/50 flex items-center justify-between">
                            <span class="flex items-center gap-1.5 text-emerald-800 dark:text-emerald-300 font-medium">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span>فعال</span>
                            </span>
                            <span class="font-bold text-emerald-900 dark:text-emerald-200">{{ $activeProjects }} <span class="text-[10px] text-emerald-600 dark:text-emerald-400">({{ $activePct }}%)</span></span>
                        </div>

                        <div class="p-2 rounded-xl bg-blue-50/60 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900/50 flex items-center justify-between">
                            <span class="flex items-center gap-1.5 text-blue-800 dark:text-blue-300 font-medium">
                                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                <span>تکمیل شده</span>
                            </span>
                            <span class="font-bold text-blue-900 dark:text-blue-200">{{ $completedProjects }} <span class="text-[10px] text-blue-600 dark:text-blue-400">({{ $compPct }}%)</span></span>
                        </div>

                        <div class="p-2 rounded-xl bg-amber-50/60 dark:bg-amber-950/30 border border-amber-100 dark:border-amber-900/50 flex items-center justify-between">
                            <span class="flex items-center gap-1.5 text-amber-800 dark:text-amber-300 font-medium">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                <span>متوقف‌شده</span>
                            </span>
                            <span class="font-bold text-amber-900 dark:text-amber-200">{{ $onHoldProjects }} <span class="text-[10px] text-amber-600 dark:text-amber-400">({{ $holdPct }}%)</span></span>
                        </div>

                        <div class="p-2 rounded-xl bg-rose-50/60 dark:bg-rose-950/30 border border-rose-100 dark:border-rose-900/50 flex items-center justify-between">
                            <span class="flex items-center gap-1.5 text-rose-800 dark:text-rose-300 font-medium">
                                <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                                <span>لغو/آرشیو</span>
                            </span>
                            <span class="font-bold text-rose-900 dark:text-rose-200">{{ $cancelledProjects + $archivedProjects }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Task Status Pipeline Funnel Card -->
            <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                        <span>وضعیت کارهای تیم</span>
                    </h2>
                    <span class="text-xs font-semibold text-slate-400">{{ $totalTasks }} کار</span>
                </div>

                <div class="space-y-2.5 text-xs">
                    <!-- TODO -->
                    @php
                        $todoPct = $totalTasks > 0 ? round(($taskStatusCounts['TODO'] / $totalTasks) * 100) : 0;
                    @endphp
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                            <span class="font-medium flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                <span>در انتظار انجام</span>
                            </span>
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ $taskStatusCounts['TODO'] }} ({{ $todoPct }}%)</span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full bg-slate-400 rounded-full transition-all duration-500" style="width: {{ $todoPct }}%"></div>
                        </div>
                    </div>

                    <!-- IN_PROGRESS -->
                    @php
                        $inProgPct = $totalTasks > 0 ? round(($taskStatusCounts['IN_PROGRESS'] / $totalTasks) * 100) : 0;
                    @endphp
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                            <span class="font-medium flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                <span>در دست اقدام</span>
                            </span>
                            <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $taskStatusCounts['IN_PROGRESS'] }} ({{ $inProgPct }}%)</span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full bg-indigo-500 rounded-full transition-all duration-500" style="width: {{ $inProgPct }}%"></div>
                        </div>
                    </div>

                    <!-- PENDING_REVIEW & NEEDS_REVISION -->
                    @php
                        $reviewCount = $taskStatusCounts['PENDING_REVIEW'] + $taskStatusCounts['NEEDS_REVISION'];
                        $reviewPct = $totalTasks > 0 ? round(($reviewCount / $totalTasks) * 100) : 0;
                    @endphp
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                            <span class="font-medium flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                <span>در انتظار بررسی و تایید</span>
                            </span>
                            <span class="font-bold text-amber-600 dark:text-amber-400">{{ $reviewCount }} ({{ $reviewPct }}%)</span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full bg-amber-500 rounded-full transition-all duration-500" style="width: {{ $reviewPct }}%"></div>
                        </div>
                    </div>

                    <!-- COMPLETED -->
                    @php
                        $compTaskPct = $totalTasks > 0 ? round(($taskStatusCounts['COMPLETED'] / $totalTasks) * 100) : 0;
                    @endphp
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                            <span class="font-medium flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span>تکمیل‌شده</span>
                            </span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $taskStatusCounts['COMPLETED'] }} ({{ $compTaskPct }}%)</span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full bg-emerald-500 rounded-full transition-all duration-500" style="width: {{ $compTaskPct }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Task Priority & Risk Matrix Card -->
            <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm space-y-4 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                            <span>اولویت کارها</span>
                        </h2>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                            {{ $taskPriorityCounts['URGENT'] }} کار فوری
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">تعداد کارها بر اساس میزان اهمیت و فوریت</p>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-1">
                    <!-- Urgent -->
                    <div class="p-3.5 rounded-2xl bg-rose-50/70 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-rose-700 dark:text-rose-300">🚨 فوری</span>
                            <p class="text-2xl font-black text-rose-800 dark:text-rose-200 mt-0.5">{{ $taskPriorityCounts['URGENT'] }}</p>
                        </div>
                    </div>

                    <!-- High -->
                    <div class="p-3.5 rounded-2xl bg-amber-50/70 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-amber-700 dark:text-amber-300">⚡ بالا</span>
                            <p class="text-2xl font-black text-amber-800 dark:text-amber-200 mt-0.5">{{ $taskPriorityCounts['HIGH'] }}</p>
                        </div>
                    </div>

                    <!-- Medium -->
                    <div class="p-3.5 rounded-2xl bg-blue-50/70 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-blue-700 dark:text-blue-300">🔹 متوسط</span>
                            <p class="text-2xl font-black text-blue-800 dark:text-blue-200 mt-0.5">{{ $taskPriorityCounts['MEDIUM'] }}</p>
                        </div>
                    </div>

                    <!-- Low -->
                    <div class="p-3.5 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300">⚪ عادی</span>
                            <p class="text-2xl font-black text-slate-800 dark:text-slate-200 mt-0.5">{{ $taskPriorityCounts['LOW'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- 5. Personal Action Center (میز کار من)      -->
        <!-- ========================================== -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
            <!-- Header with Tabs -->
            <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span>💼 کارهای من</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">کارهای محول‌شده به شما و مواردی که منتظر بررسی یا تایید هستند</p>
                </div>

                <!-- Tab Navigation Buttons -->
                <div class="flex items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-800 rounded-2xl">
                    <button type="button" @click="activeTab = 'my-tasks'" :class="activeTab === 'my-tasks' ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 font-medium'" class="px-3.5 py-1.5 rounded-xl text-xs transition-all flex items-center gap-1.5">
                        <span>کارهای من</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-mono">{{ $myActiveTasksCount }}</span>
                    </button>

                    <button type="button" @click="activeTab = 'pending-approvals'" :class="activeTab === 'pending-approvals' ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 font-medium'" class="px-3.5 py-1.5 rounded-xl text-xs transition-all flex items-center gap-1.5">
                        <span>منتظر تایید من</span>
                        @if($myPendingApprovalsCount > 0)
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300 font-mono animate-pulse">{{ $myPendingApprovalsCount }}</span>
                        @endif
                    </button>

                    <button type="button" @click="activeTab = 'created-by-me'" :class="activeTab === 'created-by-me' ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 font-medium'" class="px-3.5 py-1.5 rounded-xl text-xs transition-all flex items-center gap-1.5">
                        <span>کارهایی که ساختم</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-mono">{{ $myCreatedTasks->count() }}</span>
                    </button>
                </div>
            </div>

            <!-- Tab 1: My Tasks Content -->
            <div x-show="activeTab === 'my-tasks'" class="p-6">
                @if($myTasks->isEmpty())
                    <div class="py-12 text-center text-slate-400 space-y-2">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto text-xl">
                            🎉
                        </div>
                        <p class="text-sm font-bold text-slate-700 dark:text-slate-300">عالیه! در حال حاضر هیچ کار باز یا معوقه‌ای نداری.</p>
                        <p class="text-xs text-slate-400">همه کارهایت انجام شده یا هنوز کار جدیدی به شما اختصاص داده نشده است.</p>
                    </div>
                @else
                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($myTasks as $task)
                            @php
                                $deadlineStatus = $task->deadline_status;
                            @endphp
                            <div class="py-4 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 rounded-2xl px-3 transition-colors">
                                <div class="space-y-1.5 flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="px-2 py-0.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-mono text-[11px] font-bold border border-indigo-100 dark:border-indigo-800">
                                            {{ $task->project?->project_key }}
                                        </span>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold shadow-xs {{ $task->priority->badgeClass() }}">
                                            {{ $task->priority->label() }}
                                        </span>
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold {{ $task->status->badgeClass() }}">
                                            {{ $task->status->label() }}
                                        </span>
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $deadlineStatus['bg_class'] }}">
                                            {{ $deadlineStatus['badge'] }} {{ $deadlineStatus['label'] }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('projects.tasks.show', ['project' => $task->project, 'task' => $task]) }}" class="text-sm font-bold text-slate-800 dark:text-slate-100 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                            {{ $task->title }}
                                        </a>
                                    </div>

                                    <div class="flex items-center gap-4 text-xs text-slate-400">
                                        <span>پروژه: <strong class="text-slate-600 dark:text-slate-300">{{ $task->project?->title }}</strong></span>
                                        @if($task->due_date)
                                            <span>مهلت: <strong class="font-mono text-slate-600 dark:text-slate-300">{{ verta($task->due_date)->format('Y/m/d') }}</strong></span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Quick Actions for My Tasks -->
                                <div class="flex items-center gap-2 shrink-0">
                                    <a href="{{ route('projects.tasks.show', ['project' => $task->project, 'task' => $task]) }}" class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 text-xs font-semibold transition-colors">
                                        مشاهده جزئیات
                                    </a>

                                    @if($task->status->value === 'TODO')
                                        <button type="button" @click="openStatus({{ json_encode($task) }}, 'IN_PROGRESS', '{{ route('projects.tasks.update-status', ['project' => $task->project, 'task' => $task]) }}')" class="px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-xs transition-all">
                                            شروع کار
                                        </button>
                                    @elseif($task->status->value === 'IN_PROGRESS' || $task->status->value === 'NEEDS_REVISION')
                                        @if($task->taskSupervisors->isNotEmpty())
                                            <button type="button" @click="openStatus({{ json_encode($task) }}, 'PENDING_REVIEW', '{{ route('projects.tasks.update-status', ['project' => $task->project, 'task' => $task]) }}')" class="px-3 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-xs transition-all">
                                                ارسال برای تایید سرپرست
                                            </button>
                                        @else
                                            <button type="button" @click="openStatus({{ json_encode($task) }}, 'COMPLETED', '{{ route('projects.tasks.update-status', ['project' => $task->project, 'task' => $task]) }}')" class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition-all">
                                                ثبت به عنوان تکمیل‌شده
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Tab 2: Pending Approvals Content -->
            <div x-show="activeTab === 'pending-approvals'" class="p-6" style="display: none;">
                @if($myPendingApprovals->isEmpty())
                    <div class="py-12 text-center text-slate-400 space-y-2">
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center mx-auto text-xl">
                            👍
                        </div>
                        <p class="text-sm font-bold text-slate-700 dark:text-slate-300">موردی برای تایید وجود ندارد!</p>
                        <p class="text-xs text-slate-400">در حال حاضر هیچ کاری در انتظار بررسی یا تایید شما نیست.</p>
                    </div>
                @else
                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($myPendingApprovals as $approval)
                            @php
                                $appTask = $approval->task;
                            @endphp
                            @if($appTask)
                                <div class="py-4 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 rounded-2xl px-3 transition-colors">
                                    <div class="space-y-1.5 flex-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="px-2 py-0.5 rounded-lg bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 text-[10px] font-bold border border-amber-200 dark:border-amber-800">
                                                مرحله: {{ $approval->stage_name ?? 'تایید ناظر' }}
                                            </span>
                                            <span class="px-2 py-0.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-mono text-[11px] font-bold">
                                                {{ $appTask->project?->project_key }}
                                            </span>
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $appTask->priority->badgeClass() }}">
                                                {{ $appTask->priority->label() }}
                                            </span>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('projects.tasks.show', ['project' => $appTask->project, 'task' => $appTask]) }}" class="text-sm font-bold text-slate-800 dark:text-slate-100 hover:text-indigo-600 dark:hover:text-indigo-400">
                                                {{ $appTask->title }}
                                            </a>
                                        </div>

                                        <div class="flex items-center gap-4 text-xs text-slate-400">
                                            <span>مجری وظیفه: <strong class="text-slate-700 dark:text-slate-300">{{ $appTask->executor?->full_name ?? 'تعیین‌نشده' }}</strong></span>
                                            <span>پروژه: <strong class="text-slate-700 dark:text-slate-300">{{ $appTask->project?->title }}</strong></span>
                                        </div>
                                    </div>

                                    <!-- Direct Approve / Reject Quick Actions -->
                                    <div class="flex items-center gap-2 shrink-0">
                                        <button type="button" @click="openReview({{ json_encode($appTask) }}, 'APPROVE', '{{ route('projects.tasks.review', ['project' => $appTask->project, 'task' => $appTask]) }}')" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition-all flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>تایید کار</span>
                                        </button>

                                        <button type="button" @click="openReview({{ json_encode($appTask) }}, 'REJECT', '{{ route('projects.tasks.review', ['project' => $appTask->project, 'task' => $appTask]) }}')" class="px-3.5 py-1.5 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/60 border border-rose-200 dark:border-rose-800 text-xs font-bold transition-colors">
                                            <span>نیاز به اصلاح</span>
                                        </button>

                                        <a href="{{ route('projects.tasks.show', ['project' => $appTask->project, 'task' => $appTask]) }}" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200" title="مشاهده کامل">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </a>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Tab 3: Created By Me Content -->
            <div x-show="activeTab === 'created-by-me'" class="p-6" style="display: none;">
                @if($myCreatedTasks->isEmpty())
                    <div class="py-12 text-center text-slate-400 space-y-2">
                        <p class="text-sm font-bold text-slate-700 dark:text-slate-300">هنوز کاری توسط شما ایجاد نشده است.</p>
                    </div>
                @else
                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($myCreatedTasks as $crTask)
                            <div class="py-4 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-slate-50/60 dark:hover:bg-slate-800/40 rounded-2xl px-3 transition-colors">
                                <div class="space-y-1 flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold {{ $crTask->status->badgeClass() }}">
                                            {{ $crTask->status->label() }}
                                        </span>
                                        <a href="{{ route('projects.tasks.show', ['project' => $crTask->project, 'task' => $crTask]) }}" class="text-sm font-bold text-slate-800 dark:text-slate-100 hover:text-indigo-600 dark:hover:text-indigo-400">
                                            {{ $crTask->title }}
                                        </a>
                                    </div>
                                    <p class="text-xs text-slate-400">
                                        پروژه: <span class="text-slate-600 dark:text-slate-300 font-semibold">{{ $crTask->project?->title }}</span> • 
                                        مجری: <span class="text-slate-600 dark:text-slate-300 font-semibold">{{ $crTask->executor?->full_name ?? 'بدون مجری' }}</span>
                                    </p>
                                </div>

                                <a href="{{ route('projects.tasks.show', ['project' => $crTask->project, 'task' => $crTask]) }}" class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:bg-slate-200 transition-colors shrink-0">
                                    مشاهده
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- ========================================== -->
        <!-- 6. Main Projects Hub (بدون زیرپروژه)       -->
        <!-- ========================================== -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm p-6 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800 gap-2">
                <div>
                    <h2 class="font-bold text-slate-800 dark:text-slate-100 text-base flex items-center gap-2">
                        <span>📁 پروژه‌های اصلی</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">لیست پروژه‌های اصلی و میزان پیشرفت هرکدام</p>
                </div>
                <a href="{{ route('projects.index') }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                    <span>مشاهده همه پروژه‌ها</span>
                    <span>&larr;</span>
                </a>
            </div>

            @if($mainProjects->isEmpty())
                <div class="py-12 text-center text-slate-400 space-y-3">
                    <p class="font-semibold text-xs text-slate-500">در حال حاضر پروژه‌ای پیدا نشد.</p>
                    @if($user->canCreateProjects())
                        <div>
                            <a href="{{ route('projects.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold shadow-sm">
                                + تعریف پروژه جدید
                            </a>
                        </div>
                    @endif
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach($mainProjects as $prj)
                        @php
                            $taskProgress = $prj->tasks_count > 0 ? round(($prj->completed_tasks_count / $prj->tasks_count) * 100) : 0;
                            $timelineProg = $prj->timeline_progress;
                            $alert = $prj->deadline_alert;
                            $timeBarColor = $timelineProg >= 85 ? 'bg-rose-500' : ($timelineProg >= 60 ? 'bg-amber-500' : 'bg-emerald-500');
                        @endphp
                        <div class="p-5 rounded-3xl bg-slate-50/60 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-800 hover:border-indigo-300 dark:hover:border-indigo-700 hover:shadow-md transition-all flex flex-col justify-between space-y-4">
                            <div class="space-y-3">
                                <!-- Top Badges -->
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-1 rounded-xl bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 text-xs font-mono font-bold">
                                            {{ $prj->project_key }}
                                        </span>
                                        @if(isset($prj->priority))
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold shadow-xs {{ $prj->priority->badgeClass() }}">
                                                {{ $prj->priority->label() }}
                                            </span>
                                        @endif
                                    </div>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300">
                                        {{ $prj->status->label() }}
                                    </span>
                                </div>

                                <!-- Title -->
                                <div>
                                    <a href="{{ route('projects.show', $prj) }}" class="font-bold text-slate-900 dark:text-white text-base hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors line-clamp-1">
                                        {{ $prj->title }}
                                    </a>
                                    <p class="text-xs text-slate-400 mt-1">واحد: {{ $prj->organizationNode?->name ?? 'کل سازمان' }}</p>
                                </div>

                                @if($alert)
                                    <div class="p-2 rounded-xl border text-[11px] font-bold flex items-center gap-1.5 {{ $alert['class'] }}">
                                        <span>{{ $alert['label'] }}</span>
                                    </div>
                                @endif

                                <!-- Task Progress Bar -->
                                <div class="space-y-1 p-2.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-slate-500 font-medium">پیشرفت کارها:</span>
                                        <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $taskProgress }}% <span class="text-[10px] text-slate-400">({{ $prj->completed_tasks_count }}/{{ $prj->tasks_count }})</span></span>
                                    </div>
                                    <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                        <div class="h-full bg-indigo-600 rounded-full transition-all duration-500" style="width: {{ $taskProgress }}%"></div>
                                    </div>
                                </div>

                                <!-- Dates & PMs -->
                                <div class="text-xs text-slate-500 dark:text-slate-400 space-y-1.5 pt-1">
                                    <div class="flex items-center justify-between">
                                        <span>مهلت تحویل:</span>
                                        <span class="font-mono font-semibold text-slate-700 dark:text-slate-300">{{ $prj->deadline ? verta($prj->deadline)->format('Y/m/d') : 'تعیین‌نشده' }}</span>
                                    </div>

                                    @if($prj->projectManagers && $prj->projectManagers->isNotEmpty())
                                        <div class="flex items-center justify-between pt-1">
                                            <span>مدیران پروژه:</span>
                                            <div class="flex -space-x-1.5 space-x-reverse overflow-hidden">
                                                @foreach($prj->projectManagers as $pm)
                                                    <div class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[10px] font-bold ring-2 ring-white dark:ring-slate-900" title="{{ $pm->user?->full_name }}">
                                                        {{ mb_substr($pm->user?->first_name ?? 'P', 0, 1) }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Card Footer Action Buttons -->
                            <div class="pt-3 border-t border-slate-200/60 dark:border-slate-800 flex items-center justify-between gap-2">
                                <a href="{{ route('projects.show', $prj) }}" class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs shadow-xs transition-all flex items-center gap-1">
                                    <span>مشاهده پروژه</span>
                                    <svg class="w-3.5 h-3.5 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </a>

                                <div class="flex items-center gap-1 text-xs">
                                    <a href="{{ route('projects.gantt', $prj) }}" class="p-2 rounded-xl bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700" title="گانت چارت">
                                        📈
                                    </a>
                                    @if($prj->status->value !== 'COMPLETED')
                                        <a href="{{ route('projects.tasks.create', $prj) }}" class="px-2.5 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 font-semibold" title="افزودن کار جدید">
                                            + کار جدید
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- ========================================== -->
        <!-- 7. Bottom Grid: Deadlines & Live Feed      -->
        <!-- ========================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Urgent Deadlines Card -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="font-bold text-slate-900 dark:text-white text-sm flex items-center gap-2">
                        <span>⏰ کارهای نزدیک به موعد و دارای تاخیر</span>
                    </h2>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                        کمتر از ۴۸ ساعت یا معوقه
                    </span>
                </div>

                @if($imminentTasks->isEmpty())
                    <div class="py-8 text-center text-slate-400 text-xs">
                        در حال حاضر هیچ کار فوری یا دارای تاخیری وجود ندارد.
                    </div>
                @else
                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($imminentTasks as $imTask)
                            @php
                                $dStat = $imTask->deadline_status;
                            @endphp
                            <div class="py-3 flex items-center justify-between gap-3">
                                <div class="space-y-1 min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold {{ $imTask->priority->badgeClass() }}">
                                            {{ $imTask->priority->label() }}
                                        </span>
                                        <a href="{{ route('projects.tasks.show', ['project' => $imTask->project, 'task' => $imTask]) }}" class="font-bold text-xs text-slate-800 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400 truncate">
                                            {{ $imTask->title }}
                                        </a>
                                    </div>
                                    <p class="text-[11px] text-slate-400 truncate">
                                        پروژه: {{ $imTask->project?->title }} • مجری: {{ $imTask->executor?->full_name ?? 'بدون مجری' }}
                                    </p>
                                </div>
                                <span class="px-2.5 py-1 rounded-xl text-[10px] font-bold border shrink-0 {{ $dStat['bg_class'] }}">
                                    {{ $dStat['label'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Live Collaboration & Activity Feed -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="font-bold text-slate-900 dark:text-white text-sm flex items-center gap-2">
                        <span>💬 آخرین پیام‌ها و یادداشت‌ها</span>
                    </h2>
                    <span class="text-xs text-slate-400">گفت‌وگوها در کارهای مختلف</span>
                </div>

                <div class="space-y-3">
                    @forelse($recentChats as $chat)
                        <div class="flex items-start gap-3 p-3 rounded-2xl bg-slate-50/70 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 flex items-center justify-center text-xs font-bold shrink-0">
                                {{ mb_substr($chat->user?->first_name ?? 'U', 0, 1) }}
                            </div>
                            <div class="space-y-1 min-w-0 flex-1">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ $chat->user?->full_name }}</span>
                                    <span class="text-[10px] text-slate-400 font-mono">{{ verta($chat->created_at)->format('H:i - Y/m/d') }}</span>
                                </div>
                                <p class="text-xs text-slate-600 dark:text-slate-300 line-clamp-2">{{ $chat->message }}</p>
                                <div class="text-[10px] text-indigo-600 dark:text-indigo-400 font-medium">
                                    در کار: {{ $chat->task?->title }} ({{ $chat->task?->project?->title }})
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-slate-400 text-xs">
                            هنوز پیامی در کارهای این بخش ثبت نشده است.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- Review Action Confirmation Modal           -->
        <!-- ========================================== -->
        <div x-show="reviewModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;" x-transition>
            <div @click.away="reviewModalOpen = false" class="bg-white dark:bg-slate-900 rounded-3xl p-6 max-w-md w-full border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="font-bold text-slate-900 dark:text-white text-base">
                        <span x-text="reviewAction === 'APPROVE' ? '✅ تایید کار' : '🚨 نیاز به اصلاح (ارسال مجدد به مجری)'"></span>
                    </h3>
                    <button type="button" @click="reviewModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">&times;</button>
                </div>

                <p class="text-xs text-slate-500 dark:text-slate-400">
                    کار انتخابی: <strong class="text-slate-800 dark:text-slate-200" x-text="reviewTask?.title"></strong>
                </p>

                <form :action="reviewUrl" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="action" :value="reviewAction">

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">توضیحات سرپرست (اختیاری):</label>
                        <textarea name="comments" x-model="reviewComments" rows="3" placeholder="اگر نکته یا توضیحی دارید، اینجا بنویسید..." class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="reviewModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200">
                            انصراف
                        </button>
                        <button type="submit" :class="reviewAction === 'APPROVE' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'" class="px-5 py-2 rounded-xl text-xs font-bold text-white shadow-xs transition-all">
                            ثبت نظر
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- Quick Status Update Modal                  -->
        <!-- ========================================== -->
        <div x-show="statusModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;" x-transition>
            <div @click.away="statusModalOpen = false" class="bg-white dark:bg-slate-900 rounded-3xl p-6 max-w-md w-full border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="font-bold text-slate-900 dark:text-white text-base">
                        <span>تغییر وضعیت کار</span>
                    </h3>
                    <button type="button" @click="statusModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">&times;</button>
                </div>

                <p class="text-xs text-slate-500 dark:text-slate-400">
                    عنوان کار: <strong class="text-slate-800 dark:text-slate-200" x-text="statusTask?.title"></strong>
                </p>

                <form :action="statusUrl" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">انتخاب وضعیت جدید:</label>
                        <select name="status" x-model="statusValue" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach(\App\Enums\TaskStatus::cases() as $st)
                                <option value="{{ $st->value }}">{{ $st->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">توضیحات (اختیاری):</label>
                        <textarea name="comments" x-model="statusComments" rows="2" placeholder="توضیح مختصر درباره تغییر وضعیت..." class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="statusModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200">
                            انصراف
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-xs transition-all">
                            ذخیره وضعیت
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
