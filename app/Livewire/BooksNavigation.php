<?php

namespace App\Livewire;

use App\Filament\Pages\Books\BooksOverview;
use App\Support\Books\BookContext;
use Livewire\Component;

class BooksNavigation extends Component
{
    public ?int $businessId = null;
    public string $returnUrl = '';
    public string $currentPath = '';

    public function mount(): void
    {
        $business = app(BookContext::class)->current();
        abort_unless($business, 403);

        $this->businessId = $business->id;
        $this->returnUrl = url()->current();
        $this->currentPath = request()->path();
    }

    public function updatedBusinessId($value): void
    {
        $business = app(BookContext::class)->select((int) $value);
        $this->businessId = $business->id;

        $this->redirect(BooksOverview::getUrl(), navigate: true);
    }

    public function render()
    {
        return view('livewire.books-navigation', [
            'businesses' => app(BookContext::class)->businesses(),
        ]);
    }
}
