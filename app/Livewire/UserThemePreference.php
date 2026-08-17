<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class UserThemePreference extends Component
{
    public string $themeMode = 'system';

    public function mount(): void
    {
        $this->themeMode = $this->normaliseTheme(auth()->user()?->theme_mode);
    }

    public function saveTheme(string $themeMode): void
    {
        abort_unless(auth()->check(), 403);

        $themeMode = $this->normaliseTheme($themeMode);

        $user = auth()->user();

        if ($user->theme_mode !== $themeMode) {
            $user->forceFill(['theme_mode' => $themeMode])->saveQuietly();
        }

        $this->themeMode = $themeMode;
    }

    protected function normaliseTheme(?string $themeMode): string
    {
        return in_array($themeMode, ['light', 'dark', 'system'], true)
            ? $themeMode
            : 'system';
    }

    public function render(): View
    {
        return view('livewire.user-theme-preference');
    }
}
