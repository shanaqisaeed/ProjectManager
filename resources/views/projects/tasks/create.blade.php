<x-app-layout title="تعریف کار جدید - {{ $project->title }}" header="تعریف کار جدید">
    <link rel="stylesheet" href="{{ asset('assets/css/persian-datepicker.min.css') }}"/>
    <script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('assets/js/persian-date.min.js') }}"></script>
    <script src="{{ asset('assets/js/persian-datepicker.min.js') }}"></script>

    <div class="space-y-8 mx-auto">
        <!-- Header Banner -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-8 shadow-xl border border-indigo-900/30 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 rounded-xl bg-indigo-500/20 text-indigo-300 text-xs font-mono font-bold border border-indigo-500/30">
                        {{ $project->project_key }}
                    </span>
                    <span class="text-xs text-slate-400">پروژه:</span>
                    <a href="{{ route('projects.show', $project) }}" class="text-xs font-bold text-indigo-300 hover:underline">
                        {{ $project->title }}
                    </a>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-white">تعریف کار جدید</h1>
                <p class="text-slate-300 text-xs sm:text-sm max-w-xl">
                    ثبت مشخصات، تعیین کار اصلی (در صورت نیاز)، زمان‌بندی، مسئول انجام و مراحل تایید سرپرست.
                </p>
            </div>
            <a href="{{ route('projects.show', [$project, 'tab' => 'tasks']) }}" class="px-5 py-2.5 rounded-2xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition-all backdrop-blur-md border border-white/10 shrink-0 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>بازگشت به کارها</span>
            </a>
        </div>

        <form action="{{ route('projects.tasks.store', $project) }}" method="POST" class="space-y-8">
            @csrf

            <!-- Section 1: Task Identity & Subtask Hierarchy -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 sm:p-8 shadow-sm space-y-6">
                <h3 class="text-base font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">1</div>
                    <span>مشخصات کار</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Task Title -->
                    <div class="md:col-span-2 space-y-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            عنوان کار <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="title" value="{{ old('title') }}" required placeholder="عنوان کار را بنویسید..." class="w-full px-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs font-medium focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <!-- Task Version -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            نسخه / کد شناسه (اختیاری)
                        </label>
                        <input type="text" name="version" value="{{ old('version') }}" placeholder="مثلاً: 1.0 یا فاز اول" class="w-full px-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs font-mono focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <!-- Searchable AJAX Parent Task (For Subtasks) -->
                    <div class="md:col-span-3 space-y-2 relative" x-data="{
                        search: '',
                        selectedId: '{{ old('parent_id', $initialParentTask?->id ?? '') }}',
                        selectedTitle: '{{ $initialParentTask ? addslashes($initialParentTask->title . ($initialParentTask->version ? ' (کد: '.$initialParentTask->version.')' : '')) : '' }}',
                        open: false,
                        loading: false,
                        tasks: [],
                        init() {
                            if (this.selectedId && this.selectedTitle) {
                                this.search = this.selectedTitle;
                            }
                        },
                        async fetchTasks() {
                            this.loading = true;
                            try {
                                const url = '{{ route('projects.tasks.search-parents', $project) }}?q=' + encodeURIComponent(this.search);
                                const res = await fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
                                const data = await res.json();
                                if (data && data.tasks) {
                                    this.tasks = data.tasks;
                                }
                            } catch (e) {
                                console.error(e);
                            } finally {
                                this.loading = false;
                            }
                        },
                        openDropdown() {
                            this.open = true;
                            if (this.tasks.length === 0) {
                                this.fetchTasks();
                            }
                        },
                        selectTask(t) {
                            if (t) {
                                this.selectedId = t.id;
                                this.selectedTitle = t.title + (t.version ? ' (کد: ' + t.version + ')' : '');
                                this.search = this.selectedTitle;
                            } else {
                                this.selectedId = '';
                                this.selectedTitle = '';
                                this.search = '';
                            }
                            this.open = false;
                        },
                        clear() {
                            this.selectedId = '';
                            this.selectedTitle = '';
                            this.search = '';
                            this.open = true;
                            this.fetchTasks();
                        }
                    }" @click.away="open = false">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center justify-between">
                            <span>کار اصلی (در صورتی که این کار زیرمجموعه است)</span>
                            <span class="text-[11px] text-indigo-600 dark:text-indigo-400 font-normal">اختیاری</span>
                        </label>

                        <input type="hidden" name="parent_id" :value="selectedId">

                        <div class="relative">
                            <input type="text"
                                   x-model="search"
                                   @focus="openDropdown()"
                                   @click="openDropdown()"
                                   @input.debounce.300ms="fetchTasks()"
                                   @keydown.escape="open = false"
                                   placeholder="جستجو در بین کارهای این پروژه..."
                                   class="w-full px-4 py-3 pr-10 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs font-medium focus:ring-2 focus:ring-indigo-500">

                            <div class="absolute right-3.5 top-3.5 text-slate-400 pointer-events-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>

                            <template x-if="selectedId">
                                <button type="button" @click="clear()" class="absolute left-3 top-3 text-slate-400 hover:text-rose-500 text-xs font-bold bg-slate-200 dark:bg-slate-700 rounded-full w-5 h-5 flex items-center justify-center">
                                    ✕
                                </button>
                            </template>

                            <template x-if="loading">
                                <div class="absolute left-10 top-3 text-indigo-500 text-xs animate-spin">
                                    ⏳
                                </div>
                            </template>
                        </div>

                        <!-- Dropdown Results Menu -->
                        <div x-show="open" 
                             x-cloak 
                             class="absolute z-30 left-0 right-0 top-full mt-1 max-h-60 overflow-y-auto rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xl divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                            <div @click="selectTask(null)" 
                                 class="p-3 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer flex items-center justify-between text-slate-500 italic">
                                <span>-- کار اصلی مستقل (بدون والد) --</span>
                            </div>

                            <template x-if="loading && tasks.length === 0">
                                <div class="p-4 text-center text-slate-400 text-xs">
                                    در حال جستجو...
                                </div>
                            </template>

                            <template x-if="!loading && tasks.length === 0">
                                <div class="p-4 text-center text-slate-400 text-xs">
                                    هیچ کاری با این عنوان پیدا نشد.
                                </div>
                            </template>

                            <template x-for="t in tasks" :key="t.id">
                                <div @click="selectTask(t)" 
                                     class="p-3 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 cursor-pointer flex items-center justify-between transition-colors gap-3">
                                    <div class="space-y-1 truncate">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-800 dark:text-slate-100 truncate" x-text="t.title"></span>
                                            <template x-if="t.version">
                                                <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-[10px] font-mono" x-text="t.version"></span>
                                            </template>
                                        </div>
                                        <div class="flex items-center gap-2 text-[10px] text-slate-400">
                                            <span x-text="'پروژه: ' + (t.project_title || 'نامشخص')"></span>
                                        </div>
                                    </div>
                                    <div class="shrink-0 flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="t.badge_class" x-text="t.status_label"></span>
                                        <span class="text-[10px] px-2 py-1 rounded-xl bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-bold border border-indigo-200 dark:border-indigo-800">انتخاب</span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Dates (Start Date & Due Date) -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 sm:p-8 shadow-sm space-y-6">
                <h3 class="text-base font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">2</div>
                    <span>زمان‌بندی</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Start Date -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            📅 تاریخ شروع
                        </label>
                        <div class="relative">
                            <input type="text" id="task_start_date_picker" name="start_date" value="{{ old('start_date') }}" placeholder="۱۴۰۳/۰۶/۰۱" class="w-full px-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs font-mono focus:ring-2 focus:ring-emerald-500" readonly>
                        </div>
                        <p class="text-[11px] text-slate-400">تاریخ شروع کار</p>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            🏁 مهلت تحویل
                        </label>
                        <div class="relative">
                            <input type="text" id="task_due_date_picker" name="due_date" value="{{ old('due_date') }}" placeholder="۱۴۰۳/۰۶/۳۰" class="w-full px-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs font-mono focus:ring-2 focus:ring-emerald-500" readonly>
                        </div>
                        <p class="text-[11px] text-slate-400">آخرین زمان برای انجام کار</p>
                    </div>
                </div>
            </div>

            <!-- Section 3: Status, Priority & Executor Assignment -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 sm:p-8 shadow-sm space-y-6">
                <h3 class="text-base font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div class="w-8 h-8 rounded-xl bg-violet-50 dark:bg-violet-950 text-violet-600 dark:text-violet-400 flex items-center justify-center font-bold">3</div>
                    <span>اولویت، وضعیت و مسئول انجام</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Priority -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            اولویت <span class="text-rose-500">*</span>
                        </label>
                        <select name="priority" required class="w-full px-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs font-bold focus:ring-2 focus:ring-indigo-500">
                            @foreach($taskPriorities as $p)
                                <option value="{{ $p->value }}" {{ old('priority', 'MEDIUM') === $p->value ? 'selected' : '' }}>
                                    {{ $p->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Initial Status -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            وضعیت اولیه <span class="text-rose-500">*</span>
                        </label>
                        <select name="status" required class="w-full px-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs font-bold focus:ring-2 focus:ring-indigo-500">
                            @foreach($taskStatuses as $s)
                                <option value="{{ $s->value }}" {{ old('status', 'TODO') === $s->value ? 'selected' : '' }}>
                                    {{ $s->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Task Weight -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center justify-between">
                            <span>⚖️ وزن کار</span>
                            <span class="text-[10px] text-slate-400 font-normal">پیش‌فرض: ۱</span>
                        </label>
                        <input type="number" name="weight" id="weight" value="{{ old('weight', 1) }}" min="1" max="1000" required class="w-full px-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs font-mono font-bold focus:ring-2 focus:ring-indigo-500" placeholder="1">
                        <p class="text-[11px] text-slate-400">ضریب اهمیت یا سختی کار</p>
                    </div>

                    <!-- Searchable Executor User Select -->
                    <div class="space-y-2 relative" x-data="{
                        search: '',
                        selectedId: '{{ old('executor_user_id', '') }}',
                        open: false,
                        users: {{ json_encode($projectExecutorUsers->map(fn($u) => [
                            'id' => $u->id,
                            'name' => $u->display_title ?? $u->full_name,
                            'username' => $u->username,
                            'active_weight' => $u->active_weight ?? 0,
                            'active_tasks_count' => $u->active_tasks_count ?? 0,
                            'load_status' => ($u->active_weight ?? 0) > 10 ? 'overloaded' : (($u->active_weight ?? 0) <= 3 ? 'underutilized' : 'balanced'),
                        ])) }},
                        init() {
                            if (this.selectedId) {
                                const found = this.users.find(u => String(u.id) === String(this.selectedId));
                                if (found) {
                                    this.search = found.name + (found.username ? ' (@' + found.username + ')' : '');
                                }
                            }
                        },
                        get filteredUsers() {
                            if (!this.search.trim()) return this.users.slice(0, 15);
                            if (this.selectedId && this.search.includes('(@')) return this.users.slice(0, 15);
                            const q = this.search.toLowerCase();
                            return this.users.filter(u => 
                                u.name.toLowerCase().includes(q) || 
                                (u.username && u.username.toLowerCase().includes(q))
                            ).slice(0, 15);
                        },
                        selectUser(u) {
                            if (u) {
                                this.selectedId = u.id;
                                this.search = u.name + (u.username ? ' (@' + u.username + ')' : '');
                            } else {
                                this.selectedId = '';
                                this.search = '';
                            }
                            this.open = false;
                        },
                        clear() {
                            this.selectedId = '';
                            this.search = '';
                            this.open = true;
                        }
                    }" @click.away="open = false">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                            مسئول انجام کار
                        </label>
                        <input type="hidden" name="executor_user_id" :value="selectedId">
                        
                        <div class="relative">
                            <input type="text"
                                   x-model="search"
                                   @focus="open = true"
                                   @click="open = true"
                                   @keydown.escape="open = false"
                                   placeholder="جستجو در اعضای تیم..."
                                   class="w-full px-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs font-medium focus:ring-2 focus:ring-indigo-500">
                                   
                            <template x-if="selectedId">
                                <button type="button" @click="clear()" class="absolute left-3 top-3 text-slate-400 hover:text-rose-500 text-xs font-bold bg-slate-200 dark:bg-slate-700 rounded-full w-5 h-5 flex items-center justify-center">
                                    ✕
                                </button>
                            </template>
                        </div>

                        <!-- Dropdown Menu -->
                        <div x-show="open" 
                             x-cloak 
                             class="absolute z-30 left-0 right-0 top-full mt-1 max-h-64 overflow-y-auto rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xl divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                            <div @click="selectUser(null)" 
                                 class="p-3 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer flex items-center justify-between text-slate-500 italic">
                                <span>-- بدون مسئول --</span>
                            </div>
                            <template x-for="u in filteredUsers" :key="u.id">
                                <div @click="selectUser(u)" 
                                     class="p-3 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 cursor-pointer flex items-center justify-between transition-colors">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <strong class="font-bold text-slate-800 dark:text-slate-100" x-text="u.name"></strong>
                                            <span class="text-[10px] text-slate-400 font-mono" dir="ltr" x-text="u.username ? '@' + u.username : ''"></span>
                                        </div>
                                        <div class="flex items-center gap-1.5 mt-1">
                                            <span class="text-[10px] px-1.5 py-0.5 rounded font-mono font-bold"
                                                  :class="u.load_status === 'overloaded' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : (u.load_status === 'underutilized' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300')">
                                                <span x-text="'وزن فعال: ' + u.active_weight"></span>
                                                <span class="opacity-70" x-text="' (' + u.active_tasks_count + ' کار)'"></span>
                                            </span>
                                            <span class="text-[10px] text-slate-400 font-medium"
                                                  x-text="u.load_status === 'overloaded' ? '⚠️ پربار' : (u.load_status === 'underutilized' ? '🔹 ظرفیت خالی' : '🟢 متعادل')">
                                            </span>
                                        </div>
                                    </div>
                                    <span class="text-[10px] px-2.5 py-1 rounded-full bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-bold border border-indigo-200 dark:border-indigo-800 shrink-0">انتخاب</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Stage Supervisors -->
            @if($project->approvalStages->isNotEmpty())
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 sm:p-8 shadow-sm space-y-6">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                        <h3 class="text-base font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                            <div class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold">4</div>
                            <span>انتخاب ناظر مراحل تایید (سرپرست)</span>
                        </h3>
                        <span class="text-xs text-amber-600 dark:text-amber-400 font-semibold">{{ $project->approvalStages->count() }} مرحله تایید</span>
                    </div>

                    <div class="space-y-4">
                        @foreach($project->approvalStages as $stg)
                            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-xl bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-bold flex items-center justify-center text-xs">
                                        {{ $stg->stage_number }}
                                    </span>
                                    <div>
                                        <h4 class="font-bold text-xs text-slate-800 dark:text-slate-100">{{ $stg->stage_name }}</h4>
                                        <p class="text-[11px] text-slate-400">مرحله تایید سرپرست</p>
                                    </div>
                                </div>

                                <input type="hidden" name="stage_supervisors[{{ $stg->id }}][stage_number]" value="{{ $stg->stage_number }}">
                                <input type="hidden" name="stage_supervisors[{{ $stg->id }}][stage_name]" value="{{ $stg->stage_name }}">

                                <div class="w-full sm:w-72 relative" x-data="{
                                    search: '',
                                    selectedId: '{{ old('stage_supervisors.' . $stg->id . '.user_id', $stg->supervisor_user_id ?? '') }}',
                                    open: false,
                                    users: {{ json_encode($projectSupervisorUsers->map(fn($u) => [
                                        'id' => $u->id,
                                        'name' => $u->display_title ?? $u->full_name,
                                        'username' => $u->username
                                    ])) }},
                                    init() {
                                        if (this.selectedId) {
                                            const found = this.users.find(u => String(u.id) === String(this.selectedId));
                                            if (found) {
                                                this.search = found.name + (found.username ? ' (@' + found.username + ')' : '');
                                            }
                                        }
                                    },
                                    get filteredUsers() {
                                        if (!this.search.trim()) return this.users.slice(0, 15);
                                        if (this.selectedId && this.search.includes('(@')) return this.users.slice(0, 15);
                                        const q = this.search.toLowerCase();
                                        return this.users.filter(u => 
                                            u.name.toLowerCase().includes(q) || 
                                            (u.username && u.username.toLowerCase().includes(q))
                                        ).slice(0, 15);
                                    },
                                    selectUser(u) {
                                        if (u) {
                                            this.selectedId = u.id;
                                            this.search = u.name + (u.username ? ' (@' + u.username + ')' : '');
                                        } else {
                                            this.selectedId = '';
                                            this.search = '';
                                        }
                                        this.open = false;
                                    },
                                    clear() {
                                        this.selectedId = '';
                                        this.search = '';
                                        this.open = true;
                                    }
                                }" @click.away="open = false">
                                    <input type="hidden" name="stage_supervisors[{{ $stg->id }}][user_id]" :value="selectedId">
                                    
                                    <div class="relative">
                                        <input type="text"
                                               x-model="search"
                                               @focus="open = true"
                                               @click="open = true"
                                               @keydown.escape="open = false"
                                               placeholder="جستجو در بین ناظران..."
                                               class="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 font-semibold">
                                               
                                        <template x-if="selectedId">
                                            <button type="button" @click="clear()" class="absolute left-2.5 top-2 text-slate-400 hover:text-rose-500 text-xs font-bold bg-slate-200 dark:bg-slate-700 rounded-full w-4 h-4 flex items-center justify-center">
                                                ✕
                                            </button>
                                        </template>
                                    </div>

                                    <div x-show="open" 
                                         x-cloak 
                                         class="absolute z-30 left-0 right-0 top-full mt-1 max-h-48 overflow-y-auto rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xl divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                                        <div @click="selectUser(null)" 
                                             class="p-2.5 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer flex items-center justify-between text-slate-500 italic">
                                            <span>-- بدون نیاز به تایید --</span>
                                        </div>
                                        <template x-for="u in filteredUsers" :key="u.id">
                                            <div @click="selectUser(u)" 
                                                 class="p-2.5 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 cursor-pointer flex items-center justify-between transition-colors">
                                                <div>
                                                    <strong class="font-bold text-slate-800 dark:text-slate-100 block text-[11px]" x-text="u.name"></strong>
                                                    <span class="text-[10px] text-slate-400 font-mono" dir="ltr" x-text="u.username ? '@' + u.username : ''"></span>
                                                </div>
                                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 font-bold border border-amber-200 dark:border-amber-800">انتخاب</span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Section 5: Description & Actions -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 sm:p-8 shadow-sm space-y-6">
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        توضیحات کار
                    </label>
                    <textarea name="description" rows="4" placeholder="توضیحات، فایل‌ها یا نکات لازم برای انجام این کار..." class="w-full px-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs font-medium focus:ring-2 focus:ring-indigo-500 leading-relaxed">{{ old('description') }}</textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <a href="{{ route('projects.show', [$project, 'tab' => 'tasks']) }}" class="px-6 py-3 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all">
                        انصراف
                    </a>
                    <button type="submit" class="px-8 py-3 rounded-2xl bg-gradient-to-r from-indigo-600 to-emerald-600 hover:from-indigo-500 hover:to-emerald-500 text-white text-xs font-bold shadow-lg shadow-indigo-500/25 transition-all transform hover:-translate-y-0.5 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>ثبت کار جدید</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        $(document).ready(function() {
            if (typeof $.fn.persianDatepicker !== 'undefined') {
                $("#task_start_date_picker").persianDatepicker({
                    format: 'YYYY/MM/DD',
                    initialValue: false,
                    autoClose: true,
                    calendar: {
                        persian: { locale: 'fa' }
                    }
                });

                $("#task_due_date_picker").persianDatepicker({
                    format: 'YYYY/MM/DD',
                    initialValue: false,
                    autoClose: true,
                    calendar: {
                        persian: { locale: 'fa' }
                    }
                });
            }
        });
    </script>
</x-app-layout>
