<?php

use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new
#[Layout('components.layouts.admin')]
#[Title('Account Information')]
class extends Component {
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public $avatar = null;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(): void
    {
        $this->name = $this->admin->name;
        $this->email = $this->admin->email;
    }

    /**
     * The authenticated administrator. Resolved through the `admin` guard so
     * this never picks up a customer (web guard) session by mistake.
     */
    #[Computed]
    public function admin(): Admin
    {
        return Auth::guard('admin')->user();
    }

    /**
     * Update the admin's name and email.
     */
    public function updateProfileInformation(): void
    {
        $admin = $this->admin;

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('admins', 'email')->ignore($admin->id),
            ],
        ]);

        $admin->update($validated);

        $this->dispatch('profile-updated');
    }

    /**
     * Upload a new avatar. Stores it on the public disk under admin-avatars/
     * and writes the public URL onto the admin's avatar_url.
     */
    public function updateAvatar(): void
    {
        $this->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
        ]);

        $admin = $this->admin;

        // Clean up a previously uploaded avatar (skip external URLs like an OAuth picture).
        if ($admin->avatar_url && str_starts_with($admin->avatar_url, '/storage/admin-avatars/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $admin->avatar_url));
        }

        $path = $this->avatar->store('admin-avatars/'.$admin->id, 'public');
        $admin->update(['avatar_url' => Storage::url($path)]);

        $this->reset('avatar');
        $this->dispatch('avatar-updated');
    }

    /**
     * Update the admin's password. current_password is validated against the
     * `admin` guard so a customer password can never satisfy it.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password:admin'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        $this->admin->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

@php
    $field = 'w-full rounded-[10px] border border-zinc-300 bg-white px-3.5 py-2.5 text-sm text-black placeholder:text-zinc-600 outline-none transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15';
    $defaultAvatar = asset('assets/'.rawurlencode('New male account avatar.png'));
@endphp

<div>
    <x-slot:heading>Account Information</x-slot:heading>
    <x-slot:subheading>Manage your admin profile, photo and password.</x-slot:subheading>

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6">

        {{-- Profile --}}
        <div class="rounded-[10px] bg-white p-6 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
            <h2 class="text-base font-semibold text-zinc-900">Profile</h2>
            <p class="mt-0.5 text-xs text-zinc-600">Your name, photo and the email you sign in with.</p>

            {{-- Avatar --}}
            <div class="mt-5 flex items-center gap-5">
                <img
                    src="{{ $avatar ? $avatar->temporaryUrl() : ($this->admin->avatar_url ?: $defaultAvatar) }}"
                    alt="{{ $this->admin->name }}"
                    class="h-20 w-20 shrink-0 rounded-[10px] object-cover ring-1 ring-zinc-200"
                >
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-[10px] border border-zinc-300 bg-white px-3.5 py-2 text-sm font-medium text-zinc-700 transition-colors hover:bg-blue-100">
                            <input type="file" class="hidden" wire:model="avatar" accept="image/*">
                            <span>Change photo</span>
                        </label>
                        @if ($avatar)
                            <button
                                type="button"
                                wire:click="updateAvatar"
                                wire:loading.attr="disabled"
                                wire:target="updateAvatar"
                                class="inline-flex items-center gap-2 rounded-[10px] bg-blue-600 px-3.5 py-2 text-sm font-semibold text-white transition-colors hover:bg-blue-700"
                            >
                                <span wire:loading.remove wire:target="updateAvatar">Save photo</span>
                                <span wire:loading wire:target="updateAvatar">Saving...</span>
                            </button>
                            <button type="button" wire:click="$set('avatar', null)" class="text-sm font-medium text-zinc-600 transition-colors hover:text-zinc-900">
                                Cancel
                            </button>
                        @endif
                    </div>
                    <p class="mt-1.5 text-xs text-zinc-600">JPG, PNG, WEBP or GIF. Up to 2MB.</p>
                    @error('avatar') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    <x-action-message on="avatar-updated" class="mt-1 block text-xs font-medium text-emerald-600">Photo updated.</x-action-message>
                </div>
            </div>

            {{-- Name + email --}}
            <form wire:submit="updateProfileInformation" class="mt-6 space-y-5">
                <div>
                    <label for="acc-name" class="mb-1.5 block text-sm font-medium text-zinc-700">Full name</label>
                    <input wire:model="name" id="acc-name" type="text" required autocomplete="name" class="{{ $field }}">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="acc-email" class="mb-1.5 block text-sm font-medium text-zinc-700">Email address</label>
                    <input wire:model="email" id="acc-email" type="email" required autocomplete="email" class="{{ $field }}">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center gap-3 pt-1">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-[10px] bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40">
                        Save changes
                    </button>
                    <x-action-message on="profile-updated" class="text-sm font-medium text-emerald-600">Saved.</x-action-message>
                </div>
            </form>
        </div>

        {{-- Account details (read-only) --}}
        <div class="rounded-[10px] bg-white p-6 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
            <h2 class="text-base font-semibold text-zinc-900">Account details</h2>
            <p class="mt-0.5 text-xs text-zinc-600">Role and status are managed by a super admin.</p>

            <dl class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium text-zinc-600">Role</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center rounded-[5px] bg-blue-600 px-2 py-0.5 text-xs font-bold text-white">{{ $this->admin->role->label() }}</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-zinc-600">Status</dt>
                    <dd class="mt-1">
                        @if ($this->admin->is_active)
                            <span class="inline-flex items-center rounded-[5px] bg-emerald-500 px-2 py-0.5 text-xs font-bold text-white">Active</span>
                        @else
                            <span class="inline-flex items-center rounded-[5px] bg-red-500 px-2 py-0.5 text-xs font-bold text-white">Inactive</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-zinc-600">Last login</dt>
                    <dd class="mt-1 text-sm font-semibold text-zinc-900">{{ $this->admin->last_login_at?->format('M j, Y g:i A') ?? 'Never' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-zinc-600">Member since</dt>
                    <dd class="mt-1 text-sm font-semibold text-zinc-900">{{ $this->admin->created_at?->format('M j, Y') ?? '' }}</dd>
                </div>
            </dl>
        </div>

        {{-- Security --}}
        <div class="rounded-[10px] bg-white p-6 shadow-sm shadow-zinc-900/5 ring-1 ring-zinc-100">
            <h2 class="text-base font-semibold text-zinc-900">Security</h2>
            <p class="mt-0.5 text-xs text-zinc-600">Use a long, random password to keep the admin panel secure.</p>

            <form wire:submit="updatePassword" class="mt-5 space-y-5">
                {{-- Current password --}}
                <div x-data="{ show: false }">
                    <label for="acc-current-password" class="mb-1.5 block text-sm font-medium text-zinc-700">Current password</label>
                    <div class="relative">
                        <input
                            wire:model="current_password"
                            id="acc-current-password"
                            :type="show ? 'text' : 'password'"
                            required
                            autocomplete="current-password"
                            placeholder="Enter your current password"
                            class="{{ $field }} pr-11"
                        />
                        <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-500 transition-colors hover:text-zinc-700" :aria-label="show ? 'Hide password' : 'Show password'">
                            <svg x-show="!show" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <svg x-show="show" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true" style="display:none;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.244 7.244L19.5 19.5m-2.876-2.876L13.875 13.875M9.878 9.878a3 3 0 105.249 5.249"/>
                            </svg>
                        </button>
                    </div>
                    @error('current_password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- New password --}}
                <div x-data="{ show: false }">
                    <label for="acc-new-password" class="mb-1.5 block text-sm font-medium text-zinc-700">New password</label>
                    <div class="relative">
                        <input
                            wire:model="password"
                            id="acc-new-password"
                            :type="show ? 'text' : 'password'"
                            required
                            autocomplete="new-password"
                            placeholder="At least 8 characters"
                            class="{{ $field }} pr-11"
                        />
                        <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-500 transition-colors hover:text-zinc-700" :aria-label="show ? 'Hide password' : 'Show password'">
                            <svg x-show="!show" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <svg x-show="show" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true" style="display:none;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.244 7.244L19.5 19.5m-2.876-2.876L13.875 13.875M9.878 9.878a3 3 0 105.249 5.249"/>
                            </svg>
                        </button>
                    </div>
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Confirm new password --}}
                <div x-data="{ show: false }">
                    <label for="acc-confirm-password" class="mb-1.5 block text-sm font-medium text-zinc-700">Confirm new password</label>
                    <div class="relative">
                        <input
                            wire:model="password_confirmation"
                            id="acc-confirm-password"
                            :type="show ? 'text' : 'password'"
                            required
                            autocomplete="new-password"
                            placeholder="Re-enter your new password"
                            class="{{ $field }} pr-11"
                        />
                        <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-500 transition-colors hover:text-zinc-700" :aria-label="show ? 'Hide password' : 'Show password'">
                            <svg x-show="!show" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <svg x-show="show" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true" style="display:none;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.244 7.244L19.5 19.5m-2.876-2.876L13.875 13.875M9.878 9.878a3 3 0 105.249 5.249"/>
                            </svg>
                        </button>
                    </div>
                    @error('password_confirmation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3 pt-1">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-[10px] bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40">
                        Update password
                    </button>
                    <x-action-message on="password-updated" class="text-sm font-medium text-emerald-600">Saved.</x-action-message>
                </div>
            </form>
        </div>

    </div>
</div>
