{{-- Edit Entry --}}
<x-filament::modal id="editEntryModal" width="xl">
    @if ($activeEntry)
        <x-slot name="heading" class="bg-primary-500" style="background: #ff0000"><h1 class="text-2xl md:text-3xl font-bold">Edit Entry <small class="text-gray-500 text-sm">ID: {{ $activeEntry->id }}</small></h1></x-slot>
        <x-slot name="description"></x-slot>
        <form wire:submit="saveEntry">

            {{ $this->entryForm }}
            <div class="flex gap-5 mt-5">
                <x-filament::button type="submit">Save Entry</x-filament::button>
                <x-filament::button color="gray" wire:click="$dispatch('close-modal', { id:'editEntryModal' })">Cancel</x-filament::button>
                <x-filament::button color="danger" wire:click.stop="confirmDeleteEntry('{{$activeEntry->id}}')">Delete</x-filament::button>
            </div>
        </form>
    @endif
</x-filament::modal>

{{-- Add Entry --}}
<x-filament::modal id="addEntryModal" width="xl">
    <x-slot name="heading" class="bg-primary-500" style="background: #ff0000"><h1 class="text-2xl md:text-3xl font-bold">{{ $currentDay->format('d M, Y') }}</h1></x-slot>
    <x-slot name="description"></x-slot>

    {{-- ======================= --}}
    {{-- PROGRESS STEPS          --}}
    {{-- ======================= --}}
    <div class="w-full flex justify-center mt-6">
        <div class="w-full md:w-[80%]">
            <div class="grid grid-cols-4">

                {{-- STEP 1 --}}
                <div class="flex flex-col items-center">
                    <div class="w-full flex items-center">
                        <div class="flex-1"></div>
                        <div class="h-8 w-8 rounded-full flex items-center justify-center text-sm font-semibold
                             {{ ($stage == 1) ? 'bg-green-800 text-white' : 'bg-gray-200 text-gray-600' }}
                             {{ ($stage > 1) ? 'hover:cursor-pointer' : '' }}"
                             @if ($stage > 1) wire:click="changeStage('1')" @endif>
                            @if($stage > 1)
                                <x-heroicon-s-check class="w-5 h-5 text-gray-800"/>
                            @else
                                1
                            @endif
                        </div>
                        <div class="flex-1 h-[2px] bg-gray-300"></div>
                    </div>
                    <div class="w-24 mt-2 text-gray-500 dark:text-gray-300 text-sm text-center truncate">{{ $clientText }}</div>
                </div>

                {{-- STEP 2 --}}
                <div class="flex flex-col items-center">
                    <div class="w-full flex items-center">
                        <div class="flex-1 h-[2px] bg-gray-300"></div>
                        <div class="h-8 w-8 rounded-full flex items-center justify-center text-sm font-semibold
                              {{ ($stage == 2) ? 'bg-green-800 text-white' : 'bg-gray-200 text-gray-600' }}"
                             @if ($stage > 2) wire:click="changeStage('2')" @endif>
                            @if($stage > 2)
                                <x-heroicon-s-check class="w-5 h-5 text-gray-800"/>
                            @else
                                2
                            @endif
                        </div>
                        <div class="flex-1 h-[2px] bg-gray-300"></div>
                    </div>
                    <div class="w-24 mt-2 text-gray-500 dark:text-gray-300 text-sm text-center truncate">{{ $projectText }}</div>
                </div>

                {{-- STEP 3 --}}
                <div class="flex flex-col items-center">
                    <div class="w-full flex items-center">
                        <div class="flex-1 h-[2px] bg-gray-300"></div>
                        <div class="h-8 w-8 rounded-full flex items-center justify-center text-sm font-semibold
                              {{ ($stage == 3) ? 'bg-green-800 text-white' : 'bg-gray-200 text-gray-600' }}"
                             @if ($stage > 3) wire:click="changeStage('3')" @endif>
                            @if($stage > 3)
                                <x-heroicon-s-check class="w-5 h-5 text-gray-800"/>
                            @else
                                3
                            @endif
                        </div>
                        <div class="flex-1 h-[2px] bg-gray-300"></div>
                    </div>
                    <div class="mt-2 text-gray-500 dark:text-gray-300 text-sm">{{ $timeText }} {{ ($timeText != 'Time') ? 'hr' : '' }}</div>
                </div>

                {{-- STEP 4 --}}
                <div class="flex flex-col items-center">
                    <div class="w-full flex items-center">
                        <div class="flex-1 h-[2px] bg-gray-300"></div>
                        <div class="h-8 w-8 rounded-full flex items-center justify-center text-sm font-semibold
                            {{ ($stage == 4) ? 'bg-green-800 text-white' : 'bg-gray-200 text-gray-600' }}">
                            4
                        </div>
                        <div class="flex-1"></div>
                    </div>
                    <div class="mt-2 text-gray-500 dark:text-gray-300 text-sm">Activity</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================= --}}
    {{-- STAGE 1: Client         --}}
    {{-- ======================= --}}
    @if ($stage == 1)
        <div class="text-center">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 gap-6 justify-items-center mb-6">

                @foreach($clientProjects as $client => $projects)
                    <button wire:click="setClient('{{ $client }}')" class="w-full h-15 flex items-center justify-center rounded-xl text-sm font-semibold transition px-4 py-3 shadow-sm border
                    @if($clientText === $client)
                        bg-yellow-600 text-white border-yelloy-700
                    @else
                        bg-gray-100 text-gray-700 border-gray-300 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700
                    @endif
                    ">
                        {{ $client }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ======================= --}}
    {{-- STAGE 2: Project         --}}
    {{-- ======================= --}}
    @if ($stage == 2)
        <div class="text-center">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 gap-6 justify-items-center mb-6">
                @if ($clientText != 'Client')
                    @foreach($clientProjects[$clientText] as $project_id => $project_name)
                        <button wire:click="setProject('{{$project_id}}','{{ $project_name }}')" class="w-full h-15 flex items-center justify-center rounded-xl text-sm font-semibold transition px-4 py-3 shadow-sm border
                    @if($projectText == $project_name)
                        bg-yellow-600 text-white border-yellow-700
                    @else
                        bg-gray-100 text-gray-700 border-gray-300 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700
                    @endif
                    ">
                            {{ $project_name }}
                        </button>
                    @endforeach
                @endif
            </div>
        </div>
    @endif

    {{-- ======================= --}}
    {{-- STAGE 3: Time           --}}
    {{-- ======================= --}}
    @if ($stage == 3)
        <div class="grid grid-cols-6 gap-6 justify-items-center mb-6">
            @foreach(range(0.5, 12, 0.5) as $num)
                <button wire:click="setTime({{ $num }})"
                        class="h-16 w-16 rounded-full flex items-center justify-center text-lg border transition
                        {{ $timeText === $num ? 'bg-yellow-600 text-white border-yellow-700' : 'bg-gray-100 text-gray-700 border-gray-500 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }}">
                    {{ $num }}
                </button>
            @endforeach
        </div>
    @endif

    {{-- ======================= --}}
    {{-- STAGE 4: Activity       --}}
    {{-- ======================= --}}
    @if ($stage == 4)
        <div>
            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">
                Activity
            </label>

            <x-filament::input wire:model="activity" placeholder="What did you do to make the bacon..."
                               class="w-full rounded-lg px-3 py-2 bg-white text-gray-900 border border-gray-300 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-800 dark:text-gray-100 dark:border-gray-700 dark:focus:border-primary-400 dark:focus:ring-primary-400"
            />

        </div>
    @endif

    <div class="flex gap-5 mt-5">
        @if ($stage == 3)
            <div class="grid grid-cols-4 gap-6 justify-items-center mb-6">
                <x-filament::button color="gray" wire:click="$dispatch('close-modal', { id:'addEntryModal' })">Cancel</x-filament::button>
                @foreach(['regular', 'prebill', 'payback'] as $type)
                    <button wire:click="setType('{{ $type }}')"
                            class="rounded-lg flex items-center justify-center text-base border transition py-2 px-5
                        {{ $timeType == $type ? 'bg-yellow-600 text-white border-yellow-700' : 'bg-gray-100 text-gray-700 border-gray-500 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }}">
                        {{ $type }}
                    </button>
                @endforeach
            </div>
        @else
            <x-filament::button color="gray" wire:click="$dispatch('close-modal', { id:'addEntryModal' })">Cancel</x-filament::button>
        @endif
        @if ($stage == 4)
            <x-filament::button type="submit" wire:click="saveNewEntry">Save Entry</x-filament::button>
        @endif
    </div>
</x-filament::modal>

{{-- Delete Entry Modal --}}
<x-filament::modal id="deleteEntryModal">
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
            <div class="text-base font-bold">Delete Entry</div>
            <div class="text-md">
                {!! $this->deleteData['name'] !!}
            </div>
            <div class="py-5 text-sm text-gray-500">Are you sure you would like to do this?</div>
        </div>
        <div class="grid grid-flow-col gap-3">
            <x-filament::button color="gray" wire:click="$dispatch('close-modal', { id:'deleteEntryModal' })">Cancel</x-filament::button>
            <x-filament::button color="danger" wire:click="deleteEntry('{{$this->deleteData['id']}}')">Confirm</x-filament::button>
        </div>
    @endif
</x-filament::modal>

{{-- Confirm Entry Edit --}}
<x-filament::modal id="confirmEditEntryModal">
    <x-slot name="heading">
        This entry is already invoiced!
    </x-slot>

    <p class="text-sm text-gray-700 dark:text-gray-300">
        This time entry is part of Invoice #{{ optional($activeEntry)->invoice_id  }}.<br>
        Updating it may affect billing totals.<br><br>
        Do you still want to apply these changes?
    </p>

    <x-slot name="footer">
        <div class="flex justify-start gap-3">
            <x-filament::button color="danger" wire:click="performEntryUpdate()">Yes, update anyway</x-filament::button>
            <x-filament::button color="gray" wire:click="$dispatch('close-modal', { id:'confirmEditEntryModal' })">Cancel</x-filament::button>
        </div>
    </x-slot>
</x-filament::modal>
{{--}}
<x-filament::modal id="confirmInvoiceEditModal" width="md">
    <x-slot name="heading">Already Invoiced</x-slot>

    <div class="text-sm text-gray-700 dark:text-gray-300">
        This time entry has already been included in an invoice.
        Updating it may require adjusting the invoice as well.

        <br><br>
        Are you sure you want to proceed?
    </div>

    <x-slot name="footer">
        <x-filament::button color="danger" wire:click='confirmInvoiceEdit = true; performEntryUpdate(pendingEditData)'>
            Yes, update anyway
        </x-filament::button>

        <x-filament::button color="secondary" x-on:click="$dispatch('close-modal', { id: 'confirmInvoiceEditModal' })">
            Cancel
        </x-filament::button>
    </x-slot>
</x-filament::modal>--}}
