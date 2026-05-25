@php
    // FAQ — comprehensive, grouped accordion. Dark-mode safe (bg-white -> navy,
    // text-zinc-* remapped). Two-column: sticky title left, questions right.
    $groups = [
        'General' => [
            ['What is RshopRefills?', 'RshopRefills is a global digital marketplace. You can buy gift cards, eSIMs, mobile top-ups, bill payments and travel, and manage everything from one in-app wallet. Most products are delivered instantly.'],
            ['What countries does RshopRefills work in?', 'We serve customers across Africa and internationally. Product availability and pricing vary by region, and the store always shows what is available where you are.'],
            ['How do I use RshopRefills?', 'Create an account, choose a product, pay with your wallet, card, bank transfer, mobile money or crypto, and receive your item instantly in your dashboard and by email.'],
            ['Do I need an account to buy?', 'You can browse as a guest, but you need an account to check out, use your wallet and track your orders.'],
            ['Is RshopRefills safe to use?', 'Yes. We use strong security, automated fraud detection and an optional transaction PIN to protect your account, payments and wallet balance.'],
        ],
        'Orders and delivery' => [
            ['How fast is delivery?', 'Most digital orders are delivered instantly once your payment is confirmed. A small number of items may take a few minutes while they are being prepared.'],
            ['Where do I find my codes?', 'Open your dashboard, go to Orders and select the order. Your codes and PINs appear there, and a copy is sent to your delivery email.'],
            ['My order says processing. What does that mean?', 'It means your payment cleared and we are preparing your item. It usually completes within minutes. If it stays in processing for a long time, contact support with your order number.'],
            ['What happens if an order fails to deliver?', 'If your payment succeeds but the item cannot be delivered because of a network timeout, our system detects it and issues an automatic refund to your wallet, usually within 60 seconds.'],
        ],
        'Payments and wallet' => [
            ['What payment methods can I use?', 'You can pay by card, bank transfer, mobile money, crypto or your wallet balance. The methods shown depend on the currency you select at checkout.'],
            ['What cryptocurrencies do you accept?', 'We support major cryptocurrencies at checkout, depending on your region. You will see the available coins when you choose to pay with crypto.'],
            ['What are network fees?', 'When you pay with crypto, the blockchain network charges a small fee to process your transaction. This fee is set by the network, not by RshopRefills.'],
            ['How do I fund my wallet?', 'Go to Wallet in your dashboard, choose Add funds, pick an amount and a payment method, then confirm. Your balance updates as soon as the payment is confirmed.'],
            ['Can I hold more than one currency?', 'Yes. You can open a wallet in each supported currency and fund whichever you need.'],
            ['What are the limits on how much I can spend?', 'Limits depend on your verification tier. Verifying your identity unlocks higher limits.'],
        ],
        'Gift cards, eSIMs and top-ups' => [
            ['Are gift cards region locked?', 'Some brands are sold per country. The store shows the countries each brand is available in based on the region you select.'],
            ['What information do you need to buy a gift card?', 'Usually just your delivery email. Please double-check it before you pay, as delivered codes cannot be recovered if sent to the wrong address.'],
            ['How do I activate an eSIM?', 'After purchase, your eSIM QR code and activation details appear in your order. Scan the QR code in your phone settings to install and activate it.'],
            ['Can I top up any phone number?', 'You can top up supported operators and countries. Always double-check the phone number before paying, as top-ups are sent instantly and cannot be reversed.'],
        ],
        'Account and verification' => [
            ['Why do I need to verify my identity?', 'Verification helps keep your account and funds secure and can unlock higher limits. Some products require it. Upload your documents from the Verify Identity page in your dashboard.'],
            ['How long does verification take?', 'Most submissions are reviewed within a short window. You will be notified once you are approved or if anything further is needed.'],
            ['How do I update my account details?', 'Go to your dashboard and open Profile to update your name, contact details and preferences.'],
        ],
        'Transaction PIN and security' => [
            ['What is a transaction PIN?', 'It is a 4-digit PIN that authorizes payments from your wallet balance. You can set or change it under Security in your dashboard.'],
            ['I forgot my transaction PIN. What do I do?', 'You can change it from the Security page. For your safety, the PIN locks after several wrong attempts and unlocks automatically after a short cooldown.'],
            ['How do I keep my account safe?', 'Use a strong password, set a transaction PIN, and never share your codes, password or PIN. We will never ask you for them.'],
        ],
        'Rewards' => [
            ['How do I earn points?', 'You earn Rcoin, our rewards currency, on every completed order. See the Earn points page for the details.'],
            ['How do I redeem my Rcoin?', 'You can turn your Rcoin into wallet credit and spend it on any service we offer. Your balance and history are on your Rewards page.'],
        ],
        'Refunds' => [
            ['Can I get a refund?', 'Digital items that have been delivered and revealed are generally final sale. If an item failed to deliver or is faulty, contact support and we will make it right. Full details are in our Refund and Cancellation Policy.'],
            ['How long does a refund take?', 'Approved refunds are issued instantly as wallet credit, which you can spend right away. Reversals back to your original payment method are rare, at our discretion, and may take longer and carry processing fees.'],
        ],
    ];
@endphp

<x-layouts.app.header :title="'FAQ | RshopRefills'">

    <div class="mx-auto w-full max-w-[1140px] px-4 py-14 sm:px-6 sm:py-20">
        <div class="grid grid-cols-1 gap-10 lg:grid-cols-3 lg:gap-14">

            {{-- Title --}}
            <div class="lg:sticky lg:top-28 lg:self-start">
                <h1 class="text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl lg:text-5xl">Everything you need to know</h1>
                <p class="mt-3 text-sm text-zinc-600 sm:text-base">Frequently asked questions</p>
                <p class="mt-6 text-sm leading-relaxed text-zinc-600">
                    Still need help? Visit our <a href="{{ route('shop.help') }}" wire:navigate class="font-medium text-blue-600 hover:underline">Help Center</a>
                    or <a href="{{ route('shop.contact') }}" wire:navigate class="font-medium text-blue-600 hover:underline">contact our team</a>.
                </p>
            </div>

            {{-- Questions --}}
            <div class="lg:col-span-2">
                @foreach ($groups as $heading => $items)
                    <section class="mt-2 first:mt-0">
                        <h2 class="text-sm font-semibold uppercase tracking-wider text-zinc-500">{{ $heading }}</h2>
                        <div class="mt-3 border-t border-zinc-100">
                            @foreach ($items as [$q, $a])
                                <div x-data="{ open: false }" class="border-b border-zinc-100">
                                    <button type="button" @click="open = ! open" :aria-expanded="open.toString()" class="flex w-full items-center justify-between gap-4 py-4 text-left">
                                        <span class="text-sm font-semibold text-zinc-900 sm:text-base">{{ $q }}</span>
                                        <svg class="h-5 w-5 shrink-0 text-zinc-500 transition-transform duration-200" :class="open && 'rotate-45'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                        </svg>
                                    </button>
                                    <div x-show="open" x-collapse x-cloak>
                                        <p class="pb-4 pr-8 text-sm leading-relaxed text-zinc-600">{{ $a }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    @if (! $loop->last)
                        <div class="h-10"></div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

</x-layouts.app.header>
