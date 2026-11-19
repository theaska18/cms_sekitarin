<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$filter_date = !empty($finance_filter_date);
if (!isset($finance_filter_status_options)) {
    $finance_filter_status_options = array(
        'draft' => isset($labels['finance_tx_state_draft']) ? $labels['finance_tx_state_draft'] : 'Draft',
        'pending_approval' => isset($labels['finance_tx_state_pending_approval']) ? $labels['finance_tx_state_pending_approval'] : 'Pending',
        'approved' => isset($labels['finance_tx_state_approved']) ? $labels['finance_tx_state_approved'] : 'Approved',
        'revise' => isset($labels['finance_tx_state_revise']) ? $labels['finance_tx_state_revise'] : 'Revise',
        'rejected' => isset($labels['finance_tx_state_rejected']) ? $labels['finance_tx_state_rejected'] : 'Rejected',
        'posted' => isset($labels['finance_status_posted']) ? $labels['finance_status_posted'] : 'Posted',
        'completed' => isset($labels['finance_status_completed']) ? $labels['finance_status_completed'] : 'Completed',
    );
}
$filter_status = isset($finance_filter_status_options) && is_array($finance_filter_status_options) && $finance_filter_status_options;
$filter_flag = isset($finance_filter_flag_options) && is_array($finance_filter_flag_options) && $finance_filter_flag_options;
$filter_default_start = isset($finance_filter_default_start) ? (string) $finance_filter_default_start : date('Y-m-d', strtotime('-6 days'));
$filter_default_end = isset($finance_filter_default_end) ? (string) $finance_filter_default_end : date('Y-m-d');
$filter_target = isset($finance_filter_target) ? (string) $finance_filter_target : '';
?>
<section class="finance-filter-panel mb-5 border border-slate-200 bg-white p-4 shadow-sm sm:p-5"
         data-finance-filter-panel
         data-target="<?= html_escape($filter_target) ?>"
         data-default-start="<?= html_escape($filter_default_start) ?>"
         data-default-end="<?= html_escape($filter_default_end) ?>">
    <div class="mb-4 flex items-center justify-between gap-3">
        <h2 class="text-sm font-bold text-slate-900"><?= html_escape(isset($labels['finance_filter_title']) ? $labels['finance_filter_title'] : 'Filter') ?></h2>
        <button type="button" data-finance-filter-reset class="min-h-9 rounded-lg border border-slate-300 px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50">
            <?= html_escape(isset($labels['finance_filter_reset']) ? $labels['finance_filter_reset'] : 'Reset') ?>
        </button>
    </div>
    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
        <?php if ($filter_date): ?>
            <label class="block text-xs font-semibold text-slate-600">
                <span class="mb-1.5 block"><?= html_escape(isset($labels['finance_filter_from']) ? $labels['finance_filter_from'] : 'From') ?></span>
                <input type="date" data-finance-filter="start_on" value="<?= html_escape($filter_default_start) ?>" class="min-h-10 w-full rounded-lg border border-slate-300 px-3 text-sm text-slate-900">
            </label>
            <label class="block text-xs font-semibold text-slate-600">
                <span class="mb-1.5 block"><?= html_escape(isset($labels['finance_filter_to']) ? $labels['finance_filter_to'] : 'To') ?></span>
                <input type="date" data-finance-filter="end_on" value="<?= html_escape($filter_default_end) ?>" class="min-h-10 w-full rounded-lg border border-slate-300 px-3 text-sm text-slate-900">
            </label>
        <?php endif; ?>
        <?php if ($filter_status): ?>
            <label class="block text-xs font-semibold text-slate-600">
                <span class="mb-1.5 block"><?= html_escape(isset($labels['finance_filter_status']) ? $labels['finance_filter_status'] : 'Status') ?></span>
                <select data-finance-filter="status" class="min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900">
                    <option value=""><?= html_escape(isset($labels['finance_filter_all']) ? $labels['finance_filter_all'] : 'All') ?></option>
                    <?php foreach ($finance_filter_status_options as $value => $label): ?>
                        <option value="<?= html_escape($value) ?>"><?= html_escape($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <?php if ($filter_flag): ?>
            <label class="block text-xs font-semibold text-slate-600">
                <span class="mb-1.5 block"><?= html_escape(isset($labels['finance_filter_flag']) ? $labels['finance_filter_flag'] : 'Status') ?></span>
                <select data-finance-filter="flag" class="min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900">
                    <option value=""><?= html_escape(isset($labels['finance_filter_all']) ? $labels['finance_filter_all'] : 'All') ?></option>
                    <?php foreach ($finance_filter_flag_options as $value => $label): ?>
                        <option value="<?= html_escape($value) ?>"><?= html_escape($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <label class="block text-xs font-semibold text-slate-600 xl:col-span-2">
            <span class="mb-1.5 block"><?= html_escape(isset($labels['finance_filter_search']) ? $labels['finance_filter_search'] : 'Search') ?></span>
            <input type="search" data-finance-filter="search" autocomplete="off" class="min-h-10 w-full rounded-lg border border-slate-300 px-3 text-sm text-slate-900">
        </label>
        <label class="block text-xs font-semibold text-slate-600">
            <span class="mb-1.5 block"><?= html_escape(isset($labels['finance_filter_page_size']) ? $labels['finance_filter_page_size'] : 'Rows') ?></span>
            <select data-finance-filter="page_size" class="min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </label>
        <div class="flex items-end">
            <button type="button" data-finance-filter-apply class="min-h-10 w-full rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800">
                <?= html_escape(isset($labels['finance_filter_apply']) ? $labels['finance_filter_apply'] : 'Apply') ?>
            </button>
        </div>
    </div>
</section>
