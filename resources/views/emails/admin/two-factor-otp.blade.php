<x-mail::message>
# Hello {{ $admin->name }},

Your admin login code is:

<x-mail::panel>
<div style="text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 4px;">
{{ $otp }}
</div>
</x-mail::panel>

This code will expire in 10 minutes.

If you did not attempt to log in to the admin panel, please ignore this email or contact another administrator.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
