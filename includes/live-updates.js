(function () {
    var notice = document.getElementById('live-update-status');
    if (!notice) return;

    var version = notice.dataset.version;
    var refreshButton = document.getElementById('live-update-refresh');
    var hasUnsavedChanges = false;

    document.addEventListener('input', function (event) {
        if (event.target && event.target.closest('form')) hasUnsavedChanges = true;
    });
    document.addEventListener('change', function (event) {
        if (event.target && event.target.closest('form')) hasUnsavedChanges = true;
    });

    function checkForUpdates() {
        if (document.hidden) return;

        fetch(notice.dataset.endpoint, {
            cache: 'no-store',
            credentials: 'same-origin',
            headers: {'Accept': 'application/json'}
        })
            .then(function (response) {
                if (!response.ok) throw new Error('Live update check failed with HTTP ' + response.status);
                return response.json();
            })
            .then(function (data) {
                if (!data.version || data.version === version) return;
                if (hasUnsavedChanges) {
                    notice.hidden = false;
                    return;
                }
                window.location.reload();
            })
            .catch(function (error) {
                console.warn('Could not check for live site updates.', error);
            });
    }

    refreshButton.addEventListener('click', function () {
        window.location.reload();
    });
    document.addEventListener('visibilitychange', checkForUpdates);
    window.setInterval(checkForUpdates, 10000);
})();
