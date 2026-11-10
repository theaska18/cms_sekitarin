<?php
$nav_items = array(
    array('label' => 'Home', 'url' => '/', 'page' => 'home'),
    array('label' => 'Features', 'url' => '/features', 'page' => 'features'),
    array('label' => 'About', 'url' => '/about', 'page' => 'about'),
    array('label' => 'Contact', 'url' => '/contact', 'page' => 'contact'),
    array('label' => $admin_user !== NULL ? $admin_user['display_name'] : 'Login', 'url' => $admin_user !== NULL ? '/admin' : '/login', 'page' => $admin_user !== NULL ? 'admin' : 'login'),
);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#080d1b">
    <title><?php echo html_escape($page_title); ?> — <?php echo html_escape($web_name); ?></title>
    <?php $tailwind_css_path = FCPATH . 'assets/css/tailwind.css'; $tailwind_css_version = is_file($tailwind_css_path) ? (string) filemtime($tailwind_css_path) : '1'; ?>
    <link rel="stylesheet" href="/assets/css/tailwind.css?v=<?php echo rawurlencode($tailwind_css_version); ?>">
    <style>
        html { scroll-behavior: smooth; }
        body { font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        ::selection { background: rgba(139, 92, 246, .4); color: #fff; }
        @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } *, *::before, *::after { animation-duration: .01ms !important; transition-duration: .01ms !important; } }
    </style>
</head>
<body class="min-h-screen bg-ink text-white antialiased">
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
                <?php foreach ($nav_items as $item): ?>
                    <?php $is_current = $current_page === $item['page']; ?>
                    <li><a class="site-nav-link<?php echo $is_current ? ' is-current' : ''; ?><?php echo ($item['page'] === 'login' || $item['page'] === 'admin') ? ' site-nav-link--cta' : ''; ?>" href="<?php echo html_escape($item['url']); ?>"<?php echo $is_current ? ' aria-current="page"' : ''; ?>><?php echo html_escape($item['label']); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>
</header>
