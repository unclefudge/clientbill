{{-- =================================== --}}
{{-- UNBILLED HOURS (GROUPED BY PROJECT) --}}
{{-- =================================== --}}
<x-filament::modal id="unbilledHoursModal" width="4xl">
    <x-slot name="heading">Unbilled Hours — {{ $modalClientName }}</x-slot>

    <div class="space-y-4 max-h-[70vh] overflow-y-auto">
        @forelse ($modalEntries ?? [] as $project)

            {{-- One collapsible block per project --}}
            <x-filament::section collapsible collapsed :heading="$project['project_name'] . ' (' . $project['total_hours'] . 'h)'" class="bg-gray-800 border border-gray-700 rounded-lg text-gray-100">

                <div class="space-y-2">
                    @foreach (($project['entries'] ?? []) as $entry)
                        @php
                            $date = $entry['date'] ?? null;
                            $minutes = ($entry['duration'] ?? 0);
                            $hours = ($entry['duration'] ?? 0) / 60;
                        @endphp

                        <div class="grid grid-cols-12 py-2 px-3 rounded bg-gray-900/40">

                            {{-- Date --}}
                            <div class="col-span-4 text-gray-300">
                                {{ $date ? \Carbon\Carbon::parse($date)->format('d M Y') : '—' }}
                            </div>

                            {{-- Hours --}}
                            <div class="col-span-2 font-semibold text-amber-400">
                                {{ $hours }}h
                            </div>

                            {{-- Activity --}}
                            <div class="col-span-6 text-sm text-gray-200">
                                {{ $entry['activity'] ?? 'No description' }} {!! ($entry['type'] != 'regular') ? " &nbsp; " . $entry['badge']  : ''!!}
                            </div>

                        </div>
                    @endforeach

                </div>

            </x-filament::section>

        @empty
            <div class="text-gray-400 text-sm">No unbilled entries.</div>
        @endforelse

    </div>
</x-filament::modal>

{{-- ============================= --}}
{{-- UPCOMING RENEWALS MODAL --}}
{{-- ============================= --}}
<x-filament::modal id="renewalsModal" width="3xl">
    <x-slot name="heading">
        Renewals — {{ $modalClientName }}
    </x-slot>

    <div class="space-y-6 max-h-[70vh] overflow-y-auto">

        {{-- DOMAINS --}}
        <div>
            <h3 class="font-semibold mb-2 text-gray-200">Domains</h3>

            @forelse ($modalRenewals['domains'] as $d)
                <div class="p-4 mb-3 rounded-lg border border-gray-700 bg-gray-800 text-gray-100">
                    <div class="font-semibold text-lg">{{ $d['name'] }}</div>
                    <div class="text-sm text-amber-400">
                        Next renewal: {{ \Carbon\Carbon::parse($d['next_renewal'])->format('d M Y') }}
                    </div>
                </div>
            @empty
                <div class="text-gray-500 text-sm">No upcoming domain renewals.</div>
            @endforelse
        </div>

        {{-- HOSTING --}}
        <div>
            <h3 class="font-semibold mb-2 text-gray-200">Hosting</h3>

            @forelse ($modalRenewals['hosting'] as $h)
                <div class="p-4 mb-3 rounded-lg border border-gray-700 bg-gray-800 text-gray-100">
                    <div class="font-semibold text-lg">{{ $h['name'] }}</div>
                    <div class="text-sm text-amber-400">
                        Next renewal: {{ \Carbon\Carbon::parse($h['next_renewal'])->format('d M Y') }}
                    </div>
                </div>
            @empty
                <div class="text-gray-500 text-sm">No upcoming hosting renewals.</div>
            @endforelse
        </div>

    </div>
</x-filament::modal>

{{-- Create Invoice --}}
<x-filament::modal id="createInvoiceModal" width="md">
    <x-slot name="heading" class="bg-primary-500" style="background: #ff0000"><h1 class="text-2xl md:text-3xl font-bold">Create Invoice</h1></x-slot>
    <x-slot name="description"></x-slot>
    <form>
        {{ $this->createForm }}
        <div class="flex gap-5 mt-5">
            <x-filament::button wire:click="createInvoice()">Create</x-filament::button>
            <x-filament::button wire:click="previewInvoice()" color="info">Preview</x-filament::button>
            <x-filament::button color="gray" wire:click="$dispatch('close-modal', { id:'createInvoiceModal' })">Cancel</x-filament::button>
        </div>

    </form>
</x-filament::modal>

{{-- Preview Modal --}}
<x-filament::modal id="invoicePreviewModal" width="7xl" slide-over>
    <x-slot name="heading">Invoice Preview</x-slot>

    <div class="h-[80vh] -mx-6 -mt-4">
        <iframe src="/invoice-preview?previewData={{ $previewJson }}" class="w-full h-full rounded border bg-white p-3"></iframe>
    </div>

    <x-slot name="footerActions">
        <x-filament::button wire:click="createInvoice()" color="primary">Create Invoice</x-filament::button>
        <x-filament::button color="gray" wire:click="$dispatch('close-modal', { id:'invoicePreviewModal' })">Close</x-filament::button>
    </x-slot>
</x-filament::modal>
