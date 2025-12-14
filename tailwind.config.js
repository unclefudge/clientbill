import preset from './vendor/filament/support/tailwind.config.preset.js';

export default {
    presets: [preset],

    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',

        // REQUIRED FOR FILAMENT v4
        './resources/views/filament/**/*.blade.php',
        './resources/views/livewire/**/*.blade.php',
        './app/Filament/**/*.php',
        './app/Livewire/**/*.php',

        // Filament vendor pages
        './vendor/filament/**/*.blade.php',
    ],
};
