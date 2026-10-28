<x-app-layout title="تعریف پروژه جدید" header="تعریف پروژه جدید">
    <div class="space-y-8 mx-auto">
        <!-- Hero Header Banner -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-8 shadow-xl border border-indigo-900/30">
            <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-2">
                    <h1 class="text-2xl sm:text-3xl font-black text-white">تعریف پروژه جدید</h1>
                    <p class="text-slate-300 text-xs sm:text-sm max-w-xl">
                        اطلاعات پروژه را وارد کنید و در صورت نیاز، مراحل تایید را مشخص نمایید.
                    </p>
                </div>

                <div class="shrink-0 flex items-center gap-3">
                    <a href="{{ route('projects.index') }}" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white font-bold text-xs rounded-xl border border-white/10 flex items-center justify-center gap-2 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span>بازگشت به لیست</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Form Card Container -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 sm:p-8 shadow-sm">
            <form action="{{ route('projects.store') }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Title -->
                    <div class="md:col-span-2 space-y-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">عنوان پروژه <span class="text-rose-500">*</span></label>
                        <input type="text" name="title" id="title" value="{{ old('title') }}" required class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 text-xs" placeholder="مثلاً: بازطراحی وب‌سایت یا فاز دوم پروژه...">
                        @error('title') <p class="text-xs text-rose-500 font-semibold">{{ $message }}</p> @enderror
                    </div>

                    <!-- Priority -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">اولویت پروژه <span class="text-rose-500">*</span></label>
                        <select name="priority" id="priority" required class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 text-xs font-bold">
                            @foreach($priorities as $pr)
                                <option value="{{ $pr->value }}" {{ old('priority', 'MEDIUM') === $pr->value ? 'selected' : '' }}>
                                    {{ $pr->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('priority') <p class="text-xs text-rose-500 font-semibold">{{ $message }}</p> @enderror
                    </div>

                    <!-- Organization Scope -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">واحد سازمانی <span class="text-rose-500">*</span></label>
                        <select name="organization_node_id" id="organization_node_id" required class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 text-xs">
                            <option value="">انتخاب واحد سازمانی...</option>
                            @foreach($organizationNodes as $node)
                                <option value="{{ $node->id }}" {{ old('organization_node_id') == $node->id ? 'selected' : '' }}>
                                    {{ $node->name }} ({{ $node->type->label() }})
                                </option>
                            @endforeach
                        </select>
                        @error('organization_node_id') <p class="text-xs text-rose-500 font-semibold">{{ $message }}</p> @enderror
                    </div>

                    <!-- Parent Project -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">پروژه اصلی (اختیاری)</label>
                        <select name="parent_project_id" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 text-xs">
                            <option value="">پروژه مستقل (بدون وابستگی)</option>
                            @foreach($parentProjects as $pp)
                                @php $depth = $pp->tree_depth ?? 0; @endphp
                                <option value="{{ $pp->id }}" {{ (old('parent_project_id', $selectedParentId) == $pp->id) ? 'selected' : '' }}>
                                    {{ str_repeat('— ', $depth) }}{{ $pp->project_key }} - {{ $pp->title }}
                                </option>
                            @endforeach
                        </select>
                        @error('parent_project_id') <p class="text-xs text-rose-500 font-semibold">{{ $message }}</p> @enderror
                    </div>

                    <!-- Project Key -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">کد پروژه (یکتا)</label>
                        <input type="text" name="project_key" value="{{ old('project_key', $autoProjectKey) }}" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 text-xs" placeholder="PRJ-000001">
                        @error('project_key') <p class="text-xs text-rose-500 font-semibold">{{ $message }}</p> @enderror
                    </div>

                    <!-- Status -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">وضعیت اولیه</label>
                        <select name="status" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 text-xs">
                            @foreach($statuses as $st)
                                <option value="{{ $st->value }}" {{ old('status', 'ACTIVE') == $st->value ? 'selected' : '' }}>
                                    {{ $st->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('status') <p class="text-xs text-rose-500 font-semibold">{{ $message }}</p> @enderror
                    </div>

                    <!-- Jalali Start Date -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">تاریخ شروع</label>
                        <div class="relative">
                            <input type="text"
                                   id="start_date"
                                   name="start_date"
                                   value="{{ old('start_date') }}"
                                   placeholder="۱۴۰۵/۰۶/۰۱"
                                   autocomplete="off"
                                   class="jalali-datepicker w-full pl-10 pr-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 text-xs dir-ltr text-right">
                            <div class="absolute left-3 top-3.5 text-slate-400 pointer-events-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                        </div>
                        @error('start_date') <p class="text-xs text-rose-500 font-semibold">{{ $message }}</p> @enderror
                    </div>

                    <!-- Jalali Deadline -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">مهلت تحویل</label>
                        <div class="relative">
                            <input type="text"
                                   id="deadline"
                                   name="deadline"
                                   value="{{ old('deadline') }}"
                                   placeholder="۱۴۰۵/۰۶/۱۵"
                                   autocomplete="off"
                                   class="jalali-datepicker w-full pl-10 pr-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 text-xs dir-ltr text-right">
                            <div class="absolute left-3 top-3.5 text-slate-400 pointer-events-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                        </div>
                        @error('deadline') <p class="text-xs text-rose-500 font-semibold">{{ $message }}</p> @enderror
                    </div>

                    <!-- Description -->
                    <div class="md:col-span-2 space-y-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">توضیحات (اختیاری)</label>
                        <textarea name="description" id="description" rows="3" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 text-xs" placeholder="توضیحات تکمیلی یا اهداف پروژه...">{{ old('description') }}</textarea>
                        @error('description') <p class="text-xs text-rose-500 font-semibold">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Multi-stage Supervisor Approval Configurator -->
                <div class="border-t border-slate-200 dark:border-slate-800 pt-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-sm text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>مراحل تایید سرپرست (اختیاری)</span>
                        </h3>
                        <button type="button" id="btn_add_approval_stage" class="px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-bold text-xs hover:bg-indigo-100 transition-colors">
                            + افزودن مرحله تایید
                        </button>
                    </div>

                    <div id="approval_stages_container" class="space-y-3">
                        <!-- Dynamic stage rows inserted by JS -->
                    </div>
                </div>

                <!-- Submit Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200 dark:border-slate-800">
                    <a href="{{ route('projects.index') }}" class="px-5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 text-xs font-semibold">
                        انصراف
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-xs font-bold shadow-md">
                        ثبت پروژه
                    </button>
                </div>
            </form>
        </div>
    </div>

    <link rel="stylesheet" href="{{ asset('assets/css/persian-datepicker.min.css') }}"/>
    <script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('assets/js/persian-date.min.js') }}"></script>
    <script src="{{ asset('assets/js/persian-datepicker.min.js') }}"></script>

    <script>
        const usersList = @json($users->map(fn($u) => ['id' => $u->id, 'name' => $u->full_name]));

        let stageIndex = 0;
        function addStageRow(name = '') {
            stageIndex++;
            const html = `
                <div class="flex items-center gap-3 p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700" id="stage_row_${stageIndex}">
                    <span class="px-2.5 py-1 rounded-lg bg-indigo-100 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300 font-bold text-xs shrink-0">
                        مرحله ${stageIndex}
                    </span>
                    <input type="text" name="approval_stages[${stageIndex}][stage_name]" value="${name}" placeholder="عنوان مرحله تایید (مثلاً: بررسی فنی یا تایید سرپرست)..." class="flex-1 px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-medium" required>
                    <button type="button" onclick="$('#stage_row_${stageIndex}').remove()" class="px-3 py-2 text-rose-500 hover:bg-rose-50 rounded-xl text-xs font-bold shrink-0">
                        حذف
                    </button>
                </div>
            `;
            $('#approval_stages_container').append(html);
        }

        $(document).ready(function() {
            $('.jalali-datepicker').persianDatepicker({
                format: 'YYYY/MM/DD',
                initialValue: false,
                autoClose: true,
                observer: true
            });

            $('#btn_add_approval_stage').on('click', function() {
                addStageRow();
            });
        });
    </script>
</x-app-layout>
