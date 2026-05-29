@props([
    'label' => '',
    'value' => null,
    'strong' => true,
])
{{-- Label/value line inside <x-emails.panel>. Value via prop or slot. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td style="padding:5px 0; font-family:'Inter',-apple-system,Helvetica,Arial,sans-serif; font-size:13px; color:#71717a; vertical-align:top;">{{ $label }}</td>
        <td align="right" style="padding:5px 0 5px 16px; font-family:'Inter',-apple-system,Helvetica,Arial,sans-serif; font-size:14px; font-weight:{{ $strong ? '700' : '400' }}; color:#18181b; vertical-align:top;">{{ $value ?? $slot }}</td>
    </tr>
</table>
