@php
    use App\Domain\Admin\Queries\DashboardMetricsQuery;
    use App\Models\User;

    /** @var DashboardMetricsQuery $dashboardQuery */
    $dashboardQuery = app(DashboardMetricsQuery::class);

    // ── Aggregated overview metrics ────────────────────────────────────
    $metrics = $dashboardQuery->getOverviewMetrics();
    $totalUsers        = (int)   $metrics['total_users'];
    $totalOrders       = (int)   $metrics['total_orders'];
    $totalRevenue      = (float) $metrics['total_revenue'];
    $totalTransactions = (int)   $metrics['transactions_count'];
    $successRate       = (float) $metrics['success_rate'];
    $walletBalanceTotal = (float) $metrics['wallet_balance_total'];

    // ── Tables ─────────────────────────────────────────────────────────
    $latestUsers        = $dashboardQuery->getLatestUsers(5)->items();
    $latestTransactions = $dashboardQuery->getLatestTransactions(5)->items();

    // ── Revenue chart series (defaults to 30-day range) ────────────────
    // Range UI is Alpine-only for now; backend supports 7d/30d/6m/1y on the API endpoint.
    $revenueData = $dashboardQuery->getRevenueChartData('30d');

    $chartW = 600;
    $chartH = 220;
    $chartPad = 10;
    $seriesKeys = ['gift_cards' => '#ec4899', 'esim' => '#3b82f6', 'topup' => '#10b981', 'other' => '#f59e0b'];

    $maxRevenue = 0.0;
    foreach ($revenueData as $row) {
        foreach (array_keys($seriesKeys) as $k) {
            $maxRevenue = max($maxRevenue, (float) ($row[$k] ?? 0));
        }
    }
    $maxRevenue = max(1.0, $maxRevenue);
    $pointCount = count($revenueData);

    $buildPolyline = function (string $key) use ($revenueData, $chartW, $chartH, $chartPad, $maxRevenue, $pointCount): string {
        if ($pointCount === 0) {
            return '';
        }
        $points = [];
        foreach ($revenueData as $i => $row) {
            $x = $pointCount === 1 ? $chartW / 2 : round(($i / ($pointCount - 1)) * $chartW, 2);
            $value = (float) ($row[$key] ?? 0);
            $y = round($chartH - $chartPad - ($value / $maxRevenue) * ($chartH - 2 * $chartPad), 2);
            $points[] = "{$x},{$y}";
        }
        return implode(' ', $points);
    };

    // ── New Users (last 6 months, ad-hoc since no backend timeseries) ──
    $signups = User::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
        ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
        ->groupBy('month')
        ->orderBy('month')
        ->pluck('count', 'month');

    $signupsByMonth = [];
    for ($i = 5; $i >= 0; $i--) {
        $m = now()->subMonths($i);
        $signupsByMonth[$m->format('M')] = (int) ($signups[$m->format('Y-m')] ?? 0);
    }
    $maxSignups = max(1, ...array_values($signupsByMonth));
    $currentMonthLabel = now()->format('M');
@endphp

<x-layouts.admin>

    {{-- Page content (top bar lives in components/layouts/app/sidebar.blade.php so all admin pages share it).
         Padding is provided by the parent layout wrapper. --}}
    <div class="flex flex-1 flex-col gap-6">

        {{-- Heading moved to the top header. Just the date range picker stays here on the right.
             Range presets are Alpine-driven UI for now; backend wires them with wire:click when period filtering ships. --}}
        {{-- Date range selector (Alpine-driven). Custom range opens an inline date input pair. --}}
        <div
            x-data="{
                open: false,
                view: 'presets',
                ranges: [
                    { label: 'Today',        days: 0 },
                    { label: 'Last 7 days',  days: 7 },
                    { label: 'Last 30 days', days: 30 },
                    { label: 'Last 90 days', days: 90 },
                    { label: 'This year',    days: 365 },
                ],
                selected: 2,
                isCustom: false,
                customStart: '',
                customEnd: '',
                customLabel: '',
                fmt(d) { return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); },
                isoToday() { const d = new Date(); return d.toISOString().slice(0, 10); },
                isoDaysAgo(n) { const d = new Date(); d.setDate(d.getDate() - n); return d.toISOString().slice(0, 10); },
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
                    this.customLabel = `${this.fmt(s)} - ${this.fmt(e)}`;
                    this.isCustom = true;
                    this.selected = -1;
                    this.open = false;
                    this.view = 'presets';
                },
                get rangeLabel() {
                    if (this.isCustom && this.customLabel) return this.customLabel;
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
                    <template x-for="(r, i) in ranges" :key="r.label">
                        <button
                            type="button"
                            @click="selected = i; isCustom = false; open = false"
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
        <div
            x-data="{ navigating: false }"
            x-on:livewire:navigate.window="navigating = true"
            x-on:livewire:navigated.window="navigating = false"
            class="relative"
        >
            <div x-show="navigating" x-cloak class="skeleton-stagger-fast absolute inset-0 z-10 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-5" aria-hidden="true">
                @for ($i = 0; $i < 5; $i++)
                    <div class="rounded-2xl bg-white p-4 shadow-sm shadow-zinc-900/[0.04] ring-1 ring-zinc-100 {{ $i === 4 ? 'col-span-2 lg:col-span-1' : '' }}" style="--i: {{ $i }}">
                        <x-skeleton class="h-11 w-11 rounded-xl" />
                        <x-skeleton class="mt-3 h-4 w-24" />
                        <x-skeleton class="mt-1 h-7 w-28" />
                        <x-skeleton class="mt-3 h-3 w-32" />
                    </div>
                @endfor
            </div>

        {{-- KPI grid. 2-col on mobile (tighter, denser), 5-col on desktop.
             The 5th card (Success Rate) spans both columns on mobile so the trailing card
             doesn't sit alone in a half-row. --}}
        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-5">

            @php
                $kpiCards = [
                    ['label' => 'Total Users',         'value' => number_format($totalUsers),       'sub' => 'All-time registered',     'tone' => 'bg-blue-200',    'icon' => 'trusted by millions.svg', 'span' => false],
                    ['label' => 'Total Orders',        'value' => number_format($totalOrders),      'sub' => 'Across all time',          'tone' => 'bg-orange-200',  'icon' => 'total orders.svg',         'span' => false],
                    ['label' => 'Total Revenue',       'value' => '$' . number_format($totalRevenue, 2),       'sub' => 'Completed payments only',  'tone' => 'bg-emerald-200', 'icon' => 'total revenue.svg',         'span' => false],
                    ['label' => 'Total Transactions',  'value' => number_format($totalTransactions),'sub' => 'Payments + wallet activity','tone' => 'bg-amber-200',   'icon' => 'total transactions.svg',    'span' => false],
                    ['label' => 'Success Rate',        'value' => number_format($successRate, 2) . '%','sub' => 'Completed / total payments','tone' => 'bg-pink-200',    'icon' => 'Success rate.svg',          'span' => true],
                ];
            @endphp

            @foreach ($kpiCards as $kpi)
                @php
                    $isSuccessRate = $kpi['label'] === 'Success Rate';
                @endphp
                <div class="relative flex flex-col overflow-hidden rounded-[20px] bg-white p-4 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100 sm:p-5 {{ $kpi['span'] ? 'col-span-2 lg:col-span-1' : '' }}">
                    {{-- Decorative illustration on the Success Rate card (right side).
                         Gently floats via .animate-float (defined in app.css). Hidden from screen readers. --}}
                    @if ($isSuccessRate)
                        <img
                            src="{{ asset('assets/' . rawurlencode('success rates admin.svg')) }}"
                            alt=""
                            aria-hidden="true"
                            loading="lazy"
                            class="pointer-events-none absolute right-8 top-1/2 h-28 w-28 -translate-y-1/2 select-none object-contain opacity-90 animate-float sm:right-10 sm:h-32 sm:w-32 lg:right-6 lg:h-28 lg:w-28"
                        >
                    @endif

                    <div class="relative z-10 flex flex-1 flex-col {{ $isSuccessRate ? 'pr-24 sm:pr-32' : '' }}">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $kpi['tone'] }} sm:h-11 sm:w-11">
                            <img src="{{ asset('assets/' . rawurlencode($kpi['icon'])) }}" alt="" class="h-5 w-5 sm:h-6 sm:w-6" loading="lazy">
                        </span>
                        <p class="mt-3 text-xs font-medium text-zinc-600 sm:text-sm">{{ $kpi['label'] }}</p>
                        <p class="mt-0.5 text-xl font-bold tracking-tight text-zinc-900 sm:text-2xl">{{ $kpi['value'] }}</p>
                        <p class="mt-auto pt-3 text-[11px] text-zinc-600 sm:text-xs">{{ $kpi['sub'] }}</p>
                    </div>
                </div>
            @endforeach

        </div>
        </div> {{-- /skeleton-wrap KPIs --}}

        {{-- ─── Charts row (placeholder UI — no aggregation endpoints yet) ─ --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

            {{-- New Users chart --}}
            <div class="rounded-[20px] bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <h2 class="text-base font-semibold text-zinc-900">New Users</h2>
                        <img src="{{ asset('assets/' . rawurlencode('new info.svg')) }}" alt="" class="h-4 w-4" loading="lazy">
                    </div>
                    <button type="button" class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-2.5 py-1 text-xs font-medium text-zinc-600 transition-colors hover:bg-zinc-50">
                        6 Months
                        <svg class="h-3 w-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>

                {{-- Real signup counts grouped by month (last 6 months including current). --}}
                <div class="mt-6 flex h-56 items-end justify-between gap-3 sm:gap-5">
                    @foreach ($signupsByMonth as $month => $count)
                        @php $heightPct = round(($count / $maxSignups) * 100, 2); @endphp
                        <div class="flex flex-1 flex-col items-center gap-2">
                            <div class="relative w-full" style="height: 100%;">
                                <div
                                    class="absolute bottom-0 w-full rounded-md {{ $month === $currentMonthLabel ? 'bg-blue-600' : 'bg-blue-200' }}"
                                    style="height: {{ max(2, $heightPct) }}%;"
                                    title="{{ $count }} new users"
                                ></div>
                            </div>
                            <span class="text-xs text-zinc-600">{{ $month }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 flex items-center gap-2 text-xs text-zinc-600">
                    <span class="h-2.5 w-2.5 rounded-sm bg-blue-600"></span>
                    <span>New Registered Users</span>
                </div>
            </div>

            {{-- Revenue Overview chart --}}
            <div class="rounded-[20px] bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <h2 class="text-base font-semibold text-zinc-900">Revenue Overview</h2>
                        <img src="{{ asset('assets/' . rawurlencode('new info.svg')) }}" alt="" class="h-4 w-4" loading="lazy">
                    </div>
                    {{-- Days selector (1 to 30) — Alpine-driven; backend wires to chart filter when ready --}}
                    <div
                        x-data="{ open: false, selected: 15 }"
                        @click.outside="open = false"
                        @keydown.escape.window="open = false"
                        class="relative"
                    >
                        <button
                            type="button"
                            @click="open = !open"
                            :aria-expanded="open.toString()"
                            class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-2.5 py-1 text-xs font-medium text-zinc-600 transition-colors hover:bg-zinc-50"
                        >
                            <span x-text="selected + (selected === 1 ? ' Day' : ' Days')">15 Days</span>
                            <svg class="h-3 w-3 text-zinc-600 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
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
                            class="absolute right-0 top-full z-30 mt-2 w-[140px] overflow-hidden rounded-xl bg-white shadow-xl shadow-zinc-900/10 ring-1 ring-zinc-200"
                            role="menu"
                        >
                            <div class="max-h-64 overflow-y-auto p-1.5">
                                <template x-for="i in 30" :key="i">
                                    <button
                                        type="button"
                                        @click="selected = i; open = false"
                                        :class="selected === i ? 'bg-blue-50 text-blue-700' : 'text-zinc-700 hover:bg-blue-600 hover:text-white'"
                                        class="flex w-full items-center justify-between rounded-lg px-3 py-1.5 text-left text-xs font-medium transition-colors"
                                    >
                                        <span x-text="i + (i === 1 ? ' Day' : ' Days')"></span>
                                        <svg x-show="selected === i" class="h-3.5 w-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Real chart — one line per product category from getRevenueChartData('30d').
                     Backend returns date-grouped totals for gift_cards / esim / topup / other. --}}
                @if ($pointCount === 0)
                    <div class="mt-6 flex h-56 items-center justify-center rounded-xl bg-zinc-50 text-sm text-zinc-600">
                        No revenue data in the last 30 days yet.
                    </div>
                @else
                    <svg viewBox="0 0 600 240" class="mt-6 h-56 w-full" preserveAspectRatio="none" aria-hidden="true">
                        {{-- Gridlines --}}
                        @foreach ([0, 55, 110, 165, 220] as $y)
                            <line x1="0" y1="{{ $y }}" x2="600" y2="{{ $y }}" stroke="#e4e4e7" stroke-width="1" stroke-dasharray="2,4"/>
                        @endforeach

                        @foreach ($seriesKeys as $key => $color)
                            <polyline points="{{ $buildPolyline($key) }}" fill="none" stroke="{{ $color }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        @endforeach
                    </svg>
                @endif

                <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-zinc-600">
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-pink-500"></span>Gift Cards</span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-blue-500"></span>eSIMs</span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-emerald-500"></span>Top-ups</span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-amber-500"></span>Other</span>
                </div>
            </div>

        </div>

        {{-- ─── Tables row (real data) ──────────────────────────────── --}}
        <div
            x-data="{ navigating: false }"
            x-on:livewire:navigate.window="navigating = true"
            x-on:livewire:navigated.window="navigating = false"
            class="relative"
        >
            <div x-show="navigating" x-cloak class="skeleton-stagger absolute inset-0 z-10 grid grid-cols-1 gap-4 lg:grid-cols-2" aria-hidden="true">
                @for ($i = 0; $i < 2; $i++)
                    <div class="overflow-hidden rounded-[20px] bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100" style="--i: {{ $i }}">
                        <x-skeleton class="h-5 w-32" />
                        <div class="skeleton-stagger-fast mt-5 space-y-3">
                            @for ($r = 0; $r < 5; $r++)
                                <div class="flex items-center gap-3" style="--i: {{ $r }}">
                                    <x-skeleton shape="circle" class="h-9 w-9" />
                                    <div class="flex-1 space-y-2">
                                        <x-skeleton class="h-4 w-32" />
                                        <x-skeleton class="h-3 w-44" />
                                    </div>
                                    <x-skeleton class="h-4 w-12" />
                                </div>
                            @endfor
                        </div>
                    </div>
                @endfor
            </div>
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

            {{-- Latest Users --}}
            <div class="overflow-hidden rounded-[20px] bg-white shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                <div class="flex items-center justify-between border-b border-zinc-200 p-5">
                    <div class="flex items-center gap-2">
                        <img src="{{ asset('assets/' . rawurlencode('user.svg')) }}" alt="" class="h-5 w-5" loading="lazy">
                        <h2 class="text-base font-semibold text-zinc-900">Latest Users</h2>
                    </div>
                    <a href="#" class="rounded-lg border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-600 transition-colors hover:bg-zinc-50">View All</a>
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
                                        <a href="#" class="inline-flex items-center rounded-lg border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-600 transition-colors hover:bg-zinc-50">View</a>
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
                    <a href="#" class="rounded-lg border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-600 transition-colors hover:bg-zinc-50">View All</a>
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
                                    <td class="px-5 py-3 text-[11px] font-semibold text-zinc-900">${{ number_format((float) $tx->amount, 2) }}</td>
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
