<x-app-layout title="الگوهای آماده پروژه" header="الگوهای آماده پروژه">
    <div class="space-y-8">
        <!-- Hero Banner -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-8 shadow-xl border border-indigo-900/30">
            <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-2">
                    <h1 class="text-2xl sm:text-3xl font-black text-white">الگوهای آماده پروژه</h1>
                    <p class="text-slate-300 text-xs sm:text-sm max-w-xl">
                        ساخت سریع پروژه‌ها با استفاده از ساختار از پیش تعریف‌شده، کارها، زمان‌بندی و مراحل تایید.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('projects.index') }}" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs sm:text-sm border border-slate-700 transition-all shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span>پروژه‌ها</span>
                    </a>
                    <a href="{{ route('project-templates.create') }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs sm:text-sm shadow-lg shadow-indigo-600/30 transition-all shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>+ ساخت الگوی جدید</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
            <form method="GET" action="{{ route('project-templates.index') }}" class="flex flex-col sm:flex-row items-center gap-4">
                <div class="flex-1 w-full">
                    <label class="block text-xs font-bold text-slate-500 mb-1">فیلتر بر اساس واحد سازمانی</label>
                    <select name="organization_node_id" onchange="this.form.submit()" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-xs font-medium focus:ring-2 focus:ring-indigo-500">
                        <option value="">همه الگوها (عمومی و واحدها)</option>
                        @foreach($organizationNodes as $node)
                            <option value="{{ $node->id }}" {{ request('organization_node_id') == $node->id ? 'selected' : '' }}>
                                {{ $node->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if(request()->filled('organization_node_id'))
                    <div class="self-end">
                        <a href="{{ route('project-templates.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-500 text-xs font-bold hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
                            پاکسازی
                        </a>
                    </div>
                @endif
            </form>
        </div>

        <!-- Templates Grid -->
        <div class="space-y-6">
            @if($templates->isEmpty())
                <div class="p-12 text-center bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 space-y-4">
                    <div class="w-16 h-16 rounded-full bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center mx-auto">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <h3 class="font-bold text-base text-slate-800 dark:text-slate-200">هنوز الگوی آماده‌ای تعریف نشده است</h3>
                    <p class="text-xs text-slate-400 max-w-sm mx-auto">می‌توانید برای شروع سریع پروژه‌های پرتکرار، الگوهای آماده بسازید.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($templates as $tmpl)
                        <div class="p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between space-y-4 hover:border-indigo-300 transition-all">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="px-2.5 py-1 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 text-xs font-bold border border-indigo-200 dark:border-indigo-800">
                                        {{ $tmpl->organizationNode?->name ?? 'الگوی عمومی سازمان' }}
                                    </span>
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold shadow-sm {{ $tmpl->priority->badgeClass() }}">
                                        {{ $tmpl->priority->label() }}
                                    </span>
                                </div>

                                <h3 class="font-black text-lg text-slate-800 dark:text-slate-100 line-clamp-1">
                                    {{ $tmpl->title }}
                                </h3>

                                <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
                                    {{ $tmpl->description ?: 'بدون توضیحات تکمیلی' }}
                                </p>

                                <div class="space-y-2 pt-2 text-xs border-t border-slate-100 dark:border-slate-800">
                                    <div class="flex justify-between text-slate-600 dark:text-slate-400">
                                        <span>مدت زمان اجرا:</span>
                                        <strong class="text-slate-800 dark:text-slate-200 font-mono">{{ $tmpl->duration_days }} روز</strong>
                                    </div>
                                    @if($tmpl->children->count() > 0)
                                        <div class="flex justify-between text-slate-600 dark:text-slate-400">
                                            <span>زیرپروژه‌ها:</span>
                                            <strong class="text-purple-600 dark:text-purple-400 font-bold font-mono">{{ $tmpl->children->count() }} زیرپروژه</strong>
                                        </div>
                                    @endif
                                    <div class="flex justify-between text-slate-600 dark:text-slate-400">
                                        <span>تعداد کارها:</span>
                                        <strong class="text-emerald-600 dark:text-emerald-400 font-bold font-mono">{{ $tmpl->tasks->count() }} کار</strong>
                                    </div>
                                    <div class="flex justify-between text-slate-600 dark:text-slate-400">
                                        <span>مراحل تایید سرپرست:</span>
                                        <strong class="text-indigo-600 dark:text-indigo-400 font-bold">{{ $tmpl->approvalStages->count() }} مرحله</strong>
                                    </div>
                                    <div class="flex justify-between text-slate-600 dark:text-slate-400">
                                        <span>نقش‌ها و اعضای تیم:</span>
                                        <strong class="text-slate-800 dark:text-slate-200 font-bold">{{ $tmpl->templateRoles->count() }} نقش</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                                <form action="{{ route('project-templates.instantiate', $tmpl) }}" method="POST" onsubmit="return confirm('آیا می‌خواهید بر اساس این الگو یک پروژه جدید بسازید؟');" class="inline">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold text-xs shadow-sm transition-all flex items-center gap-1.5 cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                        <span>ساخت پروژه با این الگو</span>
                                    </button>
                                </form>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('project-templates.edit', $tmpl) }}" class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold hover:bg-slate-200 transition-colors">
                                        ویرایش
                                    </a>
                                    <form action="{{ route('project-templates.destroy', $tmpl) }}" method="POST" onsubmit="return confirm('آیا از حذف این الگو مطمئن هستید؟');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 text-xs font-bold hover:bg-rose-100 transition-colors">
                                            حذف
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($templates->hasPages())
                    <div class="p-4 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800">
                        {{ $templates->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
