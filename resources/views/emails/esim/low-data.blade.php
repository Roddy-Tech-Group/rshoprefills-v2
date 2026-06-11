@php
    $headline = $isExpiry
        ? 'Your eSIM is about to expire'
        : 'Your eSIM is running low';
    $preheader = $isExpiry
        ? 'Top up to keep your '.$esimName.' line active.'
        : 'Top up your '.$esimName.' to stay connected.';
    $accent = $isExpiry ? '#b45309' : '#dc2626';   // amber-700 for expiry, red-600 for low data
    $bgTint = $isExpiry ? '#fef3c7' : '#fee2e2';   // amber-100 / red-100
    $badge  = $isExpiry ? 'Expires soon' : 'Low data';
@endphp
<x-emails.layout :mail-message="$message ?? null" :title="$headline" :preheader="$preheader">
    <p style="margin:0 0 12px;">
        <span style="display:inline-block; padding:4px 10px; border-radius:5px; background:{{ $bgTint }}; color:{{ $accent }}; font-size:11px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase;">{{ $badge }}</span>
    </p>

    <h1 style="margin:0 0 14px; font-size:22px; line-height:1.3; font-weight:800; color:#0c1a2e;">{{ $headline }}</h1>

    <p style="margin:0 0 16px; font-size:16px; line-height:1.65; color:#3f3f46;">Hi {{ $name }},</p>

    @if ($isExpiry)
        <p style="margin:0 0 16px; font-size:16px; line-height:1.65; color:#3f3f46;">
            Your <strong>{{ $esimName }}</strong> expires
            @if ($daysRemaining <= 1)
                <strong>tomorrow</strong>.
            @else
                in <strong>{{ $daysRemaining }} days</strong>.
            @endif
            Top it up before it lapses and keep your line connected. No new QR, no re-install.
        </p>
    @else
        <p style="margin:0 0 16px; font-size:16px; line-height:1.65; color:#3f3f46;">
            Your <strong>{{ $esimName }}</strong> has used
            @if ($usagePercentage > 0)
                <strong>{{ $usagePercentage }}%</strong> of its data
                @if ($remainingData !== '')
                    , about <strong>{{ $remainingData }}</strong> remaining.
                @else
                    .
                @endif
            @else
                most of its data.
            @endif
            Top up to stay connected.
        </p>
    @endif

    <x-emails.panel title="eSIM details">
        <x-emails.row label="Name" :value="$esimName" />
        <x-emails.row label="ICCID" :value="$iccid" />
        @if ($packageData !== '')
            <x-emails.row label="Package" :value="$packageData" />
        @endif
    </x-emails.panel>

    <x-emails.button :url="$topupUrl" align="center">Top up your eSIM</x-emails.button>

    <p style="margin:18px 0 0; font-size:13px; line-height:1.6; color:#a1a1aa;">
        Top-ups land on your existing eSIM. No new install, no setup.
    </p>
</x-emails.layout>
