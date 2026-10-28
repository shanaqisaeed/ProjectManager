@props(['project'])

@php
    $statusColors = [
        'ACTIVE' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800',
        'ON_HOLD' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800',
        'COMPLETED' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800',
        'CANCELLED' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800',
        'ARCHIVED' => 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
    ];
    $colorClass = $statusColors[$project->status->value] ?? 'bg-slate-100 text-slate-700';
    $treeDepth = $project->tree_depth ?? 0;
@endphp

<div class="p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md hover:border-indigo-300 dark:hover:border-indigo-700 transition-all flex flex-col justify-between space-y-4">
    <div class="space-y-4">
        <!-- Top Bar: Key, Hierarchy Tag, Priority & Status -->
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-2">
                <span class="px-2.5 py-1 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 text-xs font-mono font-bold border border-indigo-200 dark:border-indigo-800">
                    {{ $project->project_key }}
                </span>
                
                @if(isset($project->priority))
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold shadow-sm {{ $project->priority->badgeClass() }}">
                        {{ $project->priority->label() }}
                    </span>
                @endif

                @if($treeDepth > 0)
                    <span class="px-2 py-0.5 rounded-md bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 text-[10px] font-bold">
                        ↳ زیرپروژه (سطح {{ $treeDepth }})
                    </span>
                @else
                    <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-[10px] font-bold">
                        پروژه اصلی
                    </span>
                @endif
            </div>
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $colorClass }}">
                {{ $project->status->label() }}
            </span>
        </div>

        @if($alert = $project->deadline_alert)
            <div class="p-2.5 rounded-2xl border text-xs font-bold shadow-xs flex items-center gap-2 {{ $alert['class'] }}">
                <span>{{ $alert['label'] }}</span>
            </div>
        @endif

        <!-- Title & Parent Link -->
        <div class="space-y-1">
            <a href="{{ route('projects.show', $project) }}" class="font-black text-slate-800 dark:text-slate-100 text-base sm:text-lg hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors line-clamp-2">
                {{ $project->title }}
            </a>
            @if($project->parent)
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    پروژه اصلی: <a href="{{ route('projects.show', $project->parent) }}" class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">{{ $project->parent->title }}</a>
                </p>
            @endif
        </div>

        <!-- Timeline Progress Bar -->
        @php
            $prog = $project->timeline_progress;
            $barColor = $prog >= 85 ? 'bg-rose-500' : ($prog >= 60 ? 'bg-amber-500' : 'bg-emerald-500');
        @endphp
        <div class="space-y-1.5 p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
            <div class="flex items-center justify-between text-xs">
                <span class="text-slate-500 font-semibold">میزان پیشرفت:</span>
                <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ $prog }}%</span>
            </div>
            <div class="w-full h-2 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                <div class="h-full {{ $barColor }} rounded-full transition-all duration-300" style="width: {{ min(100, $prog) }}%"></div>
            </div>
        </div>

        <!-- Metadata Tags Grid -->
        <div class="space-y-2 pt-1 text-xs">
            <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                <span class="text-slate-400 font-medium">واحد سازمانی:</span>
                <span class="font-bold text-slate-700 dark:text-slate-300">{{ $project->organizationNode?->name ?? '-' }}</span>
            </div>

            @if($project->children && $project->children->isNotEmpty())
                <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                    <span class="text-slate-400 font-medium">زیرمجموعه‌ها:</span>
                    <span class="px-2 py-0.5 rounded-lg bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 font-bold border border-violet-200 dark:border-violet-800">
                        {{ $project->children->count() }} زیرپروژه
                    </span>
                </div>
            @endif

            <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                <span class="text-slate-400 font-medium">زمان شروع:</span>
                <span class="font-mono text-slate-700 dark:text-slate-300">{{ $project->start_date ? verta($project->start_date)->format('Y/m/d') : '-' }}</span>
            </div>

            <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                <span class="text-slate-400 font-medium">مهلت تحویل:</span>
                <div class="flex items-center gap-1.5">
                    <span class="font-mono text-slate-700 dark:text-slate-300">{{ $project->deadline ? verta($project->deadline)->format('Y/m/d') : '-' }}</span>
                    @if($project->deadline && $project->status->value !== 'COMPLETED' && $project->status->value !== 'ARCHIVED')
                        @php
                            $cardDaysRem = now()->startOfDay()->diffInDays($project->deadline->startOfDay(), false);
                        @endphp
                        @if($cardDaysRem > 0)
                            <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800 text-[10px] font-bold font-mono">
                                {{ $cardDaysRem }} روز مانده
                            </span>
                        @elseif($cardDaysRem === 0)
                            <span class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800 text-[10px] font-bold">
                                امروز آخرین مهلت
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800 text-[10px] font-bold animate-pulse">
                                🚨 {{ abs($cardDaysRem) }} روز تاخیر
                            </span>
                        @endif
                    @endif
                </div>
            </div>

            @if($project->projectManagers && $project->projectManagers->isNotEmpty())
                <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                    <span class="text-slate-400 font-medium">مدیران پروژه:</span>
                    <div class="flex -space-x-1.5 space-x-reverse overflow-hidden">
                        @foreach($project->projectManagers as $pm)
                            <div class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[10px] font-bold ring-2 ring-white dark:ring-slate-900" title="{{ $pm->user?->full_name }}">
                                {{ mb_substr($pm->user?->first_name ?? 'P', 0, 1) }}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Card Actions Footer -->
    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
        <a href="{{ route('projects.show', $project) }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs shadow-sm transition-all flex items-center gap-1">
            <span>مشاهده</span>
            <svg class="w-3.5 h-3.5 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </a>

        <div class="flex items-center gap-2 text-xs font-bold">
            @if($project->status->value === 'COMPLETED')
                <span class="px-2.5 py-1 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 text-[10px] font-bold border border-blue-200 dark:border-blue-800">
                    🔒 تکمیل‌شده
                </span>
            @else
                <a href="{{ route('projects.edit', $project) }}" class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                    ویرایش
                </a>

                @if($project->status->value !== 'ARCHIVED')
                    <form action="{{ route('projects.archive', $project) }}" method="POST" onsubmit="return confirm('آیا می‌خواهید این پروژه را آرشیو کنید؟');" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/60 border border-amber-200 dark:border-amber-800 transition-colors">
                            آرشیو
                        </button>
                    </form>
                @else
                    <form action="{{ route('projects.unarchive', $project) }}" method="POST" onsubmit="return confirm('آیا می‌خواهید این پروژه از حالت آرشیو خارج شود؟');" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 border border-emerald-200 dark:border-emerald-800 transition-colors">
                            خروج از آرشیو
                        </button>
                    </form>
                    <form action="{{ route('projects.destroy', $project) }}" method="POST" onsubmit="return confirm('این پروژه آرشیو شده است. آیا از حذف کامل آن مطمئن هستید؟');" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/60 border border-rose-200 dark:border-rose-800 transition-colors">
                            حذف نهایی
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>
</div>
