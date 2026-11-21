{{-- Inline theme script to prevent flash of wrong theme --}}
{{-- Only applies explicit (non-system) preferences synchronously. All other logic in theme.js --}}
<script>
(function() {
    var storedPreference = localStorage.getItem('user_theme_preference') ||
                          document.documentElement.getAttribute('data-user-theme') ||
                          'system';

    // Resolve to the effective (light/dark) theme. 'system' is watched by
    // theme.js after load; here we just detect once to avoid FOUC.
    var effective = (storedPreference === 'light' || storedPreference === 'dark')
        ? storedPreference
        : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

    // data-bs-theme mirrors data-theme so Bootstrap's dark cascade applies.
    document.documentElement.setAttribute('data-theme', effective);
    document.documentElement.setAttribute('data-bs-theme', effective);
})();
</script>
