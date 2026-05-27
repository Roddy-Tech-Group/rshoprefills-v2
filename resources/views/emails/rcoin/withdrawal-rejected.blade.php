@php
    $user = $withdrawal->user;
@endphp
<x-emails.layout title="Your Rcoin withdrawal was not approved" preheader="Your Rcoin has been credited back to your wallet.">
    <h1 style="margin:0 0 14px; font-size:22px; line-height:1.3; font-weight:800; color:#0c1a2e;">Your Rcoin withdrawal was not approved.</h1>

    <p style="margin:0 0 16px; font-size:16px; line-height:1.65; color:#3f3f46;">
        Hi {{ $user->name }}, we weren't able to process your withdrawal this time. The full Rcoin balance has been returned to your wallet - nothing was lost.
    </p>

    @if ($withdrawal->reject_reason)
        <x-emails.panel title="Reason from our review team" background="#fff5f5" border="#fecaca" accent="#dc2626">
            <p style="margin:0; font-size:14px; line-height:1.55; color:#7f1d1d;">{{ $withdrawal->reject_reason }}</p>
        </x-emails.panel>
    @endif

    <x-emails.panel title="Request #W{{ str_pad((string) $withdrawal->id, 6, '0', STR_PAD_LEFT) }}">
        <x-emails.row label="Rcoin refunded" value="{{ number_format($withdrawal->rcoin_amount) }} Rcoin" />
        <x-emails.row label="Submitted" value="{{ $withdrawal->created_at->format('M j, Y · g:i A') }}" :strong="false" />
    </x-emails.panel>

    <p style="margin:0 0 22px; font-size:14px; line-height:1.6; color:#52525b;">
        You can re-submit the request once the issue above is sorted. If you think this was a mistake, just reply to this email.
    </p>

    <x-emails.button :url="url('/dashboard/rewards')" align="center">View my Rcoin</x-emails.button>
</x-emails.layout>
