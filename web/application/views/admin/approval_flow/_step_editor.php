<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$step_editor_steps = isset($step_editor_steps) && is_array($step_editor_steps) ? array_values($step_editor_steps) : array();
$step_editor_allow_empty = !empty($step_editor_allow_empty);
if (!$step_editor_steps && !$step_editor_allow_empty) {
    $step_editor_steps = array(array('approver_type' => 'superior'));
}
$step_editor_labels = isset($step_editor_labels) && is_array($step_editor_labels) ? $step_editor_labels : array();
$step_editor_disabled = !empty($step_editor_disabled);
$step_label = function ($key, $fallback) use ($step_editor_labels) {
    return isset($step_editor_labels[$key]) && $step_editor_labels[$key] !== '' ? $step_editor_labels[$key] : $fallback;
};
?>
<section aria-labelledby="approval-steps-heading">
    <div class="mb-3">
        <h2 id="approval-steps-heading" class="text-lg font-semibold text-slate-900"><?= html_escape($step_label('heading', 'Approval steps')) ?></h2>
        <p class="mt-1 text-sm text-slate-500"><?= html_escape($step_label('help', 'Steps are processed in order.')) ?></p>
    </div>
    <ol data-approval-step-list class="space-y-4">
        <?php foreach ($step_editor_steps as $index => $step): ?>
            <?php
            $step_type = isset($step['approver_type']) ? (string) $step['approver_type'] : 'superior';
            $employee_id = isset($step['approver_employee_id']) ? (string) $step['approver_employee_id'] : '';
            $employee_label = !empty($step['employee_name'])
                ? $step['employee_name'] . (isset($step['employee_code']) && $step['employee_code'] !== '' ? ' (' . $step['employee_code'] . ')' : '')
                : '';
            $org_id = isset($step['org_id']) ? (string) $step['org_id'] : '';
            $org_label = !empty($step['org_name'])
                ? (isset($step['org_code']) && $step['org_code'] !== '' ? $step['org_code'] . ' — ' : '') . $step['org_name']
                : '';
            ?>
            <li data-approval-step class="approval-flow-step-card rounded-xl border border-slate-200 p-4">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h3 data-step-title class="font-semibold text-slate-800"><?= html_escape($step_label('step_title', 'Step')) ?> <?= (int) $index + 1 ?></h3>
                    <button type="button" data-remove-approval-step class="rounded-lg px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500" <?= $step_editor_disabled ? 'disabled' : '' ?>><?= html_escape($step_label('remove_step', 'Remove step')) ?></button>
                </div>
                <fieldset>
                    <legend class="mb-2 text-sm font-semibold text-slate-700"><?= html_escape($step_label('approver_type', 'Approver type')) ?></legend>
                    <div class="flex flex-wrap gap-2">
                        <label class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 text-sm">
                            <input type="radio" data-approver-type value="superior" <?= $step_type === 'superior' ? 'checked' : '' ?> <?= $step_editor_disabled ? 'disabled' : '' ?>>
                            <span><?= html_escape($step_label('superior', 'Superior')) ?></span>
                        </label>
                        <label class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 text-sm">
                            <input type="radio" data-approver-type value="organization" <?= $step_type === 'organization' ? 'checked' : '' ?> <?= $step_editor_disabled ? 'disabled' : '' ?>>
                            <span><?= html_escape($step_label('organization', 'Organization')) ?></span>
                        </label>
                        <label class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 text-sm">
                            <input type="radio" data-approver-type value="employee" <?= $step_type === 'employee' ? 'checked' : '' ?> <?= $step_editor_disabled ? 'disabled' : '' ?>>
                            <span><?= html_escape($step_label('employee', 'Employee')) ?></span>
                        </label>
                        <?php if ($step_type === 'user'): ?>
                            <label class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-amber-300 bg-amber-50 px-3 text-sm">
                                <input type="radio" data-approver-type value="user" checked <?= $step_editor_disabled ? 'disabled' : '' ?>>
                                <span><?= html_escape($step_label('legacy_user', 'Legacy account')) ?>: <?= html_escape(isset($step['username']) ? $step['username'] : 'approver') ?></span>
                            </label>
                            <input type="hidden" data-legacy-user-id value="<?= (int) $step['approver_user_id'] ?>">
                        <?php endif; ?>
                    </div>
                </fieldset>
                <div data-organization-target class="mt-3" <?= $step_type === 'organization' ? '' : 'hidden' ?>>
                    <label data-organization-label class="mb-1 block text-sm font-semibold text-slate-700"><?= html_escape($step_label('choose_organization', 'Choose organization')) ?></label>
                    <input type="search" data-organization-search autocomplete="off" aria-autocomplete="list" placeholder="<?= html_escape($step_label('search_organization', 'Search organization…')) ?>" value="<?= html_escape($org_label) ?>" <?= $step_editor_disabled ? 'disabled' : '' ?> class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-base text-slate-900 sm:max-w-xl sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <input type="hidden" data-organization-id value="<?= html_escape($org_id) ?>">
                    <div data-organization-status class="sr-only" role="status" aria-live="polite"></div>
                </div>
                <div data-employee-target class="mt-3" <?= $step_type === 'employee' ? '' : 'hidden' ?>>
                    <label data-employee-label class="mb-1 block text-sm font-semibold text-slate-700"><?= html_escape($step_label('choose_employee', 'Choose employee')) ?></label>
                    <input type="search" data-employee-search autocomplete="off" aria-autocomplete="list" placeholder="<?= html_escape($step_label('search_employee', 'Search employee…')) ?>" value="<?= html_escape($employee_label) ?>" <?= $step_editor_disabled ? 'disabled' : '' ?> class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-base text-slate-900 sm:max-w-xl sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <input type="hidden" data-employee-id value="<?= html_escape($employee_id) ?>">
                    <div data-employee-status class="sr-only" role="status" aria-live="polite"></div>
                </div>
            </li>
        <?php endforeach; ?>
    </ol>
    <div class="mt-3 flex justify-end">
        <button type="button" data-add-approval-step class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700 hover:bg-indigo-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500" <?= $step_editor_disabled ? 'disabled' : '' ?>>
            <span aria-hidden="true">＋</span><?= html_escape($step_label('add_step', 'Add step')) ?>
        </button>
    </div>
</section>
<template id="approval-step-template">
    <li data-approval-step class="approval-flow-step-card rounded-xl border border-slate-200 p-4">
        <div class="mb-3 flex items-center justify-between gap-3">
            <h3 data-step-title class="font-semibold text-slate-800"><?= html_escape($step_label('step_title', 'Step')) ?></h3>
            <button type="button" data-remove-approval-step class="rounded-lg px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500"><?= html_escape($step_label('remove_step', 'Remove step')) ?></button>
        </div>
        <fieldset>
            <legend class="mb-2 text-sm font-semibold text-slate-700"><?= html_escape($step_label('approver_type', 'Approver type')) ?></legend>
            <div class="flex flex-wrap gap-2">
                <label class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 text-sm"><input type="radio" data-approver-type value="superior"><span><?= html_escape($step_label('superior', 'Superior')) ?></span></label>
                <label class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 text-sm"><input type="radio" data-approver-type value="organization"><span><?= html_escape($step_label('organization', 'Organization')) ?></span></label>
                <label class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 text-sm"><input type="radio" data-approver-type value="employee"><span><?= html_escape($step_label('employee', 'Employee')) ?></span></label>
            </div>
        </fieldset>
        <div data-organization-target class="mt-3" hidden>
            <label data-organization-label class="mb-1 block text-sm font-semibold text-slate-700"><?= html_escape($step_label('choose_organization', 'Choose organization')) ?></label>
            <input type="search" data-organization-search autocomplete="off" aria-autocomplete="list" placeholder="<?= html_escape($step_label('search_organization', 'Search organization…')) ?>" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-base text-slate-900 sm:max-w-xl sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            <input type="hidden" data-organization-id>
            <div data-organization-status class="sr-only" role="status" aria-live="polite"></div>
        </div>
        <div data-employee-target class="mt-3" hidden>
            <label data-employee-label class="mb-1 block text-sm font-semibold text-slate-700"><?= html_escape($step_label('choose_employee', 'Choose employee')) ?></label>
            <input type="search" data-employee-search autocomplete="off" aria-autocomplete="list" placeholder="<?= html_escape($step_label('search_employee', 'Search employee…')) ?>" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-base text-slate-900 sm:max-w-xl sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            <input type="hidden" data-employee-id>
            <div data-employee-status class="sr-only" role="status" aria-live="polite"></div>
        </div>
    </li>
</template>
