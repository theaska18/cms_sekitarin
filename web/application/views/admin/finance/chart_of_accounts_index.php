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
        <?php if ($can_add): ?><a href="<?= html_escape($current_path . '/new') ?>" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700"><?= html_escape(isset($labels['finance_add']) ? $labels['finance_add'] : 'Add') ?></a><?php endif; ?>
    </header>
    <?php if (in_array($finance_status, array('created', 'saved'), TRUE)): ?>
        <div role="status" class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-900"><?= html_escape($finance_status === 'created' ? (isset($labels['finance_created']) ? $labels['finance_created'] : 'Record created.') : (isset($labels['finance_saved']) ? $labels['finance_saved'] : 'Changes saved.')) ?></div>
    <?php elseif ($finance_status === 'save_error'): ?>
        <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-medium text-rose-900"><?= html_escape(isset($labels['finance_save_error']) ? $labels['finance_save_error'] : 'Could not save the record.') ?></div>
    <?php endif; ?>
    <?php if (!$finance_chart_of_accounts_available): ?>
        <div role="status" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><?= html_escape(isset($labels['module_backend_unavailable']) ? $labels['module_backend_unavailable'] : 'Finance storage is unavailable.') ?></div>
    <?php else: ?>
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto p-2 sm:p-5">
                <table id="finance-coa-table" class="finance-list-table min-w-full whitespace-nowrap text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr>
                        <th scope="col" class="px-3 py-3"><?= html_escape(isset($labels['finance_actions']) ? $labels['finance_actions'] : 'Actions') ?></th>
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
                + '<td class="px-3 py-2 text-slate-800"><span style="padding-inline-start:' + (row.level * 18) + 'px" class="inline-flex items-center">' + toggle + escapeHtml(row.account_name) + '</span></td>'
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
})();
</script>
