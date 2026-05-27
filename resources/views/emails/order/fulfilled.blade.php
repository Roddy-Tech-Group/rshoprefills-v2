<x-emails.layout title="Your gift card is ready" preheader="Your gift card and redemption code are ready to use.">
    @php
        $snap = $item->product_snapshot ?? [];
        $vsnap = $item->variant_snapshot ?? [];
        $brandKey = $snap['brand_key'] ?? null;
        $brandName = $brandKey ? \App\Models\Product::brandDisplayName($brandKey) : ($snap['name'] ?? 'Your item');
        $logo = \App\Models\Product::brandLogoUrl($brandKey, $snap['logo_url'] ?? null);

        $faceVal = $vsnap['face_value'] ?? null;
        $faceCur = $vsnap['currency'] ?? ($snap['currency_code'] ?? 'USD');
        $faceTxt = $faceVal !== null
            ? \App\Models\Product::currencySymbol($faceCur).rtrim(rtrim(number_format((float) $faceVal, 2), '0'), '.')
            : null;

        $cc = $snap['country_code'] ?? null;
        $countryNames = array_flip(config('countries.codes', [])); // ISO -> name
        $country = $cc ? ($countryNames[strtoupper($cc)] ?? $cc) : null;
        $flag = \App\Models\Product::flagUrl($cc);

        // Split the fulfillment payload into a redemption code and a PIN so both
        // show when present. Email clients strip JS, so there is no copy button
        // (the dashboard has one) — values use select-all for easy copying.
        $payload = (array) ($item->fulfillment_payload ?? []);
        $cardPin = (! empty($payload['pin']) && is_scalar($payload['pin'])) ? (string) $payload['pin'] : null;
        $cardCode = null;
        foreach (['code', 'voucher_code', 'redeem_code', 'card_number', 'serial'] as $key) {
            if (! empty($payload[$key]) && is_scalar($payload[$key])) {
                $cardCode = (string) $payload[$key];
                break;
            }
        }
        // Fallback: no recognised key, take the first scalar value as the code.
        if ($cardCode === null && $cardPin === null) {
            foreach ($payload as $value) {
                if (is_scalar($value) && $value !== '') {
                    $cardCode = (string) $value;
                    break;
                }
            }
        }

        $redeemHtml = $snap['redeem_instructions'] ?? null;

        // Branded eSIMs Cloud sharing link — surfaces a "Manage your eSIM"
        // CTA below the QR for eSIM orders, so the customer can monitor data,
        // re-install, and top up from one branded portal.
        $esimManagePayload = (array) ($payload['esim'] ?? []);
        $esimManageLink = is_scalar($payload['sharing_link'] ?? null)
            ? (string) $payload['sharing_link']
            : (is_scalar($esimManagePayload['sharingLink'] ?? null) ? (string) $esimManagePayload['sharingLink'] : null);
    @endphp

    <h1 style="margin:0 0 14px; font-size:22px; line-height:1.3; font-weight:800; color:#0c1a2e;">Your gift card is ready, {{ $name }}.</h1>

    <p style="margin:0 0 16px; font-size:16px; line-height:1.65; color:#3f3f46;">Your purchase for order <strong>#{{ $orderNumber }}</strong> has been delivered. Your card and redemption details are below, keep this email somewhere safe.</p>

    {{-- Gift card visual — mirrors the dashboard order card so it looks identical
         to what the customer sees in their account (resellers screenshot this). --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" align="center" style="width:100%; max-width:320px; margin:8px auto 0; background:#f4f4f5; border:1px solid #e4e4e7; border-radius:12px;">
        <tr>
            <td style="padding:14px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td valign="top" style="font-family:'Inter',-apple-system,Helvetica,Arial,sans-serif;">
                            @if ($logo)
                                <img src="{{ $logo }}" alt="{{ $brandName }}" height="36" style="height:36px; width:auto; max-width:110px; display:block;">
                            @else
                                <span style="font-size:18px; font-weight:800; text-transform:uppercase; color:#a1a1aa;">{{ strtoupper(substr($brandName, 0, 2)) }}</span>
                            @endif
                            <p style="margin:8px 0 0; font-size:12px; font-weight:700; color:#18181b;">For {{ $brandName }}</p>
                        </td>
                        <td valign="top" align="right" style="font-family:'Inter',-apple-system,Helvetica,Arial,sans-serif;">
                            @if ($faceTxt)
                                <p style="margin:0; font-size:18px; font-weight:800; line-height:1; color:#18181b;">{{ $faceTxt }}</p>
                            @endif
                            @if ($country)
                                <p style="margin:5px 0 0; font-size:11px; color:#52525b;">
                                    @if ($flag)<img src="{{ $flag }}" alt="" width="16" height="11" style="width:16px; height:11px; vertical-align:middle; border-radius:1px;">&nbsp;@endif{{ $country }}
                                </p>
                            @endif
                        </td>
                    </tr>
                </table>

                {{-- Card body space --}}
                <div style="height:22px; line-height:22px; font-size:0;">&nbsp;</div>

                {{-- Code + PIN. No copy button (email clients strip JS); values are
                     select-all so a tap highlights them, and the dashboard button
                     below offers true one-tap copy. --}}
                @if ($cardCode)
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#ffffff; border:1px solid #e4e4e7; border-radius:8px;">
                        <tr>
                            <td style="padding:10px 12px; font-family:'Inter',-apple-system,Helvetica,Arial,sans-serif;">
                                <span style="font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#71717a;">Code</span>
                                <span style="display:block; margin-top:2px; font-size:14px; font-weight:700; letter-spacing:0.03em; color:#18181b; word-break:break-all; user-select:all; -webkit-user-select:all;">{{ $cardCode }}</span>
                            </td>
                        </tr>
                    </table>
                @endif
                @if ($cardPin)
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="{{ $cardCode ? 'margin-top:8px; ' : '' }}background:#ffffff; border:1px solid #e4e4e7; border-radius:8px;">
                        <tr>
                            <td style="padding:10px 12px; font-family:'Inter',-apple-system,Helvetica,Arial,sans-serif;">
                                <span style="font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#71717a;">Pin</span>
                                <span style="display:block; margin-top:2px; font-size:14px; font-weight:700; letter-spacing:0.03em; color:#18181b; word-break:break-all; user-select:all; -webkit-user-select:all;">{{ $cardPin }}</span>
                            </td>
                        </tr>
                    </table>
                @endif
                @if (! $cardCode && ! $cardPin)
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#ffffff; border:1px solid #e4e4e7; border-radius:8px;">
                        <tr>
                            <td style="padding:10px 12px; font-family:'Inter',-apple-system,Helvetica,Arial,sans-serif; font-size:11px; color:#71717a;">Your code appears here once payment clears.</td>
                        </tr>
                    </table>
                @endif
            </td>
        </tr>
    </table>

    @if ($redeemHtml)
        <x-emails.panel title="How to redeem">
            <div style="font-size:14px; line-height:1.6; color:#3f3f46;">{!! $redeemHtml !!}</div>
        </x-emails.panel>
    @endif

    @if ($esimManageLink)
        {{-- eSIM customers get a direct shortcut to the RshopRefills-branded
             eSIMs Cloud portal where they can install, monitor data usage,
             and top up — primary CTA so it precedes the dashboard fallback. --}}
        <x-emails.button :url="$esimManageLink" align="center">Manage your eSIM</x-emails.button>
    @endif

    <x-emails.button :url="url('/dashboard/orders')" align="center">View &amp; copy your code</x-emails.button>

    <p style="margin:18px 0 0; font-size:13px; line-height:1.6; color:#a1a1aa;">Treat this code like cash. Do not share it with anyone you do not trust.</p>
</x-emails.layout>
