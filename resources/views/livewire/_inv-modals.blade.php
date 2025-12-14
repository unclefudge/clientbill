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

{{-- Create Item --}}
<x-filament::modal id="createItemModal" width="lg">
    <x-slot name="heading" class="bg-primary-500" style="background: #ff0000"><h1 class="text-2xl md:text-3xl font-bold">Add Invoice Item</h1></x-slot>
    <x-slot name="description"></x-slot>
    <form wire:submit="createItem">
        {{ $this->itemForm }}
        <div class="flex gap-5 mt-5">
            <x-filament::button type="submit">Create</x-filament::button>
            <x-filament::button color="gray" wire:click="$dispatch('close-modal', { id:'createItemModal' })">Cancel</x-filament::button>
        </div>
    </form>
</x-filament::modal>

{{-- Edit Summary --}}
<x-filament::modal id="editProjectSummaryModal" width="xl">
    @if ($activeProject)
        <x-slot name="heading" class="bg-primary-500" style="background: #ff0000"><h1 class="text-2xl md:text-3xl font-bold">{{ $activeProject->name }}</h1></x-slot>
        <x-slot name="description"></x-slot>
        <form wire:submit="saveProjectSummary">
            @if ($activeSummary)
                <textarea wire:model.defer="activeSummaryText" class="w-full h-32 rounded-md bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-200 border border-gray-300 dark:border-gray-700 p-3 outline-none focus:ring-0 focus:border-gray-500 dark:focus:border-gray-600">
                    {{ $activeSummaryText }}
                </textarea>
                <div class="flex gap-5 mt-5">
                    <x-filament::button type="submit">Save</x-filament::button>
                    <x-filament::button color="gray" wire:click="$dispatch('close-modal', { id:'editProjectSummaryModal' })">Cancel</x-filament::button>
                    {{--}}<x-filament::button color="danger" wire:click.stop="confirmDeleteEntry('{{$activeSummary->id}}')">Delete</x-filament::button>--}}
                </div>
            @endif
        </form>
    @endif
</x-filament::modal>

{{-- Preview Modal --}}
<x-filament::modal id="invoicePreviewModal" width="7xl" slide-over>
    <x-slot name="heading">Invoice Preview</x-slot>

    <div class="h-[85vh] -mx-6 -mt-4">
        <iframe src="/invoice-preview?previewData={{ $previewJson }}" class="w-full h-full rounded border"></iframe>
    </div>

    <x-slot name="footerActions">
        <x-filament::button wire:click="createInvoice" color="primary">Create Invoice</x-filament::button>
        <x-filament::button color="gray" wire:click="$dispatch('close-modal', { id:'invoicePreviewModal' })">Close</x-filament::button>
    </x-slot>
</x-filament::modal>

{{-- Delete Item Modal --}}
<x-filament::modal id="deleteItemModal">
    @if ($this->deleteData)
        <x-slot name="heading"></x-slot>
        <div class="mb-1 flex items-center justify-center">
            <div class="rounded-full fi-color-custom bg-custom-100 dark:bg-custom-500/20 p-3" style="--c-100:var(--danger-100);--c-400:var(--danger-400);--c-500:var(--danger-500);--c-600:var(--danger-600);">
                <svg class="fi-modal-icon h-6 w-6 text-custom-600 dark:text-custom-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"></path>
                </svg>
            </div>
        </div>
        <div class="text-center">
            <div class="text-base font-bold">Delete Item <span class="text-gray-500">[{{ $this->deleteData['id'] }}]</span></div>
            <div class="text-md">
                {!! $this->deleteData['name'] !!}
            </div>
            <div class="py-5 text-sm text-gray-500">Are you sure you would like to do this?</div>
        </div>
        <div class="grid grid-flow-col gap-3">
            <x-filament::button color="gray" wire:click="$dispatch('close-modal', { id:'deleteItemModal' })">Cancel</x-filament::button>
            <x-filament::button color="danger" wire:click="deleteItem('{{$this->deleteData['id']}}')">Confirm</x-filament::button>
        </div>
    @endif
</x-filament::modal>

{{-- Delete Invoice Modal --}}
<x-filament::modal id="deleteInvoiceModal">
    @if ($this->deleteData)
        <x-slot name="heading"></x-slot>
        <div class="mb-1 flex items-center justify-center">
            <div class="rounded-full fi-color-custom bg-custom-100 dark:bg-custom-500/20 p-3" style="--c-100:var(--danger-100);--c-400:var(--danger-400);--c-500:var(--danger-500);--c-600:var(--danger-600);">
                <svg class="fi-modal-icon h-6 w-6 text-custom-600 dark:text-custom-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"></path>
                </svg>
            </div>
        </div>
        <div class="text-center">
            <div class="text-base font-bold">Delete Invoice <span class="text-gray-500">[{{ $this->deleteData['id'] }}]</span></div>
            <div class="text-md">
                {!! $this->deleteData['name'] !!}
            </div>
            <div class="py-5 text-sm text-gray-500">Are you sure you would like to do this?</div>
        </div>
        <div class="grid grid-flow-col gap-3">
            <x-filament::button color="gray" wire:click="$dispatch('close-modal', { id:'deleteInvoiceModal' })">Cancel</x-filament::button>
            <x-filament::button color="danger" wire:click="deleteInvoice('{{$this->deleteData['id']}}')">Confirm</x-filament::button>
        </div>
    @endif
</x-filament::modal>

