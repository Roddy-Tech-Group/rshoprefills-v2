<x-emails.layout :mail-message="$message ?? null" title="Wallet funded" preheader="Your wallet top-up was successful and is ready to spend.">
    <h1 style="margin:0 0 14px; font-size:22px; line-height:1.3; font-weight:800; color:#0c1a2e;">Your wallet has been funded.</h1>

    <p style="margin:0 0 16px; font-size:16px; line-height:1.65; color:#3f3f46;">Hi {{ $name }}, your wallet top-up was successful and the funds are ready to spend.</p>

    <x-emails.panel title="Funding details">
        <x-emails.row label="Amount added" value="{{ $currency }} {{ number_format($amount, 2) }}" />
        <x-emails.row label="New balance" value="{{ $currency }} {{ number_format($balanceAfter, 2) }}" />
        <x-emails.row label="Reference" value="{{ $reference }}" :strong="false" />
    </x-emails.panel>

    <x-emails.button :url="url('/dashboard/wallet')" align="center">View your wallet</x-emails.button>
</x-emails.layout>
