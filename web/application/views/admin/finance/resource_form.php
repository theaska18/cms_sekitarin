<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$title = isset($labels[$finance_resource['title_key']]) ? $labels[$finance_resource['title_key']] : $finance_resource['title_key'];
$is_edit = $finance_id !== NULL;
$has_account_lookup = FALSE;
foreach ($finance_resource['fields'] as $finance_field) {
    if ($finance_field['type'] === 'account_lookup') {
        $has_account_lookup = TRUE;
        break;
    }
}
?>
<?php if ($has_account_lookup): ?>
<link rel="stylesheet" href="/assets/css/vendor/jquery-ui.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/jquery-ui.min.css') ? filemtime(FCPATH . 'assets/css/vendor/jquery-ui.min.css') : '1' ?>">
<?php endif; ?>
<main class="mx-auto w-full max-w-4xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <a href="/admin/finance/<?= html_escape($finance_resource['slug']) ?>" class="hover:text-indigo-700"><?= html_escape($title) ?></a>
        <span aria-hidden="true" class="mx-2">/</span>
        <span aria-current="page" class="font-medium text-slate-800"><?= html_escape($is_edit ? 'Edit' : 'New') ?></span>
    </nav>
    <header class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($is_edit ? 'Edit ' . $title : 'Add ' . $title) ?></h1>
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
                    <?php foreach ($finance_errors as $error): ?><li><?= html_escape(isset($labels['finance_error_' . $error]) ? $labels['finance_error_' . $error] : str_replace('_', ' ', $error)) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="post" action="<?= html_escape($current_path) ?>" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <input type="hidden" name="<?= html_escape($finance_csrf_name) ?>" value="<?= html_escape($finance_csrf_hash) ?>">
            <?php foreach ($finance_resource['fields'] as $field): ?>
                <?php
                    $name = $field['name'];
                    $value = isset($finance_record[$name]) ? (string) $finance_record[$name] : (isset($field['default']) ? (string) $field['default'] : '');
                    $label = isset($labels[$field['label_key'] ?? '']) ? $labels[$field['label_key']] : $field['label'];
                ?>
                <div>
                    <label for="finance-<?= html_escape($name) ?>" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($label) ?><?= !empty($field['required']) ? ' *' : '' ?></label>
                    <?php if ($field['type'] === 'account_lookup'): ?>
                        <input type="hidden" id="finance-<?= html_escape($name) ?>" name="<?= html_escape($name) ?>" value="<?= html_escape($value) ?>">
                        <input type="text" id="finance-<?= html_escape($name) ?>-label" data-finance-account-lookup data-account-target="finance-<?= html_escape($name) ?>" value="<?= html_escape(isset($finance_lookup_labels[$name]) ? $finance_lookup_labels[$name] : '') ?>" autocomplete="off" aria-autocomplete="list" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        <p class="mt-1 text-xs text-slate-500">Type at least 2 characters to search active tenant accounts.</p>
                    <?php elseif ($field['type'] === 'textarea'): ?>
                        <textarea id="finance-<?= html_escape($name) ?>" name="<?= html_escape($name) ?>" rows="4" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"><?= html_escape($value) ?></textarea>
                    <?php elseif ($field['type'] === 'select'): ?>
                        <select id="finance-<?= html_escape($name) ?>" name="<?= html_escape($name) ?>" <?= !empty($field['required']) ? 'required' : '' ?> class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                            <?php foreach ($field['options'] as $option => $option_label): ?>
                                <option value="<?= html_escape($option) ?>" <?= (string) $option === $value ? 'selected' : '' ?>><?= html_escape($option_label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <?php $input_type = in_array($field['type'], array('date', 'email', 'number', 'decimal', 'currency'), TRUE) ? ($field['type'] === 'decimal' || $field['type'] === 'currency' ? 'text' : $field['type']) : 'text'; ?>
                        <input id="finance-<?= html_escape($name) ?>" name="<?= html_escape($name) ?>" type="<?= html_escape($input_type) ?>" value="<?= html_escape($value) ?>" <?= !empty($field['required']) ? 'required' : '' ?> <?= isset($field['maxlength']) ? 'maxlength="' . (int) $field['maxlength'] . '"' : '' ?> <?= isset($field['normalize']) ? 'data-normalize="' . html_escape($field['normalize']) . '"' : '' ?> <?= $field['type'] === 'decimal' ? 'inputmode="decimal"' : '' ?> <?= $field['type'] === 'number' ? 'inputmode="numeric" min="1"' : '' ?> class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
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
<?php if ($has_account_lookup): ?>
<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') : '1' ?>"></script>
<script src="/assets/js/vendor/jquery-ui.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery-ui.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery-ui.min.js') : '1' ?>"></script>
<script>
(function () {
    $('[data-finance-account-lookup]').each(function () {
        var input = $(this);
        var hidden = $('#' + input.attr('data-account-target'));
        input.autocomplete({
            minLength: 2,
            delay: 250,
            source: function (request, response) {
                $.getJSON('/admin/finance/financial_settings/lookup/account', {term: request.term})
                    .done(response).fail(function () { response([]); });
            },
            select: function (event, item) { hidden.val(item.item.id); input.val(item.item.label); return false; },
            change: function (event, item) { if (!item.item) hidden.val(''); }
        }).on('input', function () { hidden.val(''); });
    });
})();
</script>
<?php endif; ?>
