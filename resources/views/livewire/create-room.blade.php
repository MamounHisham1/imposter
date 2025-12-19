<div dir="rtl" class="min-h-screen bg-neutral-50 dark:bg-neutral-900 flex items-center justify-center p-4 phase-transition">
    <div class="w-full max-w-md">
        <div class="text-center mb-8 animate-slide-up-fade">
            <h1 class="text-3xl font-bold text-primary-950 dark:text-primary-200 mb-2">المخادع</h1>
            <p class="text-neutral-500 dark:text-neutral-400">لعبة الكلمات العربية الاجتماعية</p>
        </div>

        <div class="bg-white dark:bg-neutral-800 rounded-2xl shadow-xl p-6 animate-slide-up-fade">
            @if($roomCode)
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-success-500/10 dark:bg-success-500/20 rounded-full mb-4 animate-bounce-in">
                        <svg class="w-8 h-8 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-primary-950 dark:text-primary-200 mb-2">تم إنشاء الغرفة!</h2>
                    <p class="text-neutral-500 dark:text-neutral-400 mb-4">شارك هذا الرمز مع أصدقائك للانضمام</p>

                    <div class="bg-primary-50 dark:bg-primary-950/30 border border-primary-200 dark:border-primary-800 rounded-lg p-4 mb-6">
                        <div class="text-3xl font-bold tracking-wider text-primary-950 dark:text-primary-200">{{ $roomCode }}</div>
                    </div>

                    <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-6">
                        سيتم توجيهك تلقائيًا إلى الغرفة...
                    </p>
                </div>
            @else
                <h2 class="text-xl font-semibold text-primary-950 dark:text-primary-200 mb-6 text-center">إنشاء غرفة جديدة</h2>

                <form wire:submit="createRoom">
                    <div class="space-y-4">
                        <div>
                            <label for="playerName" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                                اسمك
                            </label>
                            <input
                                type="text"
                                id="playerName"
                                wire:model="playerName"
                                class="w-full px-4 py-3 border border-neutral-300 dark:border-neutral-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-neutral-700 dark:text-white transition-all duration-200"
                                placeholder="أدخل اسمك"
                                required
                                autofocus
                            >
                            @error('playerName')
                                <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="category" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                                التصنيف
                            </label>
                            <select
                                id="category"
                                wire:model="category"
                                class="w-full px-4 py-3 border border-neutral-300 dark:border-neutral-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent dark:bg-neutral-700 dark:text-white transition-all duration-200"
                            >
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>

                        @error('create')
                            <div class="bg-error-500/10 dark:bg-error-500/20 border border-error-200 dark:border-error-800 rounded-lg p-4">
                                <p class="text-sm text-error-500">{{ $message }}</p>
                            </div>
                        @enderror

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span wire:loading.remove>إنشاء غرفة</span>
                            <span wire:loading>
                                <svg class="animate-spin h-5 w-5 mx-auto text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </form>

                <div class="mt-6 pt-6 border-t border-neutral-200 dark:border-neutral-700">
                    <p class="text-center text-neutral-500 dark:text-neutral-400 mb-4">لديك رمز غرفة مسبقًا؟</p>
                    <a
                        href="{{ route('join-room') }}"
                        wire:navigate
                        class="block w-full bg-neutral-100 dark:bg-neutral-700 hover:bg-neutral-200 dark:hover:bg-neutral-600 text-neutral-800 dark:text-neutral-200 font-semibold py-3 px-4 rounded-lg text-center transition-all duration-200"
                    >
                        الانضمام إلى غرفة موجودة
                    </a>
                </div>
            @endif
        </div>

        <div class="mt-6 text-center">
            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                لعبة المخادع - تحتاج إلى 3-8 لاعبين
            </p>
        </div>
    </div>
</div>
