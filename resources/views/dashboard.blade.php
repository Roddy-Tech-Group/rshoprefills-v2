@php
    use App\Models\User;
    use App\Models\Order;
    use App\Models\Payment;
    use App\Domain\Shared\Enums\PaymentStatus;
    use App\Domain\Shared\Enums\OrderStatus;

    // ── Real KPI data from shipped models ──────────────────────────────
    $totalUsers       = User::count();
    $totalOrders      = Order::count();
    $totalRevenue     = (float) Payment::where('status', PaymentStatus::Completed)->sum('amount');
    $totalTransactions = Payment::count();
    $completedPayments = Payment::where('status', PaymentStatus::Completed)->count();
    $successRate      = $totalTransactions > 0
        ? round(($completedPayments / $totalTransactions) * 100, 2)
        : 0;

    // ── Tables (real data) ─────────────────────────────────────────────
    $latestUsers  = User::latest()->take(5)->get();
    $latestOrders = Order::with(['user', 'items'])->latest()->take(5)->get();

    // ── Placeholder trend deltas (no period-over-period math shipped yet)
    $trendUsers        = '+32.54%';
    $trendOrders       = '+28.16%';
    $trendRevenue      = '+42.31%';
    $trendTransactions = '+18.74%';
    $trendSuccessRate  = '+6.35%';
@endphp

<x-layouts.app>

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
            class="relative flex justify-end"
        >
            <button
                type="button"
                @click="open = !open"
                :aria-expanded="open.toString()"
                class="flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3.5 py-2 text-sm font-medium text-zinc-700 shadow-sm shadow-zinc-900/5 transition-colors hover:bg-zinc-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
            >
                <img src="{{ asset('assets/' . rawurlencode('calender.svg')) }}" alt="" class="h-4 w-4" loading="lazy">
                <span x-text="rangeLabel">{{ now()->subDays(30)->format('M j, Y') }} - {{ now()->format('M j, Y') }}</span>
                <svg class="h-3.5 w-3.5 text-zinc-400 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
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
                        <button type="button" @click="view = 'presets'" class="inline-flex items-center gap-1 text-xs font-medium text-zinc-500 hover:text-zinc-900">
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
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">

            {{-- Total Users --}}
            <div class="rounded-[20px] bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                <div class="flex items-start justify-between">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50">
                        <img src="{{ asset('assets/' . rawurlencode('trusted by millions.svg')) }}" alt="" class="h-6 w-6" loading="lazy">
                    </span>
                </div>
                <p class="mt-3 text-sm text-zinc-500">Total Users</p>
                <p class="mt-0.5 text-2xl font-bold text-zinc-900">{{ number_format($totalUsers) }}</p>
                <div class="mt-3 flex items-center justify-between text-xs">
                    <span class="text-zinc-500">Last 30 days</span>
                    <span class="font-semibold text-emerald-600">↑ {{ $trendUsers }}</span>
                </div>
            </div>

            {{-- Total Orders --}}
            <div class="rounded-[20px] bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                <div class="flex items-start justify-between">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-50">
                        <img src="{{ asset('assets/' . rawurlencode('total orders.svg')) }}" alt="" class="h-6 w-6" loading="lazy">
                    </span>
                </div>
                <p class="mt-3 text-sm text-zinc-500">Total Orders</p>
                <p class="mt-0.5 text-2xl font-bold text-zinc-900">{{ number_format($totalOrders) }}</p>
                <div class="mt-3 flex items-center justify-between text-xs">
                    <span class="text-zinc-500">Last 30 days</span>
                    <span class="font-semibold text-emerald-600">↑ {{ $trendOrders }}</span>
                </div>
            </div>

            {{-- Total Revenue --}}
            <div class="rounded-[20px] bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                <div class="flex items-start justify-between">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50">
                        <img src="{{ asset('assets/' . rawurlencode('total revenue.svg')) }}" alt="" class="h-6 w-6" loading="lazy">
                    </span>
                </div>
                <p class="mt-3 text-sm text-zinc-500">Total Revenue</p>
                <p class="mt-0.5 text-2xl font-bold text-zinc-900">${{ number_format($totalRevenue, 2) }}</p>
                <div class="mt-3 flex items-center justify-between text-xs">
                    <span class="text-zinc-500">Last 30 days</span>
                    <span class="font-semibold text-emerald-600">↑ {{ $trendRevenue }}</span>
                </div>
            </div>

            {{-- Total Transactions --}}
            <div class="rounded-[20px] bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                <div class="flex items-start justify-between">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50">
                        <img src="{{ asset('assets/' . rawurlencode('total transactions.svg')) }}" alt="" class="h-6 w-6" loading="lazy">
                    </span>
                </div>
                <p class="mt-3 text-sm text-zinc-500">Total Transactions</p>
                <p class="mt-0.5 text-2xl font-bold text-zinc-900">{{ number_format($totalTransactions) }}</p>
                <div class="mt-3 flex items-center justify-between text-xs">
                    <span class="text-zinc-500">Last 30 days</span>
                    <span class="font-semibold text-emerald-600">↑ {{ $trendTransactions }}</span>
                </div>
            </div>

            {{-- Success Rate --}}
            <div class="rounded-[20px] bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                <div class="flex items-start justify-between">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-pink-50">
                        <img src="{{ asset('assets/' . rawurlencode('Success rate.svg')) }}" alt="" class="h-6 w-6" loading="lazy">
                    </span>
                </div>
                <p class="mt-3 text-sm text-zinc-500">Success Rate</p>
                <p class="mt-0.5 text-2xl font-bold text-zinc-900">{{ $successRate }}%</p>
                <div class="mt-3 flex items-center justify-between text-xs">
                    <span class="text-zinc-500">Last 30 days</span>
                    <span class="font-semibold text-emerald-600">↑ {{ $trendSuccessRate }}</span>
                </div>
            </div>

        </div>

        {{-- ─── Charts row (placeholder UI — no aggregation endpoints yet) ─ --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

            {{-- New Users chart --}}
            <div class="rounded-[20px] bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <h2 class="text-base font-semibold text-zinc-900">New Users</h2>
                        <img src="{{ asset('assets/' . rawurlencode('info white.png')) }}" alt="" class="h-4 w-4" loading="lazy">
                    </div>
                    <button type="button" class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-2.5 py-1 text-xs font-medium text-zinc-600 transition-colors hover:bg-zinc-50">
                        6 Months
                        <svg class="h-3 w-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>

                {{-- Placeholder bar chart --}}
                <div class="mt-6 flex h-56 items-end justify-between gap-3 sm:gap-5">
                    @foreach (['Dec' => 30, 'Jan' => 75, 'Feb' => 55, 'Mar' => 100, 'Apr' => 50, 'May' => 35] as $m => $h)
                        <div class="flex flex-1 flex-col items-center gap-2">
                            <div class="w-full rounded-md {{ $m === 'Mar' ? 'bg-blue-600' : 'bg-blue-200' }}" style="height: {{ $h }}%;"></div>
                            <span class="text-xs text-zinc-500">{{ $m }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 flex items-center gap-2 text-xs text-zinc-500">
                    <span class="h-2.5 w-2.5 rounded-sm bg-blue-600"></span>
                    <span>New Registered Users</span>
                </div>
            </div>

            {{-- Revenue Overview chart --}}
            <div class="rounded-[20px] bg-white p-5 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <h2 class="text-base font-semibold text-zinc-900">Revenue Overview</h2>
                        <img src="{{ asset('assets/' . rawurlencode('info white.png')) }}" alt="" class="h-4 w-4" loading="lazy">
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
                            <svg class="h-3 w-3 text-zinc-400 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
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

                {{-- Placeholder line chart (SVG) — one line per category/service --}}
                <svg viewBox="0 0 600 240" class="mt-6 h-56 w-full" preserveAspectRatio="none">
                    {{-- Gridlines (horizontal) --}}
                    @foreach ([0, 60, 120, 180, 240] as $y)
                        <line x1="0" y1="{{ $y }}" x2="600" y2="{{ $y }}" stroke="#e4e4e7" stroke-width="1" stroke-dasharray="2,4"/>
                    @endforeach

                    {{-- Gift Cards (pink) --}}
                    <polyline points="0,180 40,165 80,155 120,140 160,150 200,135 240,130 280,120 320,115 360,105 400,95 440,85 480,75 520,70 600,60" fill="none" stroke="#ec4899" stroke-width="2"/>
                    {{-- eSIMs (blue) --}}
                    <polyline points="0,170 40,160 80,150 120,135 160,140 200,125 240,115 280,108 320,110 360,100 400,90 440,85 480,80 520,72 600,68" fill="none" stroke="#3b82f6" stroke-width="2"/>
                    {{-- Mobile Top-ups (emerald) --}}
                    <polyline points="0,200 40,185 80,180 120,165 160,170 200,160 240,150 280,145 320,140 360,135 400,125 440,120 480,118 520,110 600,105" fill="none" stroke="#10b981" stroke-width="2"/>
                    {{-- Bill Payments (amber) --}}
                    <polyline points="0,215 40,210 80,205 120,200 160,205 200,200 240,195 280,193 320,188 360,185 400,182 440,178 480,175 520,170 600,168" fill="none" stroke="#f59e0b" stroke-width="2"/>
                    {{-- Flights (red) --}}
                    <polyline points="0,190 40,175 80,170 120,160 160,165 200,155 240,140 280,135 320,130 360,118 400,110 440,100 480,95 520,82 600,75" fill="none" stroke="#ef4444" stroke-width="2"/>
                    {{-- Stays (teal) --}}
                    <polyline points="0,205 40,195 80,188 120,175 160,180 200,170 240,160 280,155 320,148 360,140 400,135 440,128 480,122 520,118 600,110" fill="none" stroke="#14b8a6" stroke-width="2"/>
                </svg>

                <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-zinc-500">
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-pink-500"></span>Gift Cards</span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-blue-500"></span>eSIMs</span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-emerald-500"></span>Mobile Top-ups</span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-amber-500"></span>Bill Payments</span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-red-500"></span>Flights</span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-teal-500"></span>Stays</span>
                </div>
            </div>

        </div>

        {{-- ─── Tables row (real data) ──────────────────────────────── --}}
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
                        <thead class="bg-zinc-50 text-[10px] uppercase tracking-wider text-zinc-500">
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
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700">
                                                {{ $user->initials() }}
                                            </span>
                                            <div class="leading-tight">
                                                <p class="text-[11px] font-semibold text-zinc-900">{{ $user->name }}</p>
                                                <p class="text-[10px] text-zinc-500">{{ $user->email }}</p>
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
                                    <td colspan="4" class="px-5 py-12 text-center text-sm text-zinc-500">No users yet.</td>
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
                        <thead class="bg-zinc-50 text-[10px] uppercase tracking-wider text-zinc-500">
                            <tr>
                                <th class="px-5 py-3 font-semibold">ID</th>
                                <th class="px-5 py-3 font-semibold">Customer</th>
                                <th class="px-5 py-3 font-semibold">Product</th>
                                <th class="px-5 py-3 font-semibold">Amount</th>
                                <th class="px-5 py-3 font-semibold">Status</th>
                                <th class="px-5 py-3 font-semibold">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @forelse ($latestOrders as $order)
                                @php
                                    $product = $order->items->first()?->product_name ?? '—';
                                    $statusValue = $order->status->value ?? 'pending';
                                    $statusClasses = match ($statusValue) {
                                        'completed' => 'bg-emerald-50 text-emerald-700',
                                        'failed', 'cancelled' => 'bg-red-50 text-red-700',
                                        'refunded' => 'bg-zinc-100 text-zinc-700',
                                        default => 'bg-amber-50 text-amber-700',
                                    };
                                @endphp
                                <tr>
                                    <td class="px-5 py-3 text-[11px] font-mono text-zinc-600">#{{ $order->order_number }}</td>
                                    <td class="px-5 py-3 text-[11px] font-medium text-zinc-900">{{ $order->user?->name ?? '—' }}</td>
                                    <td class="px-5 py-3 text-[11px] text-zinc-600">{{ $product }}</td>
                                    <td class="px-5 py-3 text-[11px] font-semibold text-zinc-900">${{ number_format((float) $order->total, 2) }}</td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClasses }}">
                                            {{ $order->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-[11px] text-zinc-600">{{ $order->created_at->format('M j, Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-12 text-center text-sm text-zinc-500">No transactions yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

</x-layouts.app>
