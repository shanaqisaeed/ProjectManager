<link rel="stylesheet" href="{{ asset('assets/css/persian-datepicker.min.css') }}"/>
<script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
<script src="{{ asset('assets/js/persian-date.min.js') }}"></script>
<script src="{{ asset('assets/js/persian-datepicker.min.js') }}"></script>

@php
    $currentViewMode = $taskViewMode ?? request('task_view_mode', 'tree');
    $isTreeView = $currentViewMode === 'tree' && request('task_hierarchy') !== 'subtask_only';
    $progressPercent = $taskMetrics['progress_percent'] ?? $project->tasks_progress ?? 0;
    $completedCount = $taskMetrics['completed'] ?? 0;
    $totalCount = $taskMetrics['total'] ?? 0;
@endphp

<div class="space-y-6" x-data="{
    showAdvancedFilters: {{ request()->hasAny(['task_hierarchy', 'task_executor', 'task_supervisor', 'task_due_range', 'task_sort']) ? 'true' : 'false' }},
    collapsedTasks: {},

    toggleSubtasks(taskId) {
        this.collapsedTasks[taskId] = !this.collapsedTasks[taskId];
    },
    isCollapsed(taskId) {
        return !!this.collapsedTasks[taskId];
    }
}">
    <!-- Scope & Project Type Header Banner -->
    <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                @if($project->parent_project_id)
                    <span class="px-3 py-1 rounded-xl bg-amber-50 dark:bg-amber-950/80 text-amber-700 dark:text-amber-300 text-xs font-bold border border-amber-200 dark:border-amber-800 flex items-center gap-1.5">
                        <span>📁 زیرپروژه</span>
                    </span>
                    <span class="text-xs text-slate-500">پروژه اصلی: <strong class="text-slate-800 dark:text-slate-200">{{ $project->parent?->title }}</strong></span>
                @else
                    <span class="px-3 py-1 rounded-xl bg-indigo-50 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300 text-xs font-bold border border-indigo-200 dark:border-indigo-800 flex items-center gap-1.5">
                        <span>🏢 پروژه اصلی</span>
                    </span>
                    @if(isset($subProjects) && $subProjects->isNotEmpty())
                        <span class="text-xs font-bold text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-950/60 px-2.5 py-0.5 rounded-lg border border-purple-200 dark:border-purple-800">
                            شامل {{ $subProjects->count() }} زیرپروژه
                        </span>
                    @endif
                @endif
            </div>
            <h3 class="text-lg font-black text-slate-800 dark:text-slate-100">کارهای پروژه {{ $project->title }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                مشاهده و پیگیری کارهای پروژه و زیرمجموعه‌های آن.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('projects.tasks.create', $project) }}" class="px-5 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-all shadow-md shadow-indigo-500/20 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>تعریف کار جدید</span>
            </a>
        </div>
    </div>

    <!-- Metrics Row (Accurate Progress & Breakdown) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3 sm:gap-4">
        <!-- Progress Metric -->
        <div class="col-span-2 sm:col-span-3 lg:col-span-2 p-4 sm:p-5 rounded-3xl bg-gradient-to-br from-indigo-900 via-slate-900 to-indigo-950 text-white shadow-md border border-indigo-800/40 space-y-2.5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-indigo-300">پیشرفت کل کارها</span>
                <span class="text-2xl font-black text-emerald-300 font-mono">{{ $progressPercent }}٪</span>
            </div>
            <div class="w-full bg-indigo-950/80 rounded-full h-3 overflow-hidden p-0.5 border border-indigo-800/50">
                <div class="bg-gradient-to-r from-indigo-500 via-emerald-400 to-emerald-300 h-full rounded-full transition-all duration-500 shadow-sm" style="width: {{ max(0, min(100, $progressPercent)) }}%"></div>
            </div>
            <div class="flex items-center justify-between text-[11px] text-slate-300 pt-0.5">
                <span>تکمیل‌شده: <strong class="text-emerald-300 font-bold font-mono">{{ $completedCount }}</strong></span>
                <span>کل کارها: <strong class="text-white font-bold font-mono">{{ $totalCount }}</strong></span>
            </div>
        </div>

        <!-- Total Tasks -->
        <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400">کل کارها</span>
            <span class="text-2xl font-black text-slate-800 dark:text-slate-100 font-mono">{{ $totalCount }}</span>
        </div>

        <!-- Parent Tasks -->
        <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-indigo-200 dark:border-indigo-900/60 shadow-sm flex flex-col justify-between bg-indigo-50/20 dark:bg-indigo-950/10">
            <span class="text-xs font-bold text-indigo-700 dark:text-indigo-300 flex items-center gap-1">
                <span>👑 کارهای اصلی</span>
            </span>
            <span class="text-2xl font-black text-indigo-700 dark:text-indigo-300 font-mono">{{ $taskMetrics['parent_tasks'] ?? 0 }}</span>
        </div>

        <!-- Subtasks -->
        <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-purple-200 dark:border-purple-900/60 shadow-sm flex flex-col justify-between bg-purple-50/20 dark:bg-purple-950/10">
            <span class="text-xs font-bold text-purple-700 dark:text-purple-300 flex items-center gap-1">
                <span>↳ زیرکارها</span>
            </span>
            <span class="text-2xl font-black text-purple-700 dark:text-purple-300 font-mono">{{ $taskMetrics['subtasks'] ?? 0 }}</span>
        </div>

        <!-- In Progress Tasks -->
        <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-blue-200 dark:border-blue-900/60 shadow-sm flex flex-col justify-between bg-blue-50/20 dark:bg-blue-950/10">
            <span class="text-xs font-bold text-blue-700 dark:text-blue-400">در دست اقدام</span>
            <span class="text-2xl font-black text-blue-700 dark:text-blue-400 font-mono">{{ $taskMetrics['in_progress'] ?? 0 }}</span>
        </div>

        <!-- Pending Review Tasks -->
        <div class="p-4 rounded-3xl bg-white dark:bg-slate-900 border border-amber-200 dark:border-amber-900/60 shadow-sm flex flex-col justify-between bg-amber-50/30 dark:bg-amber-950/20">
            <span class="text-xs font-bold text-amber-700 dark:text-amber-400">در انتظار تایید</span>
            <span class="text-2xl font-black text-amber-700 dark:text-amber-400 font-mono">{{ $taskMetrics['pending_review'] ?? 0 }}</span>
        </div>
    </div>

    <!-- Advanced Multi-Dimensional Filter Toolbar (Clean Server Form with Instant Submissions) -->
    <form method="GET" action="{{ route('projects.show', $project) }}" class="p-4 sm:p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
        <input type="hidden" name="tab" value="tasks">
        <input type="hidden" name="task_view_mode" value="{{ $currentViewMode }}">

        <!-- Row 1: Primary Search & Dropdowns -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2.5 flex-1 min-w-[280px]">
                
                <!-- Search Box -->
                <div class="relative flex-1 min-w-[200px] max-w-md">
                    <input type="text" 
                           name="task_search"
                           value="{{ request('task_search') }}" 
                           placeholder="جستجو در عنوان یا توضیحات کار..." 
                           class="w-full pl-9 pr-9 py-2.5 text-xs rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-medium">
                    <svg class="w-4 h-4 text-slate-400 absolute right-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                <!-- Hierarchy Filter -->
                <select name="task_hierarchy" onchange="this.form.submit()" class="py-2.5 px-3 text-xs rounded-2xl border border-indigo-200 dark:border-indigo-800 bg-indigo-50/60 dark:bg-indigo-950/40 text-indigo-900 dark:text-indigo-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-bold">
                    <option value="">👑 همه کارها</option>
                    <option value="parent_only" @if(request('task_hierarchy') === 'parent_only') selected @endif>👑 فقط کارهای اصلی</option>
                    <option value="subtask_only" @if(request('task_hierarchy') === 'subtask_only') selected @endif>↳ فقط زیرکارها</option>
                    <option value="has_subtasks" @if(request('task_hierarchy') === 'has_subtasks') selected @endif>📂 کارهای دارای زیرمجموعه</option>
                </select>

                <!-- Status Filter -->
                <select name="task_status" onchange="this.form.submit()" class="py-2.5 px-3 text-xs rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-semibold">
                    <option value="">🚦 همه وضعیت‌ها</option>
                    @foreach($taskStatuses as $st)
                        <option value="{{ $st->value }}" @if(request('task_status') === $st->value) selected @endif>{{ $st->label() }}</option>
                    @endforeach
                </select>

                <!-- Priority Filter -->
                <select name="task_priority" onchange="this.form.submit()" class="py-2.5 px-3 text-xs rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-semibold">
                    <option value="">⚡ همه اولویت‌ها</option>
                    @foreach($taskPriorities as $pr)
                        <option value="{{ $pr->value }}" @if(request('task_priority') === $pr->value) selected @endif>{{ $pr->label() }}</option>
                    @endforeach
                </select>

                <!-- Project / Subproject Filter -->
                @if(isset($subProjects) && $subProjects->isNotEmpty())
                    <select name="task_project_id" onchange="this.form.submit()" class="py-2.5 px-3 text-xs rounded-2xl border border-purple-200 dark:border-purple-800 bg-purple-50/50 dark:bg-purple-950/40 text-purple-900 dark:text-purple-200 focus:outline-none focus:ring-2 focus:ring-purple-500 font-bold">
                        <option value="">🏢 همه پروژه‌ها و زیرپروژه‌ها</option>
                        <option value="{{ $project->id }}" @if(request('task_project_id') == $project->id) selected @endif>📍 فقط این پروژه ({{ $project->title }})</option>
                        @foreach($subProjects as $sub)
                            <option value="{{ $sub->id }}" @if(request('task_project_id') == $sub->id) selected @endif>📁 زیرپروژه: {{ $sub->title }}</option>
                        @endforeach
                    </select>
                @endif

                <button type="submit" class="px-4 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-all shadow-sm">
                    فیلتر
                </button>
            </div>

            <!-- View Mode Switch & Advanced Toggle -->
            <div class="flex items-center gap-2 self-end sm:self-auto">
                <!-- View Mode Toggle (Tree vs Flat) with URL state -->
                <div class="inline-flex p-1 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold">
                    <a href="{{ route('projects.show', array_merge(['project' => $project], request()->query(), ['tab' => 'tasks', 'task_view_mode' => 'tree'])) }}"
                       class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5 {{ $isTreeView ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200' }}">
                        <span>درختی</span>
                    </a>
                    <a href="{{ route('projects.show', array_merge(['project' => $project], request()->query(), ['tab' => 'tasks', 'task_view_mode' => 'flat'])) }}"
                       class="px-3 py-1.5 rounded-xl transition-all flex items-center gap-1.5 {{ !$isTreeView ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200' }}">
                        <span>جدولی</span>
                    </a>
                </div>

                <!-- Toggle Advanced Filters Button -->
                <button type="button" 
                        @click="showAdvancedFilters = !showAdvancedFilters" 
                        :class="showAdvancedFilters ? 'bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700'"
                        class="px-3 py-2 text-xs font-bold rounded-2xl border transition-all flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                    <span>فیلترهای بیشتر</span>
                </button>
            </div>
        </div>

        <!-- Row 2: Expandable Advanced Filters -->
        <div x-show="showAdvancedFilters" x-cloak class="pt-3 border-t border-slate-100 dark:border-slate-800 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 text-xs">
            
            <!-- 1. Executor Filter -->
            <div class="space-y-1">
                <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400">👤 مسئول انجام:</label>
                <select name="task_executor" onchange="this.form.submit()" class="w-full py-2 px-3 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-medium">
                    <option value="">همه اعضا</option>
                    <option value="me" @if(request('task_executor') === 'me') selected @endif>👤 کارهای من</option>
                    <option value="unassigned" @if(request('task_executor') === 'unassigned') selected @endif>⚪ بدون مسئول</option>
                    @if(isset($projectExecutorUsers) && $projectExecutorUsers->isNotEmpty())
                        @foreach($projectExecutorUsers as $u)
                            <option value="{{ $u->id }}" @if(request('task_executor') == $u->id) selected @endif>{{ $u->display_title ?? $u->full_name }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            <!-- 2. Supervisor / Approval Filter -->
            <div class="space-y-1">
                <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400">👥 وضعیت تایید سرپرست:</label>
                <select name="task_supervisor" onchange="this.form.submit()" class="w-full py-2 px-3 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-medium">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="pending_review" @if(request('task_supervisor') === 'pending_review') selected @endif>⏳ در انتظار تایید</option>
                    <option value="needs_revision" @if(request('task_supervisor') === 'needs_revision') selected @endif>🚨 نیاز به اصلاح</option>
                    <option value="with_supervisors" @if(request('task_supervisor') === 'with_supervisors') selected @endif>👥 دارای مرحله تایید</option>
                    <option value="no_supervisors" @if(request('task_supervisor') === 'no_supervisors') selected @endif>⚡ بدون تایید سرپرست</option>
                </select>
            </div>

            <!-- 3. Due Date Filter -->
            <div class="space-y-1">
                <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400">📅 مهلت انجام:</label>
                <select name="task_due_range" onchange="this.form.submit()" class="w-full py-2 px-3 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-medium">
                    <option value="">همه زمان‌ها</option>
                    <option value="overdue" @if(request('task_due_range') === 'overdue') selected @endif>🚨 دارای تاخیر</option>
                    <option value="today" @if(request('task_due_range') === 'today') selected @endif>⏰ مهلت امروز</option>
                    <option value="this_week" @if(request('task_due_range') === 'this_week') selected @endif>⚡ تا ۷ روز آینده</option>
                    <option value="no_due_date" @if(request('task_due_range') === 'no_due_date') selected @endif>⚪ بدون مهلت</option>
                </select>
            </div>

            <!-- 4. Sorting -->
            <div class="space-y-1">
                <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400">🔄 مرتب‌سازی:</label>
                <select name="task_sort" onchange="this.form.submit()" class="w-full py-2 px-3 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-medium">
                    <option value="smart" @if(request('task_sort', 'smart') === 'smart') selected @endif>🎯 خودکار (تاخیر > اولویت > مهلت)</option>
                    <option value="newest" @if(request('task_sort') === 'newest') selected @endif>جدیدترین</option>
                    <option value="oldest" @if(request('task_sort') === 'oldest') selected @endif>قدیمی‌ترین</option>
                    <option value="due_date_asc" @if(request('task_sort') === 'due_date_asc') selected @endif>نزدیک‌ترین مهلت</option>
                    <option value="priority_desc" @if(request('task_sort') === 'priority_desc') selected @endif>بیشترین اولویت</option>
                    <option value="title_asc" @if(request('task_sort') === 'title_asc') selected @endif>عنوان (الفبا)</option>
                </select>
            </div>

            <!-- 5. Reset Filters Action -->
            <div class="flex items-end">
                <a href="{{ route('projects.show', ['project' => $project, 'tab' => 'tasks', 'task_view_mode' => $currentViewMode]) }}"
                   class="w-full py-2 px-3 text-xs font-bold rounded-xl border border-rose-200 dark:border-rose-900/60 bg-rose-50/50 dark:bg-rose-950/30 text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/50 transition-all flex items-center justify-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span>حذف فیلترها</span>
                </a>
            </div>
        </div>
    </form>

    <!-- Tasks Main Listing Container -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        @if(!$allProjectTasks || $allProjectTasks->isEmpty())
            <div class="p-12 text-center space-y-3">
                <div class="w-16 h-16 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 mx-auto flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <h4 class="text-base font-bold text-slate-800 dark:text-slate-200">هیچ کاری با فیلترهای انتخاب‌شده پیدا نشد</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto">می‌توانید فیلترها را تغییر دهید یا کار جدیدی تعریف کنید.</p>
                <a href="{{ route('projects.tasks.create', $project) }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-all inline-flex items-center gap-2">
                    <span>+ تعریف کار جدید</span>
                </a>
            </div>
        @else

            <!-- ========================================== -->
            <!-- MODE 1: NESTED TREE HIERARCHY VIEW (TREE) -->
            <!-- ========================================== -->
            @if($isTreeView)
                <div class="divide-y divide-slate-200 dark:divide-slate-800">
                    @foreach($allProjectTasks as $parentTask)
                        @php
                            $subtasks = $parentTask->subtasks ?? collect();
                            $hasSubtasks = $subtasks->isNotEmpty();
                            $completedSubtasksCount = $subtasks->filter(fn($st) => ($st->status instanceof \App\Enums\TaskStatus ? $st->status->value : $st->status) === 'COMPLETED')->count();
                            $totalSubtasksCount = $subtasks->count();
                            $subtaskPercent = $totalSubtasksCount > 0 ? (int)round(($completedSubtasksCount / $totalSubtasksCount) * 100) : 0;
                            $isOverdue = $parentTask->due_date && $parentTask->status->value !== 'COMPLETED' && $parentTask->due_date->isPast();
                        @endphp

                        <div class="p-4 sm:p-5 hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors space-y-3">
                            
                            <!-- 1. PARENT TASK ROW (MAIN TASK AT TOP) -->
                            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 p-4 rounded-2xl bg-slate-50/80 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80">
                                
                                <!-- Left: Hierarchy Badge, Title, Version & Subtask Progress -->
                                <div class="space-y-2 flex-1 min-w-[280px]">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="px-2.5 py-0.5 rounded-lg bg-indigo-600 text-white font-black text-[11px] shadow-xs flex items-center gap-1 shrink-0">
                                            <span>👑 کار اصلی</span>
                                        </span>

                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $parentTask->status->badgeClass() }}">
                                            {{ $parentTask->status->label() }}
                                        </span>

                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $parentTask->priority->badgeClass() }}">
                                            {{ $parentTask->priority->label() }}
                                        </span>

                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                            ⚖️ وزن: {{ $parentTask->weight ?? 1 }}
                                        </span>

                                        @if($parentTask->project_id === $project->id)
                                            <span class="px-2 py-0.5 rounded-lg bg-slate-200/80 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-[10px]">
                                                🏢 پروژه اصلی
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-lg bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 font-bold text-[10px] border border-purple-200 dark:border-purple-800">
                                                📁 {{ $parentTask->project?->title }}
                                            </span>
                                        @endif

                                        @if($parentTask->version)
                                            <span class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-mono text-[10px] font-bold">
                                                {{ $parentTask->version }}
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Title & Actions -->
                                    <div class="flex items-start justify-between gap-3">
                                        <a href="{{ route('projects.tasks.show', [$project, $parentTask]) }}" class="text-base font-black text-slate-900 dark:text-slate-100 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                            {{ $parentTask->title }}
                                        </a>
                                    </div>

                                    <!-- Subtasks Counter & Progress Pill -->
                                    @if($hasSubtasks)
                                        <div class="flex flex-wrap items-center gap-3 pt-1">
                                            <button type="button" 
                                                    @click="toggleSubtasks({{ $parentTask->id }})"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-indigo-100 dark:bg-indigo-950 text-indigo-800 dark:text-indigo-200 font-bold text-xs hover:bg-indigo-200 dark:hover:bg-indigo-900 transition-all border border-indigo-200 dark:border-indigo-800">
                                                <span x-show="!isCollapsed({{ $parentTask->id }})">▼</span>
                                                <span x-show="isCollapsed({{ $parentTask->id }})">▶</span>
                                                <span>{{ $totalSubtasksCount }} زیرکار</span>
                                                <span class="text-[10px] font-mono text-indigo-600 dark:text-indigo-400">({{ $completedSubtasksCount }} انجام‌شده)</span>
                                            </button>

                                            <div class="w-32 bg-slate-200 dark:bg-slate-700 rounded-full h-2 overflow-hidden">
                                                <div class="bg-indigo-600 h-full rounded-full transition-all" style="width: {{ $subtaskPercent }}%"></div>
                                            </div>
                                            <span class="text-[11px] font-mono font-bold text-slate-500">{{ $subtaskPercent }}٪</span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Right: Meta details (Executor, Dates, Actions) -->
                                <div class="flex flex-wrap items-center gap-4 text-xs shrink-0">
                                    <div class="space-y-1 text-right">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-slate-400 font-bold text-[11px]">👤 مسئول:</span>
                                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ $parentTask->executor?->full_name ?? 'بدون مسئول' }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-slate-400 font-bold text-[11px]">🏁 مهلت:</span>
                                            <span class="font-mono font-bold {{ $isOverdue ? 'text-rose-600' : 'text-slate-700 dark:text-slate-300' }}">
                                                {{ $parentTask->due_date ? verta($parentTask->due_date)->format('Y/m/d') : 'بدون مهلت' }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('projects.tasks.create', ['project' => $project, 'parent_id' => $parentTask->id]) }}" 
                                           title="تعریف زیرکار جدید"
                                           class="px-3 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-bold text-xs transition-all border border-indigo-200 dark:border-indigo-800 flex items-center gap-1">
                                            <span>+ افزودن زیرکار</span>
                                        </a>

                                        <a href="{{ route('projects.tasks.show', [$project, $parentTask]) }}" 
                                           class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs transition-all shadow-sm flex items-center gap-1.5">
                                            <span>مشاهده</span>
                                            <svg class="w-3.5 h-3.5 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. NESTED SUBTASKS SECTION (DIRECTLY UNDER THE PARENT TASK) -->
                            @if($hasSubtasks)
                                <div x-show="!isCollapsed({{ $parentTask->id }})" class="mr-4 sm:mr-8 space-y-2 border-r-2 border-dashed border-indigo-300 dark:border-indigo-800/60 pr-3 sm:pr-4 pt-1">
                                    <div class="text-[11px] font-bold text-indigo-700 dark:text-indigo-400 pb-1 flex items-center gap-1.5">
                                        <span>↳ زیرکارهای «{{ $parentTask->title }}» ({{ $totalSubtasksCount }} کار):</span>
                                    </div>

                                    @foreach($subtasks as $subtask)
                                        @php
                                            $isSubtaskOverdue = $subtask->due_date && $subtask->status->value !== 'COMPLETED' && $subtask->due_date->isPast();
                                        @endphp
                                        <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-900 border-r-4 border-r-indigo-500 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3 hover:bg-indigo-50/20 transition-all">
                                            
                                            <!-- Subtask Title & Badges -->
                                            <div class="space-y-1 flex-1 min-w-[240px]">
                                                <div class="flex flex-wrap items-center gap-1.5">
                                                    <span class="text-indigo-600 font-mono font-bold text-xs">└── ↳</span>
                                                    <span class="px-2 py-0.5 rounded-md bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 font-bold text-[10px] border border-purple-200 dark:border-purple-800">
                                                        زیرکار
                                                    </span>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $subtask->status->badgeClass() }}">
                                                        {{ $subtask->status->label() }}
                                                    </span>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $subtask->priority->badgeClass() }}">
                                                        {{ $subtask->priority->label() }}
                                                    </span>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                                        ⚖️ وزن: {{ $subtask->weight ?? 1 }}
                                                    </span>
                                                </div>

                                                <a href="{{ route('projects.tasks.show', [$project, $subtask]) }}" class="font-bold text-slate-800 dark:text-slate-100 hover:text-indigo-600 dark:hover:text-indigo-400 text-sm block">
                                                    {{ $subtask->title }}
                                                </a>
                                            </div>

                                            <!-- Subtask Meta & Actions -->
                                            <div class="flex flex-wrap items-center gap-3 text-xs shrink-0">
                                                <div class="flex items-center gap-3 text-slate-600 dark:text-slate-300 text-[11px]">
                                                    <span>👤 {{ $subtask->executor?->full_name ?? 'بدون مسئول' }}</span>
                                                    <span class="font-mono {{ $isSubtaskOverdue ? 'text-rose-600 font-bold' : '' }}">
                                                        🏁 {{ $subtask->due_date ? verta($subtask->due_date)->format('Y/m/d') : '-' }}
                                                    </span>
                                                </div>

                                                <div class="flex items-center gap-1.5">
                                                    <a href="{{ route('projects.tasks.show', [$project, $subtask]) }}" 
                                                       class="px-3.5 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-600 hover:text-white dark:bg-indigo-950 dark:hover:bg-indigo-600 text-indigo-700 dark:text-indigo-300 font-bold text-xs transition-all border border-indigo-200 dark:border-indigo-800 flex items-center gap-1">
                                                        <span>مشاهده</span>
                                                        <svg class="w-3.5 h-3.5 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

            <!-- ========================================== -->
            <!-- MODE 2: FLAT TABLE / LIST VIEW (FLAT)     -->
            <!-- ========================================== -->
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-right border-collapse min-w-[900px]">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase">
                                <th class="py-3.5 px-4">عنوان کار</th>
                                <th class="py-3.5 px-3 whitespace-nowrap">پروژه</th>
                                <th class="py-3.5 px-3 whitespace-nowrap">مسئول انجام</th>
                                <th class="py-3.5 px-3 whitespace-nowrap text-center">اولویت</th>
                                <th class="py-3.5 px-3 whitespace-nowrap text-center">وزن</th>
                                <th class="py-3.5 px-3 whitespace-nowrap text-center">وضعیت</th>
                                <th class="py-3.5 px-3 whitespace-nowrap">مهلت</th>
                                <th class="py-3.5 px-3 whitespace-nowrap text-center">عملیات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                            @foreach($allProjectTasks as $task)
                                @php
                                    $isParent = is_null($task->parent_id);
                                    $isOverdue = $task->due_date && $task->status->value !== 'COMPLETED' && $task->due_date->isPast();
                                @endphp
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors {{ !$isParent ? 'bg-indigo-50/20 dark:bg-indigo-950/10 border-r-4 border-r-indigo-500' : '' }}">
                                    <!-- Title & Structure -->
                                    <td class="py-3.5 px-4 min-w-[260px]">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-1.5">
                                                @if($isParent)
                                                    <span class="px-2 py-0.5 rounded-md bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-bold text-[10px] border border-indigo-200 dark:border-indigo-800 shrink-0">
                                                        👑 کار اصلی
                                                    </span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-md bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 font-bold text-[10px] border border-purple-200 dark:border-purple-800 shrink-0">
                                                        ↳ زیرکار
                                                    </span>
                                                @endif

                                                <a href="{{ route('projects.tasks.show', [$project, $task]) }}" class="font-black text-slate-800 dark:text-slate-100 hover:text-indigo-600 dark:hover:text-indigo-400">
                                                    {{ $task->title }}
                                                </a>

                                                @if($task->version)
                                                    <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-mono text-[10px]">
                                                        {{ $task->version }}
                                                    </span>
                                                @endif
                                            </div>

                                            @if(!$isParent && $task->parent)
                                                <div class="text-[11px] text-indigo-600 dark:text-indigo-400 font-bold pr-2">
                                                    <span>↳ زیرمجموعه:</span>
                                                    <a href="{{ route('projects.tasks.show', [$project, $task->parent]) }}" class="hover:underline">
                                                        {{ $task->parent->title }}
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Project -->
                                    <td class="py-3.5 px-3 whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-[10px]">
                                            {{ $task->project_id === $project->id ? '🏢 پروژه اصلی' : '📁 ' . $task->project?->title }}
                                        </span>
                                    </td>

                                    <!-- Executor -->
                                    <td class="py-3.5 px-3 whitespace-nowrap text-slate-700 dark:text-slate-300">
                                        {{ $task->executor?->full_name ?? 'بدون مسئول' }}
                                    </td>

                                    <!-- Priority -->
                                    <td class="py-3.5 px-3 whitespace-nowrap text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $task->priority->badgeClass() }}">
                                            {{ $task->priority->label() }}
                                        </span>
                                    </td>

                                    <!-- Weight -->
                                    <td class="py-3.5 px-3 whitespace-nowrap text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 font-mono">
                                            {{ $task->weight ?? 1 }}
                                        </span>
                                    </td>

                                    <!-- Status -->
                                    <td class="py-3.5 px-3 whitespace-nowrap text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $task->status->badgeClass() }}">
                                            {{ $task->status->label() }}
                                        </span>
                                    </td>

                                    <!-- Due Date -->
                                    <td class="py-3.5 px-3 whitespace-nowrap font-mono text-[11px] {{ $isOverdue ? 'text-rose-600 font-bold' : 'text-slate-600 dark:text-slate-400' }}">
                                        {{ $task->due_date ? verta($task->due_date)->format('Y/m/d') : '-' }}
                                    </td>

                                    <!-- Actions -->
                                    <td class="py-3.5 px-3 whitespace-nowrap text-center">
                                        <a href="{{ route('projects.tasks.show', [$project, $task]) }}" class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-xs inline-flex items-center gap-1">
                                            <span>مشاهده</span>
                                            <svg class="w-3 h-3 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- PAGINATION BAR -->
            @if($allProjectTasks instanceof \Illuminate\Pagination\LengthAwarePaginator && $allProjectTasks->hasPages())
                <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <span class="text-xs text-slate-500 font-medium">
                        @if($isTreeView)
                            نمایش {{ $allProjectTasks->firstItem() }} تا {{ $allProjectTasks->lastItem() }} از {{ $allProjectTasks->total() }} کار اصلی (همراه با زیرکارها)
                        @else
                            نمایش {{ $allProjectTasks->firstItem() }} تا {{ $allProjectTasks->lastItem() }} از {{ $allProjectTasks->total() }} کار
                        @endif
                    </span>
                    <div>
                        {{ $allProjectTasks->links() }}
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>

<script>
    function initPersianPicker(selector) {
        if (typeof $.fn.persianDatepicker !== 'undefined') {
            $(selector).persianDatepicker({
                format: 'YYYY/MM/DD',
                autoClose: true,
                initialValue: false,
                calendar: {
                    persian: {
                        locale: 'fa'
                    }
                }
            });
        }
    }
</script>
