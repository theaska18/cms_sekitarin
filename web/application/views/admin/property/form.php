<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!empty($access_denied)) {
    ?>
    <main class="-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 lg:px-8">
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-5 text-sm font-medium text-rose-900">
            <?= html_escape($labels['property_access_denied']) ?>
        </div>
    </main>
    <?php return; ?>
<?php }

$is_new = $property_mode === 'new';
$title_key = $is_new ? 'property_form_new' : 'property_form_edit';
$property_title = $labels[$title_key];
$property_record = is_array($property_record) ? $property_record : array();
$field_specs = is_array($property_fields) ? $property_fields : array();
$property_errors = is_array($property_errors) ? $property_errors : array();
?>
<main class="-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-2">
            <li><?= html_escape($labels['system']) ?></li>
            <li aria-hidden="true">/</li>
            <li><a href="/admin/system/property" class="hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"><?= html_escape($labels['property_heading']) ?></a></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" class="font-medium text-slate-800"><?= html_escape($property_title) ?></li>
        </ol>
    </nav>

    <header class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($property_title) ?></h1>
        <p class="mt-2 text-base leading-7 text-slate-600"><?= html_escape($labels['property_form_intro']) ?></p>
    </header>

    <?php if (!$backend_available): ?>
        <div role="status" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <p class="font-semibold"><?= html_escape($labels['property_unavailable']) ?></p>
            <p class="mt-1"><?= html_escape($labels['module_backend_unavailable']) ?></p>
        </div>
    <?php endif; ?>
    <?php if (!$write_available): ?>
        <div role="note" class="mb-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700"><?= html_escape($labels['property_write_disabled']) ?></div>
    <?php endif; ?>
    <?php if ($property_errors): ?>
        <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
            <p class="font-semibold"><?= html_escape($labels['property_invalid']) ?></p>
            <ul class="mt-2 list-inside list-disc">
                <?php foreach ($property_errors as $error): ?>
                    <?php $error_key = 'property_' . $error; ?>
                    <?php if (isset($labels[$error_key])): ?><li><?= html_escape($labels[$error_key]) ?></li><?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= html_escape($current_path) ?>" method="post" class="w-full rounded-t-2xl rounded-b-none border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
        <input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>">
        <div class="space-y-6">
            <div>
                <label for="property-code" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['property_code_label']) ?></label>
                <div>
                <div class="relative">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.5 4 16.5M15.5 3.5 12 16.5M2.5 8h15M1.5 12h15"/></svg>
                    <input id="property-code" name="property_code" data-property-code-input type="text" required maxlength="<?= isset($field_specs['property_code']['max_length']) && $field_specs['property_code']['max_length'] > 0 ? (int) $field_specs['property_code']['max_length'] : 255 ?>" value="<?= html_escape($is_new ? (isset($property_record['property_code']) ? $property_record['property_code'] : '') : $property_code) ?>" <?= $is_new ? '' : 'readonly' ?> <?= !$write_available || !$backend_available ? 'disabled' : '' ?> autocomplete="off" autocapitalize="none" spellcheck="false" class="min-h-12 w-full sm:w-64 rounded-lg border border-slate-300 pl-10 pr-3 text-base text-slate-900 sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 disabled:bg-slate-50 disabled:text-slate-500 disabled:opacity-60 read-only:bg-slate-50 read-only:text-slate-500 read-only:opacity-70">
                </div>
                <p class="mt-1.5 text-sm text-slate-500"><?= html_escape($labels['property_code_help']) ?></p>
                </div>
            </div>
            <div>
                <label for="property-name" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['property_name_label']) ?></label>
                <input id="property-name" name="property_name" data-title-case-name type="text" required maxlength="<?= isset($field_specs['property_name']['max_length']) && $field_specs['property_name']['max_length'] > 0 ? (int) $field_specs['property_name']['max_length'] : 255 ?>" value="<?= html_escape(isset($property_record['property_name']) ? $property_record['property_name'] : '') ?>" <?= !$write_available || !$backend_available ? 'disabled' : '' ?> autocomplete="off" class="min-h-12 w-full sm:w-[32rem] rounded-lg border border-slate-300 px-3 text-base text-slate-900 sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 disabled:bg-slate-50 disabled:text-slate-500 disabled:opacity-60 read-only:bg-slate-50 read-only:text-slate-500 read-only:opacity-70">
            </div>
            <?php foreach (array('description' => 'property_description_label', 'value' => 'property_value_label') as $field_name => $label_key): ?>
                <?php if (isset($field_specs[$field_name])): ?>
                    <div>
                        <label for="property-<?= html_escape($field_name) ?>" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels[$label_key]) ?></label>
                        <textarea id="property-<?= html_escape($field_name) ?>" name="<?= html_escape($field_name) ?>" rows="<?= $field_name === 'value' ? '5' : '3' ?>" <?= isset($field_specs[$field_name]['max_length']) && $field_specs[$field_name]['max_length'] > 0 ? 'maxlength="' . (int) $field_specs[$field_name]['max_length'] . '"' : '' ?> <?= !$write_available || !$backend_available ? 'disabled' : '' ?> class="w-full max-w-4xl rounded-lg border border-slate-300 px-3 py-2.5 text-base text-slate-900 sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 disabled:bg-slate-50 disabled:text-slate-500 disabled:opacity-60 read-only:bg-slate-50 read-only:text-slate-500 read-only:opacity-70"><?= html_escape(isset($property_record[$field_name]) ? $property_record[$field_name] : '') ?></textarea>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <div class="sticky bottom-0 z-20 -mx-5 -mb-5 mt-8 flex flex-col-reverse gap-3 border-t border-slate-100 bg-white/10 px-5 pb-3 pt-4 backdrop-blur-sm shadow-none sm:-mx-8 sm:-mb-8 sm:flex-row sm:justify-between sm:px-8">
            <a href="/admin/system/property" class="admin-back-link inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12.5 4.5 7 10l5.5 5.5"/></svg><?= html_escape($labels['property_back']) ?></a>
            <button type="submit" <?= !$write_available || !$backend_available ? 'disabled aria-disabled="true"' : '' ?> class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-300"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4 3.5h10l2.5 2.5v10.5H3.5v-13H4Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6.5 3.5v4h6v-4M6.5 16v-5h7v5"/></svg><?= html_escape($labels['property_save']) ?></button>
        </div>
    </form>
</main>
<script>
(function () {
    var nameField = document.getElementById('property-name');
    function titleCaseName(value) {
        return value.replace(/(^|\s)(\S)/g, function (match, separator, letter) {
            return separator + letter.toLocaleUpperCase();
        });
    }
    if (nameField) {
        nameField.addEventListener('blur', function () {
            nameField.value = titleCaseName(nameField.value);
        });
    }
    var focusField = document.getElementById(<?= json_encode($is_new ? 'property-code' : 'property-name') ?>);
    if (!focusField) { return; }
    <?php if ($is_new): ?>
    function normalizeCode() {
        var cursor = focusField.selectionStart;
        var beforeCursor = focusField.value.slice(0, cursor).toLowerCase().replace(/\s+/g, '');
        focusField.value = focusField.value.toLowerCase().replace(/\s+/g, '');
        focusField.setSelectionRange(beforeCursor.length, beforeCursor.length);
    }
    focusField.addEventListener('input', normalizeCode);
    normalizeCode();
    <?php endif; ?>
    focusField.focus();
    if (focusField.value.length) {
        if (typeof focusField.setSelectionRange === 'function') {
            focusField.setSelectionRange(focusField.value.length, focusField.value.length);
        }
    }
})();
</script>
