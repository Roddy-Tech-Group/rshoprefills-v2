<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.dashboard')] class extends Component {
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public ?string $phone = null;
    public ?string $gender = null;
    public $avatar = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->name   = $user->name;
        $this->email  = $user->email;
        $this->phone  = $user->phone;
        $this->gender = $user->gender;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id)
            ],

            // Phone: free-form for now, allows + and digits and spaces. Backend can swap in a stricter rule
            // (libphonenumber, country-aware) once a phone library ships.
            'phone'  => ['nullable', 'string', 'max:32', 'regex:/^[\d\s\+\-\(\)]+$/'],

            // Gender enum stored as a short string. Add new values to the migration column and the union here.
            'gender' => ['nullable', 'in:male,female,other'],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Upload a new avatar. Validates the image, stores it on the public disk
     * under avatars/, and writes the public URL onto the user's avatar_url.
     */
    public function updateAvatar(): void
    {
        $this->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
        ]);

        $user = Auth::user();

        // Clean up the previous uploaded avatar (skip external URLs like Google).
        if ($user->avatar_url && str_starts_with($user->avatar_url, '/storage/avatars/')) {
            $previous = str_replace('/storage/', '', $user->avatar_url);
            Storage::disk('public')->delete($previous);
        }

        $path = $this->avatar->store('avatars/'.$user->id, 'public');
        $user->update(['avatar_url' => Storage::url($path)]);

        $this->reset('avatar');
        $this->dispatch('avatar-updated');
    }

    /**
     * Remove the current avatar, falling back to the default icon.
     */
    public function removeAvatar(): void
    {
        $user = Auth::user();

        if ($user->avatar_url && str_starts_with($user->avatar_url, '/storage/avatars/')) {
            $previous = str_replace('/storage/', '', $user->avatar_url);
            Storage::disk('public')->delete($previous);
        }

        $user->update(['avatar_url' => null]);

        $this->dispatch('avatar-updated');
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

@php
    $authUser = auth()->user();
    $hasAvatar = !empty($authUser?->avatar_url);
    $emailVerified = (bool) $authUser?->email_verified_at;
    // 2FA + identity verification, last login — backend hooks not shipped yet, flag for later wiring.
    $twoFactorEnabled = false;
    $identityVerified = false;
    $googleConnected = !empty($authUser?->google_id);
    $lastLoginAt = $authUser?->updated_at;
    $firstName = $authUser?->name ? str($authUser->name)->before(' ') : 'there';
    // Gender-aware default avatar. Resolves on every page render so changing gender in settings
    // updates every place this is read.
    $defaultAvatar = asset('assets/' . rawurlencode(match (strtolower($authUser?->gender ?? '')) {
        'female', 'f' => 'New Female Account Avatar.png',
        default       => 'New male account avatar.png',
    }));
@endphp

<div
    x-data="{
        editingProfile: false,
        uploading: false,
        notifications: {
            orderUpdates: true,
            promotions: false,
            securityAlerts: true,
        },
        country: 'Cameroon',
        countryFlag: '🇨🇲',
        language: 'English',
        gender: @js($authUser?->gender ?? ''),
        genderMenuOpen: false,
        loaded: false,
        pickGender(g) { this.gender = g; this.genderMenuOpen = false; },
        init() {
            this.$nextTick(() => { this.loaded = true; });
        }
    }"
    x-on:locale-updated.window="country = $event.detail.country; countryFlag = $event.detail.countryFlag; language = $event.detail.language"
    x-on:livewire-upload-start="uploading = true"
    x-on:livewire-upload-finish="uploading = false"
    x-on:livewire-upload-error="uploading = false"
    x-on:profile-updated.window="editingProfile = false"
    class="mx-auto flex max-w-3xl flex-col gap-6 pb-4"
>
    {{-- Desktop heading. On mobile the layout's sticky top bar shows the title, so this stays hidden there. --}}
    <div class="hidden items-center justify-between gap-3 lg:flex">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-black">Settings</h1>
            <p class="mt-1 text-[13px] text-zinc-600">Manage your account, security and preferences.</p>
        </div>
        <x-action-message on="profile-updated" class="text-xs font-medium text-emerald-600">
            Saved
        </x-action-message>
    </div>

    {{-- ─── Section 1: Personal Information ─── --}}
    <section
        x-data="{ navigating: false }"
        x-on:livewire:navigate.window="navigating = true"
        x-on:livewire:navigated.window="navigating = false"
        class="relative"
    >
        {{-- Skeleton overlay shown during wire:navigate page transitions — cascading reveal --}}
        <div x-show="navigating" x-cloak class="skeleton-stagger absolute inset-0 z-10 bg-[#eff6ff]" aria-hidden="true">
            <x-skeleton class="h-5 w-44" style="--i: 0" />
            <div class="mt-2.5 overflow-hidden rounded-3xl bg-white shadow-sm shadow-zinc-900/[0.04] ring-1 ring-zinc-100" style="--i: 1">
                <div class="flex items-center gap-4 p-6">
                    <x-skeleton shape="circle" class="h-20 w-20" />
                    <div class="flex-1 space-y-2">
                        <x-skeleton class="h-4 w-32" />
                        <x-skeleton class="h-3 w-48" />
                    </div>
                </div>
                <div class="skeleton-stagger-fast divide-y divide-zinc-100 border-t border-zinc-100">
                    @for ($i = 0; $i < 4; $i++)
                        <div class="flex items-center justify-between px-6 py-3.5" style="--i: {{ $i }}">
                            <div class="space-y-1.5">
                                <x-skeleton class="h-2.5 w-20" />
                                <x-skeleton class="h-3.5 w-36" />
                            </div>
                            <x-skeleton class="h-4 w-12 rounded-[5px]" />
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        <div class="hidden mb-2.5 items-center justify-between lg:flex">
            <h2 class="text-base font-bold text-zinc-900">Personal Information</h2>
        </div>

        <div class="overflow-hidden rounded-3xl bg-white shadow-sm shadow-zinc-900/[0.04] ring-1 ring-zinc-100">
            {{-- Hero strip: avatar + name/email + edit toggle --}}
            <div class="relative px-5 pt-6 pb-5 sm:px-6">
                <div class="flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:gap-5">
                    {{-- Avatar with subtle ring + upload --}}
                    <div class="relative">
                        <span class="relative flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-full bg-blue-50 ring-4 ring-white shadow-sm shadow-blue-600/10">
                            @if ($avatar)
                                <img src="{{ $avatar->temporaryUrl() }}" alt="" class="h-full w-full object-cover">
                            @else
                                <img src="{{ $hasAvatar ? $authUser->avatar_url : $defaultAvatar }}" alt="{{ $authUser?->name ?? 'Account' }}" class="h-full w-full object-cover">
                            @endif

                            <span x-show="uploading" x-cloak class="absolute inset-0 flex items-center justify-center bg-white/75 backdrop-blur-sm">
                                <svg class="h-6 w-6 animate-spin text-blue-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="50 50" stroke-linecap="round"/>
                                </svg>
                            </span>
                        </span>

                        @if (!$avatar)
                            <label for="avatar-input" class="absolute -bottom-1 -right-1 flex h-7 w-7 cursor-pointer items-center justify-center rounded-full bg-blue-600 text-white shadow-sm shadow-blue-600/30 ring-2 ring-white transition-transform hover:scale-105 active:scale-95" aria-label="Upload profile photo">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316zM16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/>
                                </svg>
                            </label>
                        @endif
                        <input wire:model="avatar" id="avatar-input" type="file" accept="image/png,image/jpeg,image/webp,image/gif" class="hidden">
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-lg font-bold text-black">{{ $authUser?->name ?? 'Account holder' }}</p>
                        <p class="mt-0.5 flex items-center gap-1.5 truncate text-[13px] text-zinc-600">
                            <span class="truncate">{{ $authUser?->email ?? '—' }}</span>
                            @if ($emailVerified)
                                <span class="inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white" title="Email verified">
                                    <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                    </svg>
                                </span>
                            @endif
                        </p>

                        {{-- Avatar save/cancel + upload error --}}
                        @if ($avatar)
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <button type="button" wire:click="updateAvatar" class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white transition-all hover:bg-blue-700 active:scale-95">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                    </svg>
                                    Save photo
                                </button>
                                <button type="button" wire:click="$set('avatar', null)" class="rounded-xl border border-zinc-200 bg-white px-3 py-1.5 text-xs font-semibold text-zinc-700 transition-colors hover:bg-zinc-50">Cancel</button>
                            </div>
                        @elseif ($hasAvatar)
                            <button type="button" wire:click="removeAvatar" class="mt-2 text-xs font-medium text-red-600 transition-colors hover:text-red-700">Remove photo</button>
                        @endif
                    </div>

                    {{-- Edit profile button --}}
                    <button
                        type="button"
                        @click="editingProfile = !editingProfile"
                        class="hidden shrink-0 items-center gap-1.5 self-start rounded-xl border border-zinc-200 bg-white px-3.5 py-2 text-xs font-semibold text-zinc-700 shadow-sm transition-all hover:border-blue-600 hover:bg-blue-50 hover:text-blue-700 active:scale-95 sm:inline-flex"
                        :class="editingProfile && 'border-blue-600 bg-blue-50 text-blue-700'"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M12 18.75h7.5"/>
                        </svg>
                        <span x-text="editingProfile ? 'Done' : 'Edit profile'">Edit profile</span>
                    </button>
                </div>
                @error('avatar') <p class="mt-3 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Detail rows --}}
            <div class="divide-y divide-zinc-100 border-t border-zinc-100">
                {{-- Full name --}}
                <div class="flex items-center justify-between gap-3 px-5 py-3.5 sm:px-6">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-600">Full name</p>
                        <p class="mt-0.5 truncate text-sm font-medium text-black">{{ $authUser?->name ?? '—' }}</p>
                    </div>
                    <button type="button" @click="editingProfile = true" class="rounded-lg p-1.5 text-zinc-600 transition-colors hover:bg-blue-50 hover:text-blue-600" aria-label="Edit full name">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                        </svg>
                    </button>
                </div>

                {{-- Email --}}
                <div class="flex items-center justify-between gap-3 px-5 py-3.5 sm:px-6">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-600">Email address</p>
                        <p class="mt-0.5 truncate text-sm font-medium text-black">{{ $authUser?->email ?? '—' }}</p>
                    </div>
                    @if ($emailVerified)
                        <span class="inline-flex items-center gap-1 rounded-[5px] bg-emerald-500 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">Verified</span>
                    @else
                        <button wire:click="resendVerificationNotification" class="rounded-[5px] bg-amber-500 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white transition-colors hover:bg-amber-600">Verify</button>
                    @endif
                </div>

                {{-- Phone --}}
                <button type="button" @click="editingProfile = true" class="flex w-full items-center justify-between gap-3 px-5 py-3.5 text-left sm:px-6">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-600">Phone number</p>
                        @if (! empty($phone))
                            <p class="mt-0.5 text-sm font-medium text-black">{{ $phone }}</p>
                        @else
                            <p class="mt-0.5 text-[13px] font-medium text-zinc-600 italic">Not set</p>
                        @endif
                    </div>
                    <span class="rounded-lg p-1.5 text-zinc-600" aria-label="Edit phone">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                        </svg>
                    </span>
                </button>

                {{-- Gender --}}
                <button type="button" @click="editingProfile = true" class="flex w-full items-center justify-between gap-3 px-5 py-3.5 text-left sm:px-6">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-600">Gender</p>
                        @if (! empty($gender))
                            <p class="mt-0.5 text-sm font-medium text-black">{{ ucfirst($gender) }}</p>
                        @else
                            <p class="mt-0.5 text-[13px] font-medium text-zinc-600 italic">Not set</p>
                        @endif
                    </div>
                    <span class="rounded-lg p-1.5 text-zinc-600" aria-label="Edit gender">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                        </svg>
                    </span>
                </button>

                {{-- Country — clickable row that opens the shared locale modal. Display syncs via 'locale-updated' window event. --}}
                <button type="button" @click="$dispatch('open-locale-modal')" class="flex w-full items-center justify-between gap-3 px-5 py-3.5 text-left sm:px-6">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-600">Country</p>
                        <p class="mt-0.5 flex items-center gap-2 text-sm font-medium text-black">
                            <span class="text-base leading-none" x-text="countryFlag">🇨🇲</span>
                            <span x-text="country">Cameroon</span>
                        </p>
                    </div>
                    <span class="rounded-lg p-1.5 text-zinc-600" aria-label="Change country">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                        </svg>
                    </span>
                </button>
            </div>

            {{-- Mobile edit button (full width at bottom of card on small screens) --}}
            <div class="border-t border-zinc-100 p-4 sm:hidden">
                <button
                    type="button"
                    @click="editingProfile = !editingProfile"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-blue-600/25 transition-all hover:bg-blue-700 active:scale-[0.98]"
                    :class="editingProfile && '!bg-blue-700'"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M12 18.75h7.5"/>
                    </svg>
                    <span x-text="editingProfile ? 'Close editor' : 'Edit profile'">Edit profile</span>
                </button>
            </div>

            {{-- Inline edit form (slides down) --}}
            <div
                x-show="editingProfile"
                x-cloak
                x-collapse
                class="border-t border-zinc-100 bg-zinc-50/60"
            >
                <form wire:submit="updateProfileInformation" class="space-y-4 p-5 sm:p-6">
                    <div>
                        <label for="profile-name" class="mb-1.5 block text-xs font-semibold text-zinc-700">Full name</label>
                        <input
                            wire:model="name"
                            id="profile-name"
                            type="text"
                            required
                            class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-2.5 text-sm text-black outline-none transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15"
                            placeholder="Your full name"
                        />
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="profile-email" class="mb-1.5 block text-xs font-semibold text-zinc-700">Email address</label>
                        <input
                            wire:model="email"
                            id="profile-email"
                            type="email"
                            required
                            class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-2.5 text-sm text-black outline-none transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15"
                            placeholder="you@example.com"
                        />
                        @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="profile-phone" class="mb-1.5 block text-xs font-semibold text-zinc-700">Phone number</label>
                        <input
                            wire:model="phone"
                            id="profile-phone"
                            type="tel"
                            inputmode="tel"
                            autocomplete="tel"
                            class="w-full rounded-xl border border-zinc-300 bg-white px-3.5 py-2.5 text-sm text-black outline-none transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15"
                            placeholder="+237 6XX XXX XXX"
                        />
                        @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <span class="mb-1.5 block text-xs font-semibold text-zinc-700">Gender</span>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach ([
                                ['value' => 'male',   'label' => 'Male',   'icon' => 'male.svg'],
                                ['value' => 'female', 'label' => 'Female', 'icon' => 'female.svg'],
                                ['value' => 'other',  'label' => 'Other',  'icon' => 'other.svg'],
                            ] as $opt)
                                <button
                                    type="button"
                                    wire:click="$set('gender', '{{ $opt['value'] }}')"
                                    class="flex flex-col items-center gap-1 rounded-xl border px-2 py-2.5 text-xs font-semibold transition-all active:scale-95 {{ $gender === $opt['value'] ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-zinc-300 bg-white text-zinc-700 hover:border-zinc-400' }}"
                                    aria-pressed="{{ $gender === $opt['value'] ? 'true' : 'false' }}"
                                >
                                    <img src="{{ asset('assets/' . $opt['icon']) }}" alt="" class="h-5 w-5" loading="lazy">
                                    {{ $opt['label'] }}
                                </button>
                            @endforeach
                        </div>
                        @error('gender') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-blue-600/25 transition-all hover:bg-blue-700 active:scale-[0.98]">
                            <span wire:loading.remove wire:target="updateProfileInformation">Save changes</span>
                            <span wire:loading wire:target="updateProfileInformation" class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="50 50" stroke-linecap="round"/>
                                </svg>
                                Saving
                            </span>
                        </button>
                        <button type="button" @click="editingProfile = false" class="rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-sm font-semibold text-zinc-700 transition-colors hover:bg-zinc-50">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    {{-- ─── Section 2: Security ─── --}}
    <section>
        <h2 class="mb-2.5 text-base font-bold text-zinc-900">Security</h2>

        <div class="flex flex-col gap-3">
            {{-- Change password card --}}
            <a href="{{ route('dashboard.password') }}" wire:navigate class="relative flex items-center gap-4 overflow-hidden rounded-2xl bg-white p-4 shadow-sm shadow-zinc-900/[0.04] ring-1 ring-zinc-100 sm:p-5">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-zinc-800 text-white">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                    </svg>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-black">Password</p>
                    <p class="mt-0.5 text-[13px] text-zinc-600">Change your account password</p>
                </div>
                <svg class="h-5 w-5 shrink-0 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
            </a>

            {{-- Google connection card --}}
            <div class="flex items-center gap-4 rounded-2xl bg-white p-4 shadow-sm shadow-zinc-900/[0.04] ring-1 ring-zinc-100 sm:p-5">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white ring-1 ring-zinc-200">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.76h3.56c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.56-2.76c-.98.66-2.24 1.06-3.72 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A11 11 0 0012 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.11A6.61 6.61 0 015.5 12c0-.73.13-1.44.34-2.11V7.05H2.18A11 11 0 001 12c0 1.78.43 3.46 1.18 4.95l3.66-2.84z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.05l3.66 2.84C6.71 7.31 9.14 5.38 12 5.38z"/>
                    </svg>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-black">Google account</p>
                    <p class="mt-0.5 text-[13px] text-zinc-600">
                        @if ($googleConnected)
                            Connected to {{ $authUser?->email }}
                        @else
                            Sign in faster by linking Google
                        @endif
                    </p>
                </div>
                @if ($googleConnected)
                    <span class="inline-flex items-center gap-1 rounded-[5px] bg-emerald-500 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">
                        <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                        Connected
                    </span>
                @else
                    <a href="{{ route('auth.google.redirect') }}" class="rounded-xl border border-zinc-200 bg-white px-3 py-1.5 text-xs font-semibold text-zinc-700 transition-colors hover:bg-zinc-50">Connect</a>
                @endif
            </div>

            {{-- Last login activity (small text) --}}
            <div class="flex items-center gap-2.5 px-1 text-[13px] text-zinc-600">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Last active {{ $lastLoginAt?->diffForHumans() ?? 'recently' }} on this device
            </div>
        </div>
    </section>

    {{-- ─── Section 3: Appearance ─── --}}
    <section>
        <h2 class="mb-2.5 text-base font-bold text-zinc-900">Appearance</h2>

        <div class="rounded-2xl bg-white p-4 shadow-sm shadow-zinc-900/[0.04] ring-1 ring-zinc-100 sm:p-5">
            <div class="mb-4 flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-zinc-800 text-white">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.61-1.611l-5.815 3.875a15.994 15.994 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42"/>
                    </svg>
                </span>
                <div>
                    <p class="text-sm font-semibold text-black">Theme</p>
                    <p class="mt-0.5 text-[13px] text-zinc-600">Pick how RshopRefills looks for you</p>
                </div>
            </div>

            {{-- Modern segmented selector — pure Alpine for a polished feel that stays in light mode --}}
            @php
                $themeOptions = [
                    ['value' => 'light',  'label' => 'Light',  'path' => 'M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z'],
                    ['value' => 'dark',   'label' => 'Dark',   'path' => 'M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z'],
                    ['value' => 'system', 'label' => 'System', 'path' => 'M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25'],
                ];
            @endphp

            <div x-data="{ theme: localStorage.getItem('theme') || 'light' }" x-init="$watch('theme', v => localStorage.setItem('theme', v))" class="grid grid-cols-3 gap-2 rounded-2xl bg-zinc-100 p-1">
                @foreach ($themeOptions as $opt)
                    <button
                        type="button"
                        @click="theme = '{{ $opt['value'] }}'"
                        :class="theme === '{{ $opt['value'] }}' ? 'bg-white text-black shadow-sm ring-1 ring-zinc-200' : 'text-zinc-600 hover:bg-white/70 hover:text-black hover:shadow-sm'"
                        class="flex flex-col items-center gap-1.5 rounded-xl px-2 py-2.5 text-xs font-semibold transition-all duration-200 active:scale-95"
                        :aria-pressed="theme === '{{ $opt['value'] }}'"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $opt['path'] }}"/>
                        </svg>
                        {{ $opt['label'] }}
                    </button>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ─── Section 4: Notifications ─── --}}
    <section>
        <h2 class="mb-2.5 text-base font-bold text-zinc-900">Notifications</h2>

        <div class="divide-y divide-zinc-100 overflow-hidden rounded-2xl bg-white shadow-sm shadow-zinc-900/[0.04] ring-1 ring-zinc-100">
            {{-- Order updates --}}
            <label class="flex cursor-pointer items-center justify-between gap-3 px-5 py-4 sm:px-6">
                <span class="flex items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-zinc-800 text-white">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z"/>
                        </svg>
                    </span>
                    <span>
                        <span class="block text-sm font-semibold text-black">Order updates</span>
                        <span class="block text-[13px] text-zinc-600">Delivery, refunds and receipts</span>
                    </span>
                </span>
                <button
                    type="button"
                    role="switch"
                    @click="notifications.orderUpdates = !notifications.orderUpdates"
                    :aria-checked="notifications.orderUpdates"
                    :class="notifications.orderUpdates ? 'bg-blue-600' : 'bg-zinc-200'"
                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors duration-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                >
                    <span :class="notifications.orderUpdates ? 'translate-x-5' : 'translate-x-0.5'" class="pointer-events-none absolute top-0.5 inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition-transform duration-300"></span>
                </button>
            </label>

            {{-- Promotions --}}
            <label class="flex cursor-pointer items-center justify-between gap-3 px-5 py-4 sm:px-6">
                <span class="flex items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-zinc-800 text-white">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/>
                        </svg>
                    </span>
                    <span>
                        <span class="block text-sm font-semibold text-black">Promotions</span>
                        <span class="block text-[13px] text-zinc-600">Deals, offers and new arrivals</span>
                    </span>
                </span>
                <button
                    type="button"
                    role="switch"
                    @click="notifications.promotions = !notifications.promotions"
                    :aria-checked="notifications.promotions"
                    :class="notifications.promotions ? 'bg-blue-600' : 'bg-zinc-200'"
                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors duration-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                >
                    <span :class="notifications.promotions ? 'translate-x-5' : 'translate-x-0.5'" class="pointer-events-none absolute top-0.5 inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition-transform duration-300"></span>
                </button>
            </label>

            {{-- Security alerts --}}
            <label class="flex cursor-pointer items-center justify-between gap-3 px-5 py-4 sm:px-6">
                <span class="flex items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-zinc-800 text-white">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/>
                        </svg>
                    </span>
                    <span>
                        <span class="block text-sm font-semibold text-black">Security alerts</span>
                        <span class="block text-[13px] text-zinc-600">Login attempts and account changes</span>
                    </span>
                </span>
                <button
                    type="button"
                    role="switch"
                    @click="notifications.securityAlerts = !notifications.securityAlerts"
                    :aria-checked="notifications.securityAlerts"
                    :class="notifications.securityAlerts ? 'bg-blue-600' : 'bg-zinc-200'"
                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors duration-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                >
                    <span :class="notifications.securityAlerts ? 'translate-x-5' : 'translate-x-0.5'" class="pointer-events-none absolute top-0.5 inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition-transform duration-300"></span>
                </button>
            </label>
        </div>

        <p class="mt-2.5 px-1 text-[13px] text-zinc-600">Notification delivery channels will go live when the inbox ships.</p>
    </section>

    {{-- ─── Section 5: Danger Zone ─── --}}
    <section class="mt-2">
        <h2 class="mb-2.5 text-base font-bold text-red-600">Danger zone</h2>

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm shadow-zinc-900/[0.04] ring-1 ring-zinc-100">
            {{-- Logout --}}
            <form method="POST" action="{{ route('logout') }}" class="border-b border-zinc-100">
                @csrf
                <button
                    type="submit"
                    class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left sm:px-6"
                >
                    <span class="flex items-center gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-zinc-800 text-white">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                            </svg>
                        </span>
                        <span>
                            <span class="block text-sm font-semibold text-black">Log out</span>
                            <span class="block text-[13px] text-zinc-600">End your session on this device</span>
                        </span>
                    </span>
                    <svg class="h-5 w-5 shrink-0 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                    </svg>
                </button>
            </form>

            {{-- Delete account — subtle entry point, opens the existing modal flow --}}
            <div class="px-5 py-4 sm:px-6">
                <livewire:settings.delete-user-form />
            </div>
        </div>
    </section>
</div>
