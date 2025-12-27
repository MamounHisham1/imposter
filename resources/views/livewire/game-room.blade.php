<div dir="rtl" x-data="{
    init() {
        // Initialize SSE connection with reconnection logic
        let eventSource = null;
        let reconnectAttempts = 0;
        const maxReconnectAttempts = 10;
        const reconnectDelay = 3000; // 3 seconds

        const connectSSE = () => {
            if (eventSource) {
                eventSource.close();
            }

            eventSource = new EventSource('{{ route('sse.stream', ['room' => $room->code]) }}');

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

            eventSource.addEventListener('message_sent', (e) => {
                const data = JSON.parse(e.data);
                Livewire.dispatch('message-sent', data);
            });

            // Handle connection errors and reconnection
            eventSource.onerror = (e) => {
                console.log('SSE connection error, attempting reconnection...');
                eventSource.close();

                if (reconnectAttempts < maxReconnectAttempts) {
                    reconnectAttempts++;
                    setTimeout(connectSSE, reconnectDelay);
                } else {
                    console.error('Max reconnection attempts reached');
                }
            };

            // Reset reconnect attempts on successful connection
            eventSource.onopen = () => {
                reconnectAttempts = 0;
            };
        };

        // Initial connection
        connectSSE();

        // Cleanup on component unmount
        this.$wire.on('destroy', () => {
            if (eventSource) {
                eventSource.close();
            }
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
                        <button
                            wire:click="startGame"
                            class="relative overflow-hidden group bg-gradient-to-r from-emerald-500 to-green-600
                                   hover:from-emerald-600 hover:to-green-700 text-white font-bold py-4 px-8
                                   rounded-2xl transition-all duration-300 transform hover:scale-105 active:scale-95
                                   shadow-lg hover:shadow-xl"
                        >
                            <span class="relative z-10 text-lg">بدء اللعبة</span>
                            <div class="absolute inset-0 h-full w-full transform scale-0 group-hover:scale-100
                                      transition-transform duration-300 ease-out bg-gradient-to-r
                                      from-emerald-600 to-green-700 -z-1"></div>
                        </button>
                    @elseif($room->status === 'waiting' && $room->canStartGame() && $player && $room->creator_id !== $player->id)
                        <div class="text-sm text-blue-300">
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
                @if($room->status === 'hints')
                    <!-- Hint Submission -->
                    <div class="bg-gradient-to-br from-blue-900/80 to-blue-800/80 backdrop-blur-lg rounded-3xl p-8 shadow-2xl border border-blue-700/30">
                        <h3 class="text-2xl font-bold text-blue-100 mb-6">التلميح الخاص بك</h3>

                        @if($isEliminated)
                             <div class="text-center py-10 text-blue-300">
                                لقد تم إقصاؤك، لا يمكنك تقديم تلميحات.
                            </div>
                        @elseif($hasSubmittedHint)
                            <div class="text-center py-10">
                                <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-emerald-500 to-green-600 rounded-full mb-6 transform hover:scale-105 transition-all duration-300">
                                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <p class="text-blue-300 text-lg">لقد قدمت تلميحك! انتظر باقي اللاعبين.</p>
                            </div>
                        @else
                            <form wire:submit="submitHint">
                                <div class="space-y-6">
                                    <div>
                                        <label class="block text-sm font-medium text-blue-200 mb-4">
                                            اكتب تلميحًا واحدًا أو جملة قصيرة (3 كلمات كحد أقصى)
                                        </label>
                                        <textarea
                                            wire:model="hintText"
                                            rows="4"
                                            class="w-full px-5 py-4 bg-blue-800/30 border border-blue-600/30 rounded-2xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-blue-400 transition-all duration-300"
                                            placeholder="مثال: حيوان أليف"
                                            required
                                        ></textarea>
                                        @error('hintText')
                                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <button
                                        type="submit"
                                        class="relative overflow-hidden group w-full bg-gradient-to-r from-emerald-500 to-green-600
                                               hover:from-emerald-600 hover:to-green-700 text-white font-bold py-4 px-4
                                               rounded-2xl transition-all duration-300 transform hover:scale-105 active:scale-95
                                               shadow-lg hover:shadow-xl"
                                    >
                                        <span class="relative z-10 text-lg">إرسال التلميح</span>
                                        <div class="absolute inset-0 h-full w-full transform scale-0 group-hover:scale-100
                                                  transition-transform duration-300 ease-out bg-gradient-to-r
                                                  from-emerald-600 to-green-700 -z-1"></div>
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                @endif

                @if($room->status === 'discussion')
                    <!-- Discussion Chat -->
                    <div class="bg-gradient-to-br from-blue-900/80 to-blue-800/80 backdrop-blur-lg rounded-3xl p-8 shadow-2xl border border-blue-700/30">
                        <h3 class="text-2xl font-bold text-blue-100 mb-6">النقاش</h3>

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
                                class="mb-8 h-96 overflow-y-auto bg-blue-900/30 rounded-2xl p-6 space-y-4 border border-blue-700/20"
                                x-data="{
                                    scrollToBottom() {
                                        const container = this.$el;
                                        container.scrollTop = container.scrollHeight;
                                    }
                                }"
                                x-init="scrollToBottom()"
                                x-on:message-sent-event.window="scrollToBottom()"
                            >
                                @forelse($messages as $message)
                                    <div class="flex flex-col {{ $message['player_id'] === $player->id ? 'items-end' : 'items-start' }}">
                                        <div class="flex items-center mb-2">
                                            <span class="text-sm font-medium {{ $message['player_id'] === $player->id ? 'text-blue-200' : 'text-blue-300' }}">
                                                {{ $message['player_name'] }}
                                            </span>
                                            <span class="text-xs text-blue-400 mx-3">
                                                {{ \Carbon\Carbon::parse($message['timestamp'])->format('H:i') }}
                                            </span>
                                        </div>
                                        <div class="max-w-xs md:max-w-md px-5 py-3 rounded-2xl {{ $message['player_id'] === $player->id ? 'bg-gradient-to-br from-blue-600 to-blue-700 text-white' : 'bg-gradient-to-br from-blue-800/40 to-blue-900/40 text-blue-100' }} border {{ $message['player_id'] === $player->id ? 'border-blue-600/30' : 'border-blue-700/20' }}">
                                            <p class="text-sm">{{ $message['message'] }}</p>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-10 text-blue-300">
                                        لا توجد رسائل بعد. ابدأ النقاش!
                                    </div>
                                @endforelse
                            </div>

                            <!-- Chat Input -->
                            <form wire:submit="submitMessage">
                                <div class="flex gap-3">
                                    <input
                                        type="text"
                                        wire:model="chatMessage"
                                        class="flex-1 px-5 py-4 bg-blue-800/30 border border-blue-600/30 rounded-2xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-white placeholder-blue-400 transition-all duration-300"
                                        placeholder="اكتب رسالتك هنا..."
                                        required
                                        @if($timeRemaining !== null && $timeRemaining <= 0) disabled @endif
                                    />
                                    <button
                                        type="submit"
                                        class="relative overflow-hidden group bg-gradient-to-r from-emerald-500 to-green-600
                                               hover:from-emerald-600 hover:to-green-700 text-white font-bold py-4 px-8
                                               rounded-2xl transition-all duration-300 transform hover:scale-105 active:scale-95
                                               shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
                                        @if($timeRemaining !== null && $timeRemaining <= 0) disabled @endif
                                    >
                                        <span class="relative z-10 text-lg">إرسال</span>
                                        <div class="absolute inset-0 h-full w-full transform scale-0 group-hover:scale-100
                                                  transition-transform duration-300 ease-out bg-gradient-to-r
                                                  from-emerald-600 to-green-700 -z-1"></div>
                                    </button>
                                </div>
                                @error('chatMessage')
                                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
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
                    <div class="bg-gradient-to-br from-blue-900/80 to-blue-800/80 backdrop-blur-lg rounded-3xl p-8 shadow-2xl border border-blue-700/30">
                        <h3 class="text-2xl font-bold text-blue-100 mb-8">
                            {{ $gameStatus === 'finished' ? 'النتائج النهائية' : 'نتائج التصويت' }}
                        </h3>

                        <div class="space-y-8">

                            <!-- Eliminated Player Info -->
                            @if(isset($roundResults['eliminated_player']))
                                <div class="bg-gradient-to-br from-red-900/80 to-red-800/80 backdrop-blur-lg rounded-3xl p-8 text-center border border-red-700/30">
                                    <p class="text-xl text-red-100 mb-4">
                                        تم إقصاء اللاعب: <span class="font-bold text-white">{{ $roundResults['eliminated_player']['name'] }}</span>
                                    </p>

                                    @if($gameStatus === 'finished')
                                        <p class="mt-4 text-2xl font-bold {{ $roundResults['eliminated_player']['is_imposter'] ? 'text-emerald-400' : 'text-red-400' }}">
                                            {{ $roundResults['eliminated_player']['is_imposter'] ? 'كان هو المخادع!' : 'لم يكن المخادع.' }}
                                        </p>
                                    @else
                                         <p class="mt-4 text-red-300 text-lg">
                                            لم يكن هو المخادع. اللعبة مستمرة...
                                        </p>
                                    @endif
                                </div>
                            @elseif($gameStatus === 'ongoing')
                                <div class="bg-gradient-to-br from-yellow-900/80 to-yellow-800/80 backdrop-blur-lg rounded-3xl p-8 text-center border border-yellow-700/30">
                                    <p class="text-xl text-yellow-100">
                                        لم يتم إقصاء أحد (تعادل في الأصوات).
                                    </p>
                                </div>
                            @endif

                            @if($gameStatus === 'finished')
                                <!-- Secret Word Reveal -->
                                <div class="bg-gradient-to-br from-blue-800/40 to-blue-900/40 rounded-3xl p-8 text-center border border-blue-700/20">
                                    <p class="text-sm text-blue-300 mb-4">الكلمة السرية كانت:</p>
                                    <p class="text-3xl font-bold text-white">{{ $room->current_word }}</p>
                                </div>

                                <!-- Winner Announcement -->
                                <div class="p-8 rounded-3xl text-center border {{ $winner === 'crewmates' ? 'bg-gradient-to-br from-emerald-900/80 to-green-800/80 border-emerald-700/30' : 'bg-gradient-to-br from-red-900/80 to-red-800/80 border-red-700/30' }}">
                                    <h4 class="text-3xl font-bold {{ $winner === 'crewmates' ? 'text-emerald-200' : 'text-red-200' }}">
                                        فاز {{ $winner === 'crewmates' ? 'المواطنون' : 'المخادع' }}!
                                    </h4>
                                </div>
                            @endif

                            <!-- Votes Summary -->
                            <div>
                                <h4 class="font-medium text-blue-200 mb-6">تفاصيل التصويت:</h4>
                                <div class="space-y-3">
                                    @foreach($votes as $vote)
                                        <div class="flex items-center justify-between p-5 bg-gradient-to-br from-blue-800/40 to-blue-900/40 rounded-2xl border border-blue-700/20">
                                            <span class="text-white font-medium">{{ $vote->voter->name }}</span>
                                            <span class="text-blue-400 mx-4">→</span>
                                            <span class="text-white font-medium">{{ $vote->targetPlayer->name }}</span>
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
    <div class="fixed bottom-6 left-6">
        <div class="flex items-center space-x-3 text-sm text-blue-300">
            <div class="w-3 h-3 bg-emerald-500 rounded-full animate-pulse shadow-lg"></div>
            <span class="font-medium">متصل</span>
        </div>
    </div>
</div>