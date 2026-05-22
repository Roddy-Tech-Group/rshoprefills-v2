@component('mail::message')
# Security Alert: Transaction PIN Locked

Hi {{ $user->name }},

We noticed multiple failed attempts to verify your wallet transaction PIN. For your security, we have temporarily locked your transaction PIN for 15 minutes.

If this was you, you can try again after 15 minutes.

If you did not make these attempts, please reset your PIN immediately or contact support to secure your account.

@component('mail::button', ['url' => config('app.frontend_url') . '/wallet/pin/reset'])
Reset My PIN
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
