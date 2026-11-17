<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$title = isset($labels[$finance_resource['title_key']]) ? $labels[$finance_resource['title_key']] : $finance_resource['title_key'];
$is_edit = $finance_id !== NULL;
$has_account_lookup = FALSE;
$has_fiscal_year_lookup = FALSE;
foreach ($finance_resource['fields'] as $finance_field) {
    if ($finance_field['type'] === 'account_lookup') {
        $has_account_lookup = TRUE;
    } elseif ($finance_field['type'] === 'fiscal_year_lookup') {
        $has_fiscal_year_lookup = TRUE;
    }
}
?>
<?php if ($has_account_lookup || $has_fiscal_year_lookup): ?>
<link rel="stylesheet" href="/assets/css/vendor/jquery-ui.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/jquery-ui.min.css') ? filemtime(FCPATH . 'assets/css/vendor/jquery-ui.min.css') : '1' ?>">
<?php endif; ?>
<main class="mx-auto w-full max-w-4xl px-2 py-6 sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <a href="/admin/finance/<?= html_escape($finance_resource['slug']) ?>" class="hover:text-indigo-700"><?= html_escape($title) ?></a>
        <span aria-hidden="true" class="mx-2">/</span>
        <span aria-current="page" class="font-medium text-slate-800"><?= html_escape($is_edit ? (isset($labels['finance_edit']) ? $labels['finance_edit'] : 'Edit') : (isset($labels['finance_add']) ? $labels['finance_add'] : 'New')) ?></span>
    </nav>
    <header class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape(($is_edit ? (isset($labels['finance_edit']) ? $labels['finance_edit'] : 'Edit') : (isset($labels['finance_add']) ? $labels['finance_add'] : 'Add')) . ' ' . $title) ?></h1>
    </header>
    <?php if (!$finance_available): ?>
        <div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><?= html_escape(isset($labels['module_backend_unavailable']) ? $labels['module_backend_unavailable'] : 'Finance storage is unavailable.') ?></div>
    <?php elseif (!$finance_write_available): ?>
        <div role="status" class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700"><?= html_escape(isset($labels['finance_write_disabled']) ? $labels['finance_write_disabled'] : 'Saving is unavailable because secure form protection is disabled.') ?></div>
    <?php else: ?>
        <?php if ($finance_errors): ?>
            <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
                <p class="font-semibold"><?= html_escape(isset($labels['finance_form_error']) ? $labels['finance_form_error'] : 'Please review the highlighted fields.') ?></p>
                <ul class="mt-2 list-inside list-disc">
                    <?php foreach ($finance_errors as $error): $suffix = substr($error, strrpos($error, '_') + 1); $fallback_key = in_array($suffix, array('required', 'invalid', 'long'), TRUE) ? 'finance_error_' . ($suffix === 'long' ? 'too_long' : $suffix) : 'finance_error_generic'; ?><li><?= html_escape(isset($labels['finance_error_' . $error]) ? $labels['finance_error_' . $error] : (isset($labels[$fallback_key]) ? $labels[$fallback_key] : 'Please check this field.')) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="post" action="<?= html_escape($current_path) ?>" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <input type="hidden" name="<?= html_escape($finance_csrf_name) ?>" value="<?= html_escape($finance_csrf_hash) ?>">
            <?php foreach ($finance_resource['fields'] as $field): ?>
                <?php
                    $name = $field['name'];
                    $value = isset($finance_record[$name]) ? (string) $finance_record[$name] : (isset($field['default']) ? (string) $field['default'] : '');
                    $label_key = isset($field['label_key']) ? $field['label_key'] : '';
                    $label = $label_key !== '' && isset($labels[$label_key]) ? $labels[$label_key] : $field['label'];
                    $hint = '';
                    if (isset($field['hint_key']) && isset($labels[$field['hint_key']])) {
                        $hint = (string) $labels[$field['hint_key']];
                    } elseif (isset($field['hint']) && $field['hint'] !== '') {
                        $hint = (string) $field['hint'];
                    }
                    $option_label_keys = isset($field['option_label_keys']) && is_array($field['option_label_keys']) ? $field['option_label_keys'] : array();
                    $hint_id = 'finance-' . $name . '-hint';
                    $described_by = $hint !== '' ? ' aria-describedby="' . html_escape($hint_id) . '"' : '';
                ?>
                <div>
                    <label for="finance-<?= html_escape($name) ?>" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($label) ?><?= !empty($field['required']) ? ' *' : '' ?></label>
                    <?php if ($field['type'] === 'account_lookup'): ?>
                        <input type="hidden" id="finance-<?= html_escape($name) ?>" name="<?= html_escape($name) ?>" value="<?= html_escape($value) ?>">
                        <div class="relative">
                            <input type="text" id="finance-<?= html_escape($name) ?>-label" data-finance-account-lookup data-account-target="finance-<?= html_escape($name) ?>" data-account-exclude="<?= html_escape($is_edit && $finance_resource['slug'] === 'chart_of_accounts' && $name === 'parent_account_id' ? (string) $finance_id : '') ?>" value="<?= html_escape(isset($finance_lookup_labels[$name]) ? $finance_lookup_labels[$name] : '') ?>" autocomplete="off" aria-autocomplete="list"<?= $described_by ?> class="w-full rounded-lg border border-slate-300 bg-white pe-10 ps-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false" class="pointer-events-none absolute end-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"><circle cx="10.8" cy="10.8" r="6.8"/><path stroke-linecap="round" d="m16 16 4.2 4.2"/></svg>
                        </div>
                    <?php elseif ($field['type'] === 'fiscal_year_lookup'): ?>
                        <input type="hidden" id="finance-<?= html_escape($name) ?>" name="<?= html_escape($name) ?>" value="<?= html_escape($value) ?>">
                        <div class="relative">
                            <input type="text" id="finance-<?= html_escape($name) ?>-label" data-fiscal-year-lookup data-fiscal-year-target="finance-<?= html_escape($name) ?>" value="<?= html_escape(isset($finance_lookup_labels[$name]) ? $finance_lookup_labels[$name] : '') ?>" autocomplete="off" aria-autocomplete="list"<?= $described_by ?> class="w-full rounded-lg border border-slate-300 bg-white pe-10 ps-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false" class="pointer-events-none absolute end-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"><circle cx="10.8" cy="10.8" r="6.8"/><path stroke-linecap="round" d="m16 16 4.2 4.2"/></svg>
                        </div>
                    <?php elseif ($field['type'] === 'textarea'): ?>
                        <textarea id="finance-<?= html_escape($name) ?>" name="<?= html_escape($name) ?>" rows="4"<?= $described_by ?> class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"><?= html_escape($value) ?></textarea>
                    <?php elseif ($field['type'] === 'select'): ?>
                        <select id="finance-<?= html_escape($name) ?>" name="<?= html_escape($name) ?>" <?= !empty($field['required']) ? 'required' : '' ?><?= $described_by ?> class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                            <?php foreach ($field['options'] as $option => $option_label): ?>
                                <?php if (isset($option_label_keys[$option]) && isset($labels[$option_label_keys[$option]])): ?>
                                    <?php $option_label = $labels[$option_label_keys[$option]]; ?>
                                <?php endif; ?>
                                <option value="<?= html_escape($option) ?>" <?= (string) $option === $value ? 'selected' : '' ?>><?= html_escape($option_label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <?php $input_type = in_array($field['type'], array('date', 'email', 'number', 'decimal', 'currency'), TRUE) ? ($field['type'] === 'decimal' || $field['type'] === 'currency' ? 'text' : $field['type']) : 'text'; ?>
                        <input id="finance-<?= html_escape($name) ?>" name="<?= html_escape($name) ?>" type="<?= html_escape($input_type) ?>" value="<?= html_escape($value) ?>" <?= !empty($field['required']) ? 'required' : '' ?> <?= isset($field['maxlength']) ? 'maxlength="' . (int) $field['maxlength'] . '"' : '' ?> <?= isset($field['normalize']) ? 'data-normalize="' . html_escape($field['normalize']) . '"' : '' ?> <?= $field['type'] === 'decimal' ? 'inputmode="decimal"' : '' ?> <?= $field['type'] === 'number' ? 'inputmode="numeric" min="1"' : '' ?><?= $described_by ?> class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    <?php endif; ?>
                    <?php if ($hint !== ''): ?>
                        <p id="<?= html_escape($hint_id) ?>" class="mt-1 text-xs text-slate-500"><?= html_escape($hint) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                <a href="/admin/finance/<?= html_escape($finance_resource['slug']) ?>" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"><?= html_escape(isset($labels['finance_cancel']) ? $labels['finance_cancel'] : 'Cancel') ?></a>
                <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"><?= html_escape(isset($labels['finance_save']) ? $labels['finance_save'] : 'Save') ?></button>
            </div>
        </form>
    <?php endif; ?>
</main>
<script>
(function () {
    document.querySelectorAll('[data-normalize="code"]').forEach(function (input) {
        input.addEventListener('blur', function () { input.value = input.value.toLowerCase().replace(/\s+/g, ''); });
    });
    document.querySelectorAll('[data-normalize="currency"]').forEach(function (input) {
        input.addEventListener('blur', function () { input.value = input.value.toUpperCase().replace(/\s+/g, ''); });
    });
    document.querySelectorAll('[data-normalize="name"]').forEach(function (input) {
        input.addEventListener('blur', function () {
            input.value = input.value.replace(/(^|\s)(\S)/gu, function (match, space, first) {
                return space + first.toLocaleUpperCase();
            });
        });
    });
})();
</script>
<?php if ($has_account_lookup || $has_fiscal_year_lookup): ?>
<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') : '1' ?>"></script>
<script src="/assets/js/vendor/jquery-ui.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery-ui.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery-ui.min.js') : '1' ?>"></script>
<script>
(function () {
    $('[data-finance-account-lookup]').each(function () {
        var input = $(this);
        var hidden = $('#' + input.attr('data-account-target'));
        input.autocomplete({
            minLength: 0,
            delay: 250,
            source: function (request, response) {
                $.getJSON('/admin/finance/<?= html_escape($finance_resource['slug']) ?>/lookup/account', {term: request.term, exclude_id: input.attr('data-account-exclude') || ''})
                    .done(response).fail(function () { response([]); });
            },
            select: function (event, item) { hidden.val(item.item.id); input.val(item.item.label); return false; },
            change: function (event, item) { if (!item.item) hidden.val(''); }
        }).on('focus', function () {
            if (input.val() === '') input.autocomplete('search', '');
        }).on('input', function () { hidden.val(''); });
    });
})();
</script>
<?php endif; ?>
<?php if ($has_fiscal_year_lookup): ?>
<script>
(function () {
    $('[data-fiscal-year-lookup]').each(function () {
        var input = $(this);
        var hidden = $('#' + input.attr('data-fiscal-year-target'));
        input.autocomplete({
            minLength: 0,
            delay: 250,
            source: function (request, response) {
                $.getJSON('/admin/finance/accounting_periods/lookup/fiscal_year', {term: request.term})
                    .done(response).fail(function () { response([]); });
            },
            select: function (event, item) { hidden.val(item.item.id); input.val(item.item.label); return false; },
            change: function (event, item) { if (!item.item) hidden.val(''); }
        }).on('focus', function () {
            if (input.val() === '') input.autocomplete('search', '');
        }).on('input', function () { hidden.val(''); });
    });
})();
</script>
<?php endif; ?>
