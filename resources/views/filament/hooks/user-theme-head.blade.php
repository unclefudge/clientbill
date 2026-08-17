<script>
    (() => {
        const preferredTheme = @js(in_array($themeMode, ['light', 'dark', 'system'], true) ? $themeMode : 'system');

        try {
            localStorage.setItem('theme', preferredTheme);
        } catch (error) {
            // If browser storage is unavailable, still apply the preference
            // to this page below.
        }

        const resolvedTheme = preferredTheme === 'system'
            ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
            : preferredTheme;

        document.documentElement.classList.toggle('dark', resolvedTheme === 'dark');
    })();
</script>
