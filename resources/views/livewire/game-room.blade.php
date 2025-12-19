<div dir="rtl" x-data="{
    init() {
        // Initialize SSE connection
        const eventSource = new EventSource('{{ route('sse.stream', ['room' => $room->code]) }}');

        eventSource.addEventListener('player_joined', (e) => {
            const data = JSON.parse(e.data);
            Livewire.dispatch('player-joined', data);
        });

        eventSource.addEventListener('phase_changed', (e) => {
            const data = JSON.parse(e.data);
            Livewire.dispatch('phase-changed', data);
        });

        eventSource.addEventListener('hint_submitted', (e) => {
            const data = JSON.parse(e.data);
            Livewire.dispatch('hint-submitted-event', data);
        });

        eventSource.addEventListener('vote_cast', (e) => {
            const data = JSON.parse(e.data);
            Livewire.dispatch('vote-cast-event', data);
        });

        eventSource.addEventListener('round_finished', (e) => {
            const data = JSON.parse(e.data);
            Livewire.dispatch('round-finished', data);
        });

        // Cleanup on component unmount
        this.$wire.on('destroy', () => {
            eventSource.close();
        });
    }
}"
x-init="init"
x-show="true"
x-transition:enter="transition ease-out duration-300"
x-transition:enter-start="opacity-0"
x-transition:enter-end="opacity-100"
x-transition:leave="transition ease-in duration-200"
x-transition:leave-start="opacity-100"
x-transition:leave-end="opacity-0"
class="min-h-screen bg-neutral-50 dark:bg-neutral-900 phase-transition">
    <!-- Header -->
    <header class="bg-brand-gradient text-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-white">المخادع</h1>
                    <div class="mr-4 px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm">
                        <span class="font-medium">الغرفة:</span>
                        <span class="font-mono tracking-wider">{{ $room->code }}</span>
                    </div>
                </div>

                <div class="flex items-center space-x-4 space-x-reverse">
                    <div class="text-sm text-white/90">
                        <span class="font-medium">اللاعبون:</span>
                        <span>{{ $players->count() }} / 8</span>
                    </div>
                    <div class="text-sm text-white/90">
                        <span class="font-medium">أنت:</span>
                        <span>{{ $player->name ?? 'غير معروف' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Game Status -->
        <div class="mb-8">
            <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm p-6 animate-slide-up-fade">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-primary-950 dark:text-primary-200 mb-2">
                            @switch($room->status)
                                @case('waiting')
                                    ⏳ في انتظار اللاعبين
                                    @break
                                @case('hints')
                                    ✍️ مرحلة التلميحات
                                    @break
                                @case('voting')
                                    🗳️ مرحلة التصويت
                                    @break
                                @case('results')
                                    🏆 النتائج
                                    @break
                            @endswitch
                        </h2>

                        @if($room->status === 'waiting')
                            <p class="text-neutral-500 dark:text-neutral-400">
                                انتظر حتى ينضم 3-8 لاعبين للبدء
                            </p>
                        @elseif($room->status === 'hints')
                            <p class="text-neutral-500 dark:text-neutral-400">
                                {{ $wordToShow }}
                            </p>
                        @elseif($room->status === 'voting')
                            <p class="text-neutral-500 dark:text-neutral-400">
                                اختر من تعتقد أنه المخادع
                            </p>
                        @endif
                    </div>

                    @if($room->status === 'waiting' && $room->canStartGame() && $player && $room->creator_id === $player->id)
                        <button
                            wire:click="startGame"
                            class="bg-success-500 hover:bg-success-600 text-white font-semibold py-2 px-6 rounded-lg transition-all duration-200"
                        >
                            بدء اللعبة
                        </button>
                    @elseif($room->status === 'waiting' && $room->canStartGame() && $player && $room->creator_id !== $player->id)
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            ⏳ انتظر منشئ الغرفة لبدء اللعبة
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Players & Game Info -->
            <div class="lg:col-span-1 space-y-8">
                <!-- Players List -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">اللاعبون</h3>
                    <div class="space-y-3">
                        @foreach($players as $p)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex items-center space-x-3 space-x-reverse">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                        <span class="text-blue-600 dark:text-blue-300 font-medium">
                                            {{ substr($p->name, 0, 1) }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ $p->name }}
                                            @if($p->is_imposter && $room->status === 'results')
                                                <span class="text-red-600 dark:text-red-400 text-sm">(المخادع)</span>
                                            @endif
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            النقاط: {{ $p->score }}
                                        </p>
                                    </div>
                                </div>

                                @if($room->status === 'hints')
                                    <div class="text-sm">
                                        @if($p->hasSubmittedHint())
                                            <span class="text-green-600 dark:text-green-400">✓ قدم تلميحًا</span>
                                        @else
                                            <span class="text-gray-400">⏳ يكتب...</span>
                                        @endif
                                    </div>
                                @elseif($room->status === 'voting')
                                    <div class="text-sm">
                                        @if($p->hasVoted())
                                            <span class="text-green-600 dark:text-green-400">✓ صوت</span>
                                        @else
                                            <span class="text-gray-400">⏳ يفكر...</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Game Controls -->
                @if($room->status === 'results' && $player && $room->creator_id === $player->id)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">جولة جديدة</h3>
                        <button
                            wire:click="startNewRound"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200"
                        >
                            بدء جولة جديدة
                        </button>
                    </div>
                @elseif($room->status === 'results' && $player && $room->creator_id !== $player->id)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">جولة جديدة</h3>
                        <div class="text-center py-4 text-gray-500 dark:text-gray-400">
                            ⏳ انتظر منشئ الغرفة لبدء جولة جديدة
                        </div>
                    </div>
                @endif
            </div>

            <!-- Main Game Area -->
            <div class="lg:col-span-2 space-y-8">
                @if($room->status === 'hints')
                    <!-- Hint Submission -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">التلميح الخاص بك</h3>

                        @if($hasSubmittedHint)
                            <div class="text-center py-8">
                                <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full mb-4">
                                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <p class="text-gray-600 dark:text-gray-300">لقد قدمت تلميحك! انتظر باقي اللاعبين.</p>
                            </div>
                        @else
                            <form wire:submit="submitHint">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            اكتب تلميحًا واحدًا أو جملة قصيرة (3 كلمات كحد أقصى)
                                        </label>
                                        <textarea
                                            wire:model="hintText"
                                            rows="3"
                                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                            placeholder="مثال: حيوان أليف"
                                            required
                                        ></textarea>
                                        @error('hintText')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <button
                                        type="submit"
                                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200"
                                    >
                                        إرسال التلميح
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                @endif

                @if($room->status === 'voting')
                    <!-- Voting -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">التصويت على المخادع</h3>

                        @if($hasVoted)
                            <div class="text-center py-8">
                                <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full mb-4">
                                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <p class="text-gray-600 dark:text-gray-300">لقد صوتت! انتظر باقي اللاعبين.</p>
                            </div>
                        @else
                            <!-- Hints Display -->
                            <div class="mb-6">
                                <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-3">التلميحات المقدمة:</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    @foreach($hints as $hint)
                                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                            <p class="text-gray-900 dark:text-white font-medium mb-1">{{ $hint->player->name }}</p>
                                            <p class="text-gray-600 dark:text-gray-300">{{ $hint->text }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Voting Form -->
                            <form wire:submit="submitVote">
                                <div class="space-y-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        اختر من تعتقد أنه المخادع:
                                    </label>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        @foreach($players as $p)
                                            @if($p->id !== $player->id)
                                                <label class="relative">
                                                    <input
                                                        type="radio"
                                                        wire:model="voteTargetId"
                                                        value="{{ $p->id }}"
                                                        class="sr-only peer"
                                                    >
                                                    <div class="p-4 border-2 border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:border-blue-500 peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 transition duration-200">
                                                        <div class="flex items-center space-x-3 space-x-reverse">
                                                            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                                                <span class="text-blue-600 dark:text-blue-300 font-medium">
                                                                    {{ substr($p->name, 0, 1) }}
                                                                </span>
                                                            </div>
                                                            <div>
                                                                <p class="font-medium text-gray-900 dark:text-white">{{ $p->name }}</p>
                                                                <p class="text-sm text-gray-500 dark:text-gray-400">النقاط: {{ $p->score }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </label>
                                            @endif
                                        @endforeach
                                    </div>

                                    @error('voteTargetId')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror

                                    <button
                                        type="submit"
                                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200"
                                    >
                                        تأكيد التصويت
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                @endif

                @if($room->status === 'results')
                    <!-- Results -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">نتائج الجولة</h3>

                        <div class="space-y-6">
                            <!-- Secret Word -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 text-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">الكلمة السرية كانت:</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $room->current_word }}</p>
                            </div>

                            <!-- Imposter Reveal -->
                            @if($imposter = $room->getImposter())
                                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6">
                                    <div class="flex items-center justify-center space-x-4 space-x-reverse mb-4">
                                        <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-lg font-semibold text-red-700 dark:text-red-300">المخادع كان:</p>
                                            <p class="text-2xl font-bold text-red-800 dark:text-red-200">{{ $imposter->name }}</p>
                                        </div>
                                    </div>

                                    @if($imposter->votesReceived()->count() > 0)
                                        <p class="text-center text-red-600 dark:text-red-400">
                                            تم اكتشاف المخادع! +1 نقطة لكل من صوت له
                                        </p>
                                    @else
                                        <p class="text-center text-green-600 dark:text-green-400">
                                            لم يتم اكتشاف المخادع! +1 نقطة للمخادع
                                        </p>
                                    @endif
                                </div>
                            @endif

                            <!-- Votes Summary -->
                            <div>
                                <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-3">تفاصيل التصويت:</h4>
                                <div class="space-y-2">
                                    @foreach($votes as $vote)
                                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                            <span class="text-gray-900 dark:text-white">{{ $vote->voter->name }}</span>
                                            <span class="text-gray-500 dark:text-gray-400">→</span>
                                            <span class="text-gray-900 dark:text-white">{{ $vote->targetPlayer->name }}</span>
                                            @if($vote->targetPlayer->is_imposter)
                                                <span class="px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-300 text-xs rounded-full">
                                                    +1 نقطة
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </main>

    <!-- SSE Status -->
    <div class="fixed bottom-4 left-4">
        <div class="flex items-center space-x-2 text-sm text-gray-500">
            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
            <span>متصل</span>
        </div>
    </div>
</div>
