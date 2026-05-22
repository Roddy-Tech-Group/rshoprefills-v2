<x-emails.layout title="Wallet debit" preheader="A debit was made from your wallet. Here are the details.">
    <h1 style="margin:0 0 14px; font-size:22px; line-height:1.3; font-weight:800; color:#0c1a2e;">A debit was made from your wallet.</h1>

    <p style="margin:0 0 16px; font-size:16px; line-height:1.65; color:#3f3f46;">Hi {{ $name }}, this confirms that your wallet was debited. If this was not you, contact support straight away.</p>

    <x-emails.panel title="Transaction details">
        <x-emails.row label="Amount" value="{{ $currency }} {{ number_format($amount, 2) }}" />
        <x-emails.row label="For" value="{{ $description }}" />
        <x-emails.row label="Remaining balance" value="{{ $currency }} {{ number_format($balanceAfter, 2) }}" />
        <x-emails.row label="Reference" value="{{ $reference }}" :strong="false" />
    </x-emails.panel>

    <x-emails.button :url="url('/dashboard/transactions')" align="center">View transactions</x-emails.button>
</x-emails.layout>
