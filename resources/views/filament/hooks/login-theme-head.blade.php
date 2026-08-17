<script>
    (() => {
        // The ClientBill login screen is intentionally always dark.
        // This is only rendered for unauthenticated panel requests.
        try {
            localStorage.setItem('theme', 'dark');
        } catch (error) {
            // Browser storage may be unavailable; the class below still
            // ensures this page renders in dark mode.
        }

        document.documentElement.classList.add('dark');
    })();
</script>
