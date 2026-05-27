@php
    use App\Domain\Admin\Queries\DashboardMetricsQuery;

    /** @var DashboardMetricsQuery $dashboardQuery */
    $dashboardQuery = app(DashboardMetricsQuery::class);

    // ── KPI cards — filtered by the top-right date range picker ──────
    // `?range=today|7d|30d|90d|year|custom` (custom uses `?start=` + `?end=`).
    // Missing or unknown values fall through to all-time.
    $rangePreset = request('range');
    $rangeStart  = request('start');
    $rangeEnd    = request('end');

    $metrics = $dashboardQuery->getOverviewMetrics($rangePreset, $rangeStart, $rangeEnd);
    $totalUsers        = (int)   $metrics['total_users'];
    $totalOrders       = (int)   $metrics['total_orders'];
    $totalRevenue      = (float) $metrics['total_revenue'];
    $totalTransactions = (int)   $metrics['transactions_count'];
    $successRate       = (float) $metrics['success_rate'];
    $walletBalanceTotal = (float) $metrics['wallet_balance_total'];

    // ── Tables ─────────────────────────────────────────────────────────
    $latestUsers        = $dashboardQuery->getLatestUsers(5)->items();
    $latestTransactions = $dashboardQuery->getLatestTransactions(5)->items();

    // ── Trends chart series — `?revenue_days=N` (1-365) ──────────────
    // Sales / Cost timeseries rendered by ApexCharts in the browser. The
    // period dropdown at the bottom of the chart picks a preset and SPA
    // navigates with a fresh `?revenue_days=`, so the server re-aggregates.
    // "This Month" / "This Year" are dynamic (today's day-of-month / -year);
    // raw day counts not matching a preset fall back to "Last N Days".
    $revenueDays = max(1, min(365, (int) request('revenue_days', 30)));
    $salesCostSeries = $dashboardQuery->getSalesCostTimeseries($revenueDays);
    $pointCount = count($salesCostSeries);

    $trendsPeriods = [
        ['label' => 'This Week',    'days' => 7],
        ['label' => 'This Month',   'days' => max(1, now()->day)],
        ['label' => 'Last 30 Days', 'days' => 30],
        ['label' => 'Last 90 Days', 'days' => 90],
        ['label' => 'This Year',    'days' => max(1, now()->dayOfYear)],
    ];
    $currentTrendsLabel = (collect($trendsPeriods)->firstWhere('days', $revenueDays))['label']
        ?? "Last {$revenueDays} Days";

    // ── Best Selling Countries map ──────────────────────────────────
    // `?country_days=` drives the rolling window; `?country_cat=` narrows to
    // a product category (esim | gift | topup); the Country / Region toggle
    // is client-side (re-shades the same world map). The map widget pulls
    // ISO → continent from config/continents.php.
    $countryDays = max(1, min(365, (int) request('country_days', 7)));
    $countryCategory = (string) request('country_cat', 'all');
    $countriesByCode = $dashboardQuery->getBestSellingCountries($countryDays, $countryCategory);

    $codeToContinent = (array) config('continents.codes', []);
    $salesByRegion = [];
    foreach ($countriesByCode as $cc => $total) {
        $region = $codeToContinent[$cc] ?? null;
        if ($region === null) {
            continue;
        }
        $salesByRegion[$region] = ($salesByRegion[$region] ?? 0) + (float) $total;
    }

    $countryPeriods = [
        ['label' => 'Today',        'days' => 1],
        ['label' => 'This Week',    'days' => 7],
        ['label' => 'Last 30 Days', 'days' => 30],
        ['label' => 'This Year',    'days' => max(1, now()->dayOfYear)],
    ];
    $currentCountryPeriod = (collect($countryPeriods)->firstWhere('days', $countryDays))['label']
        ?? "Last {$countryDays} Days";

    $countryCategories = [
        ['label' => 'All Products',  'key' => 'all'],
        ['label' => 'eSIMs',         'key' => 'esim'],
        ['label' => 'Gift Cards',    'key' => 'gift'],
        ['label' => 'Mobile Top-up', 'key' => 'topup'],
    ];
    $currentCountryCategory = (collect($countryCategories)->firstWhere('key', $countryCategory))['label']
        ?? 'All Products';
@endphp

<x-layouts.admin>

    {{-- Dark-mode text contrast on the KPI cards. The project's app.css remap of
         `.dark .text-zinc-600` was sitting at a too-dim grey and Tailwind's dark
         variants couldn't override it (custom dark variant uses :where() which
         zeros specificity). Inline this rule so it ships with the HTML and beats
         the cascade regardless of CSS pipeline state. --}}
    <style>
        html.dark .kpi-card .kpi-label { color: #f4f4f5 !important; }
        html.dark .kpi-card .kpi-sub   { color: #d4d4d8 !important; }
    </style>

    {{-- Page content (top bar lives in components/layouts/app/sidebar.blade.php so all admin pages share it).
         Padding is provided by the parent layout wrapper. --}}
    <div class="flex flex-1 flex-col gap-6">

        {{-- Heading moved to the top header. Just the date range picker stays here on the right.
             Range presets are Alpine-driven UI for now; backend wires them with wire:click when period filtering ships. --}}
        {{-- Date range selector (Alpine-driven). Custom range opens an inline date input pair. --}}
        {{-- Range picker — drives KPI cards via the URL: ?range=KEY for presets
             or ?range=custom plus ?start= and ?end= for the custom variant.
             Each preset SPA-navigates so the top PHP block re-runs and the
             cards re-aggregate against the new window. Initial selection is
             derived from the current URL so the dropdown reflects what the
             server actually applied. --}}
        <div
            x-data="{
                open: false,
                view: 'presets',
                ranges: [
                    { label: 'Today',        key: 'today', days: 0 },
                    { label: 'Last 7 days',  key: '7d',    days: 7 },
                    { label: 'Last 30 days', key: '30d',   days: 30 },
                    { label: 'Last 90 days', key: '90d',   days: 90 },
                    { label: 'This year',    key: 'year',  days: 365 },
                ],
                selected: {{ in_array($rangePreset, ['today','7d','30d','90d','year'], true) ? collect(['today','7d','30d','90d','year'])->search($rangePreset) : -1 }},
                isCustom: {{ $rangePreset === 'custom' ? 'true' : 'false' }},
                customStart: @js($rangeStart ?? ''),
                customEnd: @js($rangeEnd ?? ''),
                customLabel: '',
                fmt(d) { return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); },
                isoToday() { const d = new Date(); return d.toISOString().slice(0, 10); },
                isoDaysAgo(n) { const d = new Date(); d.setDate(d.getDate() - n); return d.toISOString().slice(0, 10); },
                go(url) { window.Livewire ? window.Livewire.navigate(url) : window.location.assign(url); },
                pick(i) {
                    this.selected = i;
                    this.isCustom = false;
                    this.open = false;
                    this.go('?range=' + this.ranges[i].key);
                },
                openCustom() {
                    this.view = 'custom';
                    if (!this.customStart) this.customStart = this.isoDaysAgo(30);
                    if (!this.customEnd)   this.customEnd   = this.isoToday();
                },
                applyCustom() {
                    if (!this.customStart || !this.customEnd) return;
                    const s = new Date(this.customStart);
                    const e = new Date(this.customEnd);
                    if (e < s) return;
                    this.go('?range=custom&start=' + this.customStart + '&end=' + this.customEnd);
                },
                clearRange() {
                    this.go(window.location.pathname);
                },
                get rangeLabel() {
                    if (this.isCustom && this.customStart && this.customEnd) {
                        return this.fmt(new Date(this.customStart)) + ' - ' + this.fmt(new Date(this.customEnd));
                    }
                    if (this.selected < 0) return 'All time';
                    const end = new Date();
                    const start = new Date();
                    start.setDate(end.getDate() - this.ranges[this.selected].days);
                    return this.ranges[this.selected].days === 0
                        ? this.fmt(end)
                        : `${this.fmt(start)} - ${this.fmt(end)}`;
                }
            }"
            @click.outside="open = false; view = 'presets'"
            @keydown.escape.window="open = false; view = 'presets'"
            class="relative flex justify-center lg:justify-end"
        >
            <button
                type="button"
                @click="open = !open"
                :aria-expanded="open.toString()"
                class="flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3.5 py-2 text-sm font-medium text-zinc-700 shadow-sm shadow-zinc-900/5 transition-colors hover:bg-zinc-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
            >
                <img src="{{ asset('assets/' . rawurlencode('calender.svg')) }}" alt="" class="h-4 w-4" loading="lazy">
                <span x-text="rangeLabel">{{ now()->subDays(30)->format('M j, Y') }} - {{ now()->format('M j, Y') }}</span>
                <svg class="h-3.5 w-3.5 text-zinc-600 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1"
                style="display:none;"
                class="absolute right-0 top-full z-30 mt-2 w-[280px] overflow-hidden rounded-xl bg-white shadow-xl shadow-zinc-900/10 ring-1 ring-zinc-200"
                role="menu"
            >
                {{-- Presets view --}}
                <div x-show="view === 'presets'" class="p-1.5">
                    <button
                        type="button"
                        @click="clearRange()"
                        :class="selected < 0 && !isCustom ? 'bg-blue-50 text-blue-700' : 'text-zinc-700 hover:bg-blue-600 hover:text-white'"
                        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm font-medium transition-colors"
                    >
                        <span>All time</span>
                        <svg x-show="selected < 0 && !isCustom" class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </button>
                    <template x-for="(r, i) in ranges" :key="r.label">
                        <button
                            type="button"
                            @click="pick(i)"
                            :class="selected === i && !isCustom ? 'bg-blue-50 text-blue-700' : 'text-zinc-700 hover:bg-blue-600 hover:text-white'"
                            class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm font-medium transition-colors"
                        >
                            <span x-text="r.label"></span>
                            <svg x-show="selected === i && !isCustom" class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </button>
                    </template>
                </div>

                <div x-show="view === 'presets'" class="border-t border-zinc-100 p-1.5">
                    <button
                        type="button"
                        @click="openCustom()"
                        :class="isCustom ? 'bg-blue-50 text-blue-700' : 'text-zinc-700 hover:bg-blue-600 hover:text-white'"
                        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm font-medium transition-colors"
                    >
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Custom range
                        </span>
                        <svg x-show="isCustom" class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </button>
                </div>

                {{-- Custom range view --}}
                <div x-show="view === 'custom'" class="p-3">
                    <div class="mb-3 flex items-center justify-between">
                        <button type="button" @click="view = 'presets'" class="inline-flex items-center gap-1 text-xs font-medium text-zinc-600 hover:text-zinc-900">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                            Back
                        </button>
                        <p class="text-sm font-semibold text-zinc-900">Custom range</p>
                        <span class="w-10"></span>
                    </div>

                    <label class="block">
                        <span class="mb-1 block text-xs font-medium text-zinc-600">From</span>
                        <input
                            type="date"
                            x-model="customStart"
                            :max="customEnd || isoToday()"
                            class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15"
                        />
                    </label>

                    <label class="mt-2 block">
                        <span class="mb-1 block text-xs font-medium text-zinc-600">To</span>
                        <input
                            type="date"
                            x-model="customEnd"
                            :min="customStart"
                            :max="isoToday()"
                            class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15"
                        />
                    </label>

                    <button
                        type="button"
                        @click="applyCustom()"
                        :disabled="!customStart || !customEnd"
                        class="mt-3 inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Apply range
                    </button>
                </div>
            </div>
        </div>

        {{-- ─── KPI cards (real data) ────────────────────────────────── --}}
        <div>
            {{-- Previously had a livewire:navigate skeleton overlay here. Removed:
                 admin pages run as full page loads (per 2026-05-16 sidebar fix), so
                 `livewire:navigated` didn't reliably fire to hide the overlay —
                 left grey skeletons sitting on top of the already-rendered text. --}}

        {{-- KPI grid. 2-col on mobile (tighter, denser), 5-col on desktop.
             The 5th card (Success Rate) spans both columns on mobile so the trailing card
             doesn't sit alone in a half-row. --}}
        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-5">

            @php
                $kpiCards = [
                    ['label' => 'Total Users',         'value' => number_format($totalUsers),       'sub' => 'All-time registered',     'tone' => 'bg-blue-200',    'icon' => 'trusted by millions.svg', 'span' => false],
                    ['label' => 'Total Orders',        'value' => number_format($totalOrders),      'sub' => 'Across all time',          'tone' => 'bg-orange-200',  'icon' => 'total orders.svg',         'span' => false],
                    ['label' => 'Total Revenue',       'value' => '$' . number_format($totalRevenue, 2),       'sub' => 'Completed orders only',    'tone' => 'bg-emerald-200', 'icon' => 'total revenue.svg',         'span' => false],
                    ['label' => 'Total Transactions',  'value' => number_format($totalTransactions),'sub' => 'Payments + wallet activity','tone' => 'bg-amber-200',   'icon' => 'total transactions.svg',    'span' => false],
                    ['label' => 'Success Rate',        'value' => number_format($successRate, 2) . '%','sub' => 'Completed / total payments','tone' => 'bg-pink-200',    'icon' => 'Success rate.svg',          'span' => true],
                ];
            @endphp

            @foreach ($kpiCards as $kpi)
                @php
                    $isSuccessRate = $kpi['label'] === 'Success Rate';
                @endphp
                <div class="kpi-card relative flex flex-col overflow-hidden rounded-[20px] bg-white p-4 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100 sm:p-5 {{ $kpi['span'] ? 'col-span-2 lg:col-span-1' : '' }}">
                    {{-- Decorative illustration on the Success Rate card (right side).
                         Gently floats via .animate-float (defined in app.css). Hidden from screen readers. --}}
                    @if ($isSuccessRate)
                        <img
                            src="{{ asset('assets/' . rawurlencode('success rates admin.svg')) }}"
                            alt=""
                            aria-hidden="true"
                            loading="lazy"
                            class="no-dark-invert pointer-events-none absolute right-8 top-1/2 h-28 w-28 -translate-y-1/2 select-none object-contain opacity-90 animate-float sm:right-10 sm:h-32 sm:w-32 lg:right-6 lg:h-28 lg:w-28"
                        >
                    @endif

                    <div class="relative z-10 flex flex-1 flex-col {{ $isSuccessRate ? 'pr-24 sm:pr-32' : '' }}">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $kpi['tone'] }} sm:h-11 sm:w-11">
                            {{-- no-dark-invert keeps the small emoji-style icon in its original
                                 dark artwork (against the pastel tile) instead of being flipped
                                 white by the blanket dark-mode SVG invert in app.css. --}}
                            <img src="{{ asset('assets/' . rawurlencode($kpi['icon'])) }}" alt="" class="no-dark-invert h-5 w-5 sm:h-6 sm:w-6" loading="lazy">
                        </span>
                        {{-- `.kpi-label` / `.kpi-sub` get their dark-mode colour from the
                             inline <style> block at the top of this view — bypasses the
                             Tailwind dark variant which couldn't beat app.css's broader
                             `.dark .text-zinc-600` remap. --}}
                        <p class="kpi-label mt-3 text-xs font-medium text-zinc-600 sm:text-sm">{{ $kpi['label'] }}</p>
                        <p class="mt-0.5 text-xl font-bold tracking-tight text-zinc-900 sm:text-2xl">{{ $kpi['value'] }}</p>
                        <p class="kpi-sub mt-auto pt-3 text-[11px] text-zinc-600 sm:text-xs">{{ $kpi['sub'] }}</p>
                    </div>
                </div>
            @endforeach

        </div>
        </div> {{-- /skeleton-wrap KPIs --}}

        {{-- ─── Charts row (placeholder UI — no aggregation endpoints yet) ─ --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

            {{-- Best Selling Countries — jsvectormap world map shaded by sales.
                 Footer carries the Period + Product filters (SPA-navigate the
                 server for fresh aggregates); the Country / Region toggle is
                 a client-side repaint via the Alpine factory. --}}
            <div
                x-data="bestSellingCountriesMap({
                    countries: @js($countriesByCode),
                    regions: @js($salesByRegion),
                    codeToContinent: @js($codeToContinent),
                })"
                class="flex flex-col rounded-[20px] bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100 dark:bg-[#1d3252] dark:ring-zinc-700/60"
            >
                {{-- Header: title (left), Global label (right). Mirrors the
                     reference — "Global" is purely a scope hint, not a control. --}}
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Best Selling Countries</h2>
                    <span class="flex items-center gap-1.5 rounded-lg border border-zinc-200 px-2.5 py-1 text-xs font-medium text-zinc-500 dark:border-zinc-700/60 dark:text-zinc-300">
                        Global
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </span>
                </div>

                {{-- Map canvas. jsvectormap renders into x-ref="map" once init()
                     resolves; it lazy-imports the lib + world-merc data. --}}
                @if (empty($countriesByCode))
                    <div class="mt-6 flex flex-1 items-center justify-center rounded-xl bg-zinc-50 text-sm text-zinc-600 dark:bg-[#26416b] dark:text-zinc-400" style="min-height: 320px;">
                        No completed orders in the last {{ $countryDays }} {{ $countryDays === 1 ? 'day' : 'days' }}{{ $countryCategory !== 'all' ? ' for '.$currentCountryCategory : '' }} yet.
                    </div>
                @else
                    <div x-ref="map" class="mt-2 flex-1" style="min-height: 320px;"></div>
                @endif

                {{-- Footer: Period dropdown + Product dropdown (server-side),
                     Country / Region toggle (client-side repaint). --}}
                <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        {{-- Period --}}
                        <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
                            <button
                                type="button"
                                @click="open = ! open"
                                :aria-expanded="open.toString()"
                                class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium text-zinc-600 transition-colors hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-[#26416b]"
                            >
                                <span>{{ $currentCountryPeriod }}</span>
                                <svg class="h-3 w-3 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div
                                x-show="open"
                                x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                                style="display:none;"
                                class="absolute left-0 bottom-full z-30 mb-2 w-40 overflow-hidden rounded-xl bg-white p-1.5 shadow-xl shadow-zinc-900/10 ring-1 ring-zinc-200 dark:bg-[#1d3252] dark:ring-zinc-700/60"
                                role="menu"
                            >
                                @foreach ($countryPeriods as $p)
                                    <a
                                        href="?country_days={{ $p['days'] }}&country_cat={{ $countryCategory }}"
                                        wire:navigate
                                        @class([
                                            'flex w-full items-center justify-between rounded-lg px-3 py-1.5 text-left text-xs font-medium transition-colors',
                                            'bg-blue-50 text-blue-700 dark:bg-blue-600/15 dark:text-blue-300' => $currentCountryPeriod === $p['label'],
                                            'text-zinc-700 hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-[#26416b]' => $currentCountryPeriod !== $p['label'],
                                        ])
                                    >
                                        {{ $p['label'] }}
                                        @if ($currentCountryPeriod === $p['label'])
                                            <svg class="h-3.5 w-3.5 text-blue-600 dark:text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                            </svg>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        {{-- Product category --}}
                        <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
                            <button
                                type="button"
                                @click="open = ! open"
                                :aria-expanded="open.toString()"
                                class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium text-zinc-600 transition-colors hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-[#26416b]"
                            >
                                <span>{{ $currentCountryCategory }}</span>
                                <svg class="h-3 w-3 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div
                                x-show="open"
                                x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                                style="display:none;"
                                class="absolute left-0 bottom-full z-30 mb-2 w-44 overflow-hidden rounded-xl bg-white p-1.5 shadow-xl shadow-zinc-900/10 ring-1 ring-zinc-200 dark:bg-[#1d3252] dark:ring-zinc-700/60"
                                role="menu"
                            >
                                @foreach ($countryCategories as $cat)
                                    <a
                                        href="?country_days={{ $countryDays }}&country_cat={{ $cat['key'] }}"
                                        wire:navigate
                                        @class([
                                            'flex w-full items-center justify-between rounded-lg px-3 py-1.5 text-left text-xs font-medium transition-colors',
                                            'bg-blue-50 text-blue-700 dark:bg-blue-600/15 dark:text-blue-300' => $currentCountryCategory === $cat['label'],
                                            'text-zinc-700 hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-[#26416b]' => $currentCountryCategory !== $cat['label'],
                                        ])
                                    >
                                        {{ $cat['label'] }}
                                        @if ($currentCountryCategory === $cat['label'])
                                            <svg class="h-3.5 w-3.5 text-blue-600 dark:text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                            </svg>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Country / Region toggle — client-side, repaints the map. --}}
                    <div class="flex items-center gap-1 rounded-full bg-zinc-100 p-1 dark:bg-[#26416b]">
                        <button type="button" @click="setView('country')" :class="view === 'country' ? 'bg-blue-600 text-white' : 'text-zinc-700 dark:text-zinc-200'" class="rounded-full px-3 py-1 text-xs font-semibold transition-colors">Country</button>
                        <button type="button" @click="setView('region')"  :class="view === 'region'  ? 'bg-blue-600 text-white' : 'text-zinc-700 dark:text-zinc-200'" class="rounded-full px-3 py-1 text-xs font-semibold transition-colors">Region</button>
                    </div>
                </div>
            </div>

            {{-- Trends chart — smooth sales/cost area chart via ApexCharts.
                 Alpine factory `salesCostChart` (resources/js/app.js) lazy-
                 imports ApexCharts on init so it doesn't ship to pages that
                 don't render a chart. Don't add x-init="init()" — Alpine
                 auto-runs init() and explicitly calling it again duplicates
                 the chart. --}}
            <div
                x-data="salesCostChart(@js($salesCostSeries))"
                class="rounded-[20px] bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100 dark:bg-[#1d3252] dark:ring-zinc-700/60"
            >
                {{-- Header: title + squiggle legend (left), Sales/Cost dropdown (right) --}}
                <div class="flex items-start justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                        <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Trends</h2>
                        <div class="flex items-center gap-3 text-xs font-medium text-zinc-600 dark:text-zinc-300">
                            <span class="flex items-center gap-1.5">
                                <svg viewBox="0 0 24 8" class="h-2 w-6 text-emerald-400" fill="none" aria-hidden="true">
                                    <path d="M1 4 Q 4 0, 7 4 T 13 4 T 19 4 T 23 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Sales
                            </span>
                            <span class="flex items-center gap-1.5">
                                <svg viewBox="0 0 24 8" class="h-2 w-6 text-blue-400" fill="none" aria-hidden="true">
                                    <path d="M1 4 Q 4 0, 7 4 T 13 4 T 19 4 T 23 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Cost
                            </span>
                        </div>
                    </div>

                    {{-- Sales / Cost selector — client-side series toggle. Drives the
                         chart instance via salesCostChart.setMode(); no server round-trip. --}}
                    <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative shrink-0">
                        <button
                            type="button"
                            @click="open = ! open"
                            :aria-expanded="open.toString()"
                            class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 transition-colors hover:bg-zinc-50 dark:border-zinc-700/60 dark:bg-[#26416b] dark:text-white dark:hover:bg-[#34507a]"
                        >
                            <span x-text="modeLabel()">Sales / Cost</span>
                            <svg class="h-3 w-3 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"   x-transition:leave-start="opacity-100 translate-y-0"  x-transition:leave-end="opacity-0 -translate-y-1"
                            style="display:none;"
                            class="absolute right-0 top-full z-30 mt-2 w-44 overflow-hidden rounded-xl bg-white p-1.5 shadow-xl shadow-zinc-900/10 ring-1 ring-zinc-200 dark:bg-[#1d3252] dark:ring-zinc-700/60"
                            role="menu"
                        >
                            @foreach ([['both', 'Sales / Cost'], ['sales', 'Sales'], ['cost', 'Cost']] as [$key, $label])
                                <button
                                    type="button"
                                    @click="setMode('{{ $key }}'); open = false"
                                    :class="mode === '{{ $key }}' ? 'bg-blue-50 text-blue-700 dark:bg-blue-600/15 dark:text-blue-300' : 'text-zinc-700 hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-[#26416b]'"
                                    class="flex w-full items-center justify-between rounded-lg px-3 py-1.5 text-left text-xs font-medium transition-colors"
                                >
                                    {{ $label }}
                                    <svg x-show="mode === '{{ $key }}'" class="h-3.5 w-3.5 text-blue-600 dark:text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                    </svg>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Empty / chart canvas. ApexCharts renders into x-ref="canvas"
                     once init() resolves; it lazy-imports the lib on demand. --}}
                @if ($pointCount === 0)
                    <div class="mt-6 flex h-72 items-center justify-center rounded-xl bg-zinc-50 text-sm text-zinc-600 dark:bg-[#26416b] dark:text-zinc-400">
                        No completed orders in the last {{ $revenueDays }} {{ $revenueDays === 1 ? 'day' : 'days' }} yet.
                    </div>
                @else
                    <div x-ref="canvas" class="mt-2 min-h-[320px]"></div>
                @endif

                {{-- Footer: period dropdown — SPA-navigates with ?revenue_days=N so
                     the server re-aggregates the window. Currently selected period
                     mirrors the URL (the PHP block above computes $currentTrendsLabel). --}}
                <div class="mt-3 flex items-center">
                    <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false" class="relative">
                        <button
                            type="button"
                            @click="open = ! open"
                            :aria-expanded="open.toString()"
                            class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium text-zinc-600 transition-colors hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-[#26416b]"
                        >
                            <span>{{ $currentTrendsLabel }}</span>
                            <svg class="h-3 w-3 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"   x-transition:leave-start="opacity-100 translate-y-0"  x-transition:leave-end="opacity-0 -translate-y-1"
                            style="display:none;"
                            class="absolute left-0 bottom-full z-30 mb-2 w-40 overflow-hidden rounded-xl bg-white p-1.5 shadow-xl shadow-zinc-900/10 ring-1 ring-zinc-200 dark:bg-[#1d3252] dark:ring-zinc-700/60"
                            role="menu"
                        >
                            @foreach ($trendsPeriods as $p)
                                <a
                                    href="?revenue_days={{ $p['days'] }}"
                                    wire:navigate
                                    @class([
                                        'flex w-full items-center justify-between rounded-lg px-3 py-1.5 text-left text-xs font-medium transition-colors',
                                        'bg-blue-50 text-blue-700 dark:bg-blue-600/15 dark:text-blue-300' => $currentTrendsLabel === $p['label'],
                                        'text-zinc-700 hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-[#26416b]' => $currentTrendsLabel !== $p['label'],
                                    ])
                                >
                                    {{ $p['label'] }}
                                    @if ($currentTrendsLabel === $p['label'])
                                        <svg class="h-3.5 w-3.5 text-blue-600 dark:text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                        </svg>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ─── Tables row (real data) ──────────────────────────────── --}}
        <div>
            {{-- Skeleton overlay removed — same reason as the KPI overlay above.
                 The tables render with the page; no transitional placeholder needed. --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

            {{-- Latest Users --}}
            <div class="overflow-hidden rounded-[20px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                <div class="flex items-center justify-between border-b border-zinc-200 p-5">
                    <div class="flex items-center gap-2">
                        <img src="{{ asset('assets/' . rawurlencode('user.svg')) }}" alt="" class="h-5 w-5" loading="lazy">
                        <h2 class="text-base font-semibold text-zinc-900">Latest Users</h2>
                    </div>
                    <a href="{{ route('admin.customers') }}" class="rounded-lg border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-600 transition-colors hover:bg-zinc-50">View All</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-[11px]">
                        <thead class="bg-zinc-50 text-[10px] uppercase tracking-wider text-zinc-600">
                            <tr>
                                <th class="px-5 py-3 font-semibold">User</th>
                                <th class="px-5 py-3 font-semibold">Status</th>
                                <th class="px-5 py-3 font-semibold">Registered</th>
                                <th class="px-5 py-3 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @forelse ($latestUsers as $user)
                                <tr>
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-3">
                                            @php
                                                $rowAvatar = $user->avatar_url ?: asset('assets/' . rawurlencode(match (strtolower($user->gender ?? '')) {
                                                    'female', 'f' => 'New Female Account Avatar.png',
                                                    default       => 'New male account avatar.png',
                                                }));
                                            @endphp
                                            <img src="{{ $rowAvatar }}" alt="" class="h-9 w-9 shrink-0 rounded-full object-cover ring-1 ring-blue-100">
                                            <div class="leading-tight">
                                                <p class="text-[11px] font-semibold text-zinc-900">{{ $user->name }}</p>
                                                <p class="text-[10px] text-zinc-600">{{ $user->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3">
                                        @if ($user->email_verified_at)
                                            <span class="inline-flex items-center rounded-[5px] bg-emerald-400 px-2.5 py-0.5 text-xs font-semibold text-white">Active</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">Pending</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-[11px] text-zinc-600">{{ $user->created_at->format('M j, Y') }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <a href="{{ route('admin.customers') }}" class="inline-flex items-center rounded-lg border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-600 transition-colors hover:bg-zinc-50">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-12 text-center text-sm text-zinc-600">No users yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Latest Transactions --}}
            <div class="overflow-hidden rounded-[20px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                <div class="flex items-center justify-between border-b border-zinc-200 p-5">
                    <div class="flex items-center gap-2">
                        <img src="{{ asset('assets/' . rawurlencode('Latest transactions.png')) }}" alt="" class="h-5 w-5" loading="lazy">
                        <h2 class="text-base font-semibold text-zinc-900">Latest Transactions</h2>
                    </div>
                    <a href="{{ route('admin.transactions') }}" class="rounded-lg border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-600 transition-colors hover:bg-zinc-50">View All</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-[11px]">
                        <thead class="bg-zinc-50 text-[10px] uppercase tracking-wider text-zinc-600">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Reference</th>
                                <th class="px-5 py-3 font-semibold">Customer</th>
                                <th class="px-5 py-3 font-semibold">Type</th>
                                <th class="px-5 py-3 font-semibold">Amount</th>
                                <th class="px-5 py-3 font-semibold">Status</th>
                                <th class="px-5 py-3 font-semibold">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @forelse ($latestTransactions as $tx)
                                @php
                                    $statusValue = $tx->status ?? 'pending';
                                    $statusClasses = match ($statusValue) {
                                        'completed' => 'bg-emerald-100 text-emerald-700',
                                        'failed', 'cancelled' => 'bg-red-100 text-red-700',
                                        'refunded' => 'bg-zinc-200 text-zinc-700',
                                        default => 'bg-amber-100 text-amber-700',
                                    };
                                    $typeLabel = $tx->source === 'wallet_transaction'
                                        ? 'Wallet · ' . ucfirst(str_replace('_', ' ', (string) $tx->type))
                                        : 'Payment · ' . ucfirst((string) ($tx->gateway ?? 'gateway'));
                                    $reference = $tx->reference ?: '#' . $tx->id;
                                @endphp
                                <tr>
                                    <td class="px-5 py-3 text-[11px] font-mono text-zinc-600">{{ $reference }}</td>
                                    <td class="px-5 py-3 text-[11px] font-medium text-zinc-900">{{ $tx->customer_name ?? '—' }}</td>
                                    <td class="px-5 py-3 text-[11px] text-zinc-600">{{ $typeLabel }}</td>
                                    <td class="px-5 py-3 text-[11px] font-semibold text-zinc-900">{{ \App\Models\Product::currencySymbol($tx->currency ?? 'USD') }}{{ number_format((float) $tx->amount, 2) }}</td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClasses }}">
                                            {{ ucfirst((string) $statusValue) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-[11px] text-zinc-600">{{ \Illuminate\Support\Carbon::parse($tx->date)->format('M j, Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-12 text-center text-sm text-zinc-600">No transactions yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
        </div> {{-- /skeleton-wrap tables --}}

    </div>

</x-layouts.admin>
