{{-- resources/views/components/admin/page-header.blade.php --}}
@props(['title'])

<div class="flex flex-wrap items-center justify-between gap-4 mb-7">
    <h1 class="font-display text-2xl font-extrabold tracking-tight text-hub-purple">{{ $title }}</h1>
    @if($slot->isNotEmpty())
        <div class="flex flex-wrap items-center gap-3">{{ $slot }}</div>
    @endif
</div>
