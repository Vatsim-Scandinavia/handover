{{-- Applies the stored theme before the page paints. theme.js handles the rest. --}}
<script>
(function () {
    var preference = null;
    try {
        preference = localStorage.getItem('theme_preference');
    } catch (e) { /* storage blocked: follow the system setting */ }

    var theme = (preference === 'light' || preference === 'dark')
        ? preference
        : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

    document.documentElement.setAttribute('data-theme', theme);
    document.documentElement.setAttribute('data-bs-theme', theme);
})();
</script>
