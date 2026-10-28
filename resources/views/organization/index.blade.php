<x-app-layout title="ساختار سازمانی" header="ساختار سازمانی">
    <div class="space-y-6 sm:space-y-8"
         x-data="{
             activeView: 'tree', // 'tree', 'chart', 'directory'
             searchQuery: '',
             selectedType: '',
             createNodeModal: false,
             createParentId: '',
             createParentName: '',
             editNodeModal: false,
             editForm: {
                 id: null,
                 name: '',
                 type: 'UNIT',
                 parent_id: '',
                 description: '',
                 sort_order: 0
             },
             zoomLevel: 100,
             zoomIn() { if (this.zoomLevel < 140) this.zoomLevel += 10; },
             zoomOut() { if (this.zoomLevel > 50) this.zoomLevel -= 10; },
             resetZoom() { this.zoomLevel = 100; },
             openCreateModal(data = {}) {
                 this.createParentId = data.parentId ? String(data.parentId) : '';
                 this.createParentName = data.parentName || '';
                 this.createNodeModal = true;
             },
             openEditModal(data) {
                 this.editForm = {
                     id: data.id,
                     name: data.name,
                     type: data.type,
                     parent_id: data.parentId ? String(data.parentId) : '',
                     description: data.description || '',
                     sort_order: data.sortOrder || 0
                 };
                 this.editNodeModal = true;
             },
             matchesDirectory(name, type, path) {
                 const q = this.searchQuery.toLowerCase().trim();
                 const matchesQuery = !q || name.toLowerCase().includes(q) || type.toLowerCase().includes(q) || path.toLowerCase().includes(q);
                 const matchesType = !this.selectedType || type === this.selectedType;
                 return matchesQuery && matchesType;
             }
         }"
         @open-create-modal.window="openCreateModal($event.detail)"
         @open-edit-modal.window="openEditModal($event.detail)">

        <!-- Hero Header Banner -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-5 sm:p-8 shadow-xl border border-indigo-900/30">
            <!-- Background Decorative Glow -->
            <div class="absolute -top-24 -left-24 w-72 h-72 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -right-24 w-72 h-72 bg-violet-500/20 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-5">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 border border-indigo-400/30 text-indigo-200 text-xs font-semibold">
                        <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        <span>چارت و واحدهای سازمانی</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">ساختار و واحدهای سازمانی</h1>
                    <p class="text-slate-300 text-xs sm:text-sm max-w-2xl leading-relaxed">
                        مشاهده و مدیریت معاونت‌ها، ادارات، واحدها و تیم‌های سازمانی.
                    </p>
                </div>

                @if(auth()->user()?->isSuperAdmin() || auth()->user()?->isOrganizationManager())
                    <div class="shrink-0 flex items-center gap-2.5">
                        <button type="button"
                                @click="openCreateModal()"
                                class="w-full sm:w-auto px-5 py-3 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-xs sm:text-sm rounded-2xl shadow-lg shadow-indigo-500/30 flex items-center justify-center gap-2 transition-all border border-indigo-400/30 hover:scale-[1.02] active:scale-[0.98]">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            <span>افزودن واحد جدید</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- 4 KPI Stat Counters -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5">
            <!-- Stat 1: Total Nodes -->
            <div class="bg-white dark:bg-slate-900 p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-3.5">
                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <div>
                    <span class="block text-[11px] font-bold text-slate-400 dark:text-slate-500">تعداد کل واحدها</span>
                    <span class="text-lg sm:text-2xl font-black text-slate-800 dark:text-slate-100">{{ $stats['total_nodes'] }} <span class="text-xs font-normal text-slate-400">واحد</span></span>
                </div>
            </div>

            <!-- Stat 2: Departments -->
            <div class="bg-white dark:bg-slate-900 p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-3.5">
                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                </div>
                <div>
                    <span class="block text-[11px] font-bold text-slate-400 dark:text-slate-500">معاونت‌ها و حوزه‌ها</span>
                    <span class="text-lg sm:text-2xl font-black text-slate-800 dark:text-slate-100">{{ $stats['departments_count'] }} <span class="text-xs font-normal text-slate-400">مورد</span></span>
                </div>
            </div>

            <!-- Stat 3: Units & Teams -->
            <div class="bg-white dark:bg-slate-900 p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-3.5">
                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <div>
                    <span class="block text-[11px] font-bold text-slate-400 dark:text-slate-500">واحدها و تیم‌ها</span>
                    <span class="text-lg sm:text-2xl font-black text-slate-800 dark:text-slate-100">{{ $stats['units_teams_count'] }} <span class="text-xs font-normal text-slate-400">مورد</span></span>
                </div>
            </div>

            <!-- Stat 4: Projects Count -->
            <div class="bg-white dark:bg-slate-900 p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-3.5">
                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <div>
                    <span class="block text-[11px] font-bold text-slate-400 dark:text-slate-500">پروژه‌های سازمانی</span>
                    <span class="text-lg sm:text-2xl font-black text-slate-800 dark:text-slate-100">{{ $stats['total_projects'] }} <span class="text-xs font-normal text-slate-400">مورد</span></span>
                </div>
            </div>
        </div>

        <!-- Current User Position Highlight Bar (if exists) -->
        @if($currentUserNode)
            <div class="bg-emerald-500/10 dark:bg-emerald-950/40 border border-emerald-500/30 rounded-2xl p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-emerald-900 dark:text-emerald-200">
                <div class="flex items-center gap-3">
                    <span class="relative flex h-3 w-3 shrink-0">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                    <span class="text-xs font-bold">
                        موقعیت فعلی شما:
                        <strong class="text-emerald-700 dark:text-emerald-300 font-black text-sm">{{ $currentUserNode->name }}</strong>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-200/60 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-300 mr-1.5">{{ $currentUserNode->type->label() }}</span>
                    </span>
                </div>
                <button type="button"
                        @click="searchQuery = '{{ addslashes($currentUserNode->name) }}'; activeView = 'tree'"
                        class="text-[11px] font-bold text-emerald-700 dark:text-emerald-300 hover:underline self-end sm:self-center">
                    مشاهده در چارت ←
                </button>
            </div>
        @endif

        <!-- Main Workspace: Toolbar + View Switcher -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm p-4 sm:p-6 space-y-5">

            <!-- Top Toolbar (Search, Filter, Actions, View Switcher) -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-5 border-b border-slate-100 dark:border-slate-800">
                <!-- Search & Filter Controls -->
                <div class="flex flex-col sm:flex-row sm:items-center gap-2.5 flex-1 max-w-2xl">
                    <!-- Search Input -->
                    <div class="relative flex-1">
                        <svg class="w-4 h-4 text-slate-400 absolute right-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text"
                               x-model="searchQuery"
                               placeholder="جستجو در بین واحدهای سازمانی..."
                               class="w-full pl-8 pr-10 py-2.5 rounded-2xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        <button type="button"
                                x-show="searchQuery.length > 0"
                                @click="searchQuery = ''"
                                class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs font-bold">
                            &times;
                        </button>
                    </div>

                    <!-- Type Filter -->
                    <div class="sm:w-48">
                        <select x-model="selectedType"
                                class="w-full px-3 py-2.5 rounded-2xl text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                            <option value="">همه بخش‌ها</option>
                            @foreach($nodeTypes as $nType)
                                <option value="{{ $nType->label() }}">{{ $nType->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- View Mode Switcher + Expand/Collapse Buttons -->
                <div class="flex flex-wrap items-center justify-between sm:justify-end gap-2.5">
                    <!-- Expand / Collapse All (Tree Mode only) -->
                    <div x-show="activeView === 'tree'" class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-2xl">
                        <button type="button"
                                @click="$dispatch('expand-all')"
                                class="px-2.5 py-1.5 text-[11px] font-bold text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-white dark:hover:bg-slate-700 rounded-xl transition-all flex items-center gap-1"
                                title="باز کردن تمام شاخه‌ها">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            <span>باز کردن همه</span>
                        </button>
                        <button type="button"
                                @click="$dispatch('collapse-all')"
                                class="px-2.5 py-1.5 text-[11px] font-bold text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-white dark:hover:bg-slate-700 rounded-xl transition-all flex items-center gap-1"
                                title="بستن تمام شاخه‌ها">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                            <span>بستن همه</span>
                        </button>
                    </div>

                    <!-- View Selector Tabs -->
                    <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-2xl">
                        <!-- Tree View Tab -->
                        <button type="button"
                                @click="activeView = 'tree'"
                                :class="activeView === 'tree' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'"
                                class="px-3 py-1.5 text-xs font-bold rounded-xl transition-all flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                            <span class="hidden sm:inline">نمای درختی</span>
                            <span class="sm:hidden">درختی</span>
                        </button>

                        <!-- Visual Node Chart Tab -->
                        <button type="button"
                                @click="activeView = 'chart'"
                                :class="activeView === 'chart' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'"
                                class="px-3 py-1.5 text-xs font-bold rounded-xl transition-all flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            <span class="hidden sm:inline">چارت درختی</span>
                            <span class="sm:hidden">نمودار</span>
                        </button>

                        <!-- Directory List Tab -->
                        <button type="button"
                                @click="activeView = 'directory'"
                                :class="activeView === 'directory' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'"
                                class="px-3 py-1.5 text-xs font-bold rounded-xl transition-all flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span class="hidden sm:inline">جدول واحدها</span>
                            <span class="sm:hidden">جدول</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- View 1: Responsive Hierarchical Tree -->
            <div x-show="activeView === 'tree'" class="space-y-4">
                @if($tree->isEmpty())
                    <div class="py-16 text-center text-slate-400 dark:text-slate-500">
                        <svg class="w-12 h-12 mx-auto mb-3 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        <p class="font-bold text-slate-600 dark:text-slate-400 text-sm">هنوز واحد سازمانی ثبت نشده است.</p>
                        <p class="text-xs text-slate-400 mt-1">برای شروع، روی «افزودن واحد جدید» کلیک کنید.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($tree as $rootNode)
                            @include('organization.partials.tree-node', ['node' => $rootNode, 'level' => 0])
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- View 2: Interactive Visual Org Chart -->
            <div x-show="activeView === 'chart'" class="space-y-4" style="display: none;">
                <!-- Chart Controls Bar -->
                <div class="flex flex-wrap items-center justify-between gap-3 bg-slate-50 dark:bg-slate-800/60 p-3 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 text-xs">
                    <div class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>می‌توانید با دکمه‌های زوم یا اسکرول، چارت را با دقت بیشتری ببینید.</span>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="zoomOut()" class="w-8 h-8 rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-bold flex items-center justify-center hover:bg-slate-100 transition-colors" title="کوچک‌نمایی">-</button>
                        <span class="px-2 font-mono font-bold text-slate-600 dark:text-slate-300" x-text="zoomLevel + '%'">100%</span>
                        <button type="button" @click="zoomIn()" class="w-8 h-8 rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-bold flex items-center justify-center hover:bg-slate-100 transition-colors" title="بزرگ‌نمایی">+</button>
                        <button type="button" @click="resetZoom()" class="px-2.5 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-700 rounded-xl transition-colors">بازنشانی</button>
                    </div>
                </div>

                <!-- Infinite Canvas Container -->
                <div class="overflow-x-auto overflow-y-auto p-6 sm:p-12 min-h-[480px] bg-slate-50/50 dark:bg-slate-950/40 rounded-3xl border border-dashed border-slate-200 dark:border-slate-800 text-center flex justify-center">
                    <div class="transition-transform duration-200 origin-top inline-block"
                         :style="`transform: scale(${zoomLevel / 100})`">
                        <div class="flex flex-col items-center gap-8">
                            @foreach($tree as $rootNode)
                                @include('organization.partials.visual-node', ['node' => $rootNode])
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- View 3: Directory & Breadcrumbs View -->
            <div x-show="activeView === 'directory'" class="space-y-4" style="display: none;">
                @php
                    $canAnyManage = auth()->user()?->isSuperAdmin() || auth()->user()?->isOrganizationManager() || auth()->user()?->isProjectOrUnitManager();
                @endphp
                <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800">
                    <table class="w-full text-right text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="p-3.5">نام واحد</th>
                                <th class="p-3.5">نوع سطح</th>
                                <th class="p-3.5 hidden md:table-cell">واحد بالادست</th>
                                <th class="p-3.5 text-center">پروژه‌های مرتبط</th>
                                @if($canAnyManage)
                                    <th class="p-3.5 text-left">عملیات</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 bg-white dark:bg-slate-900">
                            @foreach($flatNodes as $nodeItem)
                                @php
                                    // Build breadcrumb trail
                                    $trail = [];
                                    $curr = $nodeItem->parent;
                                    while($curr) {
                                        $trail[] = $curr->name;
                                        $curr = $curr->parent;
                                    }
                                    $trail = array_reverse($trail);
                                    $trailStr = empty($trail) ? 'واحد اصلی' : implode(' > ', $trail);
                                    $canManageRow = auth()->user()?->isSuperAdmin() || auth()->user()?->isOrganizationManager() || (auth()->user()?->isProjectOrUnitManager() && app(\App\Services\AuthorizationService::class)->canAccessNode(auth()->user(), $nodeItem->id));
                                @endphp
                                <tr x-show="matchesDirectory('{{ addslashes($nodeItem->name) }}', '{{ addslashes($nodeItem->type->label()) }}', '{{ addslashes($trailStr) }}')"
                                    class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="p-3.5 font-bold text-slate-800 dark:text-slate-100">
                                        <div class="flex items-center gap-2">
                                            <span class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 flex items-center justify-center font-black text-xs shrink-0">
                                                {{ mb_substr($nodeItem->name, 0, 1) }}
                                            </span>
                                            <span class="truncate max-w-[200px]">{{ $nodeItem->name }}</span>
                                        </div>
                                    </td>
                                    <td class="p-3.5">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                            {{ $nodeItem->type->label() }}
                                        </span>
                                    </td>
                                    <td class="p-3.5 text-slate-500 dark:text-slate-400 text-[11px] hidden md:table-cell">
                                        {{ $trailStr }}
                                    </td>
                                    <td class="p-3.5 text-center font-bold text-indigo-600 dark:text-indigo-400">
                                        {{ $nodeItem->projects_count ?? 0 }}
                                    </td>
                                    @if($canAnyManage)
                                        <td class="p-3.5 text-left">
                                            @if($canManageRow)
                                                <div class="flex items-center justify-end gap-1">
                                                    <button type="button"
                                                            @click="openCreateModal({ parentId: {{ $nodeItem->id }}, parentName: '{{ addslashes($nodeItem->name) }}' })"
                                                            class="p-1 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 rounded-lg"
                                                            title="افزودن زیرمجموعه">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                                    </button>
                                                    <button type="button"
                                                            @click="openEditModal({
                                                                id: {{ $nodeItem->id }},
                                                                name: '{{ addslashes($nodeItem->name) }}',
                                                                type: '{{ $nodeItem->type->value }}',
                                                                parentId: '{{ $nodeItem->parent_id ?? '' }}',
                                                                description: '{{ addslashes($nodeItem->description ?? '') }}',
                                                                sortOrder: {{ $nodeItem->sort_order ?? 0 }}
                                                            })"
                                                            class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg"
                                                            title="ویرایش">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                    </button>
                                                    <form method="POST" action="{{ route('organization.destroy', $nodeItem) }}" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                onclick="return confirm('آیا از حذف «{{ addslashes($nodeItem->name) }}» مطمئن هستید؟')"
                                                                class="p-1 text-rose-500 hover:text-rose-700 rounded-lg"
                                                                title="حذف">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 1. Create Node Modal -->
        <div x-show="createNodeModal"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
             style="display: none;">
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 w-full max-w-lg shadow-2xl border border-slate-200 dark:border-slate-800"
                 @click.away="createNodeModal = false">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <h3 class="font-bold text-base text-slate-800 dark:text-slate-100">افزودن واحد جدید</h3>
                        <p class="text-xs text-slate-400 mt-0.5" x-show="createParentName" x-text="'زیرمجموعه: ' + createParentName"></p>
                    </div>
                    <button type="button" @click="createNodeModal = false" class="text-slate-400 hover:text-slate-600 text-2xl font-bold leading-none">&times;</button>
                </div>

                <form method="POST" action="{{ route('organization.store') }}" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">نام واحد *</label>
                        <input type="text" name="name" placeholder="مثلاً: واحد بهره‌برداری یا معاونت فنی" required class="w-full px-3.5 py-2.5 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">نوع بخش *</label>
                            <select name="type" required class="w-full px-3.5 py-2.5 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                                @foreach($nodeTypes as $typeItem)
                                    <option value="{{ $typeItem->value }}">{{ $typeItem->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">واحد بالادست (اختیاری)</label>
                            <select name="parent_id" x-model="createParentId" class="w-full px-3.5 py-2.5 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                                <option value="">-- بخش اصلی (بدون بالادست) --</option>
                                @foreach($flatNodes as $fNode)
                                    <option value="{{ $fNode->id }}">{{ $fNode->name }} ({{ $fNode->type->label() }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">توضیحات</label>
                        <textarea name="description" rows="2.5" placeholder="حوزه فعالیت یا نکات مرتبط..." class="w-full px-3.5 py-2.5 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">ترتیب نمایش</label>
                        <input type="number" name="sort_order" value="0" min="0" class="w-full px-3.5 py-2.5 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="createNodeModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors">انصراف</button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-md transition-all">ثبت واحد</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. Edit Node Modal -->
        <div x-show="editNodeModal"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
             style="display: none;">
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 w-full max-w-lg shadow-2xl border border-slate-200 dark:border-slate-800"
                 @click.away="editNodeModal = false">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="font-bold text-base text-slate-800 dark:text-slate-100">ویرایش واحد سازمانی</h3>
                    <button type="button" @click="editNodeModal = false" class="text-slate-400 hover:text-slate-600 text-2xl font-bold leading-none">&times;</button>
                </div>

                <form method="POST" :action="'{{ url('/organization') }}/' + editForm.id" class="mt-5 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">نام واحد *</label>
                        <input type="text" name="name" x-model="editForm.name" required class="w-full px-3.5 py-2.5 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">نوع بخش *</label>
                            <select name="type" x-model="editForm.type" required class="w-full px-3.5 py-2.5 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                                @foreach($nodeTypes as $typeItem)
                                    <option value="{{ $typeItem->value }}">{{ $typeItem->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">واحد بالادست (اختیاری)</label>
                            <select name="parent_id" x-model="editForm.parent_id" class="w-full px-3.5 py-2.5 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                                <option value="">-- بخش اصلی (بدون بالادست) --</option>
                                @foreach($flatNodes as $fNode)
                                    <option value="{{ $fNode->id }}">{{ $fNode->name }} ({{ $fNode->type->label() }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">توضیحات</label>
                        <textarea name="description" x-model="editForm.description" rows="2.5" class="w-full px-3.5 py-2.5 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">ترتیب نمایش</label>
                        <input type="number" name="sort_order" x-model="editForm.sort_order" min="0" class="w-full px-3.5 py-2.5 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="editNodeModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors">انصراف</button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-md transition-all">ذخیره تغییرات</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
