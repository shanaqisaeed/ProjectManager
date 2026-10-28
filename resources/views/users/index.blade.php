<x-app-layout title="مدیریت کاربران" header="مدیریت کاربران">
    <div class="space-y-8" x-data="{ createModalOpen: false, roleModalOpen: false, selectedUserId: null, selectedUserName: '' }">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-8 shadow-xl border border-indigo-900/30">
            <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-2">
                    <h1 class="text-2xl sm:text-3xl font-black text-white">کاربران و نقش‌های سازمانی</h1>
                    <p class="text-slate-300 text-xs sm:text-sm max-w-xl">
                        مدیریت اعضای تیم، وضعیت حساب‌های کاربری و دسترسی‌ها.
                    </p>
                </div>

                <!-- Primary Create User Action Button -->
                <div class="shrink-0">
                    <button @click="createModalOpen = true" class="w-full sm:w-auto px-5 py-3 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm rounded-2xl shadow-lg shadow-indigo-500/30 flex items-center justify-center gap-2 transition-all border border-indigo-400/30">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        <span>+ افزودن کاربر جدید</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase">کل اعضا</p>
                    <p class="text-2xl font-black text-slate-900 dark:text-slate-100 mt-1">{{ number_format($totalUsers) }}</p>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 border border-indigo-100 dark:border-indigo-900">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase">حساب‌های فعال</p>
                    <p class="text-2xl font-black text-slate-900 dark:text-slate-100 mt-1">{{ number_format($activeUsers) }}</p>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-100 dark:border-emerald-900">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase">بدون نقش سازمانی</p>
                    <p class="text-2xl font-black text-slate-900 dark:text-slate-100 mt-1">{{ number_format($inactiveUsers) }}</p>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 border border-amber-100 dark:border-amber-900">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase">حساب‌های مسدود</p>
                    <p class="text-2xl font-black text-slate-900 dark:text-slate-100 mt-1">{{ number_format($suspendedUsers) }}</p>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 border border-rose-100 dark:border-rose-900">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <form method="GET" action="{{ route('users.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                    <!-- Search Input -->
                    <div class="relative lg:col-span-2">
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">جستجو در اعضا</label>
                        <div class="relative">
                            <svg class="w-4 h-4 absolute right-3.5 top-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="text"
                                   name="search"
                                   value="{{ $search }}"
                                   placeholder="جستجوی نام، نام کاربری، ایمیل، شماره تماس..."
                                   class="w-full pr-10 pl-3 py-2 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <!-- Status Filter Dropdown -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">وضعیت حساب</label>
                        <select name="status" class="w-full px-3 py-2 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">همه وضعیت‌ها</option>
                            @foreach($userStatuses as $uStatus)
                                <option value="{{ $uStatus->value }}" {{ $status === $uStatus->value ? 'selected' : '' }}>
                                    {{ $uStatus->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Role Filter Dropdown -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">نقش</label>
                        <select name="role" class="w-full px-3 py-2 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">همه نقش‌ها</option>
                            <option value="none" {{ $role === 'none' ? 'selected' : '' }}>بدون نقش</option>
                            @foreach($roles as $rItem)
                                <option value="{{ $rItem->value }}" {{ $role === $rItem->value ? 'selected' : '' }}>
                                    {{ $rItem->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Organization Node Dropdown -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">واحد سازمانی</label>
                        <select name="organization_node_id" class="w-full px-3 py-2 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">همه واحدها</option>
                            @foreach($organizationNodes as $nItem)
                                <option value="{{ $nItem->id }}" {{ (string)$organizationNodeId === (string)$nItem->id ? 'selected' : '' }}>
                                    {{ $nItem->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Per Page Dropdown (Default 50) -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">تعداد در صفحه</label>
                        <select name="per_page" class="w-full px-3 py-2 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-indigo-500 font-bold">
                            @foreach([10, 25, 50, 100, 200] as $count)
                                <option value="{{ $count }}" {{ $perPage === $count ? 'selected' : '' }}>
                                    {{ $count }} مورد {{ $count === 50 ? '(پیش‌فرض)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2 border-t border-slate-100 dark:border-slate-800/60">
                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-md">
                            اعمال فیلترها
                        </button>
                        @if($search || $status || $role || $organizationNodeId || $perPage !== 50 || $sort !== 'latest')
                            <a href="{{ route('users.index') }}" class="px-3 py-2 text-slate-500 hover:text-rose-600 text-xs font-semibold hover:underline">
                                حذف فیلترها
                            </a>
                        @endif
                    </div>

                    <!-- Sort Order Dropdown -->
                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                        <span class="text-xs text-slate-400 font-medium">مرتب‌سازی:</span>
                        <select name="sort" onchange="this.form.submit()" class="px-3 py-1.5 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="latest" {{ $sort === 'latest' ? 'selected' : '' }}>جدیدترین</option>
                            <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>قدیمی‌ترین</option>
                            <option value="name_asc" {{ $sort === 'name_asc' ? 'selected' : '' }}>نام (الف - ی)</option>
                            <option value="name_desc" {{ $sort === 'name_desc' ? 'selected' : '' }}>نام (ی - الف)</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs border-collapse">
                    <thead class="bg-slate-50/70 dark:bg-slate-800/40 text-slate-500 dark:text-slate-400 font-bold border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-6 py-4">نام و نام خانوادگی</th>
                            <th class="px-6 py-4">نام کاربری</th>
                            <th class="px-6 py-4">اطلاعات تماس</th>
                            <th class="px-6 py-4">وضعیت</th>
                            <th class="px-6 py-4">نقش و واحد</th>
                            <th class="px-6 py-4 text-center">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        @forelse($users as $userItem)
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/30 transition-colors">
                                <!-- User Avatar & Name -->
                                <td class="px-6 py-4 font-bold text-slate-800 dark:text-slate-100">
                                    {{ $userItem->full_name }}
                                </td>

                                <!-- Username -->
                                <td class="px-6 py-4 font-mono text-slate-600 dark:text-slate-400" dir="ltr">
                                    @ {{ $userItem->username }}
                                </td>

                                <!-- Contact Info -->
                                <td class="px-6 py-4 text-slate-500">
                                    {{ $userItem->email ?: ($userItem->mobile ?: '-') }}
                                </td>

                                <!-- Status Badge & Toggle -->
                                <td class="px-6 py-4">
                                    <form method="POST" action="{{ route('users.status', $userItem) }}" class="inline-block">
                                        @csrf
                                        <select name="status" onchange="this.form.submit()" class="text-xs font-bold rounded-xl px-2.5 py-1 border outline-none cursor-pointer {{ $userItem->status === \App\Enums\UserStatus::ACTIVE ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 border-emerald-300 dark:border-emerald-800' : ($userItem->status === \App\Enums\UserStatus::SUSPENDED ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/70 dark:text-rose-300 border-rose-300 dark:border-rose-800' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300 border-amber-300 dark:border-amber-800') }}">
                                            @foreach($userStatuses as $uStatus)
                                                <option value="{{ $uStatus->value }}" {{ $userItem->status === $uStatus ? 'selected' : '' }}>
                                                    {{ $uStatus->label() }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>

                                <!-- Roles & Nodes Tags -->
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        @forelse($userItem->activeRoleAssignments as $assignment)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs border {{ $assignment->is_primary ? 'bg-indigo-50 border-indigo-200 text-indigo-700 dark:bg-indigo-950 dark:border-indigo-800 dark:text-indigo-300 font-bold' : 'bg-slate-100 border-slate-200 text-slate-700 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300' }}">
                                                <span>{{ $assignment->role->label() }}</span>
                                                <span class="text-slate-400">({{ $assignment->organizationNode ? $assignment->organizationNode->name : 'کل سازمان' }})</span>

                                                <form method="POST" action="{{ route('users.remove-role', $assignment->id) }}" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" onclick="return confirm('آیا از حذف این نقش اطمینان دارید؟')" class="text-slate-400 hover:text-rose-600 font-bold ml-1">&times;</button>
                                                </form>
                                            </span>
                                        @empty
                                            <span class="text-xs text-amber-600 dark:text-amber-400 font-semibold">بدون نقش سازمانی</span>
                                        @endforelse
                                    </div>
                                </td>

                                <!-- Action Buttons -->
                                <td class="px-6 py-4 text-center">
                                    <button @click="selectedUserId = {{ $userItem->id }}; selectedUserName = '{{ $userItem->full_name }}'; roleModalOpen = true" class="px-3 py-1 text-xs font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 rounded-xl border border-indigo-200 dark:border-indigo-800 transition-all">
                                        + اختصاص نقش
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                    <p class="font-semibold">کاربری با این مشخصات پیدا نشد.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($users->hasPages())
                <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                    {{ $users->links() }}
                </div>
            @endif
        </div>

        <div x-show="createModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 w-full max-w-lg shadow-2xl border border-slate-200 dark:border-slate-800" @click.away="createModalOpen = false">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="font-bold text-base text-slate-800 dark:text-slate-100">افزودن کاربر جدید</h3>
                    <button @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600 font-bold text-xl">&times;</button>
                </div>

                <form method="POST" action="{{ route('users.store') }}" class="mt-5 space-y-4">
                    @csrf
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">نام *</label>
                            <input type="text" name="first_name" required class="w-full px-3 py-2 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">نام خانوادگی *</label>
                            <input type="text" name="last_name" required class="w-full px-3 py-2 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">نام کاربری *</label>
                        <input type="text" name="username" required class="w-full px-3 py-2 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 font-mono" placeholder="username">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">رمز عبور *</label>
                        <input type="password" name="password" required minlength="6" class="w-full px-3 py-2 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">ایمیل</label>
                            <input type="email" name="email" class="w-full px-3 py-2 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">شماره تماس</label>
                            <input type="text" name="mobile" class="w-full px-3 py-2 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 font-mono">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">نقش (اختیاری)</label>
                            <select name="role" class="w-full px-3 py-2 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                                <option value="">-- بدون نقش --</option>
                                @foreach($roles as $roleItem)
                                    @if($isGlobal || (!in_array($roleItem, [\App\Enums\UserRole::SUPER_ADMIN, \App\Enums\UserRole::ORGANIZATION_MANAGER])))
                                        <option value="{{ $roleItem->value }}">{{ $roleItem->label() }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">واحد سازمانی</label>
                            <select name="organization_node_id" class="w-full px-3 py-2 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                                @if($isGlobal)
                                    <option value="">کل سازمان</option>
                                @endif
                                @foreach($organizationNodes as $nodeItem)
                                    <option value="{{ $nodeItem->id }}">{{ $nodeItem->name }} ({{ $nodeItem->type->label() }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="createModalOpen = false" class="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 rounded-xl">انصراف</button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-md">ایجاد حساب کاربری</button>
                    </div>
                </form>
            </div>
        </div>

        <div x-show="roleModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 w-full max-w-md shadow-2xl border border-slate-200 dark:border-slate-800" @click.away="roleModalOpen = false">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="font-bold text-base text-slate-800 dark:text-slate-100">اختصاص نقش به کاربر</h3>
                    <button @click="roleModalOpen = false" class="text-slate-400 hover:text-slate-600 font-bold text-xl">&times;</button>
                </div>

                <form :action="`/users/${selectedUserId}/assign-role`" method="POST" class="mt-5 space-y-4">
                    @csrf
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                        <span class="text-xs text-slate-500">کاربر: </span>
                        <strong x-text="selectedUserName" class="text-xs text-indigo-600 dark:text-indigo-400"></strong>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">نقش *</label>
                        <select name="role" required class="w-full px-3 py-2 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                            @foreach($roles as $roleItem)
                                @if($isGlobal || (!in_array($roleItem, [\App\Enums\UserRole::SUPER_ADMIN, \App\Enums\UserRole::ORGANIZATION_MANAGER])))
                                    <option value="{{ $roleItem->value }}">{{ $roleItem->label() }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">واحد سازمانی</label>
                        <select name="organization_node_id" {{ ! $isGlobal ? 'required' : '' }} class="w-full px-3 py-2 rounded-xl text-xs border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                            @if($isGlobal)
                                <option value="">کل سازمان</option>
                            @endif
                            @foreach($organizationNodes as $nodeItem)
                                <option value="{{ $nodeItem->id }}">{{ $nodeItem->name }} ({{ $nodeItem->type->label() }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" name="is_primary" id="is_primary" value="1" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="is_primary" class="text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">نقش اصلی</label>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="roleModalOpen = false" class="px-4 py-2 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 rounded-xl">انصراف</button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-md">ذخیره نقش</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
