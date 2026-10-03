</main><script>
document.querySelectorAll('[data-confirm]').forEach(x=>x.addEventListener('click',e=>{if(!confirm(x.dataset.confirm))e.preventDefault()}));
</script><?php require __DIR__.'/../includes/live-updates.php'; ?></body></html>