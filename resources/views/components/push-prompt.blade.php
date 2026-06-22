@feature('push_notifications')
<div
    x-data="{
        show: false,
        init() {
            // Check if push is supported at all
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
            
            // If they already allowed or denied it, don't prompt.
            if (Notification.permission === 'granted' || Notification.permission === 'denied') return;
            
            // Check if dismissed recently (cooldown: 7 days)
            const dismissedAt = localStorage.getItem('pushPromptDismissedAt');
            if (dismissedAt) {
                const daysSince = (Date.now() - parseInt(dismissedAt)) / (1000 * 60 * 60 * 24);
                if (daysSince < 7) return;
            }

            // Wait 5 seconds after load so we don't bombard them instantly
            setTimeout(() => {
                this.show = true;
            }, 5000);
        },
        enable() {
            this.show = false;
            // The push-manager.js handles the actual VAPID key fetching and subscription
            if (window.PushManagerApp) {
                window.PushManagerApp.subscribe();
            }
        },
        dismiss() {
            this.show = false;
            localStorage.setItem('pushPromptDismissedAt', Date.now().toString());
        }
    }"
    x-show="show"
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
    x-transition:leave-end="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
    class="fixed bottom-4 left-4 right-4 z-[90] sm:left-auto sm:right-6 sm:bottom-6 sm:max-w-sm"
>
    <div class="rounded-2xl bg-white p-5 shadow-2xl ring-1 ring-zinc-900/10 dark:bg-[#1d3252] dark:ring-white/10">
        <div class="flex items-start gap-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-50 dark:bg-blue-500/15">
                <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>
            </div>
            <div class="pt-0.5">
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Enable Notifications</h3>
                <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-300">
                    Get instant updates on your orders, exclusive rewards, and flash sales!
                </p>
                <div class="mt-4 flex gap-3">
                    <button @click="enable" type="button" class="inline-flex items-center justify-center rounded-[10px] bg-blue-600 px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                        Allow
                    </button>
                    <button @click="dismiss" type="button" class="inline-flex items-center justify-center rounded-[10px] bg-zinc-100 px-4 py-2 text-xs font-semibold text-zinc-700 transition-colors hover:bg-zinc-200 focus:outline-none focus:ring-2 focus:ring-zinc-500/40 dark:bg-[#0c1a36] dark:text-zinc-300 dark:hover:bg-[#070f1c]">
                        Not Now
                    </button>
                </div>
            </div>
            <button @click="dismiss" type="button" class="absolute right-3 top-3 rounded-full p-1 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-500 dark:hover:bg-white/10 dark:hover:text-zinc-300">
                <span class="sr-only">Close</span>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
</div>
@endfeature
