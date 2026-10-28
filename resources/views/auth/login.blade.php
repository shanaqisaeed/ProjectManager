<x-guest-layout title="ورود به حساب کاربری">
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-8 shadow-xl border border-slate-200/80 dark:border-slate-800 backdrop-blur-md"
          x-data="{ showPassword: false, loading: false }">

        <!-- Logo & Title Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-600 text-white font-black text-2xl shadow-lg shadow-indigo-500/30 mb-4">
                PMR
            </div>
            <h2 class="text-2xl font-extrabold text-slate-800 dark:text-slate-100">خوش آمدید!</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">برای شروع، نام کاربری و رمز عبور خود را وارد کنید</p>
        </div>

        <!-- Login Form -->
        <form method="POST" action="{{ route('login') }}" @submit="loading = true" class="space-y-5">
            @csrf

            <!-- Username Field -->
            <div>
                <label for="username" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                    نام کاربری <span class="text-rose-500">*</span>
                </label>
                <input type="text"
                       id="username"
                       name="username"
                       value="{{ old('username') }}"
                       required
                       autofocus
                       placeholder="نام کاربری..."
                       class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 outline-none transition-all text-sm @error('username') border-rose-500 @enderror">
                @error('username')
                    <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password Field -->
            <div>
                <label for="password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                    رمز عبور <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <input :type="showPassword ? 'text' : 'password'"
                           id="password"
                           name="password"
                           required
                           placeholder="••••••••"
                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 outline-none transition-all text-sm @error('password') border-rose-500 @enderror">

                    <button type="button"
                            @click="showPassword = !showPassword"
                            class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">
                        <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.16 10.16 0 012.122-.063c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21M3 3l18 18"/></svg>
                    </button>
                </div>
                @error('password')
                    <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between py-1">
                <label for="remember" class="inline-flex items-center gap-2 cursor-pointer text-xs font-medium text-slate-600 dark:text-slate-400 select-none">
                    <input type="checkbox"
                           id="remember"
                           name="remember"
                           value="1"
                           class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span>مرا به خاطر بسپار</span>
                </label>
            </div>

            <!-- Submit Button -->
            <button type="submit"
                    :disabled="loading"
                    class="w-full py-3.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-indigo-500/25 transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-75">
                <span x-text="loading ? 'در حال ورود...' : 'ورود'"></span>
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-100 dark:border-slate-800 text-center">
            <p class="text-xs text-slate-400">سامانه مدیریت پروژه و سازمان</p>
        </div>
    </div>
</x-guest-layout>
