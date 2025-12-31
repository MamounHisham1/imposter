<div class="container mx-auto max-w-6xl px-4 py-8">
    <div class="mb-8 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:avatar size="lg" :initials="$user->initials()" />
            <div>
                <flux:heading size="xl">{{ $user->name }}</flux:heading>
                <flux:subheading>{{ $user->email }}</flux:subheading>
            </div>
        </div>

        @if(!$isOwnProfile && auth()->check())
            <div>
                @if($isFriend)
                    <flux:badge variant="success">صديق</flux:badge>
                @elseif($hasPendingRequest)
                    <flux:badge variant="warning">طلب معلق</flux:badge>
                @else
                    <flux:button wire:click="sendFriendRequest" variant="primary">
                        إرسال طلب صداقة
                    </flux:button>
                @endif
            </div>
        @endif
    </div>

    <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg bg-white/10 p-6 backdrop-blur-sm">
            <flux:heading size="sm" class="mb-2">إجمالي الألعاب</flux:heading>
            <flux:text class="text-3xl font-bold">{{ $stats['total_games'] }}</flux:text>
        </div>

        <div class="rounded-lg bg-white/10 p-6 backdrop-blur-sm">
            <flux:heading size="sm" class="mb-2">الألعاب المكتسبة</flux:heading>
            <flux:text class="text-3xl font-bold">{{ $stats['games_won'] }}</flux:text>
        </div>

        <div class="rounded-lg bg-white/10 p-6 backdrop-blur-sm">
            <flux:heading size="sm" class="mb-2">نسبة الفوز</flux:heading>
            <flux:text class="text-3xl font-bold">{{ $stats['win_rate'] }}%</flux:text>
        </div>

        <div class="rounded-lg bg-white/10 p-6 backdrop-blur-sm">
            <flux:heading size="sm" class="mb-2">إجمالي النقاط</flux:heading>
            <flux:text class="text-3xl font-bold">{{ $stats['total_score'] }}</flux:text>
        </div>
    </div>

    <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-lg bg-white/10 p-6 backdrop-blur-sm">
            <flux:heading size="sm" class="mb-2">ألعاب كمخادع</flux:heading>
            <flux:text class="text-2xl font-bold">{{ $stats['games_as_imposter'] }}</flux:text>
        </div>

        <div class="rounded-lg bg-white/10 p-6 backdrop-blur-sm">
            <flux:heading size="sm" class="mb-2">انتصارات المخادع</flux:heading>
            <flux:text class="text-2xl font-bold">{{ $stats['imposter_wins'] }}</flux:text>
        </div>

        <div class="rounded-lg bg-white/10 p-6 backdrop-blur-sm">
            <flux:heading size="sm" class="mb-2">مرات الإقصاء</flux:heading>
            <flux:text class="text-2xl font-bold">{{ $stats['times_eliminated'] }}</flux:text>
        </div>
    </div>

    <div class="rounded-lg bg-white/10 p-6 backdrop-blur-sm">
        <flux:heading size="lg" class="mb-6">سجل الألعاب</flux:heading>

        @if($gameHistories->count() > 0)
            <div class="space-y-4">
                @foreach($gameHistories as $history)
                    <div class="flex items-center justify-between rounded-lg bg-white/5 p-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <flux:badge variant="{{ $history->won ? 'success' : 'danger' }}">
                                    {{ $history->won ? 'فوز' : 'خسارة' }}
                                </flux:badge>

                                @if($history->was_imposter)
                                    <flux:badge variant="warning">مخادع</flux:badge>
                                @endif

                                @if($history->eliminated)
                                    <flux:badge variant="danger">تم الإقصاء</flux:badge>
                                @endif
                            </div>

                            <flux:text class="mt-2">
                                الغرفة: {{ $history->room->code ?? 'غير متوفر' }}
                            </flux:text>

                            <flux:text class="text-sm opacity-70">
                                {{ $history->game_completed_at->diffForHumans() }}
                            </flux:text>
                        </div>

                        <div class="text-left">
                            <flux:text class="text-2xl font-bold">{{ $history->score }}</flux:text>
                            <flux:text class="text-sm opacity-70">نقطة</flux:text>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $gameHistories->links() }}
            </div>
        @else
            <div class="py-12 text-center">
                <flux:text class="text-lg opacity-70">
                    لا يوجد سجل ألعاب بعد
                </flux:text>
            </div>
        @endif
    </div>
</div>
