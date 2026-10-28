@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/persian-datepicker.min.css') }}"/>
<script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
<script src="{{ asset('assets/js/persian-date.min.js') }}"></script>
<script src="{{ asset('assets/js/persian-datepicker.min.js') }}"></script>

<div class="mx-auto space-y-6" x-data="{
    chatMessage: '',
    sendingChat: false,
    uploadingAttachment: false,
    chats: {{ json_encode($task->chats->map(fn($c) => [
        'id' => $c->id,
        'user_name' => $c->user ? $c->user->full_name : 'سیستم',
        'is_current_user' => $c->user_id === auth()->id(),
        'message' => $c->message,
        'created_at' => verta($c->created_at)->format('Y/m/d H:i')
    ])) }},
    attachments: {{ json_encode($task->attachments->map(fn($a) => [
        'id' => $a->id,
        'original_name' => $a->original_name,
        'file_size' => number_format($a->file_size / 1024, 1).' KB',
        'file_url' => asset('storage/'.$a->file_path),
        'uploader_name' => $a->uploader ? $a->uploader->full_name : 'ناشناس'
    ])) }},

    scrollToChatBottom() {
        if (this.$refs.chatFeedPage) {
            this.$refs.chatFeedPage.scrollTop = this.$refs.chatFeedPage.scrollHeight;
        }
    },

    async sendChatMessage() {
        if (!this.chatMessage.trim() || this.sendingChat) return;
        this.sendingChat = true;
        try {
            const response = await fetch('{{ route("projects.tasks.chats.store", [$project, $task]) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ message: this.chatMessage })
            });
            const data = await response.json();
            if (data.success && data.chat) {
                this.chats.push(data.chat);
                this.chatMessage = '';
                this.$nextTick(() => this.scrollToChatBottom());
            }
        } catch (e) {
            alert('خطا در ارسال نظر');
        } finally {
            this.sendingChat = false;
        }
    },

    async uploadAttachment(event) {
        const file = event.target.files[0];
        if (!file || this.uploadingAttachment) return;

        const formData = new FormData();
        formData.append('file', file);

        this.uploadingAttachment = true;
        try {
            const response = await fetch('{{ route("projects.tasks.attachments.store", [$project, $task]) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            });
            const data = await response.json();
            if (data.success && data.attachment) {
                this.attachments.push(data.attachment);
                event.target.value = '';
            }
        } catch (e) {
            alert('خطا در آپلود فایل پیوست');
        } finally {
            this.uploadingAttachment = false;
        }
    },

    async removeAttachment(attId) {
        if (!confirm('آیا از حذف این پیوست اطمینان دارید؟')) return;
        try {
            const response = await fetch('/projects/{{ $project->id }}/tasks/{{ $task->id }}/attachments/' + attId, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();
            if (data.success) {
                this.attachments = this.attachments.filter(a => a.id !== attId);
            }
        } catch (e) {
            alert('خطا در حذف پیوست');
        }
    }
}" x-init="$nextTick(() => scrollToChatBottom())">

    <!-- Top Navigation Header & Breadcrumbs -->
    <div class="p-4 sm:p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-2">
            <!-- Breadcrumbs -->
            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 text-xs text-slate-500">
                <a href="{{ route('projects.index') }}" class="hover:text-indigo-600">پروژه‌ها</a>
                <span>/</span>
                <a href="{{ route('projects.show', [$project, 'tab' => 'tasks']) }}" class="hover:text-indigo-600 font-semibold truncate max-w-[200px]">{{ $project->title }}</a>
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-bold">جزئیات کار</span>
            </div>

            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                <h1 class="text-lg sm:text-xl font-black text-slate-800 dark:text-slate-100 break-words">{{ $task->title }}</h1>
                <span class="px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-full text-xs font-bold {{ $task->priority->badgeClass() }} shrink-0">
                    {{ $task->priority->label() }}
                </span>
                <span class="px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-full text-xs font-bold border {{ $task->status->badgeClass() }} shrink-0">
                    {{ $task->status->label() }}
                </span>
                <span class="px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 shrink-0">
                    ⚖️ وزن: {{ $task->weight ?? 1 }}
                </span>
                @if($task->project_id === $project->id)
                    <span class="px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-xl bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 text-xs font-bold border border-indigo-200 dark:border-indigo-800 shrink-0">
                        🏢 پروژه اصلی
                    </span>
                @else
                    <span class="px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-xl bg-purple-50 dark:bg-purple-950 text-purple-700 dark:text-purple-300 text-xs font-bold border border-purple-200 dark:border-purple-800 shrink-0">
                        📁 {{ $task->project?->title }}
                    </span>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap sm:flex-nowrap items-center gap-2.5 w-full sm:w-auto">
            @if($canEditTask ?? false)
                <a href="{{ route('projects.tasks.edit', [$project, $task]) }}" class="flex-1 sm:flex-none justify-center px-4 py-2.5 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-xs font-bold transition-all shadow-md shadow-indigo-500/20 flex items-center gap-2 transform hover:-translate-y-0.5">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    <span class="whitespace-nowrap">ویرایش کار</span>
                </a>
            @endif

            <a href="{{ route('projects.show', [$project, 'tab' => 'tasks']) }}" class="flex-1 sm:flex-none justify-center px-4 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                <span class="whitespace-nowrap">بازگشت به کارها</span>
            </a>
        </div>
    </div>

    <!-- Main Layout Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left 2 Columns: Full Task Details & Workflow Actions -->
        <div class="lg:col-span-2 space-y-6">

            <!-- 1. TASK DETAILS DISPLAY SECTION -->
            <div class="p-4 sm:p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-4">
                    <h3 class="text-sm font-black text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>مشخصات کار</span>
                    </h3>
                    <div class="flex flex-wrap items-center gap-2 sm:gap-3 text-[11px] text-slate-400">
                        @if($task->version)
                            <span class="px-2.5 py-0.5 rounded-lg bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-mono font-bold border border-indigo-200 dark:border-indigo-800">
                                نسخه: {{ $task->version }}
                            </span>
                        @endif
                        <span>آخرین تغییر: {{ verta($task->updated_at)->format('Y/m/d H:i') }}</span>
                    </div>
                </div>

                @if($task->parent)
                    <div class="p-4 rounded-2xl bg-purple-50 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                        <div class="space-y-0.5">
                            <span class="text-[11px] font-bold text-purple-700 dark:text-purple-300">↳ این کار، زیرمجموعه کار زیر است:</span>
                            <div class="flex items-center gap-2 pt-0.5">
                                <span class="font-black text-slate-800 dark:text-slate-100 text-sm">{{ $task->parent->title }}</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $task->parent->status->badgeClass() }}">
                                    {{ $task->parent->status->label() }}
                                </span>
                            </div>
                        </div>
                        <a href="{{ route('projects.tasks.show', [$project, $task->parent]) }}" class="px-3 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs transition-all shrink-0 flex items-center gap-1">
                            <span>مشاهده کار اصلی</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                    </div>
                @endif

                <div class="space-y-4 text-xs">
                    <div>
                        <span class="text-slate-400 font-bold block mb-1">توضیحات کار:</span>
                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 leading-relaxed whitespace-pre-line font-medium break-words">{{ $task->description ?: 'توضیحاتی برای این کار ثبت نشده است.' }}</div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 pt-2">
                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-1">
                            <span class="text-slate-400 block text-[11px] font-bold">👤 مسئول انجام:</span>
                            <span class="font-black text-slate-800 dark:text-slate-100 block break-words">
                                {{ $task->executor ? $task->executor->full_name : 'بدون مسئول' }}
                            </span>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-1">
                            <span class="text-slate-400 block text-[11px] font-bold">📅 تاریخ شروع:</span>
                            <span class="font-mono font-black text-slate-800 dark:text-slate-100 block">
                                {{ $task->start_date ? verta($task->start_date)->format('Y/m/d') : 'ثبت نشده' }}
                            </span>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-1">
                            <span class="text-slate-400 block text-[11px] font-bold">🏁 مهلت تحویل:</span>
                            <span class="font-mono font-black text-emerald-600 dark:text-emerald-400 block">
                                {{ $task->due_date ? verta($task->due_date)->format('Y/m/d') : 'بدون مهلت' }}
                            </span>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-1">
                            <span class="text-slate-400 block text-[11px] font-bold">📌 کار اصلی:</span>
                            <span class="font-bold text-slate-800 dark:text-slate-100 block break-words">
                                @if($task->parent)
                                    <a href="{{ route('projects.tasks.show', [$project, $task->parent]) }}" class="text-indigo-600 hover:underline">
                                        {{ $task->parent->title }}
                                    </a>
                                @else
                                    کار اصلی
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. SUBTASKS SECTION -->
            @if($task->subtasks->isNotEmpty())
                <div class="p-4 sm:p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                        <h3 class="text-sm font-black text-slate-800 dark:text-slate-100 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            <span>زیرکارها</span>
                            <span class="px-2 py-0.5 rounded-lg bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 text-[10px] font-bold">
                                {{ $task->subtasks->count() }} زیرکار
                            </span>
                        </h3>
                        @if($canEditTask ?? false)
                            <a href="{{ route('projects.tasks.create', ['project' => $project, 'parent_id' => $task->id]) }}" class="px-3 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 text-[11px] font-bold transition-all flex items-center gap-1.5 self-start sm:self-auto">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>+ افزودن زیرکار</span>
                            </a>
                        @endif
                    </div>

                    @if($task->hasUnresolvedSubtasks())
                        <div class="p-3.5 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 text-xs font-bold flex items-start gap-2.5 leading-relaxed">
                            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <div>
                                <strong class="block mb-0.5">تکمیل کار اصلی وابسته به زیرکارهاست:</strong>
                                <span>این کار {{ $task->getUnresolvedSubtasks()->count() }} زیرکار باز دارد. برای تکمیل کار اصلی، ابتدا باید تمام زیرکارها انجام یا لغو شوند.</span>
                            </div>
                        </div>
                    @else
                        <div class="p-3 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-bold flex items-center gap-2">
                            <span>✅ همه زیرکارها به پایان رسیده‌اند.</span>
                        </div>
                    @endif

                    <div class="space-y-2">
                        @foreach($task->subtasks as $subtask)
                            <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                                <div class="flex items-center gap-2 truncate">
                                    <span class="w-2 h-2 rounded-full {{ in_array($subtask->status->value, ['COMPLETED', 'CANCELLED']) ? 'bg-emerald-500' : 'bg-amber-500' }} shrink-0"></span>
                                    <a href="{{ route('projects.tasks.show', [$project, $subtask]) }}" class="font-bold text-slate-800 dark:text-slate-100 hover:text-indigo-600 dark:hover:text-indigo-400 hover:underline truncate">
                                        {{ $subtask->title }}
                                    </a>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 shrink-0">
                                    <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-bold shadow-xs {{ $subtask->status->badgeClass() }}">
                                        {{ $subtask->status->label() }}
                                    </span>
                                    <span class="text-slate-500 text-[11px]">مسئول: {{ $subtask->executor?->full_name ?? 'بدون مسئول' }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif


            <!-- 2. EXECUTOR WORKFLOW STATUS ACTIONS -->
            <div class="p-4 sm:p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                <h3 class="text-sm font-black text-slate-800 dark:text-slate-100 flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span>وضعیت و پیشرفت کار</span>
                </h3>

                <div class="flex flex-wrap items-center gap-3">
                    @if($task->status->value === 'TODO' || $task->status->value === 'NEEDS_REVISION')
                        <form action="{{ route('projects.tasks.update-status', [$project, $task]) }}" method="POST" class="w-full sm:w-auto">
                            @csrf
                            <input type="hidden" name="status" value="IN_PROGRESS">
                            <button type="submit" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-500 hover:to-blue-500 text-white text-xs font-bold shadow-md shadow-indigo-500/20 flex items-center justify-center gap-2">
                                <span>▶️ شروع کار</span>
                            </button>
                        </form>
                    @elseif($task->status->value === 'IN_PROGRESS')
                        @if($task->taskSupervisors->isNotEmpty())
                            <form action="{{ route('projects.tasks.update-status', [$project, $task]) }}" method="POST" class="w-full sm:w-auto">
                                @csrf
                                <input type="hidden" name="status" value="PENDING_REVIEW">
                                <button type="submit" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-white text-xs font-bold shadow-lg shadow-amber-500/25 flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                    <span>ارسال برای تایید سرپرست</span>
                                </button>
                            </form>
                        @else
                            <form action="{{ route('projects.tasks.update-status', [$project, $task]) }}" method="POST" class="w-full sm:w-auto">
                                @csrf
                                <input type="hidden" name="status" value="COMPLETED">
                                <button type="submit" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold shadow-lg shadow-emerald-500/25 flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>✅ پایان و ثبت انجام کار</span>
                                </button>
                            </form>
                        @endif
                    @elseif($task->status->value === 'PENDING_REVIEW')
                        <div class="px-4 py-3 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 text-xs font-bold flex items-center gap-2 w-full">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-ping shrink-0"></span>
                            <span>این کار برای تایید به سرپرست ارسال شده و در انتظار بررسی است.</span>
                        </div>
                    @elseif($task->status->value === 'COMPLETED')
                        <div class="px-4 py-3 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-bold flex items-center gap-2 w-full">
                            @if($task->taskSupervisors->isNotEmpty())
                                <span>✅ این کار انجام شده و به تایید سرپرست رسیده است.</span>
                            @else
                                <span>✅ این کار با موفقیت انجام شد و به پایان رسید.</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- 3. SUPERVISOR APPROVAL PANEL -->
            <div class="p-4 sm:p-6 rounded-3xl bg-white dark:bg-slate-900 border border-indigo-100 dark:border-indigo-900/60 bg-indigo-50/20 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 border-b border-indigo-100 dark:border-indigo-900/60 pb-3">
                    <h3 class="text-sm font-black text-indigo-900 dark:text-indigo-200 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>مراحل تایید سرپرست</span>
                    </h3>
                    <span class="text-[11px] text-indigo-600 font-semibold">تایید به ترتیب مراحل انجام می‌شود</span>
                </div>

                <div class="space-y-2.5">
                    @forelse($task->taskSupervisors->sortBy('stage_number') as $sup)
                        <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 text-xs shadow-sm">
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="px-2.5 py-0.5 rounded-lg bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-bold text-[10px]">
                                        مرحله {{ $sup->stage_number }}
                                    </span>
                                    <strong class="font-bold text-slate-800 dark:text-slate-100">{{ $sup->stage_name }}</strong>
                                    <span class="text-slate-500">({{ $sup->user ? $sup->user->full_name : 'بدون ناظر' }})</span>
                                </div>
                                @if($sup->comments)
                                    <p class="text-[11px] text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-900 p-2 rounded-xl">
                                        یادداشت سرپرست: {{ $sup->comments }}
                                    </p>
                                @endif
                            </div>
                            <div class="shrink-0">
                                @if($sup->status === 'APPROVED')
                                    <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 font-bold text-[11px] inline-block">✅ تایید شد</span>
                                @elseif($sup->status === 'REJECTED')
                                    <span class="px-3 py-1 rounded-full bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 font-bold text-[11px] inline-block">نیاز به بازنگری</span>
                                @else
                                    <span class="px-3 py-1 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 font-bold text-[11px] inline-block">در انتظار تایید</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 italic">برای این کار مرحله تاییدی تعریف نشده است.</p>
                    @endforelse
                </div>

                @php
                    $pendingSupervisorStage = $task->taskSupervisors->where('status', 'PENDING')->sortBy('stage_number')->first();
                    $isDirectSupervisor = $pendingSupervisorStage && $pendingSupervisorStage->user_id === auth()->id();
                    $isSuperAdminOrManager = auth()->user()->isSuperAdmin() || app(\App\Services\ProjectPermissionResolver::class)->hasPermission(auth()->user(), $task->project, \App\Enums\ProjectPermission::MANAGE_APPROVAL_STAGES);
                    $canReviewTask = $isDirectSupervisor || $isSuperAdminOrManager;
                @endphp

                <!-- Supervisor Action Form (Shown ONLY when status === PENDING_REVIEW) -->
                @if($task->status->value === 'PENDING_REVIEW')
                    <div class="pt-4 border-t border-indigo-100 dark:border-indigo-900/60 space-y-3">
                        @if($isDirectSupervisor)
                            <p class="text-xs font-bold text-amber-800 dark:text-amber-300 flex items-center gap-1.5">
                                <span>📥 این کار در انتظار تایید شماست (مرحله {{ $pendingSupervisorStage->stage_number }}: {{ $pendingSupervisorStage->stage_name }}):</span>
                            </p>
                        @elseif($isSuperAdminOrManager)
                            <p class="text-xs font-bold text-indigo-700 dark:text-indigo-300 flex items-center gap-1.5">
                                <span>⚡ شما به عنوان مدیر پروژه می‌توانید این مرحله را تایید یا رد کنید (ناظر: {{ $pendingSupervisorStage?->user?->full_name ?? 'بدون ناظر' }}):</span>
                            </p>
                        @endif

                        @if($canReviewTask)
                            <form action="{{ route('projects.tasks.review', [$project, $task]) }}" method="POST" class="space-y-3">
                                @csrf
                                <input type="text" name="comments" placeholder="اگر نکته یا توضیحی دارید، بنویسید..." class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500">
                                <div class="flex flex-col sm:flex-row items-center gap-3">
                                    <button type="submit" name="action" value="APPROVE" class="w-full sm:flex-1 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-500/20 transition-all flex items-center justify-center gap-1.5">
                                        <span>✅ تایید این مرحله</span>
                                    </button>
                                    <button type="submit" name="action" value="REJECT" class="w-full sm:flex-1 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-md shadow-rose-500/20 transition-all flex items-center justify-center gap-1.5">
                                        <span>بازگشت به مسئول کار برای اصلاح</span>
                                    </button>
                                </div>
                            </form>
                        @else
                            <div class="p-3 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 text-xs font-bold">
                                ⏳ این کار در انتظار تایید سرپرست ({{ $pendingSupervisorStage?->user?->full_name ?? 'بدون ناظر' }}) است.
                            </div>
                        @endif
                    </div>
                @else
                    <p class="text-[11px] text-slate-400 italic pt-2 border-t border-indigo-100 dark:border-indigo-900/40">
                        ℹ️ گزینه‌های تایید زمانی فعال می‌شوند که کار برای تایید سرپرست ارسال شود.
                    </p>
                @endif
            </div>
        </div>

        <!-- Right Column: Attachments & Live AJAX Chat Stream -->
        <div class="space-y-6">

            <!-- ATTACHMENTS PANEL -->
            <div class="p-4 sm:p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                <h3 class="text-sm font-black text-slate-800 dark:text-slate-100 flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    <span>فایل‌های پیوست</span>
                </h3>

                <div class="space-y-2">
                    <template x-if="attachments.length === 0">
                        <p class="text-xs text-slate-400 italic">هنوز فایلی پیوست نشده است.</p>
                    </template>
                    <template x-for="att in attachments" :key="att.id">
                        <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2 truncate">
                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                <a :href="att.file_url" target="_blank" class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline truncate" x-text="att.original_name"></a>
                            </div>
                            <button type="button" @click="removeAttachment(att.id)" class="text-rose-500 hover:text-rose-700 text-xs font-bold shrink-0 mr-2">حذف</button>
                        </div>
                    </template>
                </div>

                <!-- AJAX File Upload Input -->
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                    <input type="file" @change="uploadAttachment($event)" :disabled="uploadingAttachment" class="text-xs text-slate-500 file:mr-4 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 w-full">
                    <span x-show="uploadingAttachment" class="text-xs text-indigo-600 animate-pulse font-bold block mt-1">در حال آپلود فایل...</span>
                </div>
            </div>

            <!-- LIVE CHAT STREAM -->
            <div class="p-4 sm:p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col space-y-4 h-[450px] sm:h-[550px]">
                <h3 class="text-sm font-black text-slate-800 dark:text-slate-100 flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    <span>گفتگو و یادداشت‌ها</span>
                </h3>

                <!-- Chat Feed -->
                <div x-ref="chatFeedPage" class="flex-1 space-y-3 overflow-y-auto p-2">
                    <template x-if="chats.length === 0">
                        <p class="text-xs text-slate-400 text-center py-10 italic">هنوز پیامی ثبت نشده است.</p>
                    </template>
                    <template x-for="c in chats" :key="c.id">
                        <div class="space-y-1" :class="c.is_current_user ? 'text-left' : 'text-right'">
                            <div class="inline-block p-3.5 rounded-2xl text-xs max-w-xs space-y-1 shadow-sm"
                                 :class="c.is_current_user ? 'bg-indigo-600 text-white rounded-tl-none' : 'bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 border border-slate-200 dark:border-slate-700 rounded-tr-none'">
                                <div class="flex items-center justify-between gap-3 text-[10px] opacity-75 font-semibold">
                                    <span x-text="c.user_name"></span>
                                    <span x-text="c.created_at"></span>
                                </div>
                                <p class="whitespace-pre-line leading-normal" x-text="c.message"></p>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- AJAX Chat Input -->
                <form @submit.prevent="sendChatMessage()" class="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <textarea x-model="chatMessage" @keydown.enter.prevent="sendChatMessage()" rows="3" placeholder="پیام یا یادداشت خود را بنویسید..." class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 leading-relaxed"></textarea>
                    <button type="submit" :disabled="sendingChat || !chatMessage.trim()" class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition-all shadow-md shadow-indigo-500/20 disabled:opacity-50 flex items-center justify-center gap-1.5">
                        <span x-show="!sendingChat">ارسال پیام</span>
                        <span x-show="sendingChat" class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>در حال ارسال...</span>
                        </span>
                    </button>
                </form>
            </div>

        </div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof $.fn.persianDatepicker !== 'undefined') {
            $('#task_start_date_edit, #task_due_date_edit').persianDatepicker({
                format: 'YYYY/MM/DD',
                autoClose: true,
                initialValue: false,
                calendar: {
                    persian: {
                        locale: 'fa'
                    }
                }
            });
        }
    });
</script>
@endsection
