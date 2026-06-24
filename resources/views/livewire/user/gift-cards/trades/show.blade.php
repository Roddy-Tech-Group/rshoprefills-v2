<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\GiftCardTrade;
use App\Enums\TradeStatus;

new
#[Layout('components.layouts.dashboard')]
#[Title('Trade Details')]
class extends Component {
    public GiftCardTrade $trade;
    public string $new_message = '';

    public function mount(GiftCardTrade $trade)
    {
        // Ensure user can only view their own trade
        if ($trade->user_id !== auth()->id()) {
            abort(403);
        }
        $this->trade = $trade->load(['rate.brand', 'media', 'messages.sender', 'bankAccount']);
    }

    public function sendMessage()
    {
        $this->validate([
            'new_message' => 'required|string|max:1000',
        ]);

        $this->trade->messages()->create([
            'sender_type' => \App\Models\User::class,
            'sender_id' => auth()->id(),
            'message' => $this->new_message,
        ]);

        $this->new_message = '';
        $this->trade->refresh();
        $this->trade->load(['messages.sender']);
    }

    public function refreshData()
    {
        $this->trade->refresh();
        $this->trade->load(['messages.sender']);
    }
}; ?>

<div wire:poll.5s="refreshData" class="mx-auto max-w-5xl space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                    Trade #{{ substr($trade->uuid, 0, 8) }}
                </h1>
                <flux:badge color="{{ $trade->status->color() }}" class="uppercase">{{ $trade->status->label() }}</flux:badge>
            </div>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Submitted on {{ $trade->created_at->format('M j, Y g:i A') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            @if($trade->status === TradeStatus::Paid)
                <flux:button href="{{ route('dashboard.gift-cards.trades.receipt', $trade) }}" variant="primary" icon="document-text">View Receipt</flux:button>
            @endif
            <flux:button href="{{ route('dashboard.gift-cards.history') }}" variant="subtle" icon="arrow-left">Back to History</flux:button>
        </div>
    </div>

    @if($trade->status === TradeStatus::NeedMoreInfo)
        <div class="rounded-xl border border-orange-200 bg-orange-50 p-4 dark:border-orange-500/20 dark:bg-orange-500/10">
            <div class="flex items-start gap-3">
                <flux:icon.exclamation-triangle class="mt-0.5 size-5 text-orange-500" />
                <div>
                    <h3 class="text-sm font-semibold text-orange-800 dark:text-orange-400">Action Required</h3>
                    <p class="mt-1 text-sm text-orange-700 dark:text-orange-300">
                        The admin has requested more information regarding your trade. Please review the messages below and reply.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- Left Column: Details --}}
        <div class="lg:col-span-2 space-y-6">
            
            {{-- Card Details --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Gift Card Details</h2>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-6">
                    <div>
                        <div class="text-sm font-medium text-zinc-500">Brand & Region</div>
                        <div class="mt-1 text-base text-zinc-900 dark:text-white font-medium">
                            {{ $trade->rate->brand->name ?? 'Unknown' }} ({{ $trade->rate->country_code }})
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-zinc-500">Declared Value</div>
                        <div class="mt-1 text-base text-zinc-900 dark:text-white font-medium">
                            ${{ number_format($trade->declared_value, 2) }}
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <div class="text-sm font-medium text-zinc-500">Claim Code / PIN</div>
                        <div class="mt-1 p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg font-mono text-lg text-zinc-900 dark:text-white tracking-widest break-all">
                            {{ $trade->code_pin }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Payout Details --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Payout Information</h2>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-6">
                    <div>
                        <div class="text-sm font-medium text-zinc-500">Expected Payout</div>
                        <div class="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ \App\Domain\Shared\Enums\Currency::tryFrom($trade->payout_currency)?->symbol() ?? $trade->payout_currency . ' ' }}{{ number_format($trade->calculated_payout, 2) }}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-zinc-500">Payout Method</div>
                        <div class="mt-1">
                            @if($trade->payout_method === 'wallet')
                                <flux:badge color="purple" icon="wallet">Rshop Wallet</flux:badge>
                            @else
                                <flux:badge color="blue" icon="building-library">Bank Transfer</flux:badge>
                            @endif
                        </div>
                    </div>
                    
                    @if($trade->payout_method === 'bank' && $trade->bankAccount)
                        <div class="sm:col-span-2 mt-2 p-4 rounded-lg border border-zinc-100 bg-zinc-50 dark:bg-zinc-800 dark:border-zinc-700">
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-3">Bank Account Details</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <div class="text-xs text-zinc-500">Bank Name</div>
                                    <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $trade->bankAccount->bank_name }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-zinc-500">Account Number</div>
                                    <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $trade->bankAccount->account_number }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-zinc-500">Account Name</div>
                                    <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $trade->bankAccount->account_name }}</div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            
            {{-- Uploaded Images --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Uploaded Images</h2>
                
                @if($trade->media->count() > 0)
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        @foreach($trade->media as $media)
                            <a href="{{ Storage::url($media->file_path) }}" target="_blank" class="block group relative aspect-[4/3] rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-800">
                                <img src="{{ Storage::url($media->file_path) }}" alt="Card Image" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition duration-300 flex items-center justify-center">
                                    <flux:icon.magnifying-glass-plus class="text-white opacity-0 group-hover:opacity-100 size-6" />
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-zinc-500 italic">No images were uploaded for this trade.</p>
                @endif
            </div>

        </div>

        {{-- Right Column: Chat --}}
        <div class="space-y-6">
            
            {{-- Chat / Communication --}}
            <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-white/10 dark:bg-zinc-900 flex flex-col h-[600px]">
                <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-white flex items-center gap-2">
                        <flux:icon.chat-bubble-left-right class="size-5 text-zinc-500" /> Trade Support
                    </h2>
                </div>
                
                <div class="flex-1 p-4 overflow-y-auto bg-zinc-50 dark:bg-zinc-900/50 flex flex-col gap-4">
                    @forelse($trade->messages as $msg)
                        @php
                            $isUser = $msg->sender_type === \App\Models\User::class;
                        @endphp
                        <div class="flex flex-col {{ $isUser ? 'items-end' : 'items-start' }}">
                            <div class="text-[10px] text-zinc-400 mb-1 px-1">
                                {{ $isUser ? 'You' : 'Admin Support' }} • {{ $msg->created_at->format('g:i A') }}
                            </div>
                            <div class="px-4 py-2 rounded-2xl max-w-[85%] text-sm {{ $isUser ? 'bg-blue-600 text-white rounded-br-none' : 'bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-900 dark:text-zinc-100 rounded-bl-none shadow-sm' }}">
                                {{ $msg->message }}
                            </div>
                        </div>
                    @empty
                        <div class="flex-1 flex flex-col items-center justify-center text-center my-auto opacity-50">
                            <flux:icon.chat-bubble-oval-left-ellipsis class="size-10 text-zinc-400 mb-2" />
                            <p class="text-sm text-zinc-500">No messages yet.</p>
                            <p class="text-xs text-zinc-400 mt-1">If there is an issue, an admin will contact you here.</p>
                        </div>
                    @endforelse
                </div>
                
                <form wire:submit="sendMessage" class="p-4 border-t border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                    <div class="flex gap-2">
                        <flux:input wire:model="new_message" class="flex-1" placeholder="Type a message..." required />
                        <flux:button type="submit" variant="primary">Send</flux:button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
