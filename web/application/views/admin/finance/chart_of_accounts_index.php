<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$title = isset($labels['finance_chart_of_accounts']) ? $labels['finance_chart_of_accounts'] : 'Chart of Accounts';
$can_add = $finance_chart_of_accounts_can_add && $finance_chart_of_accounts_write_available;
$can_update = $finance_chart_of_accounts_can_update && $finance_chart_of_accounts_write_available;
$js_flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$type_labels = array(
    'asset' => isset($labels['finance_resource_option_account_type_asset']) ? $labels['finance_resource_option_account_type_asset'] : 'Asset',
    'liability' => isset($labels['finance_resource_option_account_type_liability']) ? $labels['finance_resource_option_account_type_liability'] : 'Liability',
    'equity' => isset($labels['finance_resource_option_account_type_equity']) ? $labels['finance_resource_option_account_type_equity'] : 'Equity',
    'revenue' => isset($labels['finance_resource_option_account_type_revenue']) ? $labels['finance_resource_option_account_type_revenue'] : 'Revenue',
    'expense' => isset($labels['finance_resource_option_account_type_expense']) ? $labels['finance_resource_option_account_type_expense'] : 'Expense',
);
$balance_labels = array(
    'debit' => isset($labels['finance_resource_option_normal_balance_debit']) ? $labels['finance_resource_option_normal_balance_debit'] : 'Debit',
    'credit' => isset($labels['finance_resource_option_normal_balance_credit']) ? $labels['finance_resource_option_normal_balance_credit'] : 'Credit',
);
?>
<main class="mx-auto w-full max-w-7xl px-2 py-6 sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500"><span><?= html_escape(isset($labels['finance']) ? $labels['finance'] : 'Finance') ?></span><span aria-hidden="true" class="mx-2">/</span><span aria-current="page"><?= html_escape($title) ?></span></nav>
    <header class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div><h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($title) ?></h1><p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600"><?= html_escape(isset($labels['finance_chart_of_accounts_tree_intro']) ? $labels['finance_chart_of_accounts_tree_intro'] : 'Manage the account hierarchy. Parent accounts can be expanded or collapsed.') ?></p></div>
        <?php if ($can_add): ?><a href="<?= html_escape($current_path . '/new') ?>" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800"><?= html_escape(isset($labels['finance_add']) ? $labels['finance_add'] : 'Add') ?></a><?php endif; ?>
    </header>
    <section class="finance-data-exchange mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-5 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-start gap-3">
                <div class="finance-data-exchange-icon flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2h16v-2"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-base font-semibold text-slate-900">COA Data Exchange</h2>
                        <span class="finance-data-exchange-badge rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">CSV</span>
                    </div>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600"><?= html_escape(isset($labels['finance_coa_import_hint']) ? $labels['finance_coa_import_hint'] : 'Export COA from an existing tenant, then import the CSV into a new tenant.') ?></p>
                </div>
            </div>
            <div class="flex shrink-0 flex-col gap-2 sm:flex-row sm:items-center">
                <?php if ($finance_chart_of_accounts_can_export): ?>
                <a href="<?= html_escape($current_path . '/export') ?>" class="finance-data-exchange-export inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10 3v9m0 0 3.5-3.5M10 12 6.5 8.5M4 16h12"/></svg>
                    <?= html_escape(isset($labels['finance_coa_export']) ? $labels['finance_coa_export'] : 'Export COA') ?>
                </a>
                <?php endif; ?>
                <?php if ($finance_chart_of_accounts_can_import): ?>
                <form method="post" action="<?= html_escape($current_path . '/import') ?>" enctype="multipart/form-data" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <input type="hidden" name="<?= html_escape($finance_csrf_name) ?>" value="<?= html_escape($finance_csrf_hash) ?>">
                    <input id="coa-file" type="file" name="coa_file" accept=".csv,text/csv" required class="sr-only" data-coa-file-input data-coa-file-default="<?= html_escape(isset($labels['finance_coa_choose_file']) ? $labels['finance_coa_choose_file'] : 'Choose CSV') ?>">
                    <label for="coa-file" class="finance-data-exchange-file inline-flex min-h-10 cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 text-sm font-medium text-slate-600 hover:border-indigo-400 hover:text-indigo-700">
                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10 13V4m0 0L6.5 7.5M10 4l3.5 3.5M4 16h12"/></svg>
                        <span data-coa-file-label><?= html_escape(isset($labels['finance_coa_choose_file']) ? $labels['finance_coa_choose_file'] : 'Choose CSV') ?></span>
                    </label>
                    <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"><?= html_escape(isset($labels['finance_coa_import']) ? $labels['finance_coa_import'] : 'Import COA') ?></button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php if ($finance_status === 'coa_imported'): ?>
        <div role="status" class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-900"><?= html_escape($finance_coa_import_skipped > 0 ? sprintf(isset($labels['finance_coa_import_success_skipped']) ? $labels['finance_coa_import_success_skipped'] : 'COA imported: %d accounts, skipped: %d.', (int) $finance_coa_import_count, (int) $finance_coa_import_skipped) : sprintf(isset($labels['finance_coa_import_success']) ? $labels['finance_coa_import_success'] : 'COA imported: %d accounts.', (int) $finance_coa_import_count)) ?></div>
    <?php elseif ($finance_status === 'coa_import_error'): ?>
        <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"><p class="font-semibold"><?= html_escape(isset($labels['finance_coa_import_error']) ? $labels['finance_coa_import_error'] : 'COA import failed.') ?></p><ul class="mt-2 list-inside list-disc"><?php foreach ((array) $finance_coa_import_errors as $error): ?><li><?= html_escape($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <?php if (in_array($finance_status, array('created', 'saved'), TRUE)): ?>
        <div role="status" class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-900"><?= html_escape($finance_status === 'created' ? (isset($labels['finance_created']) ? $labels['finance_created'] : 'Record created.') : (isset($labels['finance_saved']) ? $labels['finance_saved'] : 'Changes saved.')) ?></div>
    <?php elseif ($finance_status === 'save_error'): ?>
        <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-medium text-rose-900"><?= html_escape(isset($labels['finance_save_error']) ? $labels['finance_save_error'] : 'Could not save the record.') ?></div>
    <?php endif; ?>
    <?php if (!$finance_chart_of_accounts_available): ?>
        <div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><?= html_escape(isset($labels['module_backend_unavailable']) ? $labels['module_backend_unavailable'] : 'Finance storage is unavailable.') ?></div>
    <?php else: ?>
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table id="finance-coa-table" class="finance-list-table min-w-full whitespace-nowrap text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr>
                        <?= $this->finance_list_table->action_header(isset($labels['finance_actions']) ? $labels['finance_actions'] : 'Actions', 'px-3 py-3') ?>
                        <th scope="col" class="px-3 py-3"><?= html_escape(isset($labels['finance_resource_column_account_code']) ? $labels['finance_resource_column_account_code'] : 'Code') ?></th>
                        <th scope="col" class="px-3 py-3"><?= html_escape(isset($labels['finance_resource_column_account_name']) ? $labels['finance_resource_column_account_name'] : 'Account') ?></th>
                        <th scope="col" class="px-3 py-3"><?= html_escape(isset($labels['finance_resource_column_account_type']) ? $labels['finance_resource_column_account_type'] : 'Type') ?></th>
                        <th scope="col" class="px-3 py-3"><?= html_escape(isset($labels['finance_resource_column_normal_balance']) ? $labels['finance_resource_column_normal_balance'] : 'Normal balance') ?></th>
                        <th scope="col" class="px-3 py-3"><?= html_escape(isset($labels['finance_resource_column_parent_account_id']) ? $labels['finance_resource_column_parent_account_id'] : 'Parent') ?></th>
                        <th scope="col" class="px-3 py-3"><?= html_escape(isset($labels['finance_resource_column_active_flag']) ? $labels['finance_resource_column_active_flag'] : 'Active') ?></th>
                    </tr></thead>
                    <tbody id="finance-coa-body" class="divide-y divide-slate-100"></tbody>
                </table>
                <p id="finance-coa-empty" hidden class="p-6 text-center text-sm text-slate-500"><?= html_escape(isset($labels['finance_coa_tree_empty']) ? $labels['finance_coa_tree_empty'] : 'No accounts yet.') ?></p>
            </div>
        </section>
    <?php endif; ?>
</main>
<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') : '1' ?>"></script>
<script src="/assets/js/finance-list.js?v=<?= is_file(FCPATH . 'assets/js/finance-list.js') ? filemtime(FCPATH . 'assets/js/finance-list.js') : '1' ?>"></script>
<script>
(function () {
    var body = document.getElementById('finance-coa-body');
    if (!body) return;
    var canUpdate = <?= $can_update ? 'true' : 'false' ?>;
    var base = <?= json_encode($current_path, $js_flags) ?>;
    var typeLabels = <?= json_encode($type_labels, $js_flags) ?>;
    var balanceLabels = <?= json_encode($balance_labels, $js_flags) ?>;
    var labels = {
        actions: <?= json_encode(isset($labels['finance_actions']) ? $labels['finance_actions'] : 'Actions', $js_flags) ?>,
        edit: <?= json_encode(isset($labels['finance_edit']) ? $labels['finance_edit'] : 'Edit', $js_flags) ?>,
        yes: <?= json_encode(isset($labels['finance_yes']) ? $labels['finance_yes'] : 'Yes', $js_flags) ?>,
        no: <?= json_encode(isset($labels['finance_no']) ? $labels['finance_no'] : 'No', $js_flags) ?>,
        empty: <?= json_encode(isset($labels['finance_coa_tree_empty']) ? $labels['finance_coa_tree_empty'] : 'No accounts yet.', $js_flags) ?>
    };
    var rows = [], collapsed = {};
    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
        });
    }
    function visible(row) {
        for (var i = 0; i < row.path.length; i++) {
            if (collapsed[row.path[i]]) return false;
        }
        return true;
    }
    function menu(row) {
        if (!canUpdate) return '';
        var id = row.account_id;
        var menuId = 'coa-actions-' + id;
        return '<div class="relative inline-flex"><button type="button" data-finance-menu-toggle aria-label="' + escapeHtml(labels.actions) + '" aria-haspopup="menu" aria-expanded="false" aria-controls="' + menuId + '" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"><svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><circle cx="10" cy="4" r="1.6"/><circle cx="10" cy="10" r="1.6"/><circle cx="10" cy="16" r="1.6"/></svg></button><div id="' + menuId + '" data-finance-menu hidden role="menu" class="fixed z-[60] mt-1 w-40 rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-lg"><a href="' + escapeHtml(base + '/edit/' + id) + '" role="menuitem" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none">' + escapeHtml(labels.edit) + '</a></div></div>';
    }
    function render() {
        var html = '';
        rows.forEach(function (row) {
            if (!visible(row)) return;
            var toggle = row.has_children
                ? '<button type="button" data-coa-toggle="' + row.account_id + '" aria-expanded="' + (collapsed[row.account_id] ? 'false' : 'true') + '" class="me-1 inline-flex h-6 w-6 items-center justify-center rounded text-slate-500 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">' + (collapsed[row.account_id] ? '▸' : '▾') + '</button>'
                : '<span class="me-1 inline-block h-6 w-6"></span>';
            html += '<tr data-account-id="' + row.account_id + '">'
                + '<td class="px-3 py-2">' + menu(row) + '</td>'
                + '<td class="px-3 py-2 font-mono text-xs font-semibold text-slate-700">' + escapeHtml(row.account_code) + '</td>'
                + '<td class="px-3 py-2 text-slate-800"><span style="padding-inline-start:' + (row.level * 18) + 'px" class="inline-flex items-center">' + toggle + escapeHtml(row.account_name) + (row.posting_flag ? '' : ' <span class="ms-2 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500"><?= html_escape(isset($labels['finance_coa_group']) ? $labels['finance_coa_group'] : 'Group') ?></span>') + '</span></td>'
                + '<td class="px-3 py-2">' + escapeHtml(typeLabels[row.account_type] || row.account_type) + '</td>'
                + '<td class="px-3 py-2">' + escapeHtml(balanceLabels[row.normal_balance] || row.normal_balance) + '</td>'
                + '<td class="px-3 py-2 text-slate-600">' + escapeHtml(row.parent_name || '—') + '</td>'
                + '<td class="px-3 py-2">' + escapeHtml(row.active_flag ? labels.yes : labels.no) + '</td>'
                + '</tr>';
        });
        body.innerHTML = html;
        document.getElementById('finance-coa-empty').hidden = rows.length > 0;
    }
    body.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-coa-toggle]');
        if (!toggle) return;
        var id = parseInt(toggle.getAttribute('data-coa-toggle'), 10);
        collapsed[id] = !collapsed[id];
        render();
    });
    fetch(base + '/data', {headers: {'Accept':'application/json'}})
        .then(function (response) { if (!response.ok) throw new Error(); return response.json(); })
        .then(function (data) { rows = Array.isArray(data) ? data : []; render(); })
        .catch(function () { rows = []; render(); });
    var coaFile = document.querySelector('[data-coa-file-input]');
    if (coaFile) {
        coaFile.addEventListener('change', function () {
            var label = document.querySelector('[data-coa-file-label]');
            if (!label) return;
            label.textContent = this.files && this.files[0] ? this.files[0].name : (this.getAttribute('data-coa-file-default') || 'Choose CSV');
        });
    }
})();
</script>
