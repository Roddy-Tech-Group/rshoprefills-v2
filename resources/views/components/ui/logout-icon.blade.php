{{--
    Global logout icon. Renders the Logout.svg asset as a CSS mask so it can be
    coloured with normal Tailwind classes (red in light mode, a lighter red in
    dark mode) - far more reliable than a brightness/invert filter, which washed
    out in dark mode. Size + colour via the class attribute, e.g.
    <x-ui.logout-icon class="h-5 w-5" />.
--}}
<span
    {{ $attributes->merge(['class' => 'inline-block h-5 w-5 shrink-0 bg-red-600 dark:bg-red-400']) }}
    style="-webkit-mask: url('{{ asset('assets/Logout.svg') }}') center / contain no-repeat; mask: url('{{ asset('assets/Logout.svg') }}') center / contain no-repeat;"
    aria-hidden="true"
></span>
