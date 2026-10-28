@extends('layouts.app')

@section('title', 'گانت چارت پروژه: ' . $project->title)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('projects.show', $project) }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                    ← بازگشت به پروژه
                </a>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">📈 گانت‌چارت و زمان‌بندی: {{ $project->title }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">نمایش سلسله‌مراتبی ساختار درخت پروژه، زیرپروژه‌ها و تسک‌ها</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                کلید پروژه: <code class="font-bold">{{ $project->project_key }}</code>
            </span>
        </div>
    </div>

    <!-- Gantt Chart Container -->
    <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm">
        <div id="gantt-loading" class="py-16 text-center text-slate-500">
            <div class="inline-block animate-spin w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full mb-3"></div>
            <div>در حال دریافت ساختار درخت زمان‌بندی...</div>
        </div>

        <div id="gantt-content" class="hidden space-y-4">
            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 text-xs font-semibold">
                            <th class="py-3 px-4 min-w-[240px]">عنوان آیتم / ساختار</th>
                            <th class="py-3 px-4">نوع</th>
                            <th class="py-3 px-4">تاریخ شروع</th>
                            <th class="py-3 px-4">تاریخ مهلت</th>
                            <th class="py-3 px-4">وضعیت</th>
                            <th class="py-3 px-4 min-w-[200px]">تایم‌لاین پیشرفت</th>
                        </tr>
                    </thead>
                    <tbody id="gantt-tree-body" class="divide-y divide-slate-200 dark:divide-slate-700 text-sm">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    fetch('{{ route("projects.gantt.data", $project) }}')
        .then(response => response.json())
        .then(data => {
            document.getElementById('gantt-loading').classList.add('hidden');
            const content = document.getElementById('gantt-content');
            content.classList.remove('hidden');

            const tbody = document.getElementById('gantt-tree-body');
            tbody.innerHTML = '';

            if(!data.items || data.items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-slate-400">اطلاعات زمان‌بندی یافت نشد.</td></tr>';
                return;
            }

            data.items.forEach(item => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-slate-50/80 dark:hover:bg-slate-700/50 transition-colors';
                
                let indent = '';
                let icon = '📌';
                let bgBadge = 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300';
                
                if (item.type === 'project') {
                    icon = '📁';
                    bgBadge = 'bg-indigo-100 dark:bg-indigo-950 text-indigo-800 dark:text-indigo-300 font-bold';
                } else if (item.type === 'subproject') {
                    indent = 'mr-4';
                    icon = '📂';
                    bgBadge = 'bg-blue-100 dark:bg-blue-950 text-blue-800 dark:text-blue-300';
                } else {
                    indent = 'mr-8';
                    icon = '📝';
                }

                tr.innerHTML = `
                    <td class="py-3.5 px-4 font-medium text-slate-900 dark:text-white">
                        <div class="flex items-center gap-2 ${indent}">
                            <span>${icon}</span>
                            <span>${item.text}</span>
                        </div>
                    </td>
                    <td class="py-3.5 px-4">
                        <span class="text-xs px-2.5 py-1 rounded-md font-medium ${bgBadge}">
                            ${item.type === 'project' ? 'پروژه اصلی' : (item.type === 'subproject' ? 'زیرپروژه' : 'تسک')}
                        </span>
                    </td>
                    <td class="py-3.5 px-4 text-xs font-mono">
                        <span class="inline-flex items-center gap-1 bg-slate-100 dark:bg-slate-700/60 text-slate-700 dark:text-slate-300 px-2.5 py-1 rounded-lg">
                            📅 ${item.start_date || '—'}
                        </span>
                    </td>
                    <td class="py-3.5 px-4 text-xs font-mono">
                        <span class="inline-flex items-center gap-1 ${item.due_date ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/60' : 'bg-slate-100 dark:bg-slate-700/60 text-slate-500'} px-2.5 py-1 rounded-lg">
                            🏁 ${item.due_date || 'تعیین‌نشده'}
                        </span>
                    </td>
                    <td class="py-3.5 px-4 text-xs text-slate-600 dark:text-slate-400">${item.status}</td>
                    <td class="py-3.5 px-4">
                        <div class="flex items-center gap-2">
                            <div class="w-full bg-slate-100 dark:bg-slate-700 h-2.5 rounded-full overflow-hidden">
                                <div class="bg-emerald-500 h-2.5 rounded-full transition-all duration-500" style="width: ${item.progress}%"></div>
                            </div>
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300 min-w-[36px] text-left">${item.progress}%</span>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(err => {
            document.getElementById('gantt-loading').innerHTML = '<div class="text-rose-500 font-medium">خطا در بارگذاری داده‌های گانت‌چارت.</div>';
        });
});
</script>
@endsection
