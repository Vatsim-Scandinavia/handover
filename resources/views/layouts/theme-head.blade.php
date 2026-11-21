{{-- Inline theme script to prevent flash of wrong theme --}}
{{-- Only applies explicit (non-system) preferences synchronously. All other logic in theme.js --}}
<script>
(function() {
    function read(key) {
        try {
            return localStorage.getItem(key);
        } catch (e) {
            return null; // storage blocked: fall back to the system setting
        }
    }

    function valid(preference) {
        return (preference === 'light' || preference === 'dark' || preference === 'system') ? preference : null;
    }

    // The signed-in member's unconfirmed toggle choice, if they left one.
    function pending(userId) {
        try {
            var choice = JSON.parse(read('user_theme_pending'));
            return choice && choice.user === userId ? valid(choice.preference) : null;
        } catch (e) {
            return null;
        }
    }

    // Same order as theme.js: for a signed-in member, a toggle choice the
    // server has not confirmed yet, then the saved preference; for guests,
    // this browser's stored preference.
    var userId = document.documentElement.getAttribute('data-user-id');
    var storedPreference = userId ? valid(document.documentElement.getAttribute('data-user-theme')) : null;
    if (storedPreference) {
        storedPreference = pending(userId) || storedPreference;
    } else {
        storedPreference = valid(read('user_theme_preference'));
    }

    // Resolve to the effective (light/dark) theme. 'system' and unknown
    // values are watched by theme.js after load; here we just detect once
    // to avoid FOUC.
    var effective = (storedPreference === 'light' || storedPreference === 'dark')
        ? storedPreference
        : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

    // data-bs-theme mirrors data-theme so Bootstrap's dark cascade applies.
    document.documentElement.setAttribute('data-theme', effective);
    document.documentElement.setAttribute('data-bs-theme', effective);
})();
</script>
