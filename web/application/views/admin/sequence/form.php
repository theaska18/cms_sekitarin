<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$sequence_record = is_array($sequence_record) ? $sequence_record : array();
$sequence_field_specs = is_array($sequence_field_specs) ? $sequence_field_specs : array();
$sequence_repeat_type_options = is_array($sequence_repeat_type_options) ? $sequence_repeat_type_options : array();
$sequence_errors = is_array($sequence_errors) ? $sequence_errors : array();
$sequence_disabled = !$sequence_write_available || !$sequence_backend_available;
$sequence_is_edit = isset($sequence_is_edit) && $sequence_is_edit;
$sequence_form_title = $sequence_is_edit ? $labels['sequence_form_edit'] : $labels['sequence_form_new'];
$sequence_control_class = 'min-h-12 w-full rounded-lg border border-slate-300 bg-white px-3 text-base text-slate-900 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500 disabled:opacity-60 read-only:cursor-not-allowed read-only:bg-slate-50 read-only:text-slate-500 read-only:opacity-60 sm:text-sm';
$sequence_maxlength_attribute = function ($field_name) use ($sequence_field_specs) {
    $max_length = isset($sequence_field_specs[$field_name]['max_length']) ? (int) $sequence_field_specs[$field_name]['max_length'] : 0;
    return $max_length > 0 ? ' maxlength="' . $max_length . '"' : '';
};
$sequence_required_attribute = function ($field_name) use ($sequence_field_specs) {
    return isset($sequence_field_specs[$field_name]['required']) && $sequence_field_specs[$field_name]['required'] ? ' required' : '';
};
$sequence_error_labels = array();
foreach ($sequence_errors as $error) {
    $error_key = 'sequence_' . $error;
    if (isset($labels[$error_key])) {
        $sequence_error_labels[] = $labels[$error_key];
    }
}
?>
<main class="px-0 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-2">
            <li><?= html_escape($labels['system']) ?></li>
            <li aria-hidden="true">/</li>
            <li><a href="/admin/system/sequence" class="hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"><?= html_escape($labels['sequence_heading']) ?></a></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" class="font-medium text-slate-800"><?= html_escape($sequence_form_title) ?></li>
        </ol>
    </nav>

    <header class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($sequence_form_title) ?></h1>
        <p class="mt-2 text-base leading-7 text-slate-600"><?= html_escape($labels['sequence_form_intro']) ?></p>
    </header>

    <?php if (!$sequence_backend_available): ?>
        <div role="status" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <p class="font-semibold"><?= html_escape($labels['sequence_unavailable']) ?></p>
            <p class="mt-1"><?= html_escape($labels['module_backend_unavailable']) ?></p>
        </div>
    <?php endif; ?>
    <?php if (!$sequence_write_available): ?>
        <div role="note" class="mb-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700"><?= html_escape($labels['sequence_write_disabled']) ?></div>
    <?php endif; ?>
    <?php if ($sequence_error_labels): ?>
        <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
            <p class="font-semibold"><?= html_escape($labels['sequence_invalid']) ?></p>
            <ul class="mt-2 list-inside list-disc">
                <?php foreach ($sequence_error_labels as $error_label): ?><li><?= html_escape($error_label) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= html_escape($current_path) ?>" method="post" class="w-full rounded-t-2xl rounded-b-none border border-slate-200 bg-white p-4 shadow-sm sm:p-8">
        <input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>">
        <div class="space-y-6">
            <div>
                <label for="sequence-code" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['sequence_sequence_code']) ?></label>
                <div class="relative">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.5 4 16.5M15.5 3.5 12 16.5M2.5 8h15M1.5 12h15"/></svg>
                    <input id="sequence-code" name="sequence_code" type="text"<?= $sequence_required_attribute('sequence_code') ?><?= $sequence_maxlength_attribute('sequence_code') ?> value="<?= html_escape(isset($sequence_record['sequence_code']) ? $sequence_record['sequence_code'] : '') ?>" <?= $sequence_disabled ? 'disabled' : 'autofocus' ?> autocomplete="off" autocapitalize="none" spellcheck="false" data-normalize-code class="<?= $sequence_control_class ?> pl-10 pr-3">
                </div>
                <p class="mt-1.5 text-sm text-slate-500"><?= html_escape($labels['sequence_code_help']) ?></p>
            </div>

            <div>
                <label for="sequence-name" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['sequence_sequence_name']) ?></label>
                <input id="sequence-name" name="sequence_name" type="text"<?= $sequence_required_attribute('sequence_name') ?><?= $sequence_maxlength_attribute('sequence_name') ?> value="<?= html_escape(isset($sequence_record['sequence_name']) ? $sequence_record['sequence_name'] : '') ?>" <?= $sequence_disabled ? 'disabled' : '' ?> autocomplete="off" data-title-case-name class="<?= $sequence_control_class ?>">
                <p class="mt-1.5 text-sm text-slate-500"><?= html_escape($labels['sequence_name_help']) ?></p>
            </div>

            <div>
                <label for="sequence-description" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['sequence_description']) ?></label>
                <textarea id="sequence-description" name="description" rows="3"<?= $sequence_maxlength_attribute('description') ?> <?= $sequence_disabled ? 'disabled' : '' ?> class="<?= $sequence_control_class ?> py-2.5"><?= html_escape(isset($sequence_record['description']) ? $sequence_record['description'] : '') ?></textarea>
                <p class="mt-1.5 text-sm text-slate-500"><?= html_escape($labels['sequence_description_help']) ?></p>
            </div>

            <div>
                <label for="sequence-repeat-type" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['sequence_repeat_type']) ?></label>
                <select id="sequence-repeat-type" name="repeat_type"<?= $sequence_required_attribute('repeat_type') ?> <?= $sequence_disabled ? 'disabled' : '' ?> class="<?= $sequence_control_class ?> max-w-[200px]">
                    <option value=""><?= html_escape($labels['sequence_choose_repeat_type']) ?></option>
                    <?php foreach ($sequence_repeat_type_options as $option): ?>
                        <?php if (isset($option['option_code'], $option['option_name'])): ?>
                            <option value="<?= html_escape($option['option_code']) ?>" <?= isset($sequence_record['repeat_type']) && $sequence_record['repeat_type'] === $option['option_code'] ? 'selected' : '' ?>><?= html_escape($option['option_name']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="sequence-menu-search" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['sequence_menu']) ?></label>
                <input type="hidden" id="sequence-menu-code" name="menu_code" value="<?= html_escape(isset($sequence_record['menu_code']) ? $sequence_record['menu_code'] : '') ?>">
                <input id="sequence-menu-search" type="text" value="<?= html_escape(isset($sequence_menu_label) ? $sequence_menu_label : '') ?>" autocomplete="off" placeholder="<?= html_escape($labels['sequence_menu_search']) ?>" aria-autocomplete="list" aria-describedby="sequence-menu-help" <?= $sequence_disabled ? 'disabled' : 'required' ?> class="<?= $sequence_control_class ?>">
                <p id="sequence-menu-help" class="mt-1.5 text-sm text-slate-500"><?= html_escape($labels['sequence_menu_help']) ?></p>
            </div>

            <div>
                <label for="sequence-format" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['sequence_format']) ?></label>
                <input id="sequence-format" name="format" type="text"<?= $sequence_required_attribute('format') ?><?= $sequence_maxlength_attribute('format') ?> value="<?= html_escape(isset($sequence_record['format']) ? $sequence_record['format'] : '') ?>" <?= $sequence_disabled ? 'disabled' : '' ?> class="<?= $sequence_control_class ?>">
                <p class="mt-1.5 text-sm text-slate-500"><?= html_escape($labels['sequence_format_help']) ?></p>
            </div>
        </div>

        <div data-sequence-sticky-actions class="sticky bottom-0 z-30 -mx-5 -mb-5 mt-8 flex flex-col-reverse gap-3 border-t border-slate-100 bg-white/10 px-5 pb-3 pt-4 backdrop-blur-sm sm:-mx-8 sm:-mb-8 sm:flex-row sm:justify-between sm:px-8">
            <a id="sequence_back" href="/admin/system/sequence" class="admin-back-link inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12.5 4.5 7 10l5.5 5.5"/></svg><?= html_escape($labels['sequence_back']) ?></a>
            <button id="sequence_save" type="submit" <?= $sequence_disabled ? 'disabled aria-disabled="true"' : '' ?> class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-300"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4 3.5h10l2.5 2.5v10.5H3.5v-13H4Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6.5 3.5v4h6v-4M6.5 16v-5h7v5"/></svg><?= html_escape($labels['sequence_save']) ?></button>
        </div>
    </form>
</main>
<link rel="stylesheet" href="/assets/css/vendor/jquery-ui.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/jquery-ui.min.css') ? filemtime(FCPATH . 'assets/css/vendor/jquery-ui.min.css') : '1' ?>">
<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') : '1' ?>"></script>
<script src="/assets/js/vendor/jquery-ui.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery-ui.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery-ui.min.js') : '1' ?>"></script>
<script>
(function () {
    var form = document.querySelector('form[action^="/admin/system/sequence/"]');
    if (!form) { return; }
    var focusField = form.querySelector('input:not([type="hidden"]):not(:disabled):not([readonly]), select:not(:disabled):not([readonly]), textarea:not(:disabled):not([readonly])');
    if (!focusField) { return; }
    focusField.focus();
    var codeField = form.querySelector('[data-normalize-code]');
    if (codeField) {
        codeField.addEventListener('blur', function () {
            codeField.value = codeField.value.toLowerCase().replace(/\s+/g, '');
        });
    }
    var nameField = form.querySelector('[data-title-case-name]');
    if (nameField) {
        nameField.addEventListener('blur', function () {
            nameField.value = nameField.value.toLocaleLowerCase().replace(/(^|\s)(\S)/gu, function (match) {
                return match.toLocaleUpperCase();
            });
        });
    }
    var menuSearch = document.getElementById('sequence-menu-search');
    var menuCode = document.getElementById('sequence-menu-code');
    if (menuSearch && menuCode && !menuSearch.disabled) {
        $(menuSearch).autocomplete({
            minLength: 2,
            delay: 250,
            source: function (request, response) {
                $.getJSON('/admin/system/sequence/menu-lookup', {term: request.term})
                    .done(response)
                    .fail(function () { response([]); });
            },
            select: function (event, item) {
                menuCode.value = item.item.id;
                menuSearch.value = item.item.label;
                return false;
            },
            change: function (event, item) {
                if (!item.item) menuCode.value = '';
            }
        });
        menuSearch.addEventListener('input', function () { menuCode.value = ''; });
    }
})();
</script>
