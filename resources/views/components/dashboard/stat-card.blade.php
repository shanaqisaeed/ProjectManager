@props([
    'title',
    'value',
    'subtext' => null,
    'badge' => null,
    'badgeType' => 'neutral', // neutral, success, warning, danger, info
    'icon' => null,
    'color' => 'indigo', // indigo, emerald, blue, amber, rose, violet
    'link' => null,
])

@php
    $colorMaps = [
        'indigo' => [
            'iconBg' => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800',
            'borderHover' => 'hover:border-indigo-300 dark:hover:border-indigo-700',
            'glow' => 'from-indigo-500/5 to-transparent',
        ],
        'emerald' => [
            'iconBg' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800',
            'borderHover' => 'hover:border-emerald-300 dark:hover:border-emerald-700',
            'glow' => 'from-emerald-500/5 to-transparent',
        ],
        'blue' => [
            'iconBg' => 'bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400 border border-blue-100 dark:border-blue-800',
            'borderHover' => 'hover:border-blue-300 dark:hover:border-blue-700',
            'glow' => 'from-blue-500/5 to-transparent',
        ],
        'amber' => [
            'iconBg' => 'bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-100 dark:border-amber-800',
            'borderHover' => 'hover:border-amber-300 dark:hover:border-amber-700',
            'glow' => 'from-amber-500/5 to-transparent',
        ],
        'rose' => [
            'iconBg' => 'bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-100 dark:border-rose-800',
            'borderHover' => 'hover:border-rose-300 dark:hover:border-rose-700',
            'glow' => 'from-rose-500/5 to-transparent',
        ],
        'violet' => [
            'iconBg' => 'bg-violet-50 text-violet-600 dark:bg-violet-950/60 dark:text-violet-400 border border-violet-100 dark:border-violet-800',
            'borderHover' => 'hover:border-violet-300 dark:hover:border-violet-700',
            'glow' => 'from-violet-500/5 to-transparent',
        ],
    ];

    $badgeMaps = [
        'neutral' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        'success' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800',
        'warning' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800',
        'danger' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-200 dark:border-rose-800 animate-pulse',
        'info' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800',
    ];

    $style = $colorMaps[$color] ?? $colorMaps['indigo'];
    $badgeStyle = $badgeMaps[$badgeType] ?? $badgeMaps['neutral'];
@endphp

<div class="relative overflow-hidden bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-sm transition-all duration-300 {{ $style['borderHover'] }} hover:shadow-md group">
    <!-- Subtle Gradient Background on Hover -->
    <div class="absolute inset-0 bg-gradient-to-br {{ $style['glow'] }} opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>

    <div class="relative flex items-start justify-between gap-3">
        <div class="space-y-1.5 flex-1 min-w-0">
            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 truncate">{{ $title }}</p>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">{{ $value }}</span>
                @if($badge)
                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold {{ $badgeStyle }}">
                        {{ $badge }}
                    </span>
                @endif
            </div>
            @if($subtext)
                <p class="text-[11px] text-slate-400 dark:text-slate-500">{{ $subtext }}</p>
            @endif
        </div>

        @if($icon)
            <div class="w-12 h-12 rounded-2xl {{ $style['iconBg'] }} flex items-center justify-center shrink-0 shadow-xs transition-transform group-hover:scale-105 duration-200">
                {!! $icon !!}
            </div>
        @endif
    </div>

    @if($link)
        <a href="{{ $link }}" class="absolute inset-0 z-10" aria-label="{{ $title }}"></a>
    @endif
</div>
