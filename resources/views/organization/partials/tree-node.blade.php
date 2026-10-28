@php
    $typeConfig = match($node->type->value) {
        'ORGANIZATION' => [
            'bg' => 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800/60',
            'avatar' => 'bg-gradient-to-br from-indigo-600 to-violet-700 text-white shadow-indigo-500/25',
            'border' => 'border-r-4 border-r-indigo-600',
            'pill' => 'bg-indigo-100 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300',
        ],
        'DEPARTMENT' => [
            'bg' => 'bg-sky-500/10 text-sky-700 dark:text-sky-300 border-sky-200 dark:border-sky-800/60',
            'avatar' => 'bg-gradient-to-br from-sky-600 to-blue-700 text-white shadow-sky-500/25',
            'border' => 'border-r-4 border-r-sky-500',
            'pill' => 'bg-sky-100 dark:bg-sky-950/80 text-sky-700 dark:text-sky-300',
        ],
        'UNIT' => [
            'bg' => 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/60',
            'avatar' => 'bg-gradient-to-br from-emerald-600 to-teal-700 text-white shadow-emerald-500/25',
            'border' => 'border-r-4 border-r-emerald-500',
            'pill' => 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300',
        ],
        'TEAM' => [
            'bg' => 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/60',
            'avatar' => 'bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-amber-500/25',
            'border' => 'border-r-4 border-r-amber-500',
            'pill' => 'bg-amber-100 dark:bg-amber-950/80 text-amber-700 dark:text-amber-300',
        ],
        default => [
            'bg' => 'bg-purple-500/10 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800/60',
            'avatar' => 'bg-gradient-to-br from-purple-600 to-fuchsia-700 text-white shadow-purple-500/25',
            'border' => 'border-r-4 border-r-purple-500',
            'pill' => 'bg-purple-100 dark:bg-purple-950/80 text-purple-700 dark:text-purple-300',
        ],
    };

    $isUserHere = auth()->user()?->activeRoleAssignments?->contains('organization_node_id', $node->id);
    $hasChildren = $node->children->count() > 0;
    $isSuperAdmin = auth()->user()?->isSuperAdmin() ?? false;
    $isOrgManager = auth()->user()?->isOrganizationManager() ?? false;
    $canManageNode = $isSuperAdmin || $isOrgManager || (auth()->user()?->isProjectOrUnitManager() && app(\App\Services\AuthorizationService::class)->canAccessNode(auth()->user(), $node->id));
@endphp

<div class="space-y-2.5 transition-all duration-200"
     x-data="{
         open: true,
         nodeName: '{{ addslashes($node->name) }}',
         nodeTypeLabel: '{{ addslashes($node->type->label()) }}',
         get isVisible() {
             if (!searchQuery || searchQuery.trim() === '') return true;
             const q = searchQuery.toLowerCase().trim();
             return this.nodeName.toLowerCase().includes(q) || this.nodeTypeLabel.toLowerCase().includes(q);
         }
     }"
     @expand-all.window="open = true"
     @collapse-all.window="open = false"
     x-show="isVisible"
     x-cloak>

    <!-- Node Card -->
    <div class="relative bg-white dark:bg-slate-900 rounded-2xl border p-3 sm:p-4 shadow-sm hover:shadow-md transition-all duration-200 group {{ $typeConfig['border'] }} {{ $isUserHere ? 'ring-2 ring-emerald-500/50 border-emerald-400 dark:border-emerald-500/60 shadow-emerald-500/10' : 'border-slate-200 dark:border-slate-800' }}">

        <!-- User Location Floating Pin (if user belongs here) -->
        @if($isUserHere)
            <div class="absolute -top-2.5 left-4 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-sm flex items-center gap-1.5 animate-pulse z-10">
                <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                <span>واحد شما</span>
            </div>
        @endif

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <!-- Left Info Area: Toggle, Avatar, Title, Badges -->
            <div class="flex items-start sm:items-center gap-2.5 sm:gap-3 flex-1 min-w-0">
                <!-- Expand / Collapse Toggle Button -->
                @if($hasChildren)
                    <button type="button"
                            @click="open = !open"
                            class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 mt-0.5 sm:mt-0 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 flex items-center justify-center transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                            :title="open ? 'بستن زیرمجموعه‌ها' : 'مشاهده زیرمجموعه‌ها'">
                        <svg class="w-4 h-4 transform transition-transform duration-200" :class="open ? 'rotate-90 text-indigo-600 dark:text-indigo-400' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                @else
                    <div class="shrink-0 w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center">
                        <span class="w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-700"></span>
                    </div>
                @endif

                <!-- Node Avatar / Level Letter -->
                <div class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-xl {{ $typeConfig['avatar'] }} flex items-center justify-center font-black text-xs sm:text-sm shadow-sm">
                    {{ mb_substr($node->name, 0, 1) }}
                </div>

                <!-- Main Content & Details -->
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                        <h4 class="font-bold text-slate-800 dark:text-slate-100 text-xs sm:text-sm truncate max-w-[200px] sm:max-w-md" title="{{ $node->name }}">
                            {{ $node->name }}
                        </h4>

                        <!-- Unit Type Badge -->
                        <span class="px-2 py-0.5 rounded-lg text-[10px] sm:text-[11px] font-black {{ $typeConfig['pill'] }}">
                            {{ $node->type->label() }}
                        </span>

                        <!-- Level Indicator Badge -->
                        <span class="hidden sm:inline-flex px-1.5 py-0.5 rounded text-[9px] font-semibold text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-800/80">
                            سطح {{ $level + 1 }}
                        </span>
                    </div>

                    @if($node->description)
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1 mt-0.5" title="{{ $node->description }}">
                            {{ $node->description }}
                        </p>
                    @endif

                    <!-- Mobile Sub-metrics Counters -->
                    <div class="flex flex-wrap items-center gap-2 mt-1 text-[10px] text-slate-500 dark:text-slate-400">
                        @if($hasChildren)
                            <span class="inline-flex items-center gap-1 font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 px-1.5 py-0.5 rounded">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                {{ $node->children->count() }} زیرمجموعه
                            </span>
                        @endif

                        @if(($node->projects_count ?? 0) > 0)
                            <span class="inline-flex items-center gap-1 font-medium bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">
                                <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                {{ $node->projects_count }} پروژه
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Action Toolbar -->
            <div class="flex items-center justify-end gap-1.5 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100 dark:border-slate-800/80 shrink-0">
                @if($canManageNode)
                    <!-- Quick Add Sub-unit -->
                    <button type="button"
                            @click="$dispatch('open-create-modal', { parentId: {{ $node->id }}, parentName: '{{ addslashes($node->name) }}' })"
                            class="px-2.5 py-1.5 text-xs font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 rounded-xl transition-all flex items-center gap-1"
                            title="افزودن زیرمجموعه به این واحد">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        <span class="hidden sm:inline">افزودن زیرمجموعه</span>
                        <span class="sm:hidden text-[11px]">زیرمجموعه</span>
                    </button>

                    <!-- Edit Button -->
                    <button type="button"
                            @click="$dispatch('open-edit-modal', {
                                id: {{ $node->id }},
                                name: '{{ addslashes($node->name) }}',
                                type: '{{ $node->type->value }}',
                                parentId: '{{ $node->parent_id ?? '' }}',
                                description: '{{ addslashes($node->description ?? '') }}',
                                sortOrder: {{ $node->sort_order ?? 0 }}
                            })"
                            class="p-1.5 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all"
                            title="ویرایش اطلاعات">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>

                    <!-- Delete Button -->
                    <form method="POST" action="{{ route('organization.destroy', $node) }}" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                onclick="return confirm('آیا از حذف «{{ addslashes($node->name) }}» مطمئن هستید؟ با این کار زیرمجموعه‌های آن نیز حذف خواهند شد.')"
                                class="p-1.5 text-rose-500 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300 hover:bg-rose-50 dark:hover:bg-rose-950/60 rounded-xl transition-all"
                                title="حذف واحد">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <!-- Recursive Nested Children Container (Responsive Margins) -->
    @if($hasChildren)
        <div x-show="open"
             class="mr-2 sm:mr-6 pr-2 sm:pr-4 border-r-2 border-indigo-100 dark:border-slate-800/80 space-y-2.5 sm:space-y-3 mt-2 sm:mt-3">
            @foreach($node->children as $child)
                @include('organization.partials.tree-node', ['node' => $child, 'level' => $level + 1])
            @endforeach
        </div>
    @endif
</div>
