@props([
    'url' => '#',
    'align' => 'center',
    'color' => '#2563eb',
])
{{-- Bulletproof CTA button. A full-width row with an aligned cell reliably
     centers the inline-block button across every email client + the browser.
     <x-emails.button :url="..." align="center">Label</x-emails.button> --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:26px 0;">
    <tr>
        <td align="{{ $align }}">
            <a href="{{ $url }}" class="em-btn" style="display:inline-block; background:{{ $color }}; color:#ffffff; font-family:'Inter',-apple-system,Helvetica,Arial,sans-serif; font-size:15px; font-weight:600; line-height:1; text-decoration:none; padding:15px 30px; border-radius:10px;">{{ $slot }}</a>
        </td>
    </tr>
</table>
