@props(['subprojects', 'depth' => 0])

<div class="space-y-3">
    @foreach($subprojects as $child)
        <div class="p-4 sm:p-5 rounded-2xl border transition-all shadow-sm space-y-3 {{ $child->status->value === 'ARCHIVED' ? 'bg-slate-100/80 dark:bg-slate-800/60 border-slate-300 dark:border-slate-700' : 'bg-slate-50/50 dark:bg-slate-800/40 border-slate-200 dark:border-slate-800' }}" style="margin-right: {{ min($depth, 4) * 1 }}rem;">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    @if($depth > 0)
                        <span class="text-indigo-500 font-mono text-sm select-none">↳</span>
                    @endif
                    <span class="px-2.5 py-1 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 text-xs font-mono font-bold border border-indigo-200 dark:border-indigo-800">
                        {{ $child->project_key }}
                    </span>
                    <div>
                        <a href="{{ route('projects.show', $child) }}" class="font-bold text-slate-800 dark:text-slate-100 hover:text-indigo-600 dark:hover:text-indigo-400">
                            {{ $child->title }}
                        </a>
                        @if($child->organizationNode)
                            <span class="text-xs text-slate-400 mr-2">({{ $child->organizationNode->name }})</span>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    @php
                        $stColors = [
                            'ACTIVE' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300',
                            'ON_HOLD' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300',
                            'COMPLETED' => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300',
                            'CANCELLED' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300',
                            'ARCHIVED' => 'bg-slate-200 text-slate-700 border-slate-300 dark:bg-slate-800 dark:text-slate-300',
                        ];
                        $stClass = $stColors[$child->status->value] ?? 'bg-slate-100 text-slate-700';
                    @endphp
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $stClass }}">
                        {{ $child->status->value === 'ARCHIVED' ? 'آرشیو شده (غیرفعال)' : $child->status->label() }}
                    </span>

                    <a href="{{ route('projects.show', $child) }}" class="px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs transition-colors shadow-sm">
                        مشاهده
                    </a>

                    @if($child->status->value === 'COMPLETED')
                        <span class="px-2.5 py-1 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 font-bold text-xs">
                            🔒 تکمیل‌شده (قفل)
                        </span>
                    @elseif($child->status->value !== 'ARCHIVED')
                        <form action="{{ route('projects.archive', $child) }}" method="POST" onsubmit="return confirm('آیا از انتقال این زیرپروژه به آرشیو اطمینان دارید؟');">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-xl bg-amber-100 dark:bg-amber-950/60 hover:bg-amber-200 dark:hover:bg-amber-900/60 text-amber-800 dark:text-amber-200 border border-amber-300 dark:border-amber-800 font-bold text-xs transition-colors">
                                آرشیو
                            </button>
                        </form>
                    @else
                        <form action="{{ route('projects.unarchive', $child) }}" method="POST" onsubmit="return confirm('آیا از خروج این زیرپروژه از حالت آرشیو اطمینان دارید؟');">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 hover:bg-emerald-200 dark:hover:bg-emerald-900/60 text-emerald-800 dark:text-emerald-200 border border-emerald-300 dark:border-emerald-800 font-bold text-xs transition-colors">
                                خروج از آرشیو
                            </button>
                        </form>
                        <form action="{{ route('projects.destroy', $child) }}" method="POST" onsubmit="return confirm('این زیرپروژه در وضعیت آرشیو است. آیا از حذف نهایی آن اطمینان دارید؟');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1.5 rounded-xl bg-rose-100 dark:bg-rose-950/60 hover:bg-rose-200 dark:hover:bg-rose-900/60 text-rose-800 dark:text-rose-200 border border-rose-300 dark:border-rose-800 font-bold text-xs transition-colors">
                                حذف نهایی
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Recursive Subprojects (Subprojects of Subprojects) -->
            @if($child->childrenRecursive && $child->childrenRecursive->isNotEmpty())
                <div class="pt-3 border-t border-slate-200/80 dark:border-slate-800/80 space-y-2">
                    <p class="text-[11px] font-bold text-slate-400">زیرپروژه‌های {{ $child->title }}:</p>
                    <x-projects.subproject-tree :subprojects="$child->childrenRecursive" :depth="$depth + 1" />
                </div>
            @endif
        </div>
    @endforeach
</div>
