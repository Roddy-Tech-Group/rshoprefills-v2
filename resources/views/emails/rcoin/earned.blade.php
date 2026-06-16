@php
    $isReferral = $kind === 'referral';
    $headline = $isReferral
        ? 'You earned a referral bonus!'
        : 'You earned cashback!';
    $intro = $isReferral
        ? "Hi {$recipient->name}, ".($referredName ?: 'one of your referrals')." just completed an order - and you've been credited for sending them our way."
        : "Hi {$recipient->name}, your purchase earned you Rcoin cashback. It's already in your wallet and ready to spend.";
@endphp
<x-emails.layout :mail-message="$message ?? null" :title="$headline" preheader="Rcoin credited to your wallet.">
    <h1 style="margin:0 0 14px; font-size:22px; line-height:1.3; font-weight:800; color:#0c1a2e;">{{ $headline }}</h1>

    <p style="margin:0 0 16px; font-size:16px; line-height:1.65; color:#3f3f46;">{{ $intro }}</p>

    <x-emails.panel title="{{ $isReferral ? 'Referral bonus' : 'Cashback reward' }}">
        <x-emails.row label="{{ $isReferral ? 'Bonus earned' : 'Cashback earned' }}" value="+{{ number_format($rcoinAmount) }} Rcoin" />
        <x-emails.row label="New balance" value="{{ number_format($newBalance) }} Rcoin" />
        @if ($orderNumber)
            <x-emails.row label="{{ $isReferral ? 'From order' : 'From order' }}" value="#{{ $orderNumber }}" :strong="false" />
        @endif
    </x-emails.panel>

    <p style="margin:0 0 22px; font-size:14px; line-height:1.6; color:#52525b;">
        Use your Rcoin at checkout to pay for any product - gift cards, eSIMs, mobile top-ups, even flights. The more you shop, the more you earn.
    </p>

    <x-emails.button :url="url('/dashboard/rewards')" align="center">View my Rcoin</x-emails.button>
</x-emails.layout>
