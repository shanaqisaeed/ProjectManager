<x-app-layout title="ویرایش تمپلیت آماده پروژه" header="ویرایش تمپلیت">
    <div class="space-y-8 mx-auto">
        <!-- Hero Header Banner -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-8 shadow-xl border border-indigo-900/30">
            <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-2">
                    <h1 class="text-2xl sm:text-3xl font-black text-white">ویرایش تمپلیت: {{ $template->title }}</h1>
                    <p class="text-slate-300 text-xs sm:text-sm max-w-xl">
                        بروزرسانی مشخصات، اولویت، اکیپ‌ها و مراحل تایید ناظران تمپلیت.
                    </p>
                </div>

                <div class="shrink-0">
                    <a href="{{ route('project-templates.index') }}" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white font-bold text-xs rounded-xl border border-white/10 flex items-center justify-center gap-2 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span>بازگشت به تمپلیت‌ها</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Form Card Container -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 sm:p-8 shadow-sm">
            <form action="{{ route('project-templates.update', $template) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Title -->
                    <div class="md:col-span-2 space-y-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">عنوان تمپلیت <span class="text-rose-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $template->title) }}" required class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 text-xs">
                        @error('title') <p class="text-xs text-rose-500 font-semibold">{{ $message }}</p> @enderror
                    </div>

                    <!-- Organization Unit Scope -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">اختصاص به واحد سازمانی (چارت)</label>
                        <select name="organization_node_id" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 text-xs">
                            <option value="">تمپلیت عمومی (قابل استفاده در تمام سازمان)</option>
                            @foreach($organizationNodes as $node)
                                <option value="{{ $node->id }}" {{ old('organization_node_id', $template->organization_node_id) == $node->id ? 'selected' : '' }}>
                                    {{ $node->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('organization_node_id') <p class="text-xs text-rose-500 font-semibold">{{ $message }}</p> @enderror
                    </div>

                    <!-- Priority -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">اولویت اجرای پیش‌فرض <span class="text-rose-500">*</span></label>
                        <select name="priority" required class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 text-xs font-bold">
                            @foreach($priorities as $pr)
                                <option value="{{ $pr->value }}" {{ old('priority', $template->priority->value) === $pr->value ? 'selected' : '' }}>
                                    {{ $pr->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('priority') <p class="text-xs text-rose-500 font-semibold">{{ $message }}</p> @enderror
                    </div>

                    <!-- Duration Days -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">مدت زمان کل اجرای پروژه (به روز) <span class="text-rose-500">*</span></label>
                        <input type="number" name="duration_days" value="{{ old('duration_days', $template->duration_days) }}" required min="1" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 text-xs font-mono">
                        @error('duration_days') <p class="text-xs text-rose-500 font-semibold">{{ $message }}</p> @enderror
                    </div>

                    <!-- Description -->
                    <div class="md:col-span-2 space-y-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">توضیحات تمپلیت</label>
                        <textarea name="description" rows="3" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 text-xs">{{ old('description', $template->description) }}</textarea>
                    </div>
                </div>

                <!-- Template Roles & Crews Setup -->
                <div class="border-t border-slate-200 dark:border-slate-800 pt-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-sm text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span>نقش‌ها و اکیپ‌های پیش‌فرض</span>
                        </h3>
                        <button type="button" id="btn_add_role" class="px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-bold text-xs hover:bg-indigo-100 transition-colors">
                            + افزودن نقش / اکیپ
                        </button>
                    </div>

                    <div id="template_roles_container" class="space-y-3">
                        <!-- Loaded dynamically or rendered by loop -->
                    </div>
                </div>

                <!-- Template Approval Stages Setup -->
                <div class="border-t border-slate-200 dark:border-slate-800 pt-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-sm text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>مراحل تایید ناظران</span>
                        </h3>
                        <button type="button" id="btn_add_stage" class="px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-bold text-xs hover:bg-indigo-100 transition-colors">
                            + افزودن مرحله ناظر
                        </button>
                    </div>

                    <div id="template_stages_container" class="space-y-3">
                        <!-- Loaded dynamically or rendered by loop -->
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200 dark:border-slate-800">
                    <a href="{{ route('project-templates.index') }}" class="px-5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 text-xs font-semibold">
                        انصراف
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-xs font-bold shadow-md">
                        بروزرسانی تمپلیت
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
    <script>
        const projectRoles = @json(collect($projectMemberRoles)->map(fn($r) => ['value' => $r->value, 'label' => $r->label()]));
        const usersList = @json($users->map(fn($u) => ['id' => $u->id, 'name' => $u->full_name]));

        const existingRoles = @json($template->templateRoles->map(fn($r) => ['role' => $r->project_role->value, 'cooperation' => $r->cooperation_title, 'user_id' => $r->user_id]));
        const existingStages = @json($template->approvalStages->map(fn($s) => ['name' => $s->stage_name]));

        let roleIdx = 0;
        function addRoleRow(roleVal = '', coopTitle = '', userId = '') {
            roleIdx++;
            let roleOpts = '';
            projectRoles.forEach(r => roleOpts += `<option value="${r.value}" ${r.value === roleVal ? 'selected' : ''}>${r.label}</option>`);
            let userOpts = '<option value="">-- بدون شخص خاص --</option>';
            usersList.forEach(u => userOpts += `<option value="${u.id}" ${u.id == userId ? 'selected' : ''}>${u.name}</option>`);

            const html = `
                <div class="flex flex-col sm:flex-row items-center gap-3 p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700" id="role_row_${roleIdx}">
                    <select name="roles[${roleIdx}][project_role]" class="w-full sm:w-48 px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-medium" required>
                        ${roleOpts}
                    </select>
                    <input type="text" name="roles[${roleIdx}][cooperation_title]" value="${coopTitle || ''}" placeholder="عنوان اکیپ / مسئولیت" class="flex-1 px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-medium">
                    <select name="roles[${roleIdx}][user_id]" class="w-full sm:w-48 px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-medium">
                        ${userOpts}
                    </select>
                    <button type="button" onclick="$('#role_row_${roleIdx}').remove()" class="px-2.5 py-2 text-rose-500 font-bold text-xs">حذف</button>
                </div>
            `;
            $('#template_roles_container').append(html);
        }

        let stageIdx = 0;
        function addStageRow(name = '') {
            stageIdx++;
            const html = `
                <div class="flex items-center gap-3 p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700" id="stage_row_${stageIdx}">
                    <span class="px-2.5 py-1 rounded-lg bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-bold text-xs shrink-0">مرحله ${stageIdx}</span>
                    <input type="text" name="approval_stages[${stageIdx}][stage_name]" value="${name || ''}" placeholder="عنوان مرحله تایید تسک (مثلاً: ناظر معماری)" class="flex-1 px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-medium" required>
                    <button type="button" onclick="$('#stage_row_${stageIdx}').remove()" class="px-3 py-2 text-rose-500 font-bold text-xs shrink-0 hover:bg-rose-50 rounded-xl">حذف</button>
                </div>
            `;
            $('#template_stages_container').append(html);
        }

        $(document).ready(function() {
            $('#btn_add_role').on('click', () => addRoleRow());
            $('#btn_add_stage').on('click', () => addStageRow());

            if (existingRoles.length > 0) {
                existingRoles.forEach(r => addRoleRow(r.role, r.cooperation, r.user_id));
            } else {
                addRoleRow();
            }

            if (existingStages.length > 0) {
                existingStages.forEach(s => addStageRow(s.name));
            } else {
                addStageRow();
            }
        });
    </script>
</x-app-layout>
