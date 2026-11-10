<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="<?php echo html_escape($html_lang); ?>" dir="<?php echo html_escape($text_direction); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f4f6fa">
    <title><?php echo html_escape($labels['auth_title']); ?> — <?php echo html_escape($web_name); ?></title>
    <?php
    $tailwind_css_path = FCPATH . 'assets/css/tailwind.css';
    $tailwind_css_version = is_file($tailwind_css_path) ? (string) filemtime($tailwind_css_path) : '1';
    ?>
    <link rel="stylesheet" href="/assets/css/tailwind.css?v=<?php echo rawurlencode($tailwind_css_version); ?>">
    <style>
        #login-password { padding-inline-end: 4rem; }
        #login-password-toggle { inset-block: .25rem; inset-inline-end: .25rem; }
        .login-field-icon { inset-inline-start: .75rem; }
        .login-backdrop {
            background:
                radial-gradient(ellipse at 50% 0%, rgba(99, 102, 241, .06), transparent 48%),
                #f4f6fa;
        }
        .login-brand-panel {
            isolation: isolate;
            background: linear-gradient(150deg, #111827 0%, #172033 58%, #182b3a 100%);
        }
        .login-brand-panel::before {
            content: "";
            position: absolute;
            z-index: -1;
            inset: 0;
            background-image: radial-gradient(rgba(226, 232, 240, .42) .65px, transparent .65px);
            background-size: 22px 22px;
            opacity: .14;
            mask-image: linear-gradient(135deg, #000, transparent 76%);
            pointer-events: none;
        }
        .login-brand-panel::after {
            content: "";
            position: absolute;
            z-index: -1;
            inset-inline-end: -8rem;
            bottom: -12rem;
            width: 18rem;
            aspect-ratio: 1;
            border: 1px solid rgba(226, 232, 240, .12);
            border-radius: 9999px;
            box-shadow: 0 0 0 22px rgba(226, 232, 240, .025), 0 0 0 44px rgba(226, 232, 240, .02);
            pointer-events: none;
        }
        @media (min-width: 768px) and (max-height: 700px) {
            .login-backdrop { padding-block: .75rem; }
            #login-layout { min-height: 0; grid-template-columns: minmax(0, 1fr); }
            #login-layout > aside {
                min-height: 0;
                flex-direction: row;
                align-items: center;
                justify-content: flex-start;
                padding: .75rem 1.5rem;
            }
            #login-layout > aside > .login-brand-art,
            #login-layout > aside > div:last-child { display: none; }
            #login-layout > aside > a > span:first-child { width: 2rem; height: 2rem; }
            #login-layout > div:last-child { padding: 1rem 1.5rem; }
            #login-layout > div:last-child > div:first-child { max-width: 34rem; }
            #login-layout > div:last-child > div:first-child > p { margin-top: .25rem; }
            #login-layout > div:last-child form[method="post"] {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                align-items: end;
                gap: .75rem;
                margin-top: 1rem;
            }
            #login-layout > div:last-child form[method="post"] > * + * { margin: 0; }
            #login-layout > div:last-child form[method="post"] > button[type="submit"] { grid-column: 1 / -1; }
            #login-layout > div:last-child nav { margin-top: 1rem; padding-top: .75rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
<main class="login-backdrop relative isolate flex min-h-screen items-center justify-center overflow-hidden px-4 py-6 sm:px-6 sm:py-8">
    <section id="login-layout" class="relative grid w-full max-w-4xl overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-[0_24px_70px_-34px_rgba(15,23,42,0.24)] md:min-h-[540px] md:grid-cols-[0.82fr_1.18fr]" aria-labelledby="login-title">
        <aside class="login-brand-panel relative flex min-h-32 flex-col justify-between overflow-hidden px-6 py-5 text-white sm:min-h-36 sm:px-8 md:min-h-[540px] md:px-9 md:py-8" aria-label="<?php echo html_escape($web_name); ?>">
            <a href="/" class="relative z-10 inline-flex w-fit items-center gap-3 rounded-xl text-sm font-semibold tracking-tight text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-cyanbright focus-visible:ring-offset-4 focus-visible:ring-offset-panel">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-white p-1.5 shadow-sm">
                    <img src="/assets/logo_box.png" alt="" aria-hidden="true" class="h-full w-full object-contain">
                </span>
                <span><?php echo html_escape($web_name); ?></span>
            </a>

            <div class="login-brand-art pointer-events-none relative z-10 hidden flex-1 items-center justify-center md:flex" aria-hidden="true">
                <div class="relative grid h-56 w-56 place-items-center">
                    <span class="absolute inset-0 rounded-full border border-white/10"></span>
                    <span class="absolute inset-5 rotate-45 rounded-[2rem] border border-violet-200/20"></span>
                    <span class="absolute inset-10 rounded-full border border-cyan-100/10"></span>
                    <span class="absolute start-4 top-8 h-2 w-2 rounded-full bg-cyan-300/80"></span>
                    <span class="absolute end-1 bottom-12 h-1.5 w-1.5 rounded-full bg-violet-300/80"></span>
                    <span class="grid h-20 w-20 place-items-center rounded-[1.6rem] border border-white/15 bg-white/[.08] shadow-xl shadow-black/10">
                        <svg class="h-10 w-10 text-cyan-100" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="3" y="3" width="8" height="8" rx="2"/><rect x="13" y="3" width="8" height="8" rx="2" opacity=".55"/><rect x="3" y="13" width="8" height="8" rx="2" opacity=".55"/><rect x="13" y="13" width="8" height="8" rx="2"/></svg>
                    </span>
                </div>
            </div>

            <div class="relative z-10 hidden items-center gap-2 md:flex" aria-hidden="true">
                <span class="h-px w-8 bg-cyan-200/70"></span>
                <span class="h-px flex-1 bg-white/15"></span>
            </div>
        </aside>

        <div class="flex min-w-0 flex-col justify-center px-5 py-7 sm:px-9 sm:py-9 lg:px-12 lg:py-10">
            <div class="mx-auto w-full max-w-sm">
                <h1 id="login-title" class="text-3xl font-semibold tracking-tight text-slate-900 sm:text-[2rem]"><?php echo html_escape($labels['auth_title']); ?></h1>
                <p class="mt-2 text-sm leading-6 text-slate-600 sm:text-[.9375rem]"><?php echo html_escape($labels['auth_intro']); ?></p>

                <?php if ($error_message !== NULL): ?>
                    <p id="login-error" class="mt-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm leading-6 text-rose-800" role="alert" aria-live="assertive"><?php echo html_escape($error_message); ?></p>
                <?php endif; ?>

                <form method="post" action="/login" class="mt-7 space-y-4" autocomplete="on">
                    <input type="hidden" name="<?php echo html_escape($csrf_token_name); ?>" value="<?php echo html_escape($csrf_hash); ?>">
                    <div>
                        <label for="login-username" class="mb-2 block text-sm font-semibold text-slate-800"><?php echo html_escape($labels['auth_username']); ?></label>
                        <div class="relative">
                            <svg class="login-field-icon pointer-events-none absolute top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="8" r="4"/></svg>
                            <input id="login-username" name="username" type="text" autocomplete="username" maxlength="254" required<?php echo $error_message !== NULL ? ' aria-describedby="login-error"' : ''; ?> class="h-11 w-full rounded-xl border border-slate-200 bg-white pe-4 ps-11 text-start text-base text-slate-900 shadow-sm transition placeholder:text-slate-400 hover:border-slate-300 focus:border-indigo-500 focus:outline-none focus-visible:ring-4 focus-visible:ring-indigo-500/15">
                        </div>
                    </div>
                    <div>
                        <label for="login-password" class="mb-2 block text-sm font-semibold text-slate-800"><?php echo html_escape($labels['auth_password']); ?></label>
                        <div class="relative">
                            <svg class="login-field-icon pointer-events-none absolute top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 10V7a4 4 0 1 1 8 0v3"/><path stroke-linecap="round" d="M12 14v3"/></svg>
                            <input id="login-password" name="password" type="password" autocomplete="current-password" maxlength="4096" required<?php echo $error_message !== NULL ? ' aria-describedby="login-error"' : ''; ?> class="h-11 w-full rounded-xl border border-slate-200 bg-white pe-16 ps-11 text-start text-base text-slate-900 shadow-sm transition placeholder:text-slate-400 hover:border-slate-300 focus:border-indigo-500 focus:outline-none focus-visible:ring-4 focus-visible:ring-indigo-500/15">
                            <button id="login-password-toggle" type="button" aria-controls="login-password" aria-pressed="false" data-show-label="<?php echo html_escape($labels['auth_password_show']); ?>" data-hide-label="<?php echo html_escape($labels['auth_password_hide']); ?>" aria-label="<?php echo html_escape($labels['auth_password_show']); ?>" class="absolute inline-flex min-w-12 items-center justify-center rounded-lg p-2 text-slate-500 transition-colors hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-600" title="<?php echo html_escape($labels['auth_password_show']); ?>">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-xl bg-indigo-700 px-4 text-sm font-semibold text-white shadow-sm shadow-indigo-900/10 transition-colors hover:bg-indigo-800 focus:outline-none focus-visible:ring-4 focus-visible:ring-indigo-500/30 focus-visible:ring-offset-2"><?php echo html_escape($labels['auth_submit']); ?></button>
                </form>

                <nav class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-5" aria-label="<?php echo html_escape($labels['auth_language']); ?>">
                    <a href="/" class="inline-flex items-center gap-2 rounded-lg text-sm font-medium text-slate-600 transition hover:text-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-600">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m10 17 5-5-5-5M15 12H3"/><path stroke-linecap="round" d="M13 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5"/></svg>
                        <span><?php echo html_escape($labels['auth_home']); ?></span>
                    </a>
                    <form method="get" action="/login" class="flex items-center gap-2">
                        <label for="login-language" class="sr-only"><?php echo html_escape($labels['auth_language']); ?></label>
                        <select id="login-language" name="lang" class="h-10 max-w-40 cursor-pointer rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-800 transition hover:border-slate-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/30" onchange="this.form.submit()">
                            <option value="english" lang="en"<?php echo $locale === 'english' ? ' selected' : ''; ?>>English</option>
                            <option value="chinese" lang="zh-CN"<?php echo $locale === 'chinese' ? ' selected' : ''; ?>>中文</option>
                            <option value="french" lang="fr"<?php echo $locale === 'french' ? ' selected' : ''; ?>>Français</option>
                            <option value="german" lang="de"<?php echo $locale === 'german' ? ' selected' : ''; ?>>Deutsch</option>
                            <option value="indonesian" lang="id"<?php echo $locale === 'indonesian' ? ' selected' : ''; ?>>Bahasa Indonesia</option>
                            <option value="japanese" lang="ja"<?php echo $locale === 'japanese' ? ' selected' : ''; ?>>日本語</option>
                            <option value="korean" lang="ko"<?php echo $locale === 'korean' ? ' selected' : ''; ?>>한국어</option>
                            <option value="spanish" lang="es"<?php echo $locale === 'spanish' ? ' selected' : ''; ?>>Español</option>
                            <option value="arabic" lang="ar"<?php echo $locale === 'arabic' ? ' selected' : ''; ?>>العربية</option>
                        </select>
                    </form>
                </nav>
            </div>
        </div>
    </section>
</main>
<script>
(function () {
    var password = document.getElementById('login-password');
    var toggle = document.getElementById('login-password-toggle');
    if (!password || !toggle) return;
    toggle.addEventListener('click', function () {
        var showPassword = password.type === 'password';
        password.type = showPassword ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', String(showPassword));
        var label = showPassword ? toggle.getAttribute('data-hide-label') : toggle.getAttribute('data-show-label');
        toggle.setAttribute('aria-label', label);
        toggle.setAttribute('title', label);
    });
}());
</script>
</body>
</html>
