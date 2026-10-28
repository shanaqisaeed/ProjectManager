<x-app-layout title="{{ $project->title }} ({{ $project->project_key }})" header="جزئیات پروژه">
    @php
        $activeTab = request()->query('tab', 'overview');
        $projectMemberUsers = $project->members->whereIn('status.value', ['ACCEPTED', 'INVITED'])->pluck('user')->filter()->unique('id');
    @endphp

    <div class="space-y-6" x-data="{ 
        activeTab: '{{ $activeTab }}',
        setTab(t) {
            const u = new URL(window.location.href);
            u.searchParams.set('tab', t);
            window.location.href = u.toString();
        }
    }">
        <!-- Project Header Card -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="px-3 py-1 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 text-xs font-mono font-bold border border-indigo-200 dark:border-indigo-800">
                        {{ $project->project_key }}
                    </span>
                    <h2 class="text-2xl font-black text-slate-800 dark:text-slate-100">{{ $project->title }}</h2>
                    
                    @if(isset($project->priority))
                        <span class="px-3 py-1 rounded-full text-xs font-bold shadow-sm {{ $project->priority->badgeClass() }}">
                            {{ $project->priority->label() }}
                        </span>
                    @endif

                    <span class="px-3 py-1 rounded-full text-xs font-semibold border
                        @if($project->status->value === 'ACTIVE') bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800
                        @elseif($project->status->value === 'ON_HOLD') bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800
                        @elseif($project->status->value === 'COMPLETED') bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800
                        @elseif($project->status->value === 'ARCHIVED') bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700
                        @else bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800 @endif">
                        {{ $project->status->label() }}
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
                    <span>واحد سازمانی: <strong class="text-slate-700 dark:text-slate-300 font-semibold">{{ $project->organizationNode?->name }}</strong></span>
                    <span>ایجادکننده: <strong class="text-slate-700 dark:text-slate-300 font-semibold">{{ $project->creator?->full_name }}</strong></span>
                    @php $ancestors = $project->getAncestors(); @endphp
                    @if($ancestors->isNotEmpty())
                        <div class="flex items-center gap-1 text-xs">
                            <span>پروژه‌های بالادست:</span>
                            @foreach($ancestors as $anc)
                                <a href="{{ route('projects.show', $anc) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-bold">
                                    {{ $anc->title }}
                                </a>
                                <span class="text-slate-400 font-mono">&larr;</span>
                            @endforeach
                        </div>
                    @elseif($project->parent)
                        <span>پروژه اصلی: <a href="{{ route('projects.show', $project->parent) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-semibold">{{ $project->parent->title }}</a></span>
                    @endif
                </div>
            </div>

            <!-- Header Action Buttons -->
            <div class="flex items-center gap-3">
                <a href="{{ route('projects.gantt', $project) }}" class="px-4 py-2 rounded-xl text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 border border-emerald-200 dark:border-emerald-800 text-xs font-bold transition-all flex items-center gap-2">
                    <span>📈 نمودار گانت</span>
                </a>

                @if($project->status->value === 'COMPLETED')
                    <span class="px-4 py-2 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 text-xs font-bold flex items-center gap-2">
                        🔒 این پروژه تکمیل شده است
                    </span>
                @else
                    @can('update', $project)
                        <a href="{{ route('projects.edit', $project) }}" class="px-4 py-2 rounded-xl text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 border border-indigo-200 dark:border-indigo-800 text-xs font-bold transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            <span>ویرایش پروژه</span>
                        </a>
                    @endcan

                    @if($project->status->value !== 'ARCHIVED')
                        @can('archive', $project)
                            <form action="{{ route('projects.archive', $project) }}" method="POST" onsubmit="return confirm('آیا می‌خواهید این پروژه را آرشیو کنید؟');">
                                @csrf
                                <button type="submit" class="px-4 py-2 rounded-xl text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/60 hover:bg-amber-100 dark:hover:bg-amber-900/60 border border-amber-200 dark:border-amber-800 text-xs font-bold transition-all">
                                    آرشیو پروژه
                                </button>
                            </form>
                        @endcan
                    @else
                        @can('archive', $project)
                            <form action="{{ route('projects.unarchive', $project) }}" method="POST" onsubmit="return confirm('آیا می‌خواهید این پروژه از حالت آرشیو خارج شود؟');">
                                @csrf
                                <button type="submit" class="px-4 py-2 rounded-xl text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 border border-emerald-200 dark:border-emerald-800 text-xs font-bold transition-all">
                                    خروج از آرشیو
                                </button>
                            </form>
                        @endcan
                        @can('delete', $project)
                            <form action="{{ route('projects.destroy', $project) }}" method="POST" onsubmit="return confirm('این پروژه آرشیو شده است. آیا از حذف کامل آن مطمئن هستید؟');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-4 py-2 rounded-xl text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 dark:hover:bg-rose-900/60 border border-rose-200 dark:border-rose-800 text-xs font-bold transition-all">
                                    حذف نهایی
                                </button>
                            </form>
                        @endcan
                    @endif
                @endif
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="border-b border-slate-200 dark:border-slate-800 flex items-center gap-6 text-sm font-bold overflow-x-auto scrollbar-none">
            <!-- نمای کلی -->
            <a href="{{ route('projects.show', [$project, 'tab' => 'overview']) }}" class="py-3 whitespace-nowrap shrink-0 {{ $activeTab === 'overview' ? 'border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400' }}">
                نمای کلی
            </a>

            <!-- کارها -->
            <a href="{{ route('projects.show', [$project, 'tab' => 'tasks']) }}" class="py-3 whitespace-nowrap shrink-0 {{ $activeTab === 'tasks' ? 'border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400' }}">
                کارها ({{ $taskMetrics['total'] }})
            </a>

            <!-- مراحل تایید -->
            @if($canManageApprovalStages)
                <a href="{{ route('projects.show', [$project, 'tab' => 'approval_stages']) }}" class="py-3 whitespace-nowrap shrink-0 {{ $activeTab === 'approval_stages' ? 'border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400' }}">
                    مراحل تایید ({{ $project->approvalStages->count() }})
                </a>
            @endif

            <!-- زیرپروژه‌ها -->
            <a href="{{ route('projects.show', [$project, 'tab' => 'subprojects']) }}" class="py-3 whitespace-nowrap shrink-0 {{ $activeTab === 'subprojects' ? 'border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400' }}">
                زیرپروژه‌ها ({{ count($descendantIds) }})
            </a>

            <!-- اعضای تیم -->
            @if($canManageMembers)
                <a href="{{ route('projects.show', [$project, 'tab' => 'members']) }}" class="py-3 whitespace-nowrap shrink-0 {{ $activeTab === 'members' ? 'border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400' }}">
                    اعضای تیم ({{ $project->members->count() }})
                </a>
            @endif

            <!-- دسترسی‌ها -->
            @if($canManagePermissions)
                <a href="{{ route('projects.show', [$project, 'tab' => 'permissions']) }}" class="py-3 whitespace-nowrap shrink-0 {{ $activeTab === 'permissions' ? 'border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400' }}">
                    دسترسی‌ها
                </a>
            @endif

            <!-- الگوی آماده -->
            @if($canManageTemplates)
                <a href="{{ route('projects.show', [$project, 'tab' => 'template']) }}" class="py-3 whitespace-nowrap shrink-0 {{ $activeTab === 'template' ? 'border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400' }}">
                    <span>الگوی آماده</span>
                </a>
            @endif
            
    </div>

        <!-- TAB 1: OVERVIEW DASHBOARD -->
        <div x-show="activeTab === 'overview'" class="space-y-6">
            @if($project->parent)
                <!-- Main Parent Project Link Banner -->
                <div class="p-4 rounded-3xl bg-indigo-50/80 dark:bg-indigo-950/50 border border-indigo-200 dark:border-indigo-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-bold shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400">این پروژه زیرمجموعه است. پروژه اصلی:</p>
                            <a href="{{ route('projects.show', $project->parent) }}" class="text-sm font-black text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-300 hover:underline">
                                {{ $project->parent->title }} ({{ $project->parent->project_key }})
                            </a>
                        </div>
                    </div>
                    <a href="{{ route('projects.show', $project->parent) }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-all shrink-0 flex items-center gap-1.5">
                        <span>مشاهده پروژه اصلی</span>
                        <svg class="w-4 h-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>
            @endif

            <!-- Hero Metric Section: Timeline Progress & Status -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- HERO CARD: Status & Timeline Progress (Spans 2 columns) -->
                <div class="lg:col-span-2 relative overflow-hidden p-6 sm:p-7 rounded-3xl bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white shadow-xl border border-indigo-900/40 flex flex-col justify-between space-y-6">
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-emerald-400 animate-ping"></span>
                                <h3 class="text-xs font-bold text-indigo-300 uppercase tracking-wider">شاخص کلیدی وضعیت و پیشرفت زمان‌بندی</h3>
                            </div>
                            <div class="flex items-center gap-2">
                                @if(isset($project->priority))
                                    <span class="px-3 py-1 rounded-full text-xs font-bold shadow-sm {{ $project->priority->badgeClass() }}">
                                        {{ $project->priority->label() }}
                                    </span>
                                @endif
                                <span class="px-3 py-1 rounded-full text-xs font-bold border border-indigo-400/30 bg-indigo-950/80 text-indigo-200">
                                    {{ $project->status->label() }}
                                </span>
                            </div>
                        </div>

                        @if($alert = $project->deadline_alert)
                            <div class="p-3 rounded-2xl border text-xs font-bold shadow-xs flex items-center gap-2 {{ $alert['class'] }}">
                                <span>{{ $alert['label'] }}</span>
                            </div>
                        @endif

                        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pt-2">
                            <div>
                                <p class="text-xs text-slate-400 font-medium">پیشرفت زمانی پروژه</p>
                                <div class="flex items-baseline gap-2 mt-1">
                                    <span class="text-4xl sm:text-5xl font-black font-mono text-white">{{ $project->timeline_progress }}%</span>
                                    <span class="text-xs text-indigo-300 font-semibold">از کل بازه زمانی</span>
                                </div>
                            </div>

                            @if($project->deadline)
                                @php
                                    $daysRemaining = now()->startOfDay()->diffInDays($project->deadline->startOfDay(), false);
                                @endphp
                                <div class="px-4 py-2 rounded-2xl bg-white/10 border border-white/10 backdrop-blur-md text-left">
                                    <span class="text-[10px] text-slate-300 block font-medium">زمان مانده تا مهلت تحویل:</span>
                                    <span class="text-sm font-bold font-mono text-emerald-300">
                                        @if($daysRemaining > 0)
                                            {{ $daysRemaining }} روز مانده
                                        @elseif($daysRemaining === 0)
                                            امروز آخرین مهلت
                                        @else
                                            {{ abs($daysRemaining) }} روز تاخیر
                                        @endif
                                    </span>
                                </div>
                            @endif
                        </div>

                        <!-- Progress Bar -->
                        <div class="space-y-1.5 pt-2">
                            <div class="w-full h-3 rounded-full bg-slate-800/80 overflow-hidden border border-slate-700/50 p-0.5">
                                <div class="h-full bg-gradient-to-r from-indigo-500 via-emerald-400 to-emerald-500 rounded-full transition-all duration-700 shadow-sm" style="width: {{ min(100, $project->timeline_progress) }}%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-slate-300 font-mono pt-1">
                                <span>تاریخ شروع: {{ $project->start_date ? verta($project->start_date)->format('Y/m/d') : '-' }}</span>
                                <span>تاریخ مهلت: {{ $project->deadline ? verta($project->deadline)->format('Y/m/d') : '-' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="absolute -left-12 -bottom-12 w-48 h-48 bg-indigo-500/10 rounded-full blur-2xl pointer-events-none"></div>
                </div>

                <!-- Secondary Cards Stack -->
                <div class="space-y-4 flex flex-col justify-between">
                    <!-- Card: Organization Scope -->
                    <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between flex-1">
                        <div class="space-y-1">
                            <p class="text-xs font-semibold text-slate-400">واحد سازمانی</p>
                            <p class="text-base font-bold text-slate-800 dark:text-slate-100">
                                {{ $project->organizationNode?->name ?? 'تعیین نشده' }}
                            </p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h5m-5 0V10m0 0V5"/></svg>
                        </div>
                    </div>

                    <!-- Card: Task Approval Stages -->
                    <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between flex-1">
                        <div class="space-y-1">
                            <p class="text-xs font-semibold text-slate-400">مراحل تایید</p>
                            <p class="text-base font-bold text-slate-800 dark:text-slate-100">
                                {{ $project->approvalStages->count() }} مرحله تایید سرپرست
                            </p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 flex items-center justify-center font-bold">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comprehensive Overview Details -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column (2 Cols): Description & Cooperation Titles Breakdown -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Description & Goals -->
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-3">
                        <h3 class="font-bold text-slate-800 dark:text-slate-100 text-sm flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>توضیحات پروژه</span>
                        </h3>
                        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-line">
                            {{ $project->description ?: 'توضیحات تکمیلی برای این پروژه ثبت نشده است.' }}
                        </p>
                    </div>

                    <!-- Cooperation Titles & Teams Breakdown -->
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-slate-800 dark:text-slate-100 text-sm flex items-center gap-2">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                <span>تیم و اعضای پروژه</span>
                            </h3>
                            <span class="text-xs text-slate-400 font-medium">اعضای فعال در پروژه</span>
                        </div>

                        @php
                            $cooperationGroups = $project->members->groupBy(fn($m) => $m->cooperation_title ?: 'بدون عنوان همکاری مشخص');
                        @endphp

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach($cooperationGroups as $title => $members)
                                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-xs text-slate-800 dark:text-slate-100 flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                            <span>{{ $title }}</span>
                                        </span>
                                        <span class="px-2 py-0.5 rounded-lg bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-bold text-[10px]">
                                            {{ $members->count() }} نفر
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap gap-1 pt-1">
                                        @foreach($members as $m)
                                            <span class="px-2 py-0.5 rounded-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-[11px]">
                                                {{ $m->user?->full_name }} ({{ $m->project_role->label() }})
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Right Column (1 Col): Approval Policy & Subprojects -->
                <div class="space-y-6">
                    <!-- Task Approval Policy Box -->
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-4">
                        <h3 class="font-bold text-slate-800 dark:text-slate-100 text-sm flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>مراحل تایید ناظران در وظیفه‌ها</span>
                        </h3>

                        @if($project->approvalStages->isEmpty())
                            <p class="text-xs text-slate-400 italic">مرحله تایید ناظری تعریف نشده است.</p>
                        @else
                            <div class="space-y-2">
                                @foreach($project->approvalStages as $stg)
                                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-2">
                                            <span class="w-6 h-6 rounded-lg bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-bold flex items-center justify-center text-[11px]">
                                                {{ $stg->stage_number }}
                                            </span>
                                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ $stg->stage_name }}</span>
                                        </div>
                                        <span class="text-[10px] text-slate-400">زیرساخت تایید وظیفه</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Subprojects Summary Widget -->
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-slate-800 dark:text-slate-100 text-sm flex items-center gap-2">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                <span>زیرپروژه‌ها</span>
                            </h3>
                            <span class="px-2.5 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 font-bold text-xs">
                                {{ $project->children->count() }} زیرپروژه
                            </span>
                        </div>

                        @if($project->children->isEmpty())
                            <p class="text-xs text-slate-400 italic">بدون زیرپروژه مستقیم.</p>
                        @else
                            <div class="space-y-2">
                                @foreach($project->children->take(4) as $child)
                                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                                        <a href="{{ route('projects.show', $child) }}" class="font-bold text-slate-800 dark:text-slate-200 hover:text-indigo-600 truncate">
                                            {{ $child->title }}
                                        </a>
                                        <span class="px-2 py-0.5 rounded-md bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-[10px] font-mono">
                                            {{ $child->project_key }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: APPROVAL STAGES DEFINITION POLICY -->
        <div x-show="activeTab === 'approval_stages'" class="space-y-6">
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>مراحل تایید سرپرست در کارهای پروژه</span>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            می‌توانید مراحلی مثل تایید فنی یا تایید سرپرست را اضافه کنید تا در گردش کارهای پروژه اعمال شود.
                        </p>
                    </div>

                    @if(auth()->user()->isSuperAdmin() || auth()->user()->id === $project->created_by)
                        <form action="{{ route('projects.approval-stages.store', $project) }}" method="POST" class="flex items-center gap-2 shrink-0">
                            @csrf
                            <input type="text" name="stage_name" placeholder="عنوان مرحله تایید (مثلاً: بررسی فنی)..." required class="px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs w-64">
                            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold text-xs shadow-sm transition-all">
                                + افزودن مرحله جدید
                            </button>
                        </form>
                    @endif
                </div>

                @if($project->approvalStages->isEmpty())
                    <div class="p-8 text-center bg-slate-50 dark:bg-slate-800/40 rounded-2xl border border-slate-200 dark:border-slate-700 text-slate-500 text-xs">
                        هنوز مرحله تاییدی برای کارهای این پروژه اضافه نشده است. با استفاده از فرم بالا می‌توانید مرحله جدیدی ایجاد کنید.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($project->approvalStages as $stg)
                            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-xl bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 flex items-center justify-center font-bold text-xs">
                                        مرحله {{ $stg->stage_number }}
                                    </span>
                                    <div>
                                        <h4 class="font-bold text-sm text-slate-800 dark:text-slate-100">{{ $stg->stage_name }}</h4>
                                        <p class="text-[11px] text-slate-400 mt-0.5">مرحله تایید سرپرست</p>
                                    </div>
                                </div>

                                @if(auth()->user()->isSuperAdmin() || auth()->user()->id === $project->created_by)
                                    <form action="{{ route('projects.approval-stages.destroy', [$project, $stg]) }}" method="POST" onsubmit="return confirm('آیا از حذف این مرحله تایید اطمینان دارید؟');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 hover:bg-rose-100 text-xs font-bold transition-colors flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            <span>حذف مرحله</span>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- TAB 2: RECURSIVE SUBPROJECTS TREE -->
        <div x-show="activeTab === 'subprojects'" class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">درخت کامل زیرپروژه‌ها</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">نمایش کلیه زیرپروژه‌ها و تمامی سطوح زیرمجموعه مربوط به {{ $project->title }}</p>
                </div>
                <a href="{{ route('projects.create', ['parent_id' => $project->id]) }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>افزودن زیرپروژه جدید</span>
                </a>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                @if($project->childrenRecursive->isEmpty())
                    <p class="text-xs text-slate-500 dark:text-slate-400 text-center py-8">هیچ زیرپروژه‌ای برای این پروژه ثبت نشده است.</p>
                @else
                    <x-projects.subproject-tree :subprojects="$project->childrenRecursive" />
                @endif
            </div>
        </div>

        <!-- TAB 3: MEMBERS -->
        <div x-show="activeTab === 'members'" class="space-y-6">
            <!-- Add Member Form -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">افزودن عضو جدید به پروژه</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            @if(auth()->user()->isSuperAdmin())
                                <span class="text-indigo-600 dark:text-indigo-400 font-semibold">لیست کاربران فعال سیستم</span>
                            @else
                                <span class="text-slate-600 dark:text-slate-400 font-semibold">کاربران واحدهای تحت مدیریت شما</span>
                            @endif
                        </p>
                    </div>
                </div>

                <form action="{{ route('projects.members.store', $project) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end pt-2">
                    @csrf
                    <div class="space-y-1 relative" x-data="{
                        search: '',
                        selectedId: '',
                        selectedUser: null,
                        open: false,
                        allUsers: {{ json_encode($users->map(fn($u) => ['id' => $u->id, 'name' => $u->display_title, 'username' => $u->username])) }},
                        get filteredUsers() {
                            if (!this.search.trim()) return this.allUsers.slice(0, 10);
                            const q = this.search.toLowerCase();
                            return this.allUsers.filter(u => 
                                u.name.toLowerCase().includes(q) || 
                                (u.username && u.username.toLowerCase().includes(q))
                            ).slice(0, 15);
                        },
                        selectUser(u) {
                            this.selectedId = u.id;
                            this.selectedUser = u;
                            this.search = u.name + ' (@' + u.username + ')';
                            this.open = false;
                        },
                        clear() {
                            this.selectedId = '';
                            this.selectedUser = null;
                            this.search = '';
                            this.open = true;
                        }
                    }">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">جستجو و انتخاب کاربر (نام یا یوزرنیم) <span class="text-rose-500">*</span></label>
                        <input type="hidden" name="user_id" :value="selectedId" required>
                        
                        <div class="relative">
                            <input type="text"
                                   x-model="search"
                                   @focus="open = true"
                                   @click="open = true"
                                   @keydown.escape="open = false"
                                   placeholder="تایپ نام یا یوزرنیم..."
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs font-medium focus:ring-2 focus:ring-indigo-500">
                                   
                            <template x-if="selectedId">
                                <button type="button" @click="clear()" class="absolute left-2.5 top-2.5 text-slate-400 hover:text-rose-500 text-xs font-bold bg-slate-200 dark:bg-slate-700 rounded-full w-5 h-5 flex items-center justify-center">
                                    ✕
                                </button>
                            </template>
                        </div>

                        <!-- Dropdown Suggestions Menu -->
                        <div x-show="open && filteredUsers.length > 0" 
                             @click.away="open = false" 
                             x-cloak 
                             class="absolute z-30 left-0 right-0 top-full mt-1 max-h-56 overflow-y-auto rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xl divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                            <template x-for="u in filteredUsers" :key="u.id">
                                <div @click="selectUser(u)" 
                                     class="p-3 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 cursor-pointer flex items-center justify-between transition-colors">
                                    <div>
                                        <strong class="font-bold text-slate-800 dark:text-slate-100 block" x-text="u.name"></strong>
                                        <span class="text-[10px] text-slate-400 font-mono" dir="ltr" x-text="'@' + u.username"></span>
                                    </div>
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-bold border border-indigo-200 dark:border-indigo-800">انتخاب</span>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">نقش در پروژه <span class="text-rose-500">*</span></label>
                        <select name="project_role" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs font-medium focus:ring-2 focus:ring-indigo-500">
                            @foreach($projectMemberRoles as $r)
                                <option value="{{ $r->value }}">{{ $r->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">تیم / عنوان همکاری (اختیاری)</label>
                        <input type="text" name="cooperation_title" placeholder="مثلاً: تیم فنی یا تیم طراحی" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs">
                    </div>
                    <div class="sm:col-span-3 flex justify-end">
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                            <span>افزودن عضو</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Active Members Table -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-4">
                <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">اعضای پروژه</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500">
                                <th class="pb-3">کاربر و عنوان</th>
                                <th class="pb-3">تیم / گروه</th>
                                <th class="pb-3">نقش در پروژه</th>
                                <th class="pb-3">وضعیت عضویت</th>
                                <th class="pb-3">تاریخ عضویت</th>
                                <th class="pb-3 text-center">عملیات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($project->members->whereIn('status.value', ['ACCEPTED', 'INVITED']) as $mb)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="py-3.5">
                                        <div class="font-bold text-slate-800 dark:text-slate-100">{{ $mb->user?->display_title }}</div>
                                        <div class="text-[11px] text-slate-400 font-mono" dir="ltr">@ {{ $mb->user?->username }}</div>
                                    </td>
                                    <td class="py-3.5 text-xs font-medium">
                                        @if($mb->cooperation_title)
                                            <span class="px-2.5 py-1 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 font-bold border border-amber-200 dark:border-amber-800">
                                                {{ $mb->cooperation_title }}
                                            </span>
                                        @else
                                            <span class="text-slate-400 text-[11px]">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                            {{ $mb->project_role->label() }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 text-xs font-bold">
                                        @if($mb->status->value === 'ACCEPTED')
                                            <span class="text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                <span>فعال</span>
                                            </span>
                                        @else
                                            <span class="text-amber-600 dark:text-amber-400 flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                                <span>در انتظار پذیرش</span>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 text-xs text-slate-500 font-mono">
                                        {{ $mb->joined_at ? verta($mb->joined_at)->format('Y/m/d') : '-' }}
                                    </td>
                                    <td class="py-3.5 text-center">
                                        <form action="{{ route('projects.members.destroy', [$project, $mb]) }}" method="POST" onsubmit="return confirm('آیا می‌خواهید این عضو را از پروژه حذف کنید؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-rose-600 hover:text-rose-700 dark:text-rose-400 font-bold hover:underline">حذف</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 4: PERMISSIONS CHECKLIST MATRIX -->
        <div x-show="activeTab === 'permissions'" class="space-y-6">
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 sm:p-8 shadow-sm space-y-6">
                <div>
                    <h3 class="text-base font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-indigo-100 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <span>دسترسی‌های اختصاصی اعضا</span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        تنظیم دسترسی‌های مستقیم برای هریک از اعضای پروژه.
                    </p>
                </div>

                @if($projectMemberUsers->isEmpty())
                    <div class="p-8 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-center space-y-3">
                        <div class="w-12 h-12 mx-auto rounded-2xl bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <h4 class="font-bold text-amber-800 dark:text-amber-300 text-sm">هنوز عضوی در این پروژه ثبت نشده است</h4>
                        <p class="text-xs text-amber-700 dark:text-amber-400 max-w-md mx-auto leading-relaxed">
                            برای تنظیم دسترسی‌ها، ابتدا از بخش <strong>«اعضای تیم»</strong> افراد را اضافه کنید.
                        </p>
                    </div>
                @else
                    <form action="{{ route('projects.permissions.override', $project) }}" method="POST" class="space-y-6" x-data="{ 
                        selectedMemberId: '{{ $projectMemberUsers->first()?->id }}',
                        setAllRadios(val) {
                            document.querySelectorAll('input[name^=\'permissions_matrix\'][value=\'' + val + '\']').forEach(r => r.checked = true);
                        }
                    }">
                        @csrf

                        <!-- Member Selector -->
                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">انتخاب کاربر عضو پروژه: <span class="text-rose-500">*</span></label>
                            <select name="user_id" x-model="selectedMemberId" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 text-xs font-bold focus:ring-2 focus:ring-indigo-500">
                                @foreach($projectMemberUsers as $mUser)
                                    <option value="{{ $mUser->id }}">{{ $mUser->display_title }} (@ {{ $mUser->username }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Permissions Matrix Checklist -->
                        <div class="space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <h4 class="font-bold text-sm text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    <span>لیست دسترسی‌ها</span>
                                </h4>

                                <!-- Bulk Radio Setter -->
                                <div class="flex items-center gap-2 text-xs">
                                    <button type="button" @click="setAllRadios('ALLOW')" class="px-2.5 py-1 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 font-bold border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 transition-all">
                                        همه: مجاز
                                    </button>
                                    <button type="button" @click="setAllRadios('DENY')" class="px-2.5 py-1 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 font-bold border border-rose-200 dark:border-rose-800 hover:bg-rose-100 transition-all">
                                        همه: ممنوع
                                    </button>
                                    <button type="button" @click="setAllRadios('DEFAULT')" class="px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold border border-slate-200 dark:border-slate-700 hover:bg-slate-200 transition-all">
                                        همه: پیش‌فرض
                                    </button>
                                </div>
                            </div>

                            <div class="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach($projectPermissions as $perm)
                                    <div class="p-4 bg-white dark:bg-slate-900 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                        <div class="space-y-0.5">
                                            <p class="font-bold text-xs text-slate-800 dark:text-slate-100">{{ $perm->label() }}</p>
                                            <p class="text-[11px] text-slate-400 font-mono" dir="ltr">{{ $perm->value }}</p>
                                        </div>

                                        <div class="flex items-center gap-2 shrink-0">
                                            <!-- ALLOW -->
                                            <label class="cursor-pointer px-3 py-1.5 rounded-xl border text-xs font-bold transition-all flex items-center gap-1.5 hover:bg-emerald-50 dark:hover:bg-emerald-950/60">
                                                <input type="radio" name="permissions_matrix[{{ $perm->value }}]" value="ALLOW" class="text-emerald-600 focus:ring-emerald-500">
                                                <span class="text-emerald-700 dark:text-emerald-300">مجاز</span>
                                            </label>

                                            <!-- DENY -->
                                            <label class="cursor-pointer px-3 py-1.5 rounded-xl border text-xs font-bold transition-all flex items-center gap-1.5 hover:bg-rose-50 dark:hover:bg-rose-950/60">
                                                <input type="radio" name="permissions_matrix[{{ $perm->value }}]" value="DENY" class="text-rose-600 focus:ring-rose-500">
                                                <span class="text-rose-700 dark:text-rose-300">ممنوع</span>
                                            </label>

                                            <!-- DEFAULT -->
                                            <label class="cursor-pointer px-3 py-1.5 rounded-xl border text-xs font-semibold transition-all flex items-center gap-1.5 hover:bg-slate-100 dark:hover:bg-slate-800">
                                                <input type="radio" name="permissions_matrix[{{ $perm->value }}]" value="DEFAULT" checked class="text-slate-600 focus:ring-slate-500">
                                                <span class="text-slate-600 dark:text-slate-400">پیش‌فرض</span>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="submit" class="px-7 py-3 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-xs font-bold shadow-lg shadow-indigo-500/25 transition-all transform hover:-translate-y-0.5 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>ذخیره دسترسی‌ها</span>
                            </button>
                        </div>
                    </form>
                @endif
            </div>

            <!-- Existing Overrides List Table -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-4">
                <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">دسترسی‌های اختصاصی ثبت‌شده</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-500">
                                <th class="pb-3">کاربر عضو پروژه</th>
                                <th class="pb-3">عنوان مجوز</th>
                                <th class="pb-3">وضعیت دسترسی</th>
                                <th class="pb-3 text-center">عملیات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($project->memberPermissions as $perm)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors">
                                    <td class="py-3.5 font-bold text-slate-800 dark:text-slate-200">
                                        {{ $perm->user?->display_title }}
                                    </td>
                                    <td class="py-3.5 text-xs text-slate-600 dark:text-slate-400">
                                        {{ $perm->permission->label() }}
                                    </td>
                                    <td class="py-3.5">
                                        @if($perm->access_type->value === 'ALLOW')
                                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">مجاز</span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800">ممنوع</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 text-center">
                                        <form action="{{ route('projects.permissions.destroy', [$project, $perm]) }}" method="POST" onsubmit="return confirm('آیا می‌خواهید این دسترسی اختصاصی حذف شود؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-rose-600 hover:text-rose-700 dark:text-rose-400 font-bold hover:underline">
                                                حذف
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-slate-400 text-sm">هیچ دسترسی اختصاصی برای اعضا ثبت نشده است.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 6: TASKS & ACTIVITIES -->
        @if($allProjectTasks !== null)
            <div x-show="activeTab === 'tasks'" x-cloak class="space-y-6">
                @include('projects.partials.tasks_tab')
            </div>
        @endif

        <!-- TAB 7: TEMPLATE CONVERSION -->
        @if($canManageTemplates)
            <div x-show="activeTab === 'template'" x-cloak class="space-y-6">
                <!-- Hero Banner -->
                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-indigo-950 via-slate-900 to-indigo-900 text-white p-6 sm:p-8 shadow-xl border border-indigo-800/40">
                    <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="space-y-2">
                            <h3 class="text-2xl font-black text-white">ذخیره این پروژه به عنوان الگوی آماده</h3>
                            <p class="text-slate-300 text-xs sm:text-sm max-w-2xl leading-relaxed">
                                با ذخیره این پروژه به عنوان الگوی آماده، ساختار کامل آن شامل زیرپروژه‌ها، کارها، نقش‌ها و مراحل تایید ذخیره می‌شود تا بتوانید بعداً با یک کلیک پروژه‌های مشابه بسازید.
                            </p>
                        </div>
                        <div class="shrink-0">
                            <a href="{{ route('project-templates.index') }}" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold backdrop-blur-sm border border-white/10 transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                <span>مشاهده الگوهای آماده</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Template Structure Summary Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                    <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-1">
                        <span class="text-[11px] font-bold text-slate-400">مدت زمان محاسبه‌شده</span>
                        <div class="text-xl font-black text-indigo-600 dark:text-indigo-400 font-mono">
                            {{ $project->start_date && $project->deadline ? max(1, (int)$project->start_date->diffInDays($project->deadline)) . ' روز' : '۳۰ روز' }}
                        </div>
                    </div>

                    <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-1">
                        <span class="text-[11px] font-bold text-slate-400">زیرپروژه‌های متصل</span>
                        <div class="text-xl font-black text-slate-800 dark:text-slate-100 font-mono">
                            {{ count($descendantIds) }} <span class="text-xs font-normal text-slate-400">مورد</span>
                        </div>
                    </div>

                    <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-1">
                        <span class="text-[11px] font-bold text-slate-400">کل تسک‌ها و فعالیت‌ها</span>
                        <div class="text-xl font-black text-slate-800 dark:text-slate-100 font-mono">
                            {{ $taskMetrics['total'] }} <span class="text-xs font-normal text-slate-400">تسک</span>
                        </div>
                    </div>

                    <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-1">
                        <span class="text-[11px] font-bold text-slate-400">نقش‌ها و عناوین همکاری</span>
                        <div class="text-xl font-black text-slate-800 dark:text-slate-100 font-mono">
                            {{ $project->members->count() }} <span class="text-xs font-normal text-slate-400">نقش</span>
                        </div>
                    </div>

                    <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-1">
                        <span class="text-[11px] font-bold text-slate-400">مراحل تایید ناظران</span>
                        <div class="text-xl font-black text-slate-800 dark:text-slate-100 font-mono">
                            {{ $project->approvalStages->count() }} <span class="text-xs font-normal text-slate-400">مرحله</span>
                        </div>
                    </div>
                </div>

                <!-- Template Save Form Card -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 sm:p-8 shadow-sm space-y-6">
                    <div class="border-b border-slate-100 dark:border-slate-800 pb-4">
                        <h4 class="text-lg font-black text-slate-800 dark:text-slate-100">ذخیره الگو</h4>
                        <p class="text-xs text-slate-400 mt-1">عنوان و توضیحات الگو را وارد کنید.</p>
                    </div>

                    <form action="{{ route('projects.save-as-template', $project) }}" method="POST" onsubmit="return confirm('آیا می‌خواهید این پروژه به عنوان یک الگوی آماده ذخیره شود؟');" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                    عنوان الگو <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="title" value="{{ old('title', 'الگوی: ' . $project->title) }}" required class="w-full px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 text-xs font-medium focus:ring-2 focus:ring-indigo-500">
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                    واحد سازمانی
                                </label>
                                <div class="px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-800/50 text-slate-600 dark:text-slate-400 text-xs font-medium">
                                    {{ $project->organizationNode?->name ?? 'الگوی عمومی' }}
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                توضیحات الگو (اختیاری)
                            </label>
                            <textarea name="description" rows="3" class="w-full px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 text-xs font-medium focus:ring-2 focus:ring-indigo-500" placeholder="توضیحاتی درباره کاربرد این الگو بنویسید...">{{ old('description', $project->description) }}</textarea>
                        </div>

                        <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/50 text-amber-800 dark:text-amber-300 text-xs flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <div class="space-y-1">
                                <p class="font-bold">نکات مهم هنگام ذخیره الگو:</p>
                                <ul class="list-disc list-inside text-[11px] space-y-0.5 text-amber-700 dark:text-amber-400">
                                    <li>همه زیرپروژه‌ها و کارها همراه با فاصله‌های زمانی ذخیره می‌شوند.</li>
                                    <li>فایل‌های ضمیمه و پیام‌های گفت‌وگو در الگو ذخیره نمی‌شوند.</li>
                                    <li>هنگام ساخت پروژه جدید از روی این الگو، همه کارها با وضعیت «در انتظار انجام» و تاریخ‌های متناسب با زمان شروع جدید ثبت می‌شوند.</li>
                                </ul>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button type="submit" class="px-8 py-3.5 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                                <span>ذخیره به عنوان الگوی آماده</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
