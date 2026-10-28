<x-app-layout title="پروژه‌ها" header="لیست پروژه‌ها">
    <div class="space-y-8">
        <!-- Hero Banner -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-8 shadow-xl border border-indigo-900/30">
            <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-2">
                    <h1 class="text-2xl sm:text-3xl font-black text-white">پروژه‌ها</h1>
                    <p class="text-slate-300 text-xs sm:text-sm max-w-xl">
                        مشاهده، جستجو و پیگیری وضعیت پروژه‌ها و کارهای تیم.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('project-templates.index') }}" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs sm:text-sm border border-slate-700 transition-all shrink-0">
                        <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        <span>الگوهای آماده</span>
                    </a>
                    @if(auth()->user()?->canCreateProjects())
                        <a href="{{ route('projects.create') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs sm:text-sm shadow-lg shadow-indigo-600/30 transition-all shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>تعریف پروژه جدید</span>
                        </a>
                    @endif
                </div>
            </div>
            <div class="absolute -left-10 -bottom-10 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        </div>

        <!-- Quick Stats Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400">کل پروژه‌ها</p>
                    <p class="text-2xl font-black text-slate-800 dark:text-slate-100 mt-1">{{ number_format($totalProjects) }}</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
            </div>
            <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400">پروژه‌های فعال</p>
                    <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($activeProjects) }}</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
            </div>
            <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400">تکمیل شده</p>
                    <p class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">{{ number_format($completedProjects) }}</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400">بسیار فوری / حیاتی</p>
                    <p class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">{{ number_format($forceMajeureProjects) }}</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center font-bold">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
            </div>
        </div>

        <!-- Filter Card & Layout Switcher -->
        <div class="p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4"
             x-data="{ showAdvanced: {{ request()->anyFilled(['priority', 'subprojects', 'organization_node_id', 'sort']) ? 'true' : 'false' }} }">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-4">
                <div class="flex items-center gap-3">
                    <h2 class="font-bold text-sm text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        <span>فیلتر و تنظیمات نمایش</span>
                    </h2>
                    @php
                        $activeAdvancedCount = count(array_filter([
                            request('priority'),
                            request('subprojects') && request('subprojects') !== 'hide' ? request('subprojects') : null,
                            request('organization_node_id'),
                            request('sort') && request('sort') !== 'deadline' ? request('sort') : null,
                        ]));
                    @endphp
                    <button type="button" @click="showAdvanced = !showAdvanced" class="px-3 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs flex items-center gap-1.5 transition-all">
                        <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="{ 'rotate-180': showAdvanced }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        <span>فیلترهای پیشرفته</span>
                        @if($activeAdvancedCount > 0)
                            <span class="w-5 h-5 rounded-full bg-indigo-600 text-white text-[10px] flex items-center justify-center font-mono">{{ $activeAdvancedCount }}</span>
                        @endif
                    </button>
                </div>

                <!-- Layout View Switcher -->
                <div class="flex items-center gap-1 p-1 bg-slate-100 dark:bg-slate-800 rounded-2xl">
                    <a href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}" class="px-3 py-1.5 rounded-xl font-bold text-xs flex items-center gap-1.5 transition-all {{ $layoutView === 'grid' ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        <span>نمای کارتی</span>
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['view' => 'table']) }}" class="px-3 py-1.5 rounded-xl font-bold text-xs flex items-center gap-1.5 transition-all {{ $layoutView === 'table' ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        <span>نمای جدولی</span>
                    </a>
                </div>
            </div>

            <form method="GET" action="{{ route('projects.index') }}" class="space-y-4">
                <input type="hidden" name="view" value="{{ $layoutView }}">

                <!-- Primary Always-Visible Filters -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">جستجو</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="جستجو در عنوان، کد پروژه یا توضیحات..." class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-xs font-medium focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">وضعیت پروژه</label>
                        <select name="status" class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-xs font-medium focus:ring-2 focus:ring-indigo-500">
                            <option value="">همه وضعیت‌ها</option>
                            @foreach($statuses as $st)
                                <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>{{ $st->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 py-2 rounded-xl bg-slate-900 dark:bg-slate-800 text-white font-bold text-xs hover:bg-slate-800 transition-all shadow-sm flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <span>اعمال فیلتر</span>
                        </button>
                        @if(request()->anyFilled(['search', 'status', 'priority', 'subprojects', 'organization_node_id', 'sort', 'per_page']))
                            <a href="{{ route('projects.index', ['view' => $layoutView]) }}" class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-500 text-xs font-bold hover:bg-slate-100 dark:hover:bg-slate-800 transition-all shrink-0">
                                پاکسازی
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Secondary Advanced Collapsible Filters -->
                <div x-show="showAdvanced" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">اولویت پروژه</label>
                        <select name="priority" class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-xs font-medium focus:ring-2 focus:ring-indigo-500">
                            <option value="">همه اولویت‌ها</option>
                            @foreach($priorities as $pr)
                                <option value="{{ $pr->value }}" {{ request('priority') === $pr->value ? 'selected' : '' }}>{{ $pr->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">نمایش زیرپروژه‌ها</label>
                        <select name="subprojects" class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-xs font-medium focus:ring-2 focus:ring-indigo-500">
                            <option value="hide" {{ $subprojectFilter === 'hide' ? 'selected' : '' }}>فقط پروژه‌های اصلی</option>
                            <option value="include" {{ $subprojectFilter === 'include' ? 'selected' : '' }}>همه پروژه‌ها و زیرپروژه‌ها</option>
                            <option value="only" {{ $subprojectFilter === 'only' ? 'selected' : '' }}>فقط زیرپروژه‌ها</option>
                            <option value="tree" {{ $subprojectFilter === 'tree' ? 'selected' : '' }}>نمایش درختی</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">واحد سازمانی</label>
                        <select name="organization_node_id" class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-xs font-medium focus:ring-2 focus:ring-indigo-500">
                            <option value="">همه واحدها</option>
                            @foreach($organizationNodes as $node)
                                <option value="{{ $node->id }}" {{ request('organization_node_id') == $node->id ? 'selected' : '' }}>{{ $node->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">مرتب‌سازی بر اساس</label>
                        <select name="sort" class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-xs font-medium focus:ring-2 focus:ring-indigo-500">
                            <option value="deadline" {{ $sortBy === 'deadline' ? 'selected' : '' }}>نزدیک‌ترین مهلت تحویل</option>
                            <option value="created_at" {{ $sortBy === 'created_at' ? 'selected' : '' }}>جدیدترین‌ها</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">تعداد در صفحه</label>
                        <select name="per_page" onchange="this.form.submit()" class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-xs font-medium focus:ring-2 focus:ring-indigo-500">
                            <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>۱۰ تایی</option>
                            <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>۲۵ تایی</option>
                            <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>۵۰ تایی (پیش‌فرض)</option>
                            <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>۱۰۰ تایی</option>
                            <option value="200" {{ $perPage == 200 ? 'selected' : '' }}>۲۰۰ تایی</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <!-- Projects Display View -->
        <div class="space-y-6">
            @if($projects->isEmpty())
                <div class="p-12 text-center bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 space-y-4">
                    <div class="w-16 h-16 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mx-auto">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                    </div>
                    <h3 class="font-bold text-base text-slate-800 dark:text-slate-200">هیچ پروژه‌ای پیدا نشد</h3>
                    <p class="text-xs text-slate-400 max-w-sm mx-auto">می‌تونی فیلترها رو پاک کنی یا یک پروژه جدید بسازی.</p>
                </div>
            @else
                @if($layoutView === 'grid')
                    <!-- GRID VIEW -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($projects as $project)
                            <x-projects.card :project="$project" />
                        @endforeach
                    </div>
                @else
                    <!-- TABLE VIEW -->
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-right text-xs">
                                <thead class="bg-slate-50 dark:bg-slate-950 text-slate-500 font-bold border-b border-slate-200 dark:border-slate-800">
                                    <tr>
                                        <th class="p-4">کد و عنوان پروژه</th>
                                        <th class="p-4">اولویت</th>
                                        <th class="p-4">وضعیت</th>
                                        <th class="p-4">میزان پیشرفت</th>
                                        <th class="p-4">مهلت تحویل</th>
                                        <th class="p-4">واحد سازمانی</th>
                                        <th class="p-4">تایید سرپرستان</th>
                                        <th class="p-4">تاریخ شروع</th>
                                        <th class="p-4 text-center">عملیات</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                                    @foreach($projects as $project)
                                        @php
                                            $treeDepth = $project->tree_depth ?? 0;
                                            $prog = $project->timeline_progress;
                                            $barColor = $prog >= 85 ? 'bg-rose-500' : ($prog >= 60 ? 'bg-amber-500' : 'bg-emerald-500');
                                        @endphp
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                            <td class="p-4">
                                                <div class="flex items-center gap-3" style="margin-right: {{ $treeDepth * 24 }}px">
                                                    @if($treeDepth > 0)
                                                        <span class="text-indigo-400 font-bold">↳</span>
                                                    @endif
                                                    <div>
                                                        <a href="{{ route('projects.show', $project) }}" class="font-bold text-slate-900 dark:text-white hover:text-indigo-600 text-sm">
                                                            {{ $project->title }}
                                                        </a>
                                                        <div class="flex items-center gap-2 mt-0.5">
                                                            <span class="px-2 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 text-[10px] font-mono font-bold">
                                                                {{ $project->project_key }}
                                                            </span>
                                                            @if($project->parent)
                                                                <span class="text-[10px] text-slate-400">پروژه اصلی: {{ $project->parent->title }}</span>
                                                            @endif
                                                        </div>
                                                        @if($alert = $project->deadline_alert)
                                                            <div class="mt-1.5">
                                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border inline-block {{ $alert['class'] }}">
                                                                    {{ $alert['label'] }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="p-4 whitespace-nowrap">
                                                @if(isset($project->priority))
                                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold {{ $project->priority->badgeClass() }}">
                                                        {{ $project->priority->label() }}
                                                    </span>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="p-4 whitespace-nowrap">
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border">
                                                    {{ $project->status->label() }}
                                                </span>
                                            </td>
                                            <!-- 4. Progress -->
                                            <td class="p-4 whitespace-nowrap min-w-[130px]">
                                                <div class="space-y-1.5">
                                                    <div class="flex items-center justify-between text-[11px]">
                                                        <span class="text-slate-400 text-[10px]">پیشرفت:</span>
                                                        <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $prog }}%</span>
                                                    </div>
                                                    <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                                        <div class="h-full {{ $barColor }} rounded-full transition-all duration-300" style="width: {{ min(100, $prog) }}%"></div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- 5. Deadline & Alert -->
                                            <td class="p-4 whitespace-nowrap text-xs min-w-[120px]">
                                                <div class="space-y-1">
                                                    <span class="font-mono font-bold text-slate-700 dark:text-slate-200">
                                                        {{ $project->deadline ? verta($project->deadline)->format('Y/m/d') : 'بدون مهلت' }}
                                                    </span>
                                                    @if($alert = $project->deadline_alert)
                                                        <div>
                                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border inline-block {{ $alert['class'] }}">
                                                                {{ $alert['label'] }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>

                                            <!-- 6. Organization Node -->
                                            <td class="p-4 font-medium whitespace-nowrap">
                                                {{ $project->organizationNode?->name ?? '-' }}
                                            </td>

                                            <!-- 7. Approval Stages -->
                                            <td class="p-4 whitespace-nowrap">
                                                @if($project->approvalStages && $project->approvalStages->isNotEmpty())
                                                    <span class="px-2.5 py-1 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 text-[11px] font-bold border border-indigo-200 dark:border-indigo-800">
                                                        {{ $project->approvalStages->count() }} مرحله تایید
                                                    </span>
                                                @else
                                                    <span class="text-slate-400 text-[11px]">بدون مرحله تایید</span>
                                                @endif
                                            </td>

                                            <!-- 8. Start Date -->
                                            <td class="p-4 font-mono whitespace-nowrap text-slate-600 dark:text-slate-300">
                                                {{ $project->start_date ? verta($project->start_date)->format('Y/m/d') : '-' }}
                                            </td>

                                            <!-- 9. Actions -->
                                            <td class="p-4 text-center whitespace-nowrap">
                                                <div class="flex items-center justify-center gap-2">
                                                    <a href="{{ route('projects.show', $project) }}" class="px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-bold hover:bg-indigo-100 transition-colors">
                                                        مشاهده
                                                    </a>
                                                    <a href="{{ route('projects.edit', $project) }}" class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold hover:bg-slate-200 transition-colors">
                                                        ویرایش
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if($projects->hasPages())
                    <div class="p-4 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800">
                        {{ $projects->withQueryString()->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
