<x-app-layout title="ویرایش پروژه {{ $project->project_key }}" header="ویرایش پروژه">
    <div class="space-y-8 mx-auto">
        <!-- Hero Header Banner -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-8 shadow-xl border border-indigo-900/30">
            <!-- Background Ambient Light Circles -->
            <div class="absolute -left-12 -bottom-12 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute right-1/4 -top-10 w-48 h-48 bg-violet-500/10 rounded-full blur-2xl pointer-events-none"></div>

            <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="px-3 py-1 rounded-xl bg-indigo-500/20 text-indigo-200 text-xs font-mono font-bold border border-indigo-400/30">
                            {{ $project->project_key }}
                        </span>
                        @php
                            $statusColors = [
                                'ACTIVE' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
                                'ON_HOLD' => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
                                'COMPLETED' => 'bg-blue-500/20 text-blue-300 border-blue-500/30',
                                'CANCELLED' => 'bg-rose-500/20 text-rose-300 border-rose-500/30',
                                'ARCHIVED' => 'bg-slate-500/20 text-slate-300 border-slate-500/30',
                            ];
                            $colorClass = $statusColors[$project->status->value] ?? 'bg-slate-500/20 text-slate-300';
                        @endphp
                        <span class="px-3 py-1 rounded-full text-xs font-bold border {{ $colorClass }}">
                            {{ $project->status->label() }}
                        </span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-black text-white">ویرایش پروژه {{ $project->title }}</h1>
                    <p class="text-slate-300 text-xs sm:text-sm max-w-xl">
                        اطلاعات و تنظیمات پروژه را ویرایش کنید.
                    </p>
                </div>

                <div class="shrink-0 flex flex-wrap items-center gap-3">
                    <a href="{{ route('projects.show', $project) }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white font-bold text-xs rounded-xl backdrop-blur-md border border-white/10 flex items-center justify-center gap-2 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <span>مشاهده پروژه</span>
                    </a>
                    <a href="{{ route('projects.index') }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white font-bold text-xs rounded-xl backdrop-blur-md border border-white/10 flex items-center justify-center gap-2 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span>بازگشت به لیست</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- 2-Column Edit Form Layout -->
        <form action="{{ route('projects.update', $project) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left / Main Column (2 Cols) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Section 1: Basic Information -->
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 sm:p-8 shadow-sm space-y-6">
                        <div class="flex items-center gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center border border-indigo-100 dark:border-indigo-900">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 dark:text-slate-100 text-base">مشخصات پروژه</h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">عنوان، کد یکتا و توضیحات پروژه</p>
                            </div>
                        </div>

                        <div class="space-y-5">
                            <!-- Title -->
                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">عنوان پروژه <span class="text-rose-500">*</span></label>
                                <input type="text" name="title" value="{{ old('title', $project->title) }}" required class="w-full px-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 text-sm">
                                @error('title') <p class="text-xs text-rose-500 font-semibold">{{ $message }}</p> @enderror
                            </div>

                            <!-- Project Key -->
                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">کد پروژه (یکتا) <span class="text-rose-500">*</span></label>
                                <input type="text" name="project_key" value="{{ old('project_key', $project->project_key) }}" required class="w-full px-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 text-sm font-mono dir-ltr text-right">
                                @error('project_key') <p class="text-xs text-rose-500 font-semibold">{{ $message }}</p> @enderror
                            </div>

                            <!-- Description -->
                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">توضیحات (اختیاری)</label>
                                <textarea name="description" rows="5" class="w-full px-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 text-sm leading-relaxed">{{ old('description', $project->description) }}</textarea>
                                @error('description') <p class="text-xs text-rose-500 font-semibold">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Timelines & Status -->
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 sm:p-8 shadow-sm space-y-6">
                        <div class="flex items-center gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                            <div class="w-10 h-10 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center border border-emerald-100 dark:border-emerald-900">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 dark:text-slate-100 text-base">زمان‌بندی و وضعیت</h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">تاریخ‌های شروع، پایان و وضعیت فعلی پروژه</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <!-- Jalali Start Date -->
                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">تاریخ شروع</label>
                                <div class="relative">
                                    <input type="text"
                                           id="start_date"
                                           name="start_date"
                                           value="{{ old('start_date', $project->start_date ? verta($project->start_date)->format('Y/m/d') : '') }}"
                                           placeholder="مثال: ۱۴۰۵/۰۶/۰۱"
                                           autocomplete="off"
                                           class="jalali-datepicker w-full pl-10 pr-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 text-sm dir-ltr text-right font-mono">
                                    <div class="absolute left-3.5 top-3.5 text-slate-400 pointer-events-none">
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
                                           value="{{ old('deadline', $project->deadline ? verta($project->deadline)->format('Y/m/d') : '') }}"
                                           placeholder="مثال: ۱۴۰۵/۰۶/۱۵"
                                           autocomplete="off"
                                           class="jalali-datepicker w-full pl-10 pr-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 text-sm dir-ltr text-right font-mono">
                                    <div class="absolute left-3.5 top-3.5 text-slate-400 pointer-events-none">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                </div>
                                @error('deadline') <p class="text-xs text-rose-500 font-semibold">{{ $message }}</p> @enderror
                            </div>

                            <!-- Priority -->
                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">اولویت پروژه <span class="text-rose-500">*</span></label>
                                <select name="priority" required class="w-full px-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 text-sm font-bold">
                                    @foreach($priorities as $pr)
                                        <option value="{{ $pr->value }}" {{ old('priority', $project->priority?->value ?? 'MEDIUM') === $pr->value ? 'selected' : '' }}>
                                            {{ $pr->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('priority') <p class="text-xs text-rose-500 font-semibold">{{ $message }}</p> @enderror
                            </div>

                            <!-- Status -->
                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">وضعیت پروژه <span class="text-rose-500">*</span></label>
                                <select name="status" required class="w-full px-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 text-sm">
                                    @foreach($statuses as $st)
                                        <option value="{{ $st->value }}" {{ old('status', $project->status->value) == $st->value ? 'selected' : '' }}>
                                            {{ $st->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('status') <p class="text-xs text-rose-500 font-semibold">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column (1 Col) -->
                <div class="space-y-6">
                    <!-- Section 3: Organization Scope -->
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-4">
                        <div class="flex items-center gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                            <div class="w-9 h-9 rounded-xl bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 flex items-center justify-center border border-violet-100 dark:border-violet-900 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0h4m-4 0H9m4 0V7m0 0h4m-4 0H9"/></svg>
                            </div>
                            <h3 class="font-bold text-slate-800 dark:text-slate-100 text-sm">واحد سازمانی</h3>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">واحد سازمانی <span class="text-rose-500">*</span></label>
                            <select name="organization_node_id" required class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 text-xs">
                                @foreach($organizationNodes as $node)
                                    <option value="{{ $node->id }}" {{ old('organization_node_id', $project->organization_node_id) == $node->id ? 'selected' : '' }}>
                                        {{ $node->name }} ({{ $node->type->label() }})
                                    </option>
                                @endforeach
                            </select>
                            @error('organization_node_id') <p class="text-xs text-rose-500 font-semibold">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <!-- Section 4: Hierarchy / Parent Project -->
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-4">
                        <div class="flex items-center gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                            <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center border border-amber-100 dark:border-amber-900 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            </div>
                            <h3 class="font-bold text-slate-800 dark:text-slate-100 text-sm">پروژه اصلی</h3>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">پروژه اصلی (اختیاری)</label>
                            <select name="parent_project_id" class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 text-xs">
                                <option value="">پروژه مستقل (بدون وابستگی)</option>
                                @foreach($parentProjects as $pp)
                                    @php $depth = $pp->tree_depth ?? 0; @endphp
                                    <option value="{{ $pp->id }}" {{ old('parent_project_id', $project->parent_project_id) == $pp->id ? 'selected' : '' }}>
                                        {{ str_repeat('— ', $depth) }}{{ $pp->project_key }} - {{ $pp->title }}
                                    </option>
                                @endforeach
                            </select>
                            @error('parent_project_id') <p class="text-xs text-rose-500 font-semibold">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <!-- Section 5: Audit Meta Card -->
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm space-y-3 text-xs text-slate-500 dark:text-slate-400">
                        <h4 class="font-bold text-slate-700 dark:text-slate-300 pb-2 border-b border-slate-100 dark:border-slate-800">اطلاعات ثبت</h4>
                        <div class="flex justify-between py-1">
                            <span>ثبت‌شده توسط:</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $project->creator?->full_name }}</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span>تاریخ ثبت:</span>
                            <span class="font-mono text-slate-800 dark:text-slate-200">{{ verta($project->created_at)->format('Y/m/d - H:i') }}</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span>آخرین ویرایش:</span>
                            <span class="font-mono text-slate-800 dark:text-slate-200">{{ verta($project->updated_at)->format('Y/m/d - H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Action Footer -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm flex items-center justify-between gap-4">
                <a href="{{ route('projects.show', $project) }}" class="px-5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 text-sm font-semibold transition-all">
                    انصراف
                </a>
                <button type="submit" class="px-7 py-3 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-sm font-bold shadow-lg shadow-indigo-500/25 transition-all transform hover:-translate-y-0.5 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>ذخیره تغییرات</span>
                </button>
            </div>
        </form>
    </div>

    <link rel="stylesheet" href="{{ asset('assets/css/persian-datepicker.min.css') }}"/>
    <script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('assets/js/persian-date.min.js') }}"></script>
    <script src="{{ asset('assets/js/persian-datepicker.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            $('.jalali-datepicker').persianDatepicker({
                format: 'YYYY/MM/DD',
                initialValue: false,
                initialValueType: 'persian',
                autoClose: true,
                observer: true,
                calendar: {
                    persian: {
                        locale: 'fa'
                    }
                }
            });
        });
    </script>
</x-app-layout>
