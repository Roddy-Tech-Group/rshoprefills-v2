<x-mail::message>
# Hello {{ $trade->user->name }},

The status of your gift card trade **#{{ substr($trade->uuid, 0, 8) }}** has been updated to: **{{ $trade->status->label() }}**.

@if($trade->status === \App\Enums\TradeStatus::Approved)
Your gift card has been verified successfully. We are now processing your payout to your selected payment method!
@endif

@if($reason)
<x-mail::panel>
**Admin Note:** {{ $reason }}
</x-mail::panel>
@endif

<x-mail::button :url="url('/dashboard/gift-cards/trades/'.$trade->id)">
View Trade Details
</x-mail::button>

Thank you for trading with us,<br>
{{ config('app.name') }}
</x-mail::message>
