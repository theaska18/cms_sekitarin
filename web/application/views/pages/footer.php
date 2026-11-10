<footer class="site-footer border-t border-white/10">
    <div class="site-footer__inner mx-auto flex max-w-7xl flex-col gap-6 px-6 py-8 text-sm text-slate-400 md:flex-row md:items-center md:justify-between lg:px-8">
        <a class="footer-link font-semibold tracking-wide text-white" href="/"><?php echo html_escape($web_name); ?></a>
        <nav aria-label="Footer navigation">
            <ul class="flex flex-wrap gap-x-6 gap-y-3">
                <li><a class="footer-link" href="/">Home</a></li>
                <li><a class="footer-link" href="/features">Features</a></li>
                <li><a class="footer-link" href="/about">About</a></li>
                <li><a class="footer-link" href="/contact">Contact</a></li>
                <li><a class="footer-link" href="<?php echo $admin_user !== NULL ? '/admin' : '/login'; ?>"><?php echo $admin_user !== NULL ? html_escape($admin_user['display_name']) : 'Login'; ?></a></li>
            </ul>
        </nav>
        <p>© <?php echo date('Y'); ?> <?php echo html_escape($web_name); ?></p>
    </div>
</footer>
<script>
    (function () {
        var toggle = document.getElementById('menu-toggle');
        var navigation = document.getElementById('primary-navigation');
        if (!toggle || !navigation) return;
        toggle.addEventListener('click', function () {
            var expanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', String(!expanded));
            navigation.classList.toggle('hidden', expanded);
        });
        navigation.addEventListener('click', function (event) {
            if (event.target.closest('a') && window.matchMedia('(max-width: 767px)').matches) {
                toggle.setAttribute('aria-expanded', 'false');
                navigation.classList.add('hidden');
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
                toggle.setAttribute('aria-expanded', 'false');
                navigation.classList.add('hidden');
                toggle.focus();
            }
        });
    }());
</script>
</body>
</html>
