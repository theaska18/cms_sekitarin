<?php
defined('BASEPATH') OR exit('No direct script access allowed');

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
$filter_date = !empty($finance_filter_date);
$filter_status = isset($finance_filter_status_options) && is_array($finance_filter_status_options) && $finance_filter_status_options;
$filter_flag = isset($finance_filter_flag_options) && is_array($finance_filter_flag_options) && $finance_filter_flag_options;
$filter_default_start = isset($finance_filter_default_start) ? (string) $finance_filter_default_start : date('Y-m-d', strtotime('-6 days'));
$filter_default_end = isset($finance_filter_default_end) ? (string) $finance_filter_default_end : date('Y-m-d');
$filter_target = isset($finance_filter_target) ? (string) $finance_filter_target : '';
$filter_id = 'finance-filter-details-' . substr(md5($filter_target . $filter_default_start), 0, 8);
?>
<section class="finance-filter-panel relative z-30 mb-5 border border-slate-200 bg-white p-4 shadow-sm sm:p-5"
         data-finance-filter-panel
         data-target="<?= html_escape($filter_target) ?>"
         data-default-start="<?= html_escape($filter_default_start) ?>"
         data-default-end="<?= html_escape($filter_default_end) ?>">
    <div class="flex items-center justify-end gap-2">
        <?php if (!empty($finance_filter_add_action['url'])): ?>
            <a href="<?= html_escape($finance_filter_add_action['url']) ?>" class="inline-flex h-11 shrink-0 items-center gap-2 rounded-lg bg-indigo-600 px-3 text-sm font-semibold text-white hover:bg-indigo-700">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" d="M10 4v12M4 10h12"/></svg>
                <span><?= html_escape(isset($finance_filter_add_action['label']) ? $finance_filter_add_action['label'] : 'Add') ?></span>
            </a>
        <?php endif; ?>
        <div class="relative w-full min-w-0 max-w-xs">
            <input type="search" data-finance-filter="search" autocomplete="off"
                   aria-label="<?= html_escape(isset($labels['finance_filter_search']) ? $labels['finance_filter_search'] : 'Search') ?>"
                   placeholder="<?= html_escape(isset($labels['finance_filter_search']) ? $labels['finance_filter_search'] : 'Search') ?>"
                   class="min-h-11 w-full rounded-lg border border-slate-300 pe-10 ps-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-200">
            <svg class="pointer-events-none absolute end-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false"><circle cx="8.5" cy="8.5" r="5.5"/><path stroke-linecap="round" d="m13 13 4 4"/></svg>
        </div>
        <button type="button" data-finance-filter-toggle aria-expanded="false" aria-controls="<?= html_escape($filter_id) ?>"
                aria-label="<?= html_escape(isset($labels['finance_filter_title']) ? $labels['finance_filter_title'] : 'Filter') ?>"
                title="<?= html_escape(isset($labels['finance_filter_title']) ? $labels['finance_filter_title'] : 'Filter') ?>"
                class="relative inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" focusable="false" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5h14M6 10h8m-5 5h2"/></svg>
            <span data-finance-filter-indicator hidden class="absolute end-1.5 top-1.5 h-2 w-2 rounded-full bg-emerald-600" aria-hidden="true"></span>
        </button>
    </div>
    <div id="<?= html_escape($filter_id) ?>" data-finance-filter-details hidden class="mt-4 border-t border-slate-100 pt-4">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <?php if (!empty($finance_filter_date_fields)): ?>
                <?php foreach ($finance_filter_date_fields as $date_field): ?>
                    <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-3">
                        <p class="mb-2 text-xs font-bold text-slate-700"><?= html_escape($date_field['label']) ?></p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <label class="block text-[11px] font-semibold text-slate-600">
                                <span class="mb-1 block"><?= html_escape(isset($labels['finance_filter_from']) ? $labels['finance_filter_from'] : 'From') ?></span>
                                <input type="date" data-finance-filter="<?= html_escape($date_field['start_name']) ?>" value="<?= html_escape($date_field['start_value']) ?>" class="min-h-10 w-full rounded-lg border border-slate-300 px-3 text-sm text-slate-900">
                            </label>
                            <label class="block text-[11px] font-semibold text-slate-600">
                                <span class="mb-1 block"><?= html_escape(isset($labels['finance_filter_to']) ? $labels['finance_filter_to'] : 'To') ?></span>
                                <input type="date" data-finance-filter="<?= html_escape($date_field['end_name']) ?>" value="<?= html_escape($date_field['end_value']) ?>" class="min-h-10 w-full rounded-lg border border-slate-300 px-3 text-sm text-slate-900">
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
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
            <?php if (!empty($finance_filter_field_filters)): ?>
                <?php foreach ($finance_filter_field_filters as $field): ?>
                    <?php if ($field['type'] === 'range'): ?>
                        <?php $min_name = !empty($field['min_name']) ? $field['min_name'] : $field['name'] . '_min'; $max_name = !empty($field['max_name']) ? $field['max_name'] : $field['name'] . '_max'; ?>
                        <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-3 md:col-span-2">
                            <p class="mb-2 text-xs font-bold text-slate-700"><?= html_escape($field['label']) ?></p>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <input type="number" step="0.01" data-finance-filter="<?= html_escape($min_name) ?>" placeholder="Min" class="min-h-10 w-full rounded-lg border border-slate-300 px-3 text-sm">
                                <input type="number" step="0.01" data-finance-filter="<?= html_escape($max_name) ?>" placeholder="Max" class="min-h-10 w-full rounded-lg border border-slate-300 px-3 text-sm">
                            </div>
                        </div>
                    <?php elseif ($field['type'] === 'select'): ?>
                        <label class="block text-xs font-semibold text-slate-600">
                            <span class="mb-1.5 block"><?= html_escape($field['label']) ?></span>
                            <select data-finance-filter="<?= html_escape($field['name']) ?>" class="min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                                <option value=""><?= html_escape(isset($labels['finance_filter_all']) ? $labels['finance_filter_all'] : 'All') ?></option>
                                <?php foreach ((array) $field['options'] as $value => $label): ?><option value="<?= html_escape($value) ?>"><?= html_escape($label) ?></option><?php endforeach; ?>
                            </select>
                        </label>
                    <?php else: ?>
                        <label class="block text-xs font-semibold text-slate-600">
                            <span class="mb-1.5 block"><?= html_escape($field['label']) ?></span>
                            <input type="text" data-finance-filter="<?= html_escape($field['name']) ?>" class="min-h-10 w-full rounded-lg border border-slate-300 px-3 text-sm">
                        </label>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
            <label class="block text-xs font-semibold text-slate-600">
                <span class="mb-1.5 block"><?= html_escape(isset($labels['finance_filter_page_size']) ? $labels['finance_filter_page_size'] : 'Rows') ?></span>
                <select data-finance-filter="page_size" class="min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </label>
        </div>
        <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                    <div class="relative shrink-0" data-finance-export>
            <button type="button" data-finance-export-toggle aria-expanded="false" class="inline-flex h-11 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10 3v9m0 0 3-3m-3 3L7 9M4 14v2h12v-2"/></svg>
                Export
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="h-3.5 w-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="m6 8 4 4 4-4"/></svg>
            </button>
            <div data-finance-export-menu hidden class="absolute end-0 z-[200] mt-1 w-44 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg">
                <button type="button" data-finance-export-pdf class="flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3h7l3 3v11H5V3Zm2 8h6M7 14h4"/></svg>
                    PDF
                </button>
                <button type="button" data-finance-export-excel class="flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3h7l3 3v11H5V3Zm2 7 2 3m0 0 2-3m-2 3 2 3m-2-3-2 3"/></svg>
                    Excel
                </button>
            </div>
        </div>
            <div class="ms-auto flex flex-wrap gap-2">
            <button type="button" data-finance-filter-reset class="min-h-10 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <?= html_escape(isset($labels['finance_filter_reset']) ? $labels['finance_filter_reset'] : 'Reset') ?>
            </button>
            <button type="button" data-finance-filter-apply class="min-h-10 rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-800">
                <?= html_escape(isset($labels['finance_filter_apply']) ? $labels['finance_filter_apply'] : 'Apply') ?>
            </button>
            </div>
        </div>
    </div>
</section>
