(function () {
    try {
        var stored = window.localStorage.getItem('lumora-theme');
        var preference = stored === 'light' || stored === 'dark' || stored === 'system' ? stored : 'system';
        var dark = preference === 'dark' || (preference === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', dark);
        document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
    } catch (error) {
        // Theme persistence is a progressive enhancement.
    }
})();
