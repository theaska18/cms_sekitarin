<?php
$menu_icons = array(
    'dashboard' => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/>',
    'building' => '<rect x="4" y="2" width="16" height="20" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 22v-4h6v4M8 6h.01M12 6h.01M16 6h.01M8 10h.01M12 10h.01M16 10h.01M8 14h.01M12 14h.01M16 14h.01"/>',
    'sliders-horizontal' => '<path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/><circle cx="9" cy="6" r="2"/><circle cx="15" cy="12" r="2"/><circle cx="7" cy="18" r="2"/>',
    'parameter' => '<path stroke-linecap="round" d="M5 5h14M5 12h14M5 19h14"/><path stroke-linecap="round" d="M8 3v4M16 10v4M11 17v4"/>',
    'menu' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r=".8"/><circle cx="4.5" cy="12" r=".8"/><circle cx="4.5" cy="18" r=".8"/>',
    'list-ordered' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 7h12l-3-3m3 3-3 3M20 17H8l3 3m-3-3 3-3"/>',
    'users' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18M10 12v2h4v-2"/>',
    'shield-check' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3 20 6v5c0 5-3.5 8-8 10-4.5-2-8-5-8-10V6l8-3Z"/><path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-4"/>',
    'user-round' => '<circle cx="12" cy="8" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 21a8 8 0 0 1 16 0"/>',
    'default' => '<circle cx="12" cy="12" r="8"/>',
);
?>
<!doctype html>
<html lang="<?php echo html_escape($html_lang); ?>" dir="<?php echo html_escape($text_direction); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <title><?php echo html_escape($page_title); ?> — <?php echo html_escape($web_name); ?></title>
    <link rel="icon" type="image/png" href="/assets/logo_box.png">
    <link rel="stylesheet" href="/assets/css/tailwind.css?v=<?= is_file(FCPATH . 'assets/css/tailwind.css') ? filemtime(FCPATH . 'assets/css/tailwind.css') : '1' ?>">
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
<div id="admin-app-shell">
<header class="admin-topbar sticky top-0 z-30 border-b border-slate-200 text-slate-900">
    <div class="mx-auto flex min-h-16 max-w-screen-2xl items-center justify-between gap-1 px-4 sm:gap-3 sm:px-6 lg:px-8">
        <div class="flex min-w-0 items-center gap-2">
            <button id="admin-menu-toggle" type="button" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-700 shadow-sm hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white md:hidden" aria-label="<?php echo html_escape($labels['open_navigation']); ?>" aria-controls="admin-sidebar" aria-expanded="false">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" /></svg>
            </button>
            <a href="/admin" class="flex min-w-0 items-center gap-2 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500" aria-label="<?php echo html_escape($web_name . ' ' . $labels['dashboard']); ?>">
                <img src="/assets/logo_box.png" alt="" aria-hidden="true" class="h-8 w-8 shrink-0 object-contain sm:h-10 sm:w-10">
                <span class="max-w-[5.5rem] truncate text-xs font-bold tracking-tight text-slate-900 max-[429px]:hidden sm:max-w-none sm:text-lg"><?php echo html_escape($web_name); ?></span>
            </a>
        </div>
        <nav aria-label="<?php echo html_escape($labels['topbar_navigation']); ?>" class="flex shrink-0 items-center gap-0 sm:gap-2">
            <button id="admin-home-link" type="button" class="hidden md:inline-flex md:h-11 md:w-auto shrink-0 items-center justify-center rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-100 hover:text-slate-950 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500" aria-label="Home"><svg class="h-5 w-5 md:mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5L12 3l9 7.5M5 9v11h14V9M9 20v-6h6v6"/></svg><span class="sr-only md:not-sr-only">Home</span></button>
            <form id="admin-language-form" method="get" class="relative flex items-center">
                <label class="sr-only" for="admin-language"><?php echo html_escape($labels['language']); ?></label>
                <select id="admin-language" name="lang" class="h-10 w-16 shrink-0 rounded-lg bg-white px-1 text-xl text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 sm:h-11 sm:w-auto sm:px-3" onchange="this.form.submit()">
                    <?php foreach ($admin_languages as $language_code => $language_option): ?>
                        <option value="<?php echo html_escape($language_code); ?>" lang="<?php echo html_escape($language_option['html_lang']); ?>" aria-label="<?php echo html_escape($language_option['label']); ?>"<?php echo $locale === $language_code ? ' selected' : ''; ?>><?php echo html_escape($language_option['flag']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button id="admin-language-toggle" type="button" hidden class="inline-flex h-10 w-16 shrink-0 items-center justify-center rounded-lg bg-white px-1 text-xl text-slate-800 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 sm:h-11 sm:w-auto sm:px-3" aria-label="<?php echo html_escape($labels['language'] . ': ' . $admin_languages[$locale]['label']); ?>" data-language-label="<?php echo html_escape($labels['language']); ?>" aria-haspopup="listbox" aria-expanded="false" aria-controls="admin-language-options">
                    <span id="admin-language-current-flag" aria-hidden="true"><svg class="h-5 w-7 rounded-sm" viewBox="0 0 28 20" focusable="false"><rect width="28" height="20" fill="#012169"/><path d="M0 0 28 20M28 0 0 20" stroke="#fff" stroke-width="5"/><path d="M0 0 28 20M28 0 0 20" stroke="#c8102e" stroke-width="2"/><path d="M14 0v20M0 10h28" stroke="#fff" stroke-width="7"/><path d="M14 0v20M0 10h28" stroke="#c8102e" stroke-width="3"/></svg></span>
                    <span class="sr-only"><?php echo html_escape($labels['language'] . ': ' . $admin_languages[$locale]['label']); ?></span>
                </button>
                <div id="admin-language-options" role="listbox" aria-label="<?php echo html_escape($labels['language']); ?>" hidden class="admin-language-options absolute left-0 top-full z-50 mt-2 grid grid-cols-2 gap-1.5 rounded-xl border border-slate-200 bg-white p-2 text-slate-700 shadow-xl">
                    <?php foreach ($admin_languages as $language_code => $language_option): ?>
                        <div role="option" tabindex="-1" data-locale="<?php echo html_escape($language_code); ?>" lang="<?php echo html_escape($language_option['html_lang']); ?>" aria-label="<?php echo html_escape($language_option['label']); ?>" aria-selected="<?php echo $locale === $language_code ? 'true' : 'false'; ?>" class="flex min-h-12 min-w-0 cursor-pointer items-center gap-2 rounded-lg border px-2 text-start text-xs leading-4 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-emerald-600 <?php echo $locale === $language_code ? 'border-emerald-200 bg-emerald-50 font-semibold text-emerald-900' : 'border-transparent text-slate-700 hover:border-slate-200 hover:bg-slate-50'; ?>">
                            <?php if ($language_code === 'english'): ?><svg aria-hidden="true" class="h-5 w-7 rounded-sm" viewBox="0 0 28 20" focusable="false"><rect width="28" height="20" fill="#012169"/><path d="M0 0 28 20M28 0 0 20" stroke="#fff" stroke-width="5"/><path d="M0 0 28 20M28 0 0 20" stroke="#c8102e" stroke-width="2"/><path d="M14 0v20M0 10h28" stroke="#fff" stroke-width="7"/><path d="M14 0v20M0 10h28" stroke="#c8102e" stroke-width="3"/></svg>
                            <?php elseif ($language_code === 'indonesian'): ?><svg aria-hidden="true" class="h-5 w-7 rounded-sm" viewBox="0 0 28 20" focusable="false"><rect width="28" height="10" fill="#e31b23"/><rect y="10" width="28" height="10" fill="#fff"/></svg>
                            <?php elseif ($language_code === 'japanese'): ?><svg aria-hidden="true" class="h-5 w-7 rounded-sm" viewBox="0 0 28 20" focusable="false"><rect width="28" height="20" fill="#fff"/><circle cx="14" cy="10" r="6" fill="#bc002d"/></svg>
                            <?php elseif ($language_code === 'chinese'): ?><svg aria-hidden="true" class="h-5 w-7 rounded-sm" viewBox="0 0 28 20" focusable="false"><rect width="28" height="20" fill="#de2910"/><path d="m6 2 1.1 2.4 2.6.3-1.9 1.7.5 2.6L6 7.7l-2.3 1.3.5-2.6-1.9-1.7 2.6-.3z" fill="#ffde00"/><circle cx="12" cy="3" r=".8" fill="#ffde00"/><circle cx="14" cy="5.5" r=".8" fill="#ffde00"/><circle cx="14" cy="8" r=".8" fill="#ffde00"/><circle cx="12" cy="10" r=".8" fill="#ffde00"/></svg>
                            <?php elseif ($language_code === 'arabic'): ?><svg aria-hidden="true" class="h-5 w-7 rounded-sm" viewBox="0 0 28 20" focusable="false"><rect width="28" height="20" fill="#006c35"/><path d="M5 7h14" stroke="#fff" stroke-width="1.3"/><path d="M5 12h17M5 11l-.8 1 1.6 1" fill="none" stroke="#fff" stroke-width="1.4" stroke-linecap="round"/></svg>
                            <?php elseif ($language_code === 'korean'): ?><svg aria-hidden="true" class="h-5 w-7 rounded-sm" viewBox="0 0 28 20" focusable="false"><rect width="28" height="20" fill="#fff"/><path d="M8 10a3 3 0 0 1 6 0 3 3 0 0 1-6 0" fill="#cd2e3a"/><path d="M14 10a3 3 0 0 1 6 0 3 3 0 0 1-6 0" fill="#0047a0"/><path d="M3 4h5M3 6h5M20 14h5M20 16h5" stroke="#111" stroke-width=".8"/></svg>
                            <?php elseif ($language_code === 'spanish'): ?><svg aria-hidden="true" class="h-5 w-7 rounded-sm" viewBox="0 0 28 20" focusable="false"><rect width="28" height="20" fill="#aa151b"/><rect y="5" width="28" height="10" fill="#f1bf00"/><path d="M8 7h2v4H8z" fill="#aa151b"/></svg>
                            <?php elseif ($language_code === 'french'): ?><svg aria-hidden="true" class="h-5 w-7 rounded-sm" viewBox="0 0 28 20" focusable="false"><rect width="9.34" height="20" fill="#0055a4"/><rect x="9.34" width="9.33" height="20" fill="#fff"/><rect x="18.67" width="9.33" height="20" fill="#ef4135"/></svg>
                            <?php else: ?><svg aria-hidden="true" class="h-5 w-7 rounded-sm" viewBox="0 0 28 20" focusable="false"><rect width="28" height="6.67" fill="#000"/><rect y="6.67" width="28" height="6.66" fill="#d00"/><rect y="13.33" width="28" height="6.67" fill="#ffce00"/></svg>
                            <?php endif; ?>
                            <span class="min-w-0 flex-1"><?php echo html_escape($language_option['label']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="sr-only focus:not-sr-only focus:ml-1 focus:rounded focus:bg-white focus:px-2 focus:py-1 focus:text-xs focus:text-emerald-800"><?php echo html_escape($labels['apply_language']); ?></button>
            </form>
            <div class="relative shrink-0">
                <button id="admin-profile-toggle" type="button" class="inline-flex min-h-10 items-center gap-1 rounded-lg px-1 text-sm font-medium text-slate-700 hover:bg-slate-100 hover:text-slate-950 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 sm:min-h-11 sm:gap-2 sm:px-2" aria-label="<?php echo html_escape($current_user['display_name']); ?>" aria-haspopup="menu" aria-expanded="false" aria-controls="admin-profile-menu">
                    <img src="<?php echo html_escape(!empty($current_user['profile_image']) ? $current_user['profile_image'] : '/assets/images/avatar-placeholder.svg'); ?>" alt="" aria-hidden="true" class="h-9 w-9 rounded-full border border-slate-200 bg-slate-100 object-cover">
                    <span class="sr-only sm:not-sr-only sm:text-sm sm:font-semibold sm:text-slate-800"><?php echo html_escape($current_user['display_name']); ?></span>
                </button>
                <div id="admin-profile-menu" role="menu" hidden class="absolute <?php echo $is_rtl ? 'left-0' : 'right-0'; ?> top-full z-50 mt-2 w-48 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-slate-700 shadow-xl" aria-labelledby="admin-profile-toggle">
                    <a role="menuitem" href="/admin/setting" class="block min-h-11 px-4 py-3 text-sm font-medium hover:bg-slate-50 focus:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-emerald-500"><?php echo html_escape($labels['settings']); ?></a>
                    <a role="menuitem" href="/admin/profile" class="block min-h-11 px-4 py-3 text-sm font-medium hover:bg-slate-50 focus:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-emerald-500"><?php echo html_escape($labels['profile']); ?></a>
                    <form method="post" action="/logout" role="none">
                        <input type="hidden" name="<?php echo html_escape($csrf_token_name); ?>" value="<?php echo html_escape($csrf_hash); ?>">
                        <button role="menuitem" type="submit" class="block min-h-11 w-full px-4 py-3 text-start text-sm font-medium hover:bg-slate-50 focus:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-emerald-500"><?php echo html_escape($labels['logout']); ?></button>
                    </form>
                </div>
            </div>
        </nav>
    </div>
</header>
<button id="admin-sidebar-backdrop" type="button" class="fixed inset-0 z-40 hidden bg-slate-950/40 md:hidden" aria-label="<?php echo html_escape($labels['close_navigation']); ?>"></button>
<div class="mx-auto flex max-w-screen-2xl gap-6 px-4 py-5 sm:px-6 lg:gap-8 lg:px-8">
    <aside id="admin-sidebar" class="fixed inset-y-0 <?php echo $is_rtl ? 'right-0 border-l' : 'left-0 border-r'; ?> z-50 hidden w-72 overflow-x-hidden overflow-y-auto border-slate-200 bg-white px-3 py-4 shadow-xl md:sticky md:inset-auto md:top-20 md:z-auto md:block md:h-[calc(100vh-6rem)] md:w-60 md:shrink-0 md:rounded-2xl md:border md:shadow-sm lg:w-64" aria-label="<?php echo html_escape($labels['admin_area']); ?>">
        <div class="relative mb-5 flex min-h-14 items-center justify-center px-12 md:hidden">
            <a href="/admin" aria-label="<?php echo html_escape($web_name); ?>" class="inline-flex max-w-full items-center justify-center rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                <img src="/assets/logo_banner.png" alt="" aria-hidden="true" class="mx-auto h-auto max-h-12 w-48 max-w-full object-contain">
            </a>
            <button id="admin-sidebar-close" type="button" class="absolute <?php echo $is_rtl ? 'left-0' : 'right-0'; ?> top-1/2 inline-flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500" aria-label="<?php echo html_escape($labels['close_navigation']); ?>"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="m6 6 12 12M18 6 6 18"/></svg></button>
        </div>
        <div role="search" class="mb-4">
            <div class="admin-sidebar-filter-wrap relative min-w-0">
                <svg class="admin-sidebar-filter-icon pointer-events-none absolute top-1/2 h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false"><circle cx="8.5" cy="8.5" r="5.5"/><path stroke-linecap="round" d="m13 13 4 4"/></svg>
                <input id="admin-sidebar-filter" type="search" autocomplete="off" aria-label="<?php echo html_escape($labels['filter_label']); ?>" aria-controls="admin-sidebar-navigation" data-results-template="<?php echo html_escape($labels['filter_results']); ?>" placeholder="<?php echo html_escape($labels['filter_placeholder']); ?>" class="admin-sidebar-filter h-10 min-w-0 w-full rounded-lg border border-slate-300 bg-white text-base text-slate-800 placeholder:text-slate-400 focus:border-emerald-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 md:text-sm">
            </div>
        </div>
        <nav id="admin-sidebar-navigation" aria-label="<?php echo html_escape($labels['sidebar_navigation']); ?>" class="space-y-4">
            <section data-admin-menu-group aria-label="<?php echo html_escape($labels['dashboard']); ?>">
                <ul class="space-y-1">
                    <li data-admin-menu-item><button id="admin-home-link-mobile" type="button" data-menu-label="Home" aria-label="Home" class="admin-menu-link flex min-h-10 w-full items-center gap-2.5 rounded-lg px-2.5 text-start text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 md:hidden"><svg class="admin-menu-icon h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5L12 3l9 7.5M5 9v11h14V9M9 20v-6h6v6"/></svg><span class="admin-menu-label">Home</span></button></li>
                    <li data-admin-menu-item><a href="/admin" data-admin-menu-link data-menu-label="<?php echo html_escape($labels['dashboard']); ?>" class="admin-menu-link flex min-h-10 items-center gap-2.5 rounded-lg px-2.5 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 <?php echo $current_path === '/admin' ? 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-100' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>"<?php echo $current_path === '/admin' ? ' aria-current="page"' : ''; ?>><svg class="admin-menu-icon h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false"><?php echo $menu_icons['dashboard']; ?></svg><span class="admin-menu-label"><?php echo html_escape($labels['dashboard']); ?></span></a></li>
                </ul>
            </section>
            <?php foreach ($menu_groups as $group): ?>
                <section data-admin-menu-group aria-label="<?php echo html_escape($group['label']); ?>">
                    <h2 class="px-2.5 text-[.68rem] font-bold uppercase tracking-[.12em] text-slate-400"><?php echo html_escape($group['label']); ?></h2>
                    <ul class="mt-1.5 space-y-0.5">
                        <?php foreach ($group['items'] as $item): ?>
                            <?php $is_current = $current_path === $item['route']; ?>
                            <li data-admin-menu-item><a href="<?php echo html_escape($item['route']); ?>" data-admin-menu-link data-menu-label="<?php echo html_escape($item['label']); ?>" class="admin-menu-link flex min-h-10 items-center gap-2.5 rounded-lg px-2.5 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 <?php echo $is_current ? 'bg-emerald-50 font-semibold text-emerald-800 ring-1 ring-emerald-100' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>"<?php echo $is_current ? ' aria-current="page"' : ''; ?>><svg class="admin-menu-icon h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false"><?php echo isset($menu_icons[$item['icon']]) ? $menu_icons[$item['icon']] : $menu_icons['default']; ?></svg><span class="admin-menu-label"><?php echo html_escape($item['label']); ?></span></a></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endforeach; ?>
        </nav>
        <div id="admin-sidebar-filter-empty" hidden class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-3 text-sm text-slate-600" role="status" aria-live="polite">
            <p data-filter-empty-message><?php echo html_escape($labels['filter_empty']); ?></p>
        </div>
        <p id="admin-sidebar-filter-status" class="sr-only" role="status" aria-live="polite" aria-atomic="true"></p>
    </aside>
    <main id="admin-main" class="min-w-0 flex-1" tabindex="-1">