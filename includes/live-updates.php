<div id="live-update-status" data-version="<?=e(live_data_version())?>" data-endpoint="<?=e(BASE_URL.'/live-status.php')?>" role="status" aria-live="polite" hidden>
    <span>New updates are available.</span>
    <button type="button" id="live-update-refresh">Refresh to see them</button>
</div>
<style>
#live-update-status{position:fixed;right:20px;bottom:20px;z-index:1000;display:flex;align-items:center;gap:14px;max-width:calc(100vw - 40px);padding:12px 16px;border:1px solid #d9d0c6;border-radius:9px;background:#fff;color:#17202a;box-shadow:0 5px 22px #17202a33;font:14px Arial,sans-serif}
#live-update-status[hidden]{display:none}
#live-update-refresh{border:0;border-radius:5px;padding:9px 12px;background:#9e2b25;color:#fff;font:700 13px Arial,sans-serif;cursor:pointer}
#live-update-refresh:focus-visible{outline:2px solid #17202a;outline-offset:2px}
</style>
<script src="<?=e(BASE_URL)?>/includes/live-updates.js" defer></script>
