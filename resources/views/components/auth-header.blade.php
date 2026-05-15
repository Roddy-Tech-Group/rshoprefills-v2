@props([
    'title',
    'description',
])

<div class="flex w-full flex-col gap-2 text-center">
    <h1 class="text-xl font-medium dark:text-zinc-600">{{ $title }}</h1>
    <p class="text-center text-base dark:text-zinc-600">{{ $description }}</p>
</div>
