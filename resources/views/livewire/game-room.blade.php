<div
    dir="rtl"
    x-data="{
    // State
    serverTimeRemaining: {{ $timeRemaining ?? 0 }},
    clientTimeRemaining: {{ $timeRemaining ?? 0 }},
    timerInterval: null,
    lastSync: Date.now(),
    roomStatus: '{{ $room->status }}',
    roomCode: '{{ $room->code }}',

    init() {
        console.log('🔌 Subscribing to room channel:', `room.${this.roomCode}`);

        // Wait for Echo to be available, then subscribe
        this.$nextTick(() => {
            if (typeof window.Echo !== 'undefined') {
                this.subscribeToChannel();
            } else {
                // Echo might not be ready yet, try again shortly
                setTimeout(() => this.subscribeToChannel(), 100);
            }
        });

        // Start client-side timer for smooth countdown
        this.initTimer();
    },

    subscribeToChannel() {
        if (typeof window.Echo === 'undefined') {
            console.error('Echo still not available');
            return;
        }

        const channel = window.Echo.channel(`room.${this.roomCode}`);

        // Listen to all game events
        channel
            .listen('.player.joined', (e) => {
                console.log('👤 Player joined:', e);
                this.$wire.$refresh();
            })
            .listen('.phase.changed', (e) => {
                console.log('🔄 Phase changed:', e);
                this.$wire.$refresh();
                this.resetTimer();
            })
            .listen('.hint.submitted', (e) => {
                console.log('💡 Hint submitted:', e);
                this.$wire.$refresh();
            })
            .listen('.vote.cast', (e) => {
                console.log('🗳️ Vote cast:', e);
                this.$wire.$refresh();
            })
            .listen('.round.finished', (e) => {
                console.log('🏁 Round finished:', e);
                this.$wire.$refresh();
                this.stopTimer();
            })
            .listen('.message.sent', (e) => {
                console.log('💬 Message sent:', e);
                Livewire.dispatch('message-sent', e);
            })
            .listen('.state.updated', (e) => {
                console.log('🔃 State updated:', e);
                this.$wire.$refresh();
            });

        console.log('✅ WebSocket channel subscribed');

        // Cleanup on component unmount
        this.$wire.on('destroy', () => {
            console.log('👋 Leaving channel:', `room.${this.roomCode}`);
            this.stopTimer();
            window.Echo.leave(`room.${this.roomCode}`);
        });
    },

    initTimer() {
        if (this.roomStatus === 'discussion' && this.clientTimeRemaining > 0) {
            this.startTimer();
        }
    },

    startTimer() {
        this.stopTimer();
        this.lastSync = Date.now();

        this.timerInterval = setInterval(() => {
            if (this.clientTimeRemaining > 0) {
                this.clientTimeRemaining--;
            } else {
                this.stopTimer();
            }
        }, 1000);
    },

    stopTimer() {
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
            this.timerInterval = null;
        }
    },

    resetTimer() {
        this.stopTimer();
        this.$wire.$refresh();
        this.$nextTick(() => {
            // After Livewire refresh, sync values from server
            const newTime = {{ $timeRemaining ?? 0 }};
            this.serverTimeRemaining = newTime;
            this.clientTimeRemaining = newTime;
            this.roomStatus = '{{ $room->status }}';
            this.initTimer();
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
class="min-h-screen bg-gradient-to-br from-blue-900 to-blue-700 phase-transition">
    <!-- Header -->
    <header class="bg-gradient-to-r from-blue-800/80 to-blue-900/80 backdrop-blur-lg border-b border-blue-700/30 shadow-xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center space-x-4 space-x-reverse">
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-200 to-white bg-clip-text text-transparent">
                        المخادع
                    </h1>
                    <div class="px-4 py-2 bg-blue-800/50 backdrop-blur-sm rounded-2xl border border-blue-600/30">
                        <span class="font-medium text-blue-200">الغرفة:</span>
                        <span class="font-mono tracking-widest text-white ml-2">{{ $room->code }}</span>
                    </div>
                </div>

                <div class="flex items-center space-x-6 space-x-reverse">
                    <div class="text-sm text-blue-200">
                        <span class="font-medium">اللاعبون:</span>
                        <span class="text-white font-bold ml-1">{{ $players->count() }} / 8</span>
                    </div>
                    <div class="text-sm text-blue-200">
                        <span class="font-medium">أنت:</span>
                        <span class="text-white font-bold ml-1">{{ $player->name ?? 'غير معروف' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Game Status -->
        <div class="mb-8">
            <div class="bg-gradient-to-br from-blue-900/80 to-blue-800/80 backdrop-blur-lg rounded-3xl p-8 shadow-2xl border border-blue-700/30 animate-fade-in">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <h2 class="text-2xl font-bold text-blue-100 mb-3">
                            @switch($room->status)
                                @case('waiting')
                                    ⏳ في انتظار اللاعبين
                                    @break
                                @case('hints')
                                    ✍️ مرحلة التلميحات
                                    @break
                                @case('discussion')
                                    💬 مرحلة النقاش
                                    @break
                                @case('voting')
                                    🗳️ مرحلة التصويت
                                    @break
                                @case('results')
                                    @if($gameStatus === 'finished')
                                        🏁 انتهت اللعبة!
                                    @else
                                        ⚖️ نتائج الجولة
                                    @endif
                                    @break
                            @endswitch
                        </h2>

                        @if($room->status === 'waiting')
                            <p class="text-blue-300">
                                انتظر حتى ينضم 3-8 لاعبين للبدء
                            </p>
                        @elseif($room->status === 'hints')
                            <p class="text-blue-300">
                                {{ $wordToShow }}
                            </p>
                        @elseif($room->status === 'discussion')
                            <p class="text-blue-300">
                                ناقش مع اللاعبين لتحديد المخادع
                                @if($timeRemaining !== null)
                                    <span class="font-bold text-white">({{ $timeRemaining }} ثانية)</span>
                                @endif
                            </p>
                        @elseif($room->status === 'voting')
                            <p class="text-blue-300">
                                اختر من تعتقد أنه المخادع
                            </p>
                        @elseif($room->status === 'results' && $gameStatus === 'finished')
                             <p class="text-xl font-bold {{ $winner === 'crewmates' ? 'text-emerald-400' : 'text-red-400' }}">
                                الفائز: {{ $winner === 'crewmates' ? 'المواطنون' : 'المخادع' }}
                            </p>
                        @endif
                    </div>

                    @if($room->status === 'waiting' && $room->canStartGame() && $player && $room->creator_id === $player->id)
                        <!-- Discussion Time Setting -->
                        <div class="mb-6" x-data="{ time: {{ $room->discussion_time }} }">
                            <label class="block text-sm font-medium text-blue-200 mb-3">
                                ⏱️ وقت النقاش (بالثواني)
                            </label>
                            <div class="flex items-center gap-3">
                                <input
                                    type="range"
                                    x-model="time"
                                    min="10"
                                    max="180"
                                    step="10"
                                    class="flex-1 h-2 bg-blue-800/50 rounded-lg appearance-none cursor-pointer"
                                    style="accent-color: #10b981;"
                                />
                                <div class="flex items-center gap-2 px-4 py-2 bg-blue-800/50 rounded-xl border border-blue-600/30 min-w-[80px] justify-center">
                                    <span class="text-white font-bold text-lg" x-text="time"></span>
                                    <span class="text-blue-300 text-sm">ث</span>
                                </div>
                            </div>
                            <div class="mt-2 flex justify-between text-xs text-blue-400">
                                <span>10 ثانية</span>
                                <span>3 دقائق</span>
                            </div>
                            <button
                                @click="$wire.call('updateDiscussionTime', time)"
                                class="mt-3 w-full px-4 py-2 bg-blue-700/50 hover:bg-blue-600/50 rounded-lg text-blue-200 text-sm transition-colors"
                            >
                                حفظ الوقت
                            </button>
                        </div>

                        <button
                            wire:click="startGame"
                            class="relative overflow-hidden group bg-gradient-to-r from-emerald-500 to-green-600
                                   hover:from-emerald-600 hover:to-green-700 text-white font-bold py-4 px-8
                                   rounded-2xl transition-all duration-300 transform hover:scale-105 active:scale-95
                                   shadow-lg hover:shadow-xl w-full"
                        >
                            <span class="relative z-10 text-lg">بدء اللعبة</span>
                            <div class="absolute inset-0 h-full w-full transform scale-0 group-hover:scale-100
                                      transition-transform duration-300 ease-out bg-gradient-to-r
                                      from-emerald-600 to-green-700 -z-1"></div>
                        </button>
                    @elseif($room->status === 'waiting' && $room->canStartGame() && $player && $room->creator_id !== $player->id)
                        <div class="text-sm text-blue-300 text-center py-4">
                            ⏳ انتظر منشئ الغرفة لبدء اللعبة
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if($isEliminated)
            <div class="mb-8 bg-gradient-to-br from-red-900/80 to-red-800/80 backdrop-blur-lg rounded-3xl p-6 shadow-2xl border border-red-700/30 text-center animate-fade-in">
                <p class="text-red-200 font-bold text-xl mb-2">⚠️ لقد تم إقصاؤك من اللعبة</p>
                <p class="text-red-300 text-sm">يمكنك مشاهدة بقية اللعبة ولكن لا يمكنك المشاركة.</p>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Players & Game Info -->
            <div class="lg:col-span-1 space-y-8">
                <!-- Players List -->
                <div class="bg-gradient-to-br from-blue-900/80 to-blue-800/80 backdrop-blur-lg rounded-3xl p-6 shadow-2xl border border-blue-700/30">
                    <h3 class="text-xl font-bold text-blue-100 mb-6">اللاعبون</h3>
                    <div class="space-y-4">
                        @foreach($players as $p)
                            <div class="flex items-center justify-between p-4 {{ $p->isEliminated() ? 'bg-gradient-to-br from-red-900/40 to-red-800/40 opacity-75' : 'bg-gradient-to-br from-blue-800/40 to-blue-900/40' }} rounded-2xl border {{ $p->isEliminated() ? 'border-red-700/20' : 'border-blue-700/20' }}">
                                <div class="flex items-center space-x-4 space-x-reverse">
                                    <div class="w-10 h-10 rounded-full {{ $p->isEliminated() ? 'bg-gradient-to-br from-red-700 to-red-800' : 'bg-gradient-to-br from-blue-600 to-blue-700' }} flex items-center justify-center shadow-lg">
                                        <span class="{{ $p->isEliminated() ? 'text-red-200' : 'text-blue-200' }} font-bold">
                                            {{ substr($p->name, 0, 1) }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="font-bold text-white">
                                            {{ $p->name }}
                                            @if($p->isEliminated())
                                                 <span class="text-red-300 text-xs font-normal">(مقصى)</span>
                                            @endif
                                            @if($p->is_imposter && ($room->status === 'results' && $gameStatus === 'finished'))
                                                <span class="text-red-400 text-sm font-bold">(المخادع)</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                @if($room->status === 'hints' && $p->isAlive())
                                    <div class="text-sm">
                                        @if($p->hasSubmittedHint())
                                            <span class="text-emerald-400 font-medium">✓ قدم تلميحًا</span>
                                        @else
                                            <span class="text-blue-300">⏳ يكتب...</span>
                                        @endif
                                    </div>
                                @elseif($room->status === 'voting' && $p->isAlive())
                                    <div class="text-sm">
                                        @if($p->hasVoted())
                                            <span class="text-emerald-400 font-medium">✓ صوت</span>
                                        @else
                                            <span class="text-blue-300">⏳ يفكر...</span>
                                        @endif
                                    </div>
                                @elseif($room->status === 'discussion' && $p->isAlive())
                                    <div class="text-sm">
                                        <span class="text-blue-300 font-medium">💬 يناقش</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Game Controls for Creator -->
                @if($room->status === 'results' && $player && $room->creator_id === $player->id)
                    <div class="bg-gradient-to-br from-blue-900/80 to-blue-800/80 backdrop-blur-lg rounded-3xl p-6 shadow-2xl border border-blue-700/30">
                        @if($gameStatus === 'finished')
                            <h3 class="text-xl font-bold text-blue-100 mb-6">لعبة جديدة</h3>
                            <button
                                wire:click="startNewRound"
                                class="relative overflow-hidden group w-full bg-gradient-to-r from-blue-600 to-blue-700
                                       hover:from-blue-700 hover:to-blue-800 text-white font-bold py-4 px-4
                                       rounded-2xl transition-all duration-300 transform hover:scale-105 active:scale-95
                                       shadow-lg hover:shadow-xl"
                            >
                                <span class="relative z-10 text-lg">بدء لعبة جديدة</span>
                                <div class="absolute inset-0 h-full w-full transform scale-0 group-hover:scale-100
                                          transition-transform duration-300 ease-out bg-gradient-to-r
                                          from-blue-700 to-blue-800 -z-1"></div>
                            </button>
                        @else
                            <h3 class="text-xl font-bold text-blue-100 mb-6">استمرار اللعبة</h3>
                            <button
                                wire:click="startNextTurn"
                                class="relative overflow-hidden group w-full bg-gradient-to-r from-emerald-500 to-green-600
                                       hover:from-emerald-600 hover:to-green-700 text-white font-bold py-4 px-4
                                       rounded-2xl transition-all duration-300 transform hover:scale-105 active:scale-95
                                       shadow-lg hover:shadow-xl"
                            >
                                <span class="relative z-10 text-lg">الجولة التالية</span>
                                <div class="absolute inset-0 h-full w-full transform scale-0 group-hover:scale-100
                                          transition-transform duration-300 ease-out bg-gradient-to-r
                                          from-emerald-600 to-green-700 -z-1"></div>
                            </button>
                        @endif
                    </div>
                @elseif($room->status === 'results' && $player && $room->creator_id !== $player->id)
                    <div class="bg-gradient-to-br from-blue-900/80 to-blue-800/80 backdrop-blur-lg rounded-3xl p-6 shadow-2xl border border-blue-700/30">
                        <div class="text-center py-6 text-blue-300">
                             ⏳ انتظر منشئ الغرفة
                        </div>
                    </div>
                @endif
            </div>

            <!-- Main Game Area -->
            <div class="lg:col-span-2 space-y-8">
                @if($room->status === 'reveal_word')
                    <!-- Word Reveal Phase -->
                    <div
                        class="bg-gradient-to-br from-blue-900/80 to-blue-800/80 backdrop-blur-lg rounded-3xl p-12 shadow-2xl border border-blue-700/30"
                        x-data="{
                            revealTimer: {{ $timeRemaining ?? 10 }},
                            hasTransitioned: false,
                            countdown: null,

                            init() {
                                this.startCountdown();
                            },

                            startCountdown() {
                                this.countdown = setInterval(() => {
                                    if (this.revealTimer > 0) {
                                        this.revealTimer--;
                                    } else if (!this.hasTransitioned) {
                                        this.hasTransitioned = true;
                                        clearInterval(this.countdown);
                                        $wire.call('transitionToDiscussion');
                                    }
                                }, 1000);
                            }
                        }"
                        x-init="init()"
                    >
                        <div class="text-center">
                            <h3 class="text-3xl font-bold text-blue-100 mb-8">الكلمة</h3>

                            <!-- Word Display -->
                            <div class="relative mb-8">
                                <div class="absolute inset-0 bg-gradient-to-r from-emerald-500/20 to-green-500/20 rounded-3xl blur-2xl"></div>
                                <div class="relative bg-gradient-to-br from-emerald-600 to-green-700 rounded-3xl p-12 shadow-2xl border-4 border-emerald-500/50">
                                    <p class="text-6xl md:text-8xl font-black text-white tracking-wider animate-bounce-in">
                                        {{ $wordToShow }}
                                    </p>
                                </div>
                            </div>

                            <!-- Countdown Timer -->
                            <div class="flex flex-col items-center gap-4 mb-6">
                                <p class="text-xl text-blue-200">النقاش يبدأ خلال</p>
                                <div class="flex items-center justify-center w-20 h-20 bg-blue-800/50 rounded-full border-4 border-blue-600/50">
                                    <span class="text-4xl font-bold text-white" x-text="Math.max(0, revealTimer)">{{ max(0, $timeRemaining ?? 10) }}</span>
                                </div>
                            </div>

                            <p class="text-blue-300 text-lg">
                                احفظ الكلمة! سيبدأ النقاش قريبًا...
                            </p>
                        </div>
                    </div>
                @endif

                @if($room->status === 'discussion')
                    <!-- Discussion Chat -->
                    <div
                        class="bg-gradient-to-br from-blue-900/80 to-blue-800/80 backdrop-blur-lg rounded-3xl p-8 shadow-2xl border border-blue-700/30"
                    >
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-2xl font-bold text-blue-100">💬 النقاش</h3>
                            <div class="flex items-center gap-3">
                                @if($room->creator_id === $player->id)
                                    <!-- Discussion Time Controls (Creator Only) -->
                                    <div x-data="{ editing: false, time: {{ $room->discussion_time }} }">
                                        <div x-show="!editing" class="flex items-center gap-2">
                                            <div x-show="$root.clientTimeRemaining > 0" class="flex items-center gap-2 px-4 py-2 bg-blue-800/50 rounded-xl border border-blue-600/30">
                                                <svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span class="text-white font-bold" x-text="$root.clientTimeRemaining + 's'">{{ $timeRemaining }}s</span>
                                            </div>
                                            <button
                                                @click="editing = true"
                                                class="px-3 py-2 bg-blue-700/50 hover:bg-blue-600/50 rounded-lg transition-colors"
                                                title="تعديل الوقت"
                                            >
                                                <svg class="w-5 h-5 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                                </svg>
                                            </button>
                                        </div>
                                        <div x-show="editing" class="flex items-center gap-2">
                                            <input
                                                type="number"
                                                x-model="time"
                                                min="10"
                                                max="300"
                                                step="10"
                                                class="w-20 px-3 py-2 bg-blue-800/50 border border-blue-600/30 rounded-lg text-white text-center"
                                            />
                                            <span class="text-blue-200 text-sm">ثانية</span>
                                            <button
                                                @click="$wire.call('updateDiscussionTime', time); editing = false"
                                                class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors"
                                            >
                                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                            <button
                                                @click="editing = false; time = {{ $room->discussion_time }}"
                                                class="px-3 py-2 bg-red-600 hover:bg-red-700 rounded-lg transition-colors"
                                            >
                                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <div x-show="$root.clientTimeRemaining > 0" class="flex items-center gap-2 px-4 py-2 bg-blue-800/50 rounded-xl border border-blue-600/30">
                                        <svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span class="text-white font-bold" x-text="$root.clientTimeRemaining + 's'">{{ $timeRemaining }}s</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if($isEliminated)
                            <div class="text-center py-10 text-blue-300">
                                لقد تم إقصاؤك، يمكنك المشاهدة فقط.
                            </div>
                        @elseif(!$canChat)
                            <div class="text-center py-10 text-blue-300">
                                لا يمكنك المشاركة في النقاش الآن.
                            </div>
                        @else
                            <!-- Chat Messages -->
                            <div
                                id="chat-messages"
                                class="mb-6 h-[28rem] overflow-y-auto bg-gradient-to-b from-blue-900/40 to-blue-950/60 rounded-2xl p-6 space-y-4 border border-blue-700/30 shadow-inner scrollbar-thin scrollbar-thumb-blue-700 scrollbar-track-blue-900/20"
                                x-data="{
                                    messageCount: {{ count($messages) }},
                                    scrollToBottom(smooth = true) {
                                        const container = this.$el;
                                        container.scrollTo({
                                            top: container.scrollHeight,
                                            behavior: smooth ? 'smooth' : 'auto'
                                        });
                                    }
                                }"
                                x-init="scrollToBottom(false);"
                                x-effect="
                                    if ({{ count($messages) }} !== messageCount) {
                                        messageCount = {{ count($messages) }};
                                        $nextTick(() => scrollToBottom(true));
                                    }
                                "
                                @message-sent.window="$nextTick(() => scrollToBottom(true))"
                            >
                                @forelse($messages as $index => $message)
                                    <div
                                        class="flex flex-col {{ $message['player_id'] === $player->id ? 'items-end' : 'items-start' }} animate-fade-in"
                                        style="animation-delay: {{ $index * 0.05 }}s"
                                    >
                                        <div class="flex items-center gap-2 mb-1.5 {{ $message['player_id'] === $player->id ? 'flex-row-reverse' : '' }}">
                                            <!-- Avatar -->
                                            <div class="w-7 h-7 rounded-full {{ $message['player_id'] === $player->id ? 'bg-gradient-to-br from-emerald-500 to-green-600' : 'bg-gradient-to-br from-blue-600 to-blue-700' }} flex items-center justify-center shadow-md">
                                                <span class="text-white text-xs font-bold">
                                                    {{ mb_substr($message['player_name'], 0, 1) }}
                                                </span>
                                            </div>
                                            <span class="text-sm font-semibold {{ $message['player_id'] === $player->id ? 'text-emerald-300' : 'text-blue-300' }}">
                                                {{ $message['player_name'] }}
                                            </span>
                                            <span class="text-xs text-blue-500">
                                                {{ \Carbon\Carbon::parse($message['timestamp'])->format('H:i') }}
                                            </span>
                                        </div>
                                        <div class="max-w-xs md:max-w-md px-5 py-3 rounded-2xl shadow-lg {{ $message['player_id'] === $player->id ? 'bg-gradient-to-br from-emerald-600 to-green-700 text-white rounded-tr-sm' : 'bg-gradient-to-br from-blue-800/60 to-blue-900/60 text-blue-50 rounded-tl-sm backdrop-blur-sm' }} border {{ $message['player_id'] === $player->id ? 'border-emerald-600/50' : 'border-blue-700/40' }}">
                                            <p class="text-base leading-relaxed break-words">{{ $message['message'] }}</p>
                                        </div>
                                    </div>
                                @empty
                                    <div class="flex flex-col items-center justify-center h-full text-center py-10">
                                        <div class="w-20 h-20 bg-blue-800/30 rounded-full flex items-center justify-center mb-4">
                                            <svg class="w-10 h-10 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                            </svg>
                                        </div>
                                        <p class="text-blue-300 text-lg font-medium">لا توجد رسائل بعد</p>
                                        <p class="text-blue-400 text-sm mt-2">ابدأ النقاش واكتشف المخادع!</p>
                                    </div>
                                @endforelse
                            </div>

                            <!-- Chat Input -->
                            <form wire:submit="submitMessage" class="relative">
                                <div class="flex gap-3 items-end">
                                    <div class="flex-1">
                                        <textarea
                                            wire:model="chatMessage"
                                            rows="1"
                                            class="w-full px-5 py-4 bg-blue-800/40 border-2 border-blue-600/40 rounded-2xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-white placeholder-blue-400 transition-all duration-300 resize-none"
                                            placeholder="اكتب رسالتك هنا..."
                                            required
                                            :disabled="$root.clientTimeRemaining <= 0"
                                            x-data="{
                                                resize() {
                                                    $el.style.height = 'auto';
                                                    $el.style.height = $el.scrollHeight + 'px';
                                                }
                                            }"
                                            x-init="resize()"
                                            @input="resize()"
                                            @keydown.enter.prevent="
                                                if (!$event.shiftKey) {
                                                    $el.form.requestSubmit();
                                                }
                                            "
                                        ></textarea>
                                    </div>
                                    <button
                                        type="submit"
                                        class="relative overflow-hidden group bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-bold py-4 px-6 rounded-2xl transition-all duration-300 transform hover:scale-105 active:scale-95 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
                                        :disabled="$root.clientTimeRemaining <= 0"
                                    >
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                        </svg>
                                    </button>
                                </div>
                                @error('chatMessage')
                                    <p class="mt-2 text-sm text-red-400 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </form>
                        @endif
                    </div>
                @endif

                @if($room->status === 'voting')
                    <!-- Voting -->
                    <div class="bg-gradient-to-br from-blue-900/80 to-blue-800/80 backdrop-blur-lg rounded-3xl p-8 shadow-2xl border border-blue-700/30">
                        <h3 class="text-2xl font-bold text-blue-100 mb-6">التصويت على المخادع</h3>

                        <!-- Hints Display -->
                        <div class="mb-8">
                            <h4 class="font-medium text-blue-200 mb-4">التلميحات المقدمة:</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach($hints as $hint)
                                    <div class="bg-gradient-to-br from-blue-800/40 to-blue-900/40 rounded-2xl p-5 border border-blue-700/20">
                                        <p class="text-blue-100 font-bold mb-2">{{ $hint->player->name }}</p>
                                        <p class="text-blue-300">{{ $hint->text }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        @if($isEliminated)
                             <div class="text-center py-10 text-blue-300">
                                لقد تم إقصاؤك، لا يمكنك التصويت.
                            </div>
                        @elseif($hasVoted)
                            <div class="text-center py-10">
                                <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-emerald-500 to-green-600 rounded-full mb-6 transform hover:scale-105 transition-all duration-300">
                                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <p class="text-blue-300 text-lg">لقد صوتت! انتظر باقي اللاعبين.</p>
                            </div>
                        @else
                            <!-- Voting Form -->
                            <form wire:submit="submitVote">
                                <div class="space-y-6">
                                    <label class="block text-sm font-medium text-blue-200 mb-4">
                                        اختر من تعتقد أنه المخادع:
                                    </label>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        @foreach($players as $p)
                                            {{-- Can only vote for other people who are ALIVE --}}
                                            @if($p->id !== $player->id && $p->isAlive())
                                                <label class="relative">
                                                    <input
                                                        type="radio"
                                                        wire:model="voteTargetId"
                                                        value="{{ $p->id }}"
                                                        class="sr-only peer"
                                                    >
                                                    <div class="p-5 bg-gradient-to-br from-blue-800/40 to-blue-900/40 border-2 border-blue-700/30 rounded-2xl cursor-pointer hover:border-blue-500 peer-checked:border-blue-500 peer-checked:bg-gradient-to-br from-blue-700/40 to-blue-800/40 transition-all duration-300 transform hover:scale-105">
                                                        <div class="flex items-center space-x-4 space-x-reverse">
                                                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-600 to-blue-700 flex items-center justify-center shadow-lg">
                                                                <span class="text-blue-200 font-bold">
                                                                    {{ substr($p->name, 0, 1) }}
                                                                </span>
                                                            </div>
                                                            <div>
                                                                <p class="font-bold text-white">{{ $p->name }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </label>
                                            @endif
                                        @endforeach
                                    </div>

                                    @error('voteTargetId')
                                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                                    @enderror

                                    <button
                                        type="submit"
                                        class="relative overflow-hidden group w-full bg-gradient-to-r from-emerald-500 to-green-600
                                               hover:from-emerald-600 hover:to-green-700 text-white font-bold py-4 px-4
                                               rounded-2xl transition-all duration-300 transform hover:scale-105 active:scale-95
                                               shadow-lg hover:shadow-xl"
                                    >
                                        <span class="relative z-10 text-lg">تأكيد التصويت</span>
                                        <div class="absolute inset-0 h-full w-full transform scale-0 group-hover:scale-100
                                                  transition-transform duration-300 ease-out bg-gradient-to-r
                                                  from-emerald-600 to-green-700 -z-1"></div>
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                @endif

                @if($room->status === 'results')
                    <!-- Results -->
                    <div
                        class="bg-gradient-to-br from-blue-900/80 to-blue-800/80 backdrop-blur-lg rounded-3xl p-8 shadow-2xl border border-blue-700/30"
                        x-data="{
                            countdown: 5,
                            interval: null,
                            gameStatus: '{{ $gameStatus ?? '' }}',
                            init() {
                                console.log('Results view initialized, gameStatus:', this.gameStatus);
                                // Only auto-transition if game is ongoing
                                if (this.gameStatus === 'ongoing') {
                                    console.log('Starting auto-transition countdown');
                                    this.interval = setInterval(() => {
                                        this.countdown--;
                                        console.log('Countdown:', this.countdown);
                                        if (this.countdown <= 0) {
                                            clearInterval(this.interval);
                                            console.log('Calling startNextTurn');
                                            $wire.call('startNextTurn');
                                        }
                                    }, 1000);
                                } else {
                                    console.log('Game is finished, no auto-transition');
                                }
                            },
                            destroy() {
                                if (this.interval) {
                                    console.log('Clearing interval');
                                    clearInterval(this.interval);
                                }
                            }
                        }"
                        x-init="init()"
                    >
                        <div class="flex items-center justify-between mb-8">
                            <h3 class="text-2xl font-bold text-blue-100">
                                {{ $gameStatus === 'finished' ? 'النتائج النهائية' : 'نتائج التصويت' }}
                            </h3>
                            @if($gameStatus === 'ongoing')
                                <div class="flex items-center gap-2 px-4 py-2 bg-blue-800/50 rounded-xl border border-blue-600/30">
                                    <svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-white font-bold" x-text="countdown"></span>
                                </div>
                            @endif
                        </div>

                        <div class="space-y-8">
                            <!-- Eliminated Player - Big Announcement -->
                            @if(isset($roundResults['eliminated_player']))
                                <div class="relative overflow-hidden bg-gradient-to-br from-red-900/90 to-red-800/90 backdrop-blur-lg rounded-3xl p-12 text-center border-4 {{ $roundResults['eliminated_player']['is_imposter'] && $gameStatus === 'finished' ? 'border-emerald-500' : 'border-red-500' }} animate-bounce-in">
                                    <div class="absolute inset-0 bg-gradient-to-r {{ $roundResults['eliminated_player']['is_imposter'] && $gameStatus === 'finished' ? 'from-emerald-500/10 to-green-500/10' : 'from-red-500/10 to-red-600/10' }} blur-2xl"></div>

                                    <div class="relative">
                                        <p class="text-lg text-red-200 mb-4">تم إقصاء</p>

                                        <!-- Kicked Player Avatar & Name -->
                                        <div class="flex flex-col items-center gap-4 mb-6">
                                            <div class="w-24 h-24 rounded-full bg-gradient-to-br from-red-700 to-red-900 flex items-center justify-center shadow-2xl border-4 border-red-500">
                                                <span class="text-white text-4xl font-bold">
                                                    {{ mb_substr($roundResults['eliminated_player']['name'], 0, 1) }}
                                                </span>
                                            </div>
                                            <p class="text-4xl font-black text-white">
                                                {{ $roundResults['eliminated_player']['name'] }}
                                            </p>
                                        </div>

                                        @if($gameStatus === 'finished')
                                            @if($roundResults['eliminated_player']['is_imposter'])
                                                <div class="p-6 bg-gradient-to-r from-emerald-600/30 to-green-600/30 rounded-2xl border-2 border-emerald-500 mb-4">
                                                    <p class="text-3xl font-bold text-emerald-300 mb-2">🎯 كان هو المخادع!</p>
                                                    <p class="text-xl text-emerald-200">تم القبض عليه!</p>
                                                </div>
                                            @else
                                                <div class="p-6 bg-gradient-to-r from-red-600/30 to-red-700/30 rounded-2xl border-2 border-red-500 mb-4">
                                                    <p class="text-3xl font-bold text-red-300 mb-2">❌ لم يكن المخادع</p>
                                                    <p class="text-xl text-red-200">تم إقصاء بريء!</p>
                                                </div>
                                            @endif
                                        @else
                                            <div class="p-6 bg-red-800/50 rounded-2xl border-2 border-red-600">
                                                <p class="text-2xl font-bold text-red-200 mb-2">❌ لم يكن المخادع</p>
                                                <p class="text-lg text-red-300">اللعبة مستمرة...</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @elseif($gameStatus === 'ongoing')
                                <div class="bg-gradient-to-br from-yellow-900/80 to-yellow-800/80 backdrop-blur-lg rounded-3xl p-12 text-center border border-yellow-700/30 animate-bounce-in">
                                    <div class="w-20 h-20 bg-yellow-700/50 rounded-full flex items-center justify-center mx-auto mb-6">
                                        <svg class="w-10 h-10 text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <p class="text-2xl font-bold text-yellow-100 mb-2">تعادل في الأصوات!</p>
                                    <p class="text-lg text-yellow-200">لم يتم إقصاء أحد</p>
                                </div>
                            @endif

                            @if($gameStatus === 'finished')
                                <!-- Secret Word Reveal -->
                                <div class="bg-gradient-to-br from-blue-800/40 to-blue-900/40 rounded-3xl p-8 text-center border border-blue-700/20">
                                    <p class="text-sm text-blue-300 mb-4">الكلمة السرية كانت:</p>
                                    <p class="text-4xl font-bold text-white">{{ $room->current_word }}</p>
                                </div>

                                <!-- Winner Announcement -->
                                <div class="relative overflow-hidden p-12 rounded-3xl text-center border-4 {{ $winner === 'crewmates' ? 'bg-gradient-to-br from-emerald-900/90 to-green-800/90 border-emerald-500' : 'bg-gradient-to-br from-red-900/90 to-red-800/90 border-red-500' }}">
                                    <div class="absolute inset-0 {{ $winner === 'crewmates' ? 'bg-gradient-to-r from-emerald-500/20 to-green-500/20' : 'bg-gradient-to-r from-red-500/20 to-red-600/20' }} blur-3xl"></div>
                                    <div class="relative">
                                        <div class="text-6xl mb-4">{{ $winner === 'crewmates' ? '👥' : '🎭' }}</div>
                                        <h4 class="text-5xl font-black {{ $winner === 'crewmates' ? 'text-emerald-200' : 'text-red-200' }} mb-2">
                                            {{ $winner === 'crewmates' ? 'فاز المواطنون!' : 'فاز المخادع!' }}
                                        </h4>
                                        <p class="text-xl {{ $winner === 'crewmates' ? 'text-emerald-300' : 'text-red-300' }}">
                                            {{ $winner === 'crewmates' ? 'تم القبض على المخادع' : 'لم يتم اكتشاف المخادع' }}
                                        </p>
                                    </div>
                                </div>
                            @else
                                <!-- Auto-transition message -->
                                <div class="bg-blue-800/30 rounded-2xl p-6 text-center border border-blue-600/30">
                                    <p class="text-blue-200 text-lg">
                                        الجولة التالية تبدأ تلقائياً خلال <span class="font-bold text-white" x-text="countdown"></span> ثوانٍ...
                                    </p>
                                </div>
                            @endif

                            <!-- Votes Summary -->
                            <div>
                                <h4 class="font-bold text-blue-100 mb-6 text-xl">📊 تفاصيل التصويت:</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    @foreach($votes as $vote)
                                        <div class="flex items-center justify-between p-4 bg-gradient-to-br from-blue-800/40 to-blue-900/40 rounded-xl border border-blue-700/20">
                                            <span class="text-white font-semibold">{{ $vote->voter->name }}</span>
                                            <span class="text-blue-400 mx-3">→</span>
                                            <span class="text-white font-semibold">{{ $vote->targetPlayer->name }}</span>
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

    <!-- WebSocket Connection Status -->
    <div class="fixed bottom-6 left-6" x-data="{ connected: false }" x-init="
        setTimeout(() => {
            if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
                const connection = window.Echo.connector.pusher.connection;

                // Set initial state
                connected = connection.state === 'connected';

                // Listen for state changes
                connection.bind('state_change', (states) => {
                    console.log('Pusher state changed:', states);
                    connected = states.current === 'connected';
                });

                connection.bind('error', (err) => {
                    console.error('Pusher connection error:', err);
                });

                console.log('Initial Pusher state:', connection.state);
            } else {
                console.error('Echo not initialized properly');
            }
        }, 100);
    ">
        <div class="flex items-center space-x-3 text-sm text-blue-300">
            <div
                class="w-3 h-3 rounded-full shadow-lg transition-all"
                :class="connected ? 'bg-emerald-500 animate-pulse' : 'bg-red-500'"
            ></div>
            <span class="font-medium" x-text="connected ? 'متصل' : 'غير متصل'"></span>
        </div>
    </div>
</div>