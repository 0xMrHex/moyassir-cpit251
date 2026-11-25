</main>
<footer class="site-footer no-print">
    <div class="container">
        Moyassir (ميسر) &mdash; Hajj &amp; Umrah Campaign Logistics System | CPIT251 Software Engineering (FCIT, King Abdulaziz University)
    </div>
</footer>
<script>
(function () {
    var openBtn = document.getElementById('mobileMenuOpen');
    var closeBtn = document.getElementById('mobileMenuClose');
    var drawer = document.getElementById('mobileDrawer');
    var backdrop = document.getElementById('drawerBackdrop');
    if (!openBtn || !drawer || !backdrop) return;

    function setOpen(isOpen) {
        drawer.classList.toggle('open', isOpen);
        backdrop.classList.toggle('open', isOpen);
        openBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        drawer.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }

    openBtn.addEventListener('click', function () { setOpen(true); });
    if (closeBtn) closeBtn.addEventListener('click', function () { setOpen(false); });
    backdrop.addEventListener('click', function () { setOpen(false); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && drawer.classList.contains('open')) setOpen(false);
    });
    var items = drawer.querySelectorAll('a.drawer-item');
    for (var i = 0; i < items.length; i++) {
        items[i].addEventListener('click', function () { setOpen(false); });
    }
})();
</script>
</body>
</html>
