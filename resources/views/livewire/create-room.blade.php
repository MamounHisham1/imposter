<div class="min-h-screen flex items-center justify-center p-4 animate-fade-in">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold mb-4 bg-gradient-to-r from-blue-200 to-white bg-clip-text text-transparent">
                المخادع
            </h1>
            <p class="text-blue-200">لعبة الكلمات العربية الاجتماعية</p>
        </div>

        <div class="bg-gradient-to-br from-blue-900/80 to-blue-800/80 backdrop-blur-lg rounded-3xl p-8 shadow-2xl border border-blue-700/30">
            @if($roomCode)
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-emerald-500 to-green-600 rounded-full mb-6 transform hover:scale-105 transition-all duration-300">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold mb-3 text-blue-100">تم إنشاء الغرفة!</h2>
                    <p class="text-blue-300 mb-6">شارك هذا الرمز مع أصدقائك للانضمام</p>

                    <div class="bg-gradient-to-br from-blue-800/40 to-blue-900/40 p-6 rounded-2xl border border-blue-700/20 mb-8">
                        <div class="text-4xl font-bold tracking-widest text-center text-white">{{ $roomCode }}</div>
                    </div>

                    <p class="text-sm text-blue-300 mb-8">
                        سيتم توجيهك تلقائيًا إلى الغرفة...
                    </p>
                </div>
            @else
                <h2 class="text-2xl font-bold mb-8 text-center text-blue-100">إنشاء غرفة جديدة</h2>

                <form wire:submit="createRoom">
                    <div class="space-y-6">
                        <div>
                            <label for="playerName" class="block text-sm font-medium mb-3 text-blue-200">
                                اسمك
                            </label>
                            <input
                                type="text"
                                id="playerName"
                                wire:model="playerName"
                                class="w-full px-5 py-4 bg-blue-800/30 border border-blue-600/30 rounded-2xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-blue-400 transition-all duration-300"
                                placeholder="أدخل اسمك"
                                required
                                autofocus
                            >
                            @error('playerName')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="category" class="block text-sm font-medium mb-3 text-blue-200">
                                التصنيف
                            </label>
                            <select
                                id="category"
                                wire:model="category"
                                class="w-full px-5 py-4 bg-blue-800/30 border border-blue-600/30 rounded-2xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white transition-all duration-300"
                            >
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}" class="bg-blue-900">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>

                        @error('create')
                            <div class="bg-red-500/20 border border-red-500/30 rounded-2xl p-4">
                                <p class="text-sm text-red-400">{{ $message }}</p>
                            </div>
                        @enderror

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="relative overflow-hidden group w-full bg-gradient-to-r from-emerald-500 to-green-600
                                   hover:from-emerald-600 hover:to-green-700 text-white font-bold py-4 px-4
                                   rounded-2xl transition-all duration-300 transform hover:scale-105 active:scale-95
                                   shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span class="relative z-10 text-lg" wire:loading.remove>إنشاء غرفة</span>
                            <span class="relative z-10" wire:loading>
                                <svg class="animate-spin h-6 w-6 mx-auto text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                            <div class="absolute inset-0 h-full w-full transform scale-0 group-hover:scale-100
                                      transition-transform duration-300 ease-out bg-gradient-to-r
                                      from-emerald-600 to-green-700 -z-1"></div>
                        </button>
                    </div>
                </form>

                <div class="mt-8 pt-8 border-t border-blue-700/30">
                    <p class="text-center text-blue-300 mb-6">لديك رمز غرفة مسبقًا؟</p>
                    <a
                        href="{{ route('join-room') }}"
                        wire:navigate
                        class="block w-full bg-blue-800/30 hover:bg-blue-800/50 text-blue-200 font-bold py-4 px-4 rounded-2xl text-center transition-all duration-300 transform hover:scale-105 border border-blue-600/30"
                    >
                        الانضمام إلى غرفة موجودة
                    </a>
                </div>
            @endif
        </div>

        <div class="mt-8 text-center">
            <p class="text-sm text-blue-300">
                لعبة المخادع - تحتاج إلى 3-8 لاعبين
            </p>
        </div>
    </div>
</div>
