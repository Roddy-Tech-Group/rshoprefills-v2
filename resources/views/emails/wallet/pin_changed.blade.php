@component('mail::message')
# Transaction PIN Changed

Hi {{ $user->name }},

This is a confirmation that your wallet transaction PIN has been successfully changed.

If you did not make this change, please contact support immediately to secure your account.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
