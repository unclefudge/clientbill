@php
    $links = [
        ['label' => 'Overview', 'url' => \App\Filament\Pages\Books\BooksOverview::getUrl(), 'active' => $currentPath === 'books'],
        ['label' => 'Transactions', 'url' => \App\Filament\Pages\Books\BooksTransactions::getUrl(), 'active' => str_starts_with($currentPath, 'books/transactions')],
        ['label' => 'Import', 'url' => \App\Filament\Pages\Books\BooksImport::getUrl(), 'active' => str_starts_with($currentPath, 'books/import')],
        ['label' => 'BAS', 'url' => \App\Filament\Pages\Books\BooksBas::getUrl(), 'active' => str_starts_with($currentPath, 'books/bas')],
        ['label' => 'Reports', 'url' => \App\Filament\Pages\Books\BooksReports::getUrl(), 'active' => str_starts_with($currentPath, 'books/reports')],
    ];

    $moreActive = str_starts_with($currentPath, 'books/settings')
        || str_starts_with($currentPath, 'books/quickbas-migration');
@endphp

<div class="mb-6 border-b border-gray-200 dark:border-white/10">
    <div class="flex flex-col gap-3 pb-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-3">
            <div class="text-lg font-semibold text-gray-950 dark:text-white">Books</div>

            @if ($businesses->count() > 1)
                <div class="w-64">
                    <x-books.select
                        model="businessId"
                        :value="$businessId"
                        :options="$businesses->pluck('name', 'id')->all()"
                        placeholder="Select business"
                    />
                </div>
            @else
                <div class="rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700 dark:bg-white/5 dark:text-gray-200">
                    {{ $businesses->first()?->name }}
                </div>
            @endif
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-x-1 gap-y-2">
        @foreach ($links as $link)
            <a
                href="{{ $link['url'] }}"
                wire:navigate
                @class([
                    'border-b-2 px-3 py-2 text-sm font-semibold transition',
                    'border-primary-500 text-primary-600 dark:text-primary-400' => $link['active'],
                    'border-transparent text-gray-600 hover:border-gray-300 hover:text-gray-950 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:text-white' => ! $link['active'],
                ])
            >
                {{ $link['label'] }}
            </a>
        @endforeach

        <x-filament::dropdown placement="bottom-start">
            <x-slot name="trigger">
                <button
                    type="button"
                    @class([
                        'flex items-center gap-1 border-b-2 px-3 py-2 text-sm font-semibold transition',
                        'border-primary-500 text-primary-600 dark:text-primary-400' => $moreActive,
                        'border-transparent text-gray-600 hover:text-gray-950 dark:text-gray-400 dark:hover:text-white' => ! $moreActive,
                    ])
                >
                    More
                    <x-heroicon-m-chevron-down class="h-4 w-4" />
                </button>
            </x-slot>

            <x-filament::dropdown.list>
                <x-filament::dropdown.list.item tag="a" href="{{ \App\Filament\Resources\BookCategories\BookCategoryResource::getUrl() }}" icon="heroicon-o-tag">
                    Categories
                </x-filament::dropdown.list.item>

                <x-filament::dropdown.list.item tag="a" href="{{ \App\Filament\Resources\BookCategoryRules\BookCategoryRuleResource::getUrl() }}" icon="heroicon-o-bolt">
                    Category Rules
                </x-filament::dropdown.list.item>

                <x-filament::dropdown.list.item tag="a" href="{{ \App\Filament\Resources\BookBankAccounts\BookBankAccountResource::getUrl() }}" icon="heroicon-o-building-library">
                    Bank Accounts
                </x-filament::dropdown.list.item>

                <x-filament::dropdown.list.item tag="a" href="{{ \App\Filament\Resources\BookFinancialYears\BookFinancialYearResource::getUrl() }}" icon="heroicon-o-calendar-days">
                    Financial Years
                </x-filament::dropdown.list.item>

                @if (auth()->user()?->can_manage_users)
                    <x-filament::dropdown.list.item tag="a" href="{{ \App\Filament\Pages\Books\BooksQuickBasMigration::getUrl() }}" icon="heroicon-o-arrow-up-tray">
                        QuickBAS Migration
                    </x-filament::dropdown.list.item>

                    <x-filament::dropdown.list.item tag="a" href="{{ \App\Filament\Resources\BookBusinesses\BookBusinessResource::getUrl() }}" icon="heroicon-o-briefcase">
                        Business Settings
                    </x-filament::dropdown.list.item>
                @endif
            </x-filament::dropdown.list>
        </x-filament::dropdown>
    </div>
</div>
