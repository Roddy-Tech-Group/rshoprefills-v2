<x-mail::message>
# New Trade Submitted

A user has just submitted a new gift card trade.

**User:** {{ $trade->user->name }} ({{ $trade->user->email }})  
**Card Brand:** {{ $trade->rate->brand->name ?? 'Unknown' }}  
**Declared Value:** ${{ number_format($trade->declared_value, 2) }}  
**Expected Payout:** {{ \App\Domain\Shared\Enums\Currency::tryFrom($trade->payout_currency)?->symbol() ?? $trade->payout_currency . ' ' }}{{ number_format($trade->calculated_payout, 2) }}  

<x-mail::button :url="url('/admin/gift-cards/trades/'.$trade->id)">
Review Trade Now
</x-mail::button>

Please review this trade as soon as possible to ensure a fast payout experience for the user.

Thanks,<br>
System Notification System
</x-mail::message>
