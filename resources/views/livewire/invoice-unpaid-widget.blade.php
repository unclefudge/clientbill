<div class="flex items-center gap-3">
    {{-- Big Number --}}
    <div class="text-2xl font-bold">
        {{ $count }}
    </div>

    {{-- Optional overdue --}}
    @if ($overdue > 0)
        <div class="text-xs text-red-500">
            {{ $overdue }} overdue
        </div>
    @endif
</div>
