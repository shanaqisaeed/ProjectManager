<x-app-layout title="ماتریس دسترسی نقش‌های پروژه - سیستم مدیریت سازمانی" header="دسترسی نقش‌های پروژه">
    <div class="space-y-8" x-data="{
        checkAll() {
            document.querySelectorAll('input[type=checkbox][name^=matrix]').forEach(cb => cb.checked = true);
        },
        uncheckAll() {
            document.querySelectorAll('input[type=checkbox][name^=matrix]').forEach(cb => cb.checked = false);
        },
        checkAllRole(roleValue) {
            document.querySelectorAll(`.role-check-${roleValue}`).forEach(cb => cb.checked = true);
        },
        uncheckAllRole(roleValue) {
            document.querySelectorAll(`.role-check-${roleValue}`).forEach(cb => cb.checked = false);
        },
        checkGroup(groupKey, state = true) {
            document.querySelectorAll(`input[data-perm-group='${groupKey}']`).forEach(cb => cb.checked = state);
        },
        checkGroupForRole(roleValue, groupKey, state = true) {
            document.querySelectorAll(`.role-check-${roleValue}[data-perm-group='${groupKey}']`).forEach(cb => cb.checked = state);
        }
    }">
        <!-- Hero Header Banner -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-8 shadow-xl border border-indigo-900/30">
            <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-2">
                    <h1 class="text-2xl sm:text-3xl font-black text-white">ماتریس دسترسی پیش‌فرض نقش‌های پروژه</h1>
                    <p class="text-slate-300 text-xs sm:text-sm max-w-xl">
                        تعیین و پیکربندی مجوزهای پیش‌فرض برای نقش‌های مختلف اعضا (مدیر پروژه، راهبر فنی، عضو تیم، ناظر).
                    </p>
                </div>
            </div>
            <div class="absolute -left-10 -bottom-10 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        </div>

        <!-- Quick Bulk Action Bar -->
        <div class="p-5 rounded-3xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-xs text-indigo-900 dark:text-indigo-200 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span>عملیات گروهی سریع (Bulk Actions)</span>
                </h3>
                <span class="text-[11px] text-indigo-600 dark:text-indigo-400 font-medium">جلوگیری از کلیک‌های تکراری</span>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <button type="button" @click="checkGroup('TASK')" class="px-3.5 py-2 rounded-xl bg-white dark:bg-slate-900 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-600 text-indigo-700 dark:text-indigo-300 font-bold border border-indigo-200 dark:border-indigo-800 transition-all shadow-xs flex items-center gap-1.5">
                    <span>⚡ اعطای تمام دسترسی‌های تسک‌ها</span>
                </button>
                <button type="button" @click="checkGroup('PROJECT')" class="px-3.5 py-2 rounded-xl bg-white dark:bg-slate-900 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-600 text-indigo-700 dark:text-indigo-300 font-bold border border-indigo-200 dark:border-indigo-800 transition-all shadow-xs flex items-center gap-1.5">
                    <span>📁 اعطای تمام دسترسی‌های پروژه‌ها</span>
                </button>
                <button type="button" @click="checkGroup('MEMBER')" class="px-3.5 py-2 rounded-xl bg-white dark:bg-slate-900 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-600 text-indigo-700 dark:text-indigo-300 font-bold border border-indigo-200 dark:border-indigo-800 transition-all shadow-xs flex items-center gap-1.5">
                    <span>👥 اعطای تمام دسترسی‌های اعضا</span>
                </button>
                <button type="button" @click="checkGroup('TEMPLATE')" class="px-3.5 py-2 rounded-xl bg-white dark:bg-slate-900 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-600 text-indigo-700 dark:text-indigo-300 font-bold border border-indigo-200 dark:border-indigo-800 transition-all shadow-xs flex items-center gap-1.5">
                    <span>📋 اعطای تمام دسترسی‌های الگوها</span>
                </button>
                <div class="h-4 w-px bg-indigo-200 dark:bg-indigo-800 mx-1 hidden sm:block"></div>
                <button type="button" @click="checkAll()" class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold transition-all shadow-xs">
                    انتخاب همه مجوزها
                </button>
                <button type="button" @click="uncheckAll()" class="px-3.5 py-2 rounded-xl bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 text-rose-700 dark:text-rose-300 font-bold border border-rose-200 dark:border-rose-800 transition-all shadow-xs">
                    پاکسازی کامل
                </button>
            </div>
        </div>

        <!-- Role Descriptions Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($roles as $r)
                @php
                    $roleBadges = [
                        'PROJECT_MANAGER' => 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800',
                        'TECHNICAL_LEAD' => 'bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 border-violet-200 dark:border-violet-800',
                        'MEMBER' => 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                        'VIEWER' => 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700',
                    ];
                    $badgeStyle = $roleBadges[$r->value] ?? 'bg-slate-100 text-slate-700';
                @endphp
                <div class="p-4 rounded-2xl border bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 shadow-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="px-2.5 py-1 rounded-xl text-xs font-bold border {{ $badgeStyle }}">
                            {{ $r->label() }}
                        </span>
                        <span class="text-[10px] font-mono text-slate-400">{{ $r->value }}</span>
                    </div>
                    <div class="flex items-center justify-between pt-2 text-[11px]">
                        <button type="button" @click="checkAllRole('{{ $r->value }}')" class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline">
                            انتخاب همه نقش
                        </button>
                        <span class="text-slate-300">|</span>
                        <button type="button" @click="uncheckAllRole('{{ $r->value }}')" class="text-rose-600 dark:text-rose-400 font-bold hover:underline">
                            لغو همه نقش
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Permissions Matrix Form -->
        <form method="POST" action="{{ route('project-role-permissions.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <h3 class="font-bold text-slate-800 dark:text-slate-100 text-base">جدول تنظیمات دسترسی به ازای نقش</h3>
                        <p class="text-xs text-slate-400 mt-0.5">تیک زدن هر مجوز باعث اعطای آن به صورت پیش‌فرض به تمام کاربران دارای آن نقش در پروژه‌ها می‌شود.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-right text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold">
                                <th class="pb-3 pr-4">عنوان مجوز سیستم</th>
                                @foreach($roles as $r)
                                    <th class="pb-3 text-center min-w-[140px]">
                                        <div class="font-bold text-slate-800 dark:text-slate-200">{{ $r->label() }}</div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
                            @php
                                $taskPerms = ['CREATE_TASK', 'UPDATE_TASK', 'DELETE_TASK', 'ASSIGN_TASK', 'MANAGE_MILESTONES', 'MANAGE_GANTT', 'MANAGE_DEPENDENCIES'];
                                $projectPerms = ['VIEW_PROJECT', 'UPDATE_PROJECT', 'DELETE_PROJECT', 'ARCHIVE_PROJECT', 'CREATE_SUBPROJECT', 'UPDATE_SUBPROJECT', 'DELETE_SUBPROJECT', 'EXPORT_PROJECT', 'VIEW_ARCHIVED'];
                                $memberPerms = ['MANAGE_MEMBERS', 'INVITE_MEMBERS', 'REMOVE_MEMBERS', 'MANAGE_TEAMS'];
                                $templatePerms = ['CREATE_TEMPLATE', 'MANAGE_TEMPLATES', 'CREATE_FROM_TEMPLATE'];
                            @endphp

                            @foreach($permissions as $p)
                                @php
                                    $groupKey = in_array($p->value, $taskPerms) ? 'TASK' : (in_array($p->value, $projectPerms) ? 'PROJECT' : (in_array($p->value, $memberPerms) ? 'MEMBER' : (in_array($p->value, $templatePerms) ? 'TEMPLATE' : 'OTHER')));
                                @endphp

                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/30 transition-colors">
                                    <!-- Permission Name & Description -->
                                    <td class="py-4 pr-4">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-800 dark:text-slate-100">{{ $p->label() }}</span>
                                            @if($groupKey === 'TASK')
                                                <span class="px-2 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 text-[9px] font-bold">تسک‌ها</span>
                                            @elseif($groupKey === 'PROJECT')
                                                <span class="px-2 py-0.5 rounded-md bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 text-[9px] font-bold">پروژه</span>
                                            @elseif($groupKey === 'MEMBER')
                                                <span class="px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 text-[9px] font-bold">اعضا</span>
                                            @elseif($groupKey === 'TEMPLATE')
                                                <span class="px-2 py-0.5 rounded-md bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 text-[9px] font-bold">الگوها</span>
                                            @endif
                                        </div>
                                        <div class="text-[10px] font-mono text-slate-400 mt-0.5">{{ $p->value }}</div>
                                    </td>

                                    <!-- Role Checkboxes -->
                                    @foreach($roles as $r)
                                        @php
                                            $isAllowed = isset($rolePermissions[$r->value]) && in_array($p->value, $rolePermissions[$r->value]);
                                        @endphp
                                        <td class="py-4 text-center">
                                            <label class="inline-flex items-center justify-center cursor-pointer">
                                                <input type="checkbox"
                                                       name="matrix[{{ $r->value }}][{{ $p->value }}]"
                                                       value="1"
                                                       data-perm-group="{{ $groupKey }}"
                                                       {{ $isAllowed ? 'checked' : '' }}
                                                       class="role-check-{{ $r->value }} w-5 h-5 rounded-lg border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 bg-slate-50 dark:bg-slate-800 transition-all cursor-pointer">
                                            </label>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Submit Button Card -->
            <div class="flex items-center justify-end gap-4 p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-500/20 flex items-center gap-2 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>ذخیره تغییرات ماتریس دسترسی نقش‌ها</span>
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
