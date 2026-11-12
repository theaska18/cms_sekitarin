<?php
$admin_user = is_array($current_user) ? $current_user : array();
$theme = strtolower(trim((string) (isset($admin_user['theme']) ? $admin_user['theme'] : '')));
if (!in_array($theme, array('dark', 'light'), TRUE)) {
    $theme = 'light';
}
$csrf_name = isset($csrf_token_name) ? $csrf_token_name : $this->security->get_csrf_token_name();
$csrf_value = isset($csrf_hash) ? $csrf_hash : $this->security->get_csrf_hash();
$labels = isset($labels) && is_array($labels) ? $labels : array();
$theme_dark_label = $labels['setting_theme_dark'] ?? $labels['admin_setting_theme_dark'] ?? 'Dark';
$theme_light_label = $labels['setting_theme_light'] ?? $labels['admin_setting_theme_light'] ?? 'Light';
$theme_label = $labels['setting_theme'] ?? $labels['admin_setting_theme'] ?? 'Theme';
$theme_note = $labels['setting_theme_note'] ?? $labels['admin_setting_theme_note'] ?? 'Your theme preference is saved to your account.';
$setting_locale = isset($setting_locale) ? $setting_locale : 'english';
$admin_languages = isset($admin_languages) && is_array($admin_languages) ? $admin_languages : array();
if (!$admin_languages) {
    $admin_languages = array('english' => array('label' => 'English', 'flag' => '🇬🇧', 'html_lang' => 'en'));
}
$setting_locale = isset($admin_languages[$setting_locale]) ? $setting_locale : (isset($admin_languages['english']) ? 'english' : (string) key($admin_languages));
$setting_language_label = isset($labels['setting_current_language']) ? $labels['setting_current_language'] : (isset($labels['admin_setting_current_language']) ? $labels['admin_setting_current_language'] : 'Language');
?>
<div class="mb-6">
    <p class="text-xs font-bold uppercase tracking-[.16em] text-emerald-700"><?php echo html_escape($labels['admin_area']); ?></p>
    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl"><?php echo html_escape($page_title); ?></h1>
    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600"><?php echo html_escape($labels['setting_intro']); ?></p>
</div>

<?php if ($setting_notice === 'saved'): ?>
    <div role="status" class="mb-5 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"><span class="mt-0.5 font-bold" aria-hidden="true">✓</span><p><?php echo html_escape($labels['setting_saved']); ?></p></div>
<?php elseif ($setting_notice === 'invalid'): ?>
    <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900"><?php echo html_escape($labels['setting_invalid']); ?></div>
<?php endif; ?>

<div class="grid max-w-5xl gap-5 lg:grid-cols-[minmax(0,1fr)_19rem]">
    <form action="<?= site_url('admin/setting') ?>" method="post" class="space-y-5" id="admin-setting-form">
        <input type="hidden" name="<?php echo html_escape($csrf_name); ?>" value="<?php echo html_escape($csrf_value); ?>">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="setting-language-heading">
            <div class="flex items-start gap-3"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-800 ring-1 ring-emerald-100" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5h12M10 3v2m4 0c-.7 4.1-3.1 7.5-7 9m1-5c1.2 2 3.1 3.7 5.5 5M14 20l3.3-8 3.2 8m-5.5-2h4.5"/></svg></span><div><h2 id="setting-language-heading" class="text-base font-bold text-slate-900"><?php echo html_escape($labels['setting_language_heading']); ?></h2><p class="mt-1 text-sm leading-6 text-slate-600"><?php echo html_escape($labels['setting_language_help']); ?></p></div></div>
            <div class="mt-5 max-w-xl">
                <label for="setting-locale" class="mb-2 block text-sm font-semibold text-slate-700"><?php echo html_escape($setting_language_label); ?></label>
                <div class="flex items-center gap-3"><span id="setting-locale-flag" class="text-3xl" aria-hidden="true"><?php echo html_escape($admin_languages[$setting_locale]['flag']); ?></span><select id="setting-locale" name="locale" form="admin-setting-form" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-200" aria-label="<?php echo html_escape($setting_language_label); ?>">
                    <?php foreach ($admin_languages as $locale_code => $language): ?><option value="<?php echo html_escape($locale_code); ?>" data-flag="<?php echo html_escape($language['flag']); ?>" lang="<?php echo html_escape($language['html_lang']); ?>" <?php echo $setting_locale === $locale_code ? 'selected' : ''; ?>><?php echo html_escape($language['label']); ?></option><?php endforeach; ?>
                </select></div>
            </div>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="setting-appearance-heading">
            <div class="flex items-start gap-3"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-amber-50 text-amber-800 ring-1 ring-amber-100" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 3a9 9 0 0 0 0 18V3Z"/></svg></span><div><h2 id="setting-appearance-heading" class="text-base font-bold text-slate-900"><?php echo html_escape($labels['setting_appearance_heading']); ?></h2><p class="mt-1 text-sm leading-6 text-slate-600"><?php echo html_escape($labels['setting_appearance_help']); ?></p></div></div>
            <fieldset class="mt-5 max-w-xl"><legend class="mb-2 block text-sm font-semibold text-slate-700"><?php echo html_escape($theme_label); ?></legend><div class="grid gap-3 sm:grid-cols-2">
                <label class="flex min-h-12 cursor-pointer items-center gap-3 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-800"><input type="radio" name="theme" value="light" class="h-4 w-4 accent-emerald-700" <?php echo $theme === 'light' ? 'checked' : ''; ?>><span><?php echo html_escape($theme_light_label); ?></span></label>
                <label class="flex min-h-12 cursor-pointer items-center gap-3 rounded-lg border border-slate-700 bg-slate-900 px-4 text-sm font-semibold text-white"><input type="radio" name="theme" value="dark" class="h-4 w-4 accent-emerald-400" <?php echo $theme === 'dark' ? 'checked' : ''; ?>><span><?php echo html_escape($theme_dark_label); ?></span></label>
            </div><p id="setting-theme-note" class="mt-2 text-xs leading-5 text-slate-500"><?php echo html_escape($theme_note); ?></p></fieldset>
        </section>
    </form>
    <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="setting-account-heading"><div class="flex items-center gap-3"><img src="<?php echo html_escape(!empty($admin_user['profile_image']) ? $admin_user['profile_image'] : '/assets/images/avatar-placeholder.svg'); ?>" alt="" aria-hidden="true" class="h-12 w-12 rounded-full border border-slate-200 bg-slate-100 object-cover"><div class="min-w-0"><p class="text-xs font-medium uppercase tracking-wide text-slate-500"><?php echo html_escape($labels['setting_account_heading']); ?></p><p class="truncate text-sm font-bold text-slate-900"><?php echo html_escape($admin_user['display_name'] ?? ''); ?></p></div></div><p class="mt-4 text-sm leading-6 text-slate-600"><?php echo html_escape($labels['setting_account_help']); ?></p><a href="/admin/profile" class="mt-4 inline-flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"><?php echo html_escape($labels['profile']); ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg></a></aside>
</div>

<script>
(function () {
    var language = document.getElementById('setting-locale');
    var languageFlag = document.getElementById('setting-locale-flag');
    if (language && languageFlag) {
        language.addEventListener('change', function () {
            var option = language.options[language.selectedIndex];
            languageFlag.textContent = option.getAttribute('data-flag') || '';
            language.form.requestSubmit();
        });
    }

    var form = document.getElementById('admin-setting-form');
    if (!form) return;

    form.querySelectorAll('input[name="theme"]').forEach(function (input) {
        input.addEventListener('change', function () {
            if (input.checked) form.requestSubmit();
        });
    });
}());
</script>
