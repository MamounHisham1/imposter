<div class="container mx-auto max-w-4xl px-4 py-8">
    <div class="mb-6">
        <flux:heading size="xl" class="mb-4">الأصدقاء</flux:heading>

        <flux:input
            wire:model.live="searchTerm"
            type="text"
            placeholder="ابحث عن صديق..."
            class="w-full"
        />
    </div>

    @if($friends->count() > 0)
        <div class="space-y-3">
            @foreach($friends as $friend)
                <div class="flex items-center justify-between rounded-lg bg-white/10 p-4 backdrop-blur-sm">
                    <div class="flex items-center gap-4">
                        <flux:avatar :initials="$friend->initials()" />
                        <div>
                            <flux:heading size="sm">{{ $friend->name }}</flux:heading>
                            <flux:text class="text-sm opacity-70">{{ $friend->email }}</flux:text>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <flux:button
                            wire:navigate
                            :href="route('user.profile', $friend)"
                            variant="ghost"
                        >
                            عرض الملف الشخصي
                        </flux:button>

                        <flux:button
                            wire:click="removeFriend({{ $friend->id }})"
                            wire:confirm="هل أنت متأكد من إزالة هذا الصديق؟"
                            variant="danger"
                        >
                            إزالة
                        </flux:button>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="rounded-lg bg-white/10 p-12 text-center backdrop-blur-sm">
            <flux:text class="text-lg opacity-70">
                @if($searchTerm)
                    لم يتم العثور على أصدقاء يطابقون بحثك
                @else
                    ليس لديك أصدقاء بعد
                @endif
            </flux:text>
        </div>
    @endif
</div>
