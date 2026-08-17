<div
    class="hidden"
    aria-hidden="true"
    x-data="{
        lastTheme: @js($themeMode),
        timer: null,

        currentTheme() {
            const theme = localStorage.getItem('theme') ?? 'system';

            return ['light', 'dark', 'system'].includes(theme)
                ? theme
                : 'system';
        },

        startThemeWatcher() {
            this.lastTheme = this.currentTheme();

            this.timer = window.setInterval(() => {
                const theme = this.currentTheme();

                if (theme === this.lastTheme) {
                    return;
                }

                this.lastTheme = theme;
                $wire.saveTheme(theme);
            }, 500);
        },
    }"
    x-init="startThemeWatcher()"
></div>
