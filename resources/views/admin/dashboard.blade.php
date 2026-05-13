<x-layouts.admin>
    <x-slot:title>
        Admin Dashboard
    </x-slot:title>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-zinc-900">Dashboard</h1>
        <p class="mt-1 text-sm text-zinc-500">Welcome back, {{ Auth::guard('admin')->user()->name }}.</p>
    </div>

    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Placeholder cards --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-medium text-zinc-500">Total Users</h3>
            <p class="mt-2 text-3xl font-bold text-zinc-900">0</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-medium text-zinc-500">Total Orders</h3>
            <p class="mt-2 text-3xl font-bold text-zinc-900">0</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-medium text-zinc-500">Revenue</h3>
            <p class="mt-2 text-3xl font-bold text-zinc-900">$0.00</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-medium text-zinc-500">Active Sessions</h3>
            <p class="mt-2 text-3xl font-bold text-zinc-900">1</p>
        </div>
    </div>
</x-layouts.admin>
