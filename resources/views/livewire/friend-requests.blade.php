<div class="container mx-auto max-w-4xl px-4 py-8">
    <flux:heading size="xl" class="mb-6">طلبات الصداقة</flux:heading>

    <div class="mb-8">
        <flux:heading size="lg" class="mb-4">الطلبات الواردة</flux:heading>

        @if($pendingRequests->count() > 0)
            <div class="space-y-3">
                @foreach($pendingRequests as $request)
                    <div class="flex items-center justify-between rounded-lg bg-white/10 p-4 backdrop-blur-sm">
                        <div class="flex items-center gap-4">
                            <flux:avatar :initials="$request->user->initials()" />
                            <div>
                                <flux:heading size="sm">{{ $request->user->name }}</flux:heading>
                                <flux:text class="text-sm opacity-70">{{ $request->user->email }}</flux:text>
                                <flux:text class="text-xs opacity-50">
                                    {{ $request->created_at->diffForHumans() }}
                                </flux:text>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <flux:button
                                wire:click="acceptRequest({{ $request->id }})"
                                variant="primary"
                            >
                                قبول
                            </flux:button>

                            <flux:button
                                wire:click="declineRequest({{ $request->id }})"
                                variant="danger"
                            >
                                رفض
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-lg bg-white/10 p-12 text-center backdrop-blur-sm">
                <flux:text class="opacity-70">لا توجد طلبات صداقة واردة</flux:text>
            </div>
        @endif
    </div>

    <div>
        <flux:heading size="lg" class="mb-4">الطلبات المرسلة</flux:heading>

        @if($sentRequests->count() > 0)
            <div class="space-y-3">
                @foreach($sentRequests as $request)
                    <div class="flex items-center justify-between rounded-lg bg-white/10 p-4 backdrop-blur-sm">
                        <div class="flex items-center gap-4">
                            <flux:avatar :initials="$request->friend->initials()" />
                            <div>
                                <flux:heading size="sm">{{ $request->friend->name }}</flux:heading>
                                <flux:text class="text-sm opacity-70">{{ $request->friend->email }}</flux:text>
                                <flux:text class="text-xs opacity-50">
                                    تم الإرسال {{ $request->created_at->diffForHumans() }}
                                </flux:text>
                            </div>
                        </div>

                        <flux:badge variant="warning">قيد الانتظار</flux:badge>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-lg bg-white/10 p-12 text-center backdrop-blur-sm">
                <flux:text class="opacity-70">لم ترسل أي طلبات صداقة</flux:text>
            </div>
        @endif
    </div>
</div>
