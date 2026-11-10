<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#080d1b">
    <title><?php echo html_escape($web_name); ?> — Content, in control</title>
    <?php $tailwind_css_path = FCPATH . 'assets/css/tailwind.css'; $tailwind_css_version = is_file($tailwind_css_path) ? (string) filemtime($tailwind_css_path) : '1'; ?>
    <link rel="stylesheet" href="/assets/css/tailwind.css?v=<?php echo rawurlencode($tailwind_css_version); ?>">
    <style>
        html { scroll-behavior: smooth; }
        body { background-color: #080d1b; }
        ::selection { background: rgba(139, 92, 246, .55); color: #fff; }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after { animation-duration: .01ms !important; transition-duration: .01ms !important; }
        }
    </style>
</head>
<body class="bg-ink text-slate-100 antialiased">
    <header class="site-header sticky top-0 z-50 border-b border-white/10">
        <div class="site-header__inner mx-auto flex max-w-7xl items-center justify-between px-5 py-3 sm:px-8">
            <a href="/" class="site-brand flex items-center gap-3 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-cyanbright" aria-label="<?php echo html_escape($web_name); ?> home">
                <img src="/assets/logo_box.png" alt="" class="h-11 w-11 object-contain">
                <span class="text-lg font-bold tracking-tight text-white sm:text-xl"><?php echo html_escape($web_name); ?></span>
            </a>
            <button id="menu-toggle" type="button" class="menu-toggle inline-flex items-center justify-center rounded-lg border border-white/15 p-2 text-slate-200 hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyanbright md:hidden" aria-label="Toggle navigation" aria-controls="primary-navigation" aria-expanded="false">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" /></svg>
            </button>
            <nav id="primary-navigation" aria-label="Primary navigation" class="site-navigation absolute left-0 right-0 top-full hidden border-b border-white/10 bg-ink px-5 py-4 md:static md:block md:border-0 md:bg-ink md:p-0">
                <ul class="flex flex-col gap-1 md:flex-row md:items-center md:gap-7">
                    <li><a class="site-nav-link is-current" href="/" aria-current="page">Home</a></li>
                    <li><a class="site-nav-link" href="/features">Features</a></li>
                    <li><a class="site-nav-link" href="/about">About</a></li>
                    <li><a class="site-nav-link" href="/contact">Contact</a></li>
                    <li><a class="site-nav-link site-nav-link--cta" href="<?php echo $admin_user !== NULL ? '/admin' : '/login'; ?>"><?php echo $admin_user !== NULL ? html_escape($admin_user['display_name']) : 'Login'; ?></a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section id="home" class="relative isolate overflow-hidden scroll-mt-24">
            <div class="pointer-events-none absolute -left-40 top-10 -z-10 h-96 w-96 rounded-full bg-violetglow/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -right-40 top-20 -z-10 h-96 w-96 rounded-full bg-cyanbright/10 blur-3xl"></div>
            <div class="mx-auto grid max-w-7xl items-center gap-14 px-5 py-20 sm:px-8 sm:py-28 lg:grid-cols-[1.1fr_.9fr] lg:py-32">
                <div>
                    <div class="mb-7 inline-flex items-center gap-2 rounded-full border border-violetglow/30 bg-violetglow/10 px-4 py-2 text-xs font-semibold uppercase tracking-[.18em] text-violet-200">
                        <span class="h-2 w-2 rounded-full bg-cyanbright"></span> A smarter content workspace
                    </div>
                    <h1 class="max-w-3xl text-4xl font-bold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">Your content,<br><span class="bg-gradient-to-r from-cyanbright via-blue-400 to-violet-400 bg-clip-text text-transparent">beautifully in control.</span></h1>
                    <p class="mt-6 max-w-xl text-base leading-7 text-slate-300 sm:text-lg">A focused home for creating, organizing, and sharing the stories that move your work forward.</p>
                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <a href="#features" class="inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-r from-violet-600 to-blue-600 px-6 py-3 font-semibold text-white shadow-glow transition hover:-translate-y-0.5 hover:from-violet-500 hover:to-blue-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyanbright">Explore features <span aria-hidden="true">→</span></a>
                        <a href="#about" class="inline-flex items-center justify-center rounded-full border border-white/15 px-6 py-3 font-semibold text-slate-200 transition hover:border-white/30 hover:bg-white/5">Discover <?php echo html_escape($web_name); ?></a>
                    </div>
                    <p class="mt-8 text-sm text-slate-500">A clear, calm place for your content to grow.</p>
                </div>
                <div class="relative mx-auto w-full max-w-lg">
                    <div class="absolute inset-5 rounded-[2rem] bg-gradient-to-br from-violet-600/30 to-cyan-400/20 blur-2xl"></div>
                    <div class="relative rounded-[2rem] border border-white/10 bg-gradient-to-br from-white/[.09] to-white/[.02] p-6 shadow-glow backdrop-blur sm:p-8">
                        <div class="flex items-center justify-between border-b border-white/10 pb-5">
                            <div><p class="text-xs font-medium uppercase tracking-[.16em] text-slate-400">Workspace</p><p class="mt-1 text-lg font-semibold text-white"><?php echo html_escape($web_name); ?></p></div>
                            <span class="rounded-full border border-cyanbright/20 bg-cyanbright/10 px-3 py-1 text-xs font-medium text-cyan-200">Ready to create</span>
                        </div>
                        <div class="mt-6 space-y-3">
                            <div class="rounded-2xl border border-white/10 bg-ink/70 p-4">
                                <div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-500/15 text-violet-200" aria-hidden="true">✦</span><div class="min-w-0 flex-1"><p class="font-medium text-white">Ideas, gathered</p><p class="mt-1 text-sm text-slate-400">Keep every draft in reach.</p></div><span class="text-cyanbright" aria-hidden="true">✓</span></div>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-ink/70 p-4">
                                <div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-cyanbright/10 text-cyan-200" aria-hidden="true">▤</span><div class="min-w-0 flex-1"><p class="font-medium text-white">Stories, in order</p><p class="mt-1 text-sm text-slate-400">Find the right thing, fast.</p></div><span class="text-cyanbright" aria-hidden="true">✓</span></div>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-ink/70 p-4">
                                <div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-500/15 text-blue-200" aria-hidden="true">↗</span><div class="min-w-0 flex-1"><p class="font-medium text-white">Ready to share</p><p class="mt-1 text-sm text-slate-400">Bring good work to the world.</p></div><span class="text-cyanbright" aria-hidden="true">✓</span></div>
                            </div>
                        </div>
                        <div class="mt-6 flex items-center justify-between rounded-xl bg-gradient-to-r from-violet-600/20 to-cyan-500/10 px-4 py-3"><span class="text-sm text-slate-300">One workspace. More momentum.</span><span class="text-lg text-cyanbright" aria-hidden="true">✧</span></div>
                    </div>
                </div>
            </div>
        </section>

        <section id="features" class="scroll-mt-24 border-y border-white/5 bg-white/[.02]">
            <div class="mx-auto max-w-7xl px-5 py-20 sm:px-8 sm:py-24">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-[.2em] text-cyanbright">Features</p>
                    <h2 class="mt-4 text-3xl font-bold tracking-tight text-white sm:text-4xl">Everything starts with clarity.</h2>
                    <p class="mt-4 text-slate-400">Thoughtful tools for the everyday work behind great content.</p>
                </div>
                <div class="mt-12 grid gap-5 md:grid-cols-3">
                    <article class="rounded-2xl border border-white/10 bg-panel/80 p-6 transition hover:-translate-y-1 hover:border-violetglow/50">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violetglow/15 text-2xl text-violet-200" aria-hidden="true">✎</span>
                        <h3 class="mt-5 text-lg font-semibold text-white">Create with focus</h3><p class="mt-2 leading-6 text-slate-400">Give ideas room to become polished, purposeful content.</p>
                    </article>
                    <article class="rounded-2xl border border-white/10 bg-panel/80 p-6 transition hover:-translate-y-1 hover:border-cyanbright/50">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-cyanbright/10 text-2xl text-cyan-200" aria-hidden="true">▦</span>
                        <h3 class="mt-5 text-lg font-semibold text-white">Organize effortlessly</h3><p class="mt-2 leading-6 text-slate-400">Keep your content structured, searchable, and easy to manage.</p>
                    </article>
                    <article class="rounded-2xl border border-white/10 bg-panel/80 p-6 transition hover:-translate-y-1 hover:border-blue-400/50">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-400/10 text-2xl text-blue-200" aria-hidden="true">◎</span>
                        <h3 class="mt-5 text-lg font-semibold text-white">Share with confidence</h3><p class="mt-2 leading-6 text-slate-400">Make it simpler to bring the right content to the right people.</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="about" class="scroll-mt-24">
            <div class="mx-auto grid max-w-7xl gap-10 px-5 py-20 sm:px-8 sm:py-24 md:grid-cols-2 md:items-center">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[.2em] text-violet-300">About <?php echo html_escape($web_name); ?></p>
                    <h2 class="mt-4 text-3xl font-bold tracking-tight text-white sm:text-4xl">Make room for the work that matters.</h2>
                </div>
                <div class="space-y-4 text-base leading-7 text-slate-400">
                    <p><?php echo html_escape($web_name); ?> is a content workspace designed to make the path from first thought to shared story feel more considered.</p>
                    <p>Bring the pieces together, keep the details clear, and spend more energy on what you want to say.</p>
                </div>
            </div>
        </section>

        <section id="contact" class="scroll-mt-24 border-y border-white/5 bg-white/[.02]">
            <div class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-20">
                <div class="rounded-3xl border border-white/10 bg-gradient-to-br from-panel to-[#11152a] p-7 sm:p-10 md:flex md:items-center md:justify-between md:gap-8">
                    <div><p class="text-sm font-semibold uppercase tracking-[.2em] text-cyanbright">Contact</p><h2 class="mt-3 text-2xl font-bold text-white sm:text-3xl">Let’s make something meaningful.</h2><p class="mt-3 max-w-2xl leading-6 text-slate-400">For help or product questions, please contact your CMS administrator. Public contact details have not been configured yet.</p></div>
                    <a href="#home" class="mt-7 inline-flex shrink-0 items-center justify-center rounded-full border border-white/15 px-5 py-3 font-semibold text-white transition hover:border-cyanbright/60 hover:bg-white/5 md:mt-0">Back to top ↑</a>
                </div>
            </div>
        </section>

        <section id="workflow" class="scroll-mt-24">
            <div class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-20">
                <div class="mx-auto max-w-2xl rounded-3xl border border-violetglow/25 bg-violetglow/[.07] p-7 text-center sm:p-10">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-violetglow/20 text-xl text-violet-200" aria-hidden="true">⌑</span>
                    <h2 class="mt-5 text-2xl font-bold text-white">A calmer publishing workflow</h2>
                    <p class="mt-3 leading-6 text-slate-300">Keep your content organized and your attention on the work that matters.</p>
                    <a href="/features" class="mt-6 inline-flex items-center justify-center rounded-full border border-white/15 px-5 py-3 font-semibold text-white transition hover:border-cyanbright/60 hover:bg-white/5">Explore CMSNivo features <span class="ml-2" aria-hidden="true">→</span></a>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer border-t border-white/10">
        <div class="site-footer__inner mx-auto flex max-w-7xl flex-col gap-4 px-5 py-7 text-sm text-slate-500 sm:px-8 md:flex-row md:items-center md:justify-between">
            <p>© <?php echo date('Y'); ?> <?php echo html_escape($web_name); ?>. Content, in control.</p>
            <nav aria-label="Footer navigation">
                <ul class="flex flex-wrap gap-x-5 gap-y-2">
                    <li><a href="/" class="footer-link" aria-current="page">Home</a></li>
                    <li><a href="/features" class="footer-link">Features</a></li>
                    <li><a href="/about" class="footer-link">About</a></li>
                    <li><a href="/contact" class="footer-link">Contact</a></li>
                    <li><a href="<?php echo $admin_user !== NULL ? '/admin' : '/login'; ?>" class="footer-link"><?php echo $admin_user !== NULL ? html_escape($admin_user['display_name']) : 'Login'; ?></a></li>
                </ul>
            </nav>
            <a href="#home" class="footer-link" aria-label="Back to top">Back to top ↑</a>
        </div>
    </footer>
    <script>
        (function () {
            var toggle = document.getElementById('menu-toggle');
            var navigation = document.getElementById('primary-navigation');
            if (!toggle || !navigation) return;
            toggle.addEventListener('click', function () {
                var isExpanded = toggle.getAttribute('aria-expanded') === 'true';
                toggle.setAttribute('aria-expanded', String(!isExpanded));
                navigation.classList.toggle('hidden', isExpanded);
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
