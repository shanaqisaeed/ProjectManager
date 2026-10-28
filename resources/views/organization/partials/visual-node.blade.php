@php
    $typeConfig = match($node->type->value) {
        'ORGANIZATION' => [
            'header' => 'bg-indigo-600 text-white',
            'border' => 'border-indigo-400/30',
            'badge' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300',
        ],
        'DEPARTMENT' => [
            'header' => 'bg-sky-600 text-white',
            'border' => 'border-sky-400/30',
            'badge' => 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300',
        ],
        'UNIT' => [
            'header' => 'bg-emerald-600 text-white',
            'border' => 'border-emerald-400/30',
            'badge' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
        ],
        'TEAM' => [
            'header' => 'bg-amber-500 text-white',
            'border' => 'border-amber-400/30',
            'badge' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
        ],
        default => [
            'header' => 'bg-purple-600 text-white',
            'border' => 'border-purple-400/30',
            'badge' => 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300',
        ],
    };
    $isUserHere = auth()->user()?->activeRoleAssignments?->contains('organization_node_id', $node->id);
    $hasChildren = $node->children->count() > 0;
    $isSuperAdmin = auth()->user()?->isSuperAdmin() ?? false;
    $isOrgManager = auth()->user()?->isOrganizationManager() ?? false;
    $canManageNode = $isSuperAdmin || $isOrgManager || (auth()->user()?->isProjectOrUnitManager() && app(\App\Services\AuthorizationService::class)->canAccessNode(auth()->user(), $node->id));
@endphp

<div class="flex flex-col items-center">
    <!-- Node Box -->
    <div class="w-64 bg-white dark:bg-slate-900 rounded-2xl border shadow-md hover:shadow-xl transition-all duration-200 overflow-hidden relative group {{ $isUserHere ? 'ring-2 ring-emerald-500 border-emerald-500 shadow-emerald-500/10' : 'border-slate-200 dark:border-slate-800' }}">
        @if($isUserHere)
            <div class="absolute top-2 left-2 z-10">
                <span class="flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                </span>
            </div>
        @endif

        <!-- Card Header with Type Color -->
        <div class="{{ $typeConfig['header'] }} px-3.5 py-2 flex items-center justify-between">
            <span class="text-[10px] font-extrabold tracking-wider uppercase opacity-90">{{ $node->type->label() }}</span>
            @if($isUserHere)
                <span class="text-[9px] font-bold bg-white/20 backdrop-blur-sm px-1.5 py-0.5 rounded-md">واحد شما</span>
            @endif
        </div>

        <!-- Card Body -->
        <div class="p-3.5 space-y-2 text-right" dir="rtl">
            <h5 class="font-bold text-xs text-slate-800 dark:text-slate-100 line-clamp-1" title="{{ $node->name }}">{{ $node->name }}</h5>

            @if($node->description)
                <p class="text-[10px] text-slate-500 dark:text-slate-400 line-clamp-2">{{ $node->description }}</p>
            @endif

            <!-- Stats & Counters -->
            <div class="flex items-center justify-between text-[10px] text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-800/80">
                <span>{{ $node->children->count() }} زیرمجموعه</span>
                @if(($node->projects_count ?? 0) > 0)
                    <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ $node->projects_count }} پروژه</span>
                @else
                    <span>بدون پروژه</span>
                @endif
            </div>

            <!-- Quick Action Toolbar -->
            @if($canManageNode)
                <div class="flex items-center justify-end gap-1 pt-1.5">
                    <button type="button"
                            @click="$dispatch('open-create-modal', { parentId: {{ $node->id }}, parentName: '{{ addslashes($node->name) }}' })"
                            class="p-1 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 rounded-lg transition-colors"
                            title="افزودن زیرمجموعه">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    </button>
                    <button type="button"
                            @click="$dispatch('open-edit-modal', {
                                id: {{ $node->id }},
                                name: '{{ addslashes($node->name) }}',
                                type: '{{ $node->type->value }}',
                                parentId: '{{ $node->parent_id ?? '' }}',
                                description: '{{ addslashes($node->description ?? '') }}',
                                sortOrder: {{ $node->sort_order ?? 0 }}
                            })"
                            class="p-1 text-slate-500 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                            title="ویرایش">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Connector and Children -->
    @if($hasChildren)
        <!-- Vertical connector line down from parent -->
        <div class="w-0.5 h-6 bg-slate-300 dark:bg-slate-700"></div>

        <!-- Horizontal branch line across children -->
        <div class="flex items-start justify-center relative pt-4">
            <!-- Horizontal connector bar -->
            @if($node->children->count() > 1)
                <div class="absolute top-0 right-32 left-32 h-0.5 bg-slate-300 dark:bg-slate-700"></div>
            @endif

            <div class="flex items-start gap-6">
                @foreach($node->children as $child)
                    <div class="flex flex-col items-center relative">
                        <!-- Vertical line down to child -->
                        <div class="w-0.5 h-4 bg-slate-300 dark:bg-slate-700 absolute -top-4"></div>
                        @include('organization.partials.visual-node', ['node' => $child])
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
