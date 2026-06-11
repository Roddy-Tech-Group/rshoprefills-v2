@php
    $user = $withdrawal->user;
    $headline = $paid
        ? 'Your Rcoin withdrawal has been paid'
        : 'Your Rcoin withdrawal has been approved';
    $methodLabel = match ($withdrawal->method) {
        'bank' => 'Bank transfer',
        'mobile_money' => 'Mobile money',
        default => 'Wallet',
    };
@endphp
<x-emails.layout :mail-message="$message ?? null" :title="$headline" preheader="Your Rcoin withdrawal update.">
    <h1 style="margin:0 0 14px; font-size:22px; line-height:1.3; font-weight:800; color:#0c1a2e;">{{ $headline }}</h1>

    <p style="margin:0 0 16px; font-size:16px; line-height:1.65; color:#3f3f46;">
        @if ($paid)
            Hi {{ $user->name }}, your withdrawal has been settled - the funds are on their way to your <strong>{{ strtolower($methodLabel) }}</strong>.
        @else
            Hi {{ $user->name }}, we've approved your withdrawal request. The payout will reach your <strong>{{ strtolower($methodLabel) }}</strong> within 24 hours.
        @endif
    </p>

    <x-emails.panel title="Withdrawal #W{{ str_pad((string) $withdrawal->id, 6, '0', STR_PAD_LEFT) }}">
        <x-emails.row label="Rcoin debited" value="{{ number_format($withdrawal->rcoin_amount) }} Rcoin" />
        <x-emails.row label="Payout" value="USD {{ number_format((float) $withdrawal->payout_usd, 2) }}" />
        @if ((float) $withdrawal->fee_usd > 0)
            <x-emails.row label="Processing fee" value="USD {{ number_format((float) $withdrawal->fee_usd, 2) }}" :strong="false" />
        @endif
        <x-emails.row label="Method" value="{{ $methodLabel }}" :strong="false" />
        @if ($paid && $withdrawal->payout_reference)
            <x-emails.row label="Payout reference" value="{{ $withdrawal->payout_reference }}" :strong="false" />
        @endif
    </x-emails.panel>

    @unless ($paid)
        <p style="margin:0 0 22px; font-size:14px; line-height:1.6; color:#52525b;">
            We'll send you another email the moment your funds are sent. If you don't see them within 24 hours, reply to this email and our team will look into it.
        </p>
    @endunless

    <x-emails.button :url="url('/dashboard/rewards')" align="center">View my Rcoin</x-emails.button>
</x-emails.layout>
