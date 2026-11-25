</main>
<footer class="site-footer no-print">
    <div class="container">
        Moyassir (ميسر) &mdash; Hajj &amp; Umrah Campaign Logistics System | CPIT251 Software Engineering (FCIT, King Abdulaziz University)
    </div>
</footer>
<script>
(function () {
    // 1. Mobile YouTube-style slide-out drawer toggle
    var openBtn = document.getElementById('mobileMenuOpen');
    var closeBtn = document.getElementById('mobileMenuClose');
    var drawer = document.getElementById('mobileDrawer');
    var backdrop = document.getElementById('drawerBackdrop');

    if (openBtn && drawer && backdrop) {
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
    }

    // 2. Auto-growing support message & reply textareas
    function autoGrow(el) {
        if (!el) return;
        el.style.height = 'auto';
        var next = Math.max(112, el.scrollHeight + 4);
        el.style.height = next + 'px';
    }

    var composers = document.querySelectorAll('textarea.composer-textarea');
    for (var j = 0; j < composers.length; j++) {
        autoGrow(composers[j]);
        composers[j].addEventListener('input', function () { autoGrow(this); });
    }

    var detailsBlocks = document.querySelectorAll('details.reply-details');
    for (var d = 0; d < detailsBlocks.length; d++) {
        detailsBlocks[d].addEventListener('toggle', function () {
            if (this.open) {
                var ta = this.querySelector('textarea.composer-textarea');
                autoGrow(ta);
            }
        });
    }

    // 3. One-tap Quick Reply / Topic preset chips
    var chips = document.querySelectorAll('.chip-btn');
    for (var k = 0; k < chips.length; k++) {
        chips[k].addEventListener('click', function () {
            var targetId = this.getAttribute('data-target');
            var ta = targetId ? document.getElementById(targetId) : null;
            if (ta) {
                ta.value = this.getAttribute('data-text') || '';
                autoGrow(ta);
                ta.focus();
            }
            var stId = this.getAttribute('data-status-target');
            var stVal = this.getAttribute('data-status');
            if (stId && stVal) {
                var sel = document.getElementById(stId);
                if (sel) sel.value = stVal;
            }
            var subjId = this.getAttribute('data-subject-target');
            var subjVal = this.getAttribute('data-subject');
            if (subjId && subjVal) {
                var subj = document.getElementById(subjId);
                if (subj && subj.value.trim() === '') subj.value = subjVal;
            }
            var catId = this.getAttribute('data-category-target');
            var catVal = this.getAttribute('data-category');
            if (catId && catVal) {
                var cat = document.getElementById(catId);
                if (cat) cat.value = catVal;
            }
            var prioId = this.getAttribute('data-priority-target');
            var prioVal = this.getAttribute('data-priority');
            if (prioId && prioVal) {
                var prio = document.getElementById(prioId);
                if (prio) prio.value = prioVal;
            }
        });
    }
})();
</script>
</body>
</html>
