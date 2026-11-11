<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$is_new = $parameter_mode === 'new';
$parameter_record = is_array($parameter_record) ? $parameter_record : array();
$parameter_options = is_array($parameter_options) && $parameter_options ? array_values($parameter_options) : array(array('option_code' => '', 'option_name' => '', 'active_flag' => 1));
$parameter_fields = is_array($parameter_fields) ? $parameter_fields : array();
$parameter_errors = is_array($parameter_errors) ? $parameter_errors : array();
$title_key = $is_new ? 'parameter_form_new' : 'parameter_form_edit';
$parameter_title = $labels[$title_key];
?>
<?php if ($form_access_denied): $access_message_key = isset($form_access_message_key, $labels[$form_access_message_key]) ? $form_access_message_key : 'parameter_no_access'; ?>
<main class="mx-auto min-w-0 w-full max-w-7xl px-0 py-6 sm:px-6 lg:px-8"><div role="status" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"><?= html_escape($labels[$access_message_key]) ?></div></main>
<?php return; endif; ?>
<main class="mx-auto min-w-0 w-full max-w-7xl px-0 py-6 sm:px-6 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-2">
            <li><?= html_escape($labels['system']) ?></li><li aria-hidden="true">/</li>
            <li><a href="/admin/system/parameter" class="hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"><?= html_escape($labels['parameter_heading']) ?></a></li>
            <li aria-hidden="true">/</li><li aria-current="page" class="font-medium text-slate-800"><?= html_escape($parameter_title) ?></li>
        </ol>
    </nav>
    <header class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($parameter_title) ?></h1>
        <p class="mt-2 text-base leading-7 text-slate-600"><?= html_escape($labels['parameter_form_intro']) ?></p>
    </header>
    <?php if ($delete_denied): ?><div role="status" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"><?= html_escape($labels['parameter_delete_denied']) ?></div><?php endif; ?>
    <?php if (!$backend_available): ?>
        <div role="status" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><p class="font-semibold"><?= html_escape($labels['parameter_unavailable']) ?></p></div>
    <?php endif; ?>
    <?php if (!$write_available): ?>
        <div role="note" class="mb-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700"><?= html_escape($labels['parameter_write_disabled']) ?></div>
    <?php endif; ?>
    <?php if ($parameter_errors): ?>
        <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
            <p class="font-semibold"><?= html_escape($labels['parameter_invalid']) ?></p>
            <ul class="mt-2 list-inside list-disc">
                <?php foreach ($parameter_errors as $error): ?><?php $error_key = 'parameter_' . $error; ?>
                    <?php if (isset($labels[$error_key])): ?><li><?= html_escape($labels[$error_key]) ?></li><?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form action="<?= html_escape($current_path) ?>" method="post" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
        <input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>">
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="parameter-code" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['parameter_code_label']) ?></label>
                <input id="parameter-code" name="parameter_code" data-parameter-code-input type="text" required maxlength="<?= isset($parameter_fields['parameter_code']['max_length']) && $parameter_fields['parameter_code']['max_length'] > 0 ? (int) $parameter_fields['parameter_code']['max_length'] : 32 ?>" value="<?= html_escape($is_new ? (isset($parameter_record['parameter_code']) ? $parameter_record['parameter_code'] : '') : $parameter_code) ?>" <?= $is_new ? '' : 'readonly' ?> <?= !$write_available || !$backend_available ? 'disabled' : '' ?> <?= $is_new && $write_available && $backend_available ? 'autofocus' : '' ?> autocomplete="off" autocapitalize="none" spellcheck="false" class="min-h-12 w-full rounded-lg border border-slate-300 px-3 font-mono text-base text-slate-900 sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 read-only:bg-slate-50 read-only:text-slate-500 disabled:bg-slate-50 disabled:text-slate-500">
            </div>
            <div>
                <label for="parameter-name" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['parameter_name_label']) ?></label>
                <input id="parameter-name" name="parameter_name" data-name-title-case type="text" required maxlength="<?= isset($parameter_fields['parameter_name']['max_length']) && $parameter_fields['parameter_name']['max_length'] > 0 ? (int) $parameter_fields['parameter_name']['max_length'] : 64 ?>" value="<?= html_escape(isset($parameter_record['parameter_name']) ? $parameter_record['parameter_name'] : '') ?>" <?= !$is_new && $write_available && $backend_available ? 'autofocus' : '' ?> <?= !$write_available || !$backend_available ? 'disabled' : '' ?> class="min-h-12 w-full rounded-lg border border-slate-300 px-3 text-base text-slate-900 sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 read-only:bg-slate-50 read-only:text-slate-500 disabled:bg-slate-50 disabled:text-slate-500">
            </div>
            <div class="sm:col-span-2">
                <label for="parameter-description" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['parameter_description_label']) ?></label>
                <textarea id="parameter-description" name="description" rows="3" maxlength="<?= isset($parameter_fields['description']['max_length']) && $parameter_fields['description']['max_length'] > 0 ? (int) $parameter_fields['description']['max_length'] : 512 ?>" <?= !$write_available || !$backend_available ? 'disabled' : '' ?> class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-base text-slate-900 sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 read-only:bg-slate-50 read-only:text-slate-500 disabled:bg-slate-50 disabled:text-slate-500"><?= html_escape(isset($parameter_record['description']) && $parameter_record['description'] !== NULL ? $parameter_record['description'] : '') ?></textarea>
            </div>
        </div>
        <section aria-labelledby="parameter-options-heading" class="mt-8">
            <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div><h2 id="parameter-options-heading" class="text-lg font-semibold text-slate-950"><?= html_escape($labels['parameter_option_name_label']) ?></h2><p class="mt-1 text-sm text-slate-500"><?= html_escape($labels['parameter_option_minimum']) ?></p></div>
            </div>
            <div class="parameter-option-table-wrapper max-w-full min-w-0 rounded-xl border border-slate-200">
                <table class="parameter-option-table w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500"><tr>
                        <th scope="col" class="w-12 px-1 py-3 text-center"><span class="sr-only"><?= html_escape($labels['parameter_option_move_up']) ?></span></th>
                        <th scope="col" class="px-3 py-3"><?= html_escape($labels['parameter_option_code_label']) ?></th>
                        <th scope="col" class="px-3 py-3"><?= html_escape($labels['parameter_option_name_label']) ?></th>
                        <th scope="col" class="w-28 px-3 py-3 text-center"><?= html_escape($labels['parameter_active_label']) ?></th>
                        <th scope="col" class="w-16 px-3 py-3 text-center"><?= html_escape($labels['parameter_option_remove']) ?></th>
                    </tr></thead>
                    <tbody data-option-list class="divide-y divide-slate-100">
                    <?php foreach ($parameter_options as $index => $option): ?>
                        <tr data-option-row class="bg-white hover:bg-slate-50/70">
                            <td class="parameter-option-order-cell whitespace-nowrap px-1 py-2 text-center"><div class="inline-flex items-center justify-center gap-1">
                                <button type="button" data-option-move-up aria-label="<?= html_escape($labels['parameter_option_move_up']) ?>" title="<?= html_escape($labels['parameter_option_move_up']) ?>" <?= !$write_available || !$backend_available ? 'disabled' : '' ?> class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-40"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 5-5 5 5"/></svg></button>
                                <button type="button" data-option-move-down aria-label="<?= html_escape($labels['parameter_option_move_down']) ?>" title="<?= html_escape($labels['parameter_option_move_down']) ?>" <?= !$write_available || !$backend_available ? 'disabled' : '' ?> class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-40"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m5 8 5 5 5-5"/></svg></button>
                            </div></td>
                            <td class="parameter-option-code-cell px-3 py-2"><span class="parameter-option-mobile-label"><?= html_escape($labels['parameter_option_code_label']) ?></span><input name="options[<?= (int) $index ?>][option_code]" data-option-code type="text" required maxlength="<?= isset($parameter_fields['option_code']['max_length']) && $parameter_fields['option_code']['max_length'] > 0 ? (int) $parameter_fields['option_code']['max_length'] : 32 ?>" value="<?= html_escape(isset($option['option_code']) ? $option['option_code'] : '') ?>" <?= !$write_available || !$backend_available ? 'disabled' : '' ?> autocomplete="off" autocapitalize="none" spellcheck="false" aria-label="<?= html_escape($labels['parameter_option_code_label']) ?>" class="parameter-option-code-input min-h-11 w-full rounded-lg border border-slate-300 px-3 font-mono text-base text-slate-900 sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 read-only:bg-slate-50 read-only:text-slate-500 disabled:bg-slate-50 disabled:text-slate-500"></td>
                            <td class="parameter-option-name-cell px-3 py-2"><span class="parameter-option-mobile-label"><?= html_escape($labels['parameter_option_name_label']) ?></span><input name="options[<?= (int) $index ?>][option_name]" data-option-name data-name-title-case type="text" required maxlength="<?= isset($parameter_fields['option_name']['max_length']) && $parameter_fields['option_name']['max_length'] > 0 ? (int) $parameter_fields['option_name']['max_length'] : 64 ?>" value="<?= html_escape(isset($option['option_name']) ? $option['option_name'] : '') ?>" <?= !$write_available || !$backend_available ? 'disabled' : '' ?> aria-label="<?= html_escape($labels['parameter_option_name_label']) ?>" class="parameter-option-name-input min-h-11 w-full rounded-lg border border-slate-300 px-3 text-base text-slate-900 sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 read-only:bg-slate-50 read-only:text-slate-500 disabled:bg-slate-50 disabled:text-slate-500"></td>
                            <td class="parameter-option-active-cell px-3 py-2 text-center"><label class="inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-lg px-3 hover:bg-slate-50"><span class="parameter-option-mobile-label"><?= html_escape($labels['parameter_active_label']) ?></span><input name="options[<?= (int) $index ?>][active_flag]" data-option-active type="checkbox" value="1" <?= !empty($option['active_flag']) ? 'checked' : '' ?> <?= !$write_available || !$backend_available ? 'disabled' : '' ?> class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"><span class="sr-only"><?= html_escape($labels['parameter_active_label']) ?></span></label></td>
                            <td class="parameter-option-remove-cell px-3 py-2 text-center"><button type="button" data-option-remove aria-label="<?= html_escape($labels['parameter_option_remove']) ?>" title="<?= html_escape($labels['parameter_option_remove']) ?>" <?= !$write_available || !$backend_available ? 'disabled' : '' ?> class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-rose-700 transition hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-500 disabled:cursor-not-allowed disabled:opacity-50"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" d="M4 5.5h12M8 3.5h4m-6.5 2 1 11h7l1-11M8.5 8v5.5m3-5.5v5.5"/></svg></button></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3 flex justify-end">
                <button type="button" data-option-add <?= !$write_available || !$backend_available ? 'disabled' : '' ?> class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50">
                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-4 w-4"><path d="M10 4a.75.75 0 0 1 .75.75v4.5h4.5a.75.75 0 0 1 0 1.5h-4.5v4.5a.75.75 0 0 1-1.5 0v-4.5h-4.5a.75.75 0 0 1 0-1.5h4.5v-4.5A.75.75 0 0 1 10 4Z"/></svg><?= html_escape($labels['parameter_option_add']) ?>
                </button>
            </div>
        </section>
        <div data-parameter-form-actions class="sticky bottom-0 z-20 -mx-5 -mb-5 mt-8 flex flex-col-reverse gap-3 border-t border-slate-100 bg-white/50 px-5 pb-5 pt-4 backdrop-blur sm:-mx-8 sm:-mb-8 sm:flex-row sm:justify-between sm:px-8 sm:pb-8">
            <a href="/admin/system/parameter" class="admin-back-link inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12.5 4.5 7 10l5.5 5.5"/></svg><?= html_escape($labels['parameter_back']) ?></a>
            <button type="submit" <?= !$write_available || !$backend_available ? 'disabled aria-disabled="true"' : '' ?> class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-300"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4 3.5h10l2.5 2.5v10.5H3.5v-13H4Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6.5 3.5v4h6v-4M6.5 16v-5h7v5"/></svg><?= html_escape($labels['parameter_save']) ?></button>
        </div>
    </form>
</main>
<template id="parameter-option-template">
    <tr data-option-row class="bg-white hover:bg-slate-50/70">
        <td class="parameter-option-order-cell whitespace-nowrap px-1 py-2 text-center"><div class="inline-flex items-center justify-center gap-1"><button type="button" data-option-move-up aria-label="<?= html_escape($labels['parameter_option_move_up']) ?>" title="<?= html_escape($labels['parameter_option_move_up']) ?>" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-40"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 5-5 5 5"/></svg></button><button type="button" data-option-move-down aria-label="<?= html_escape($labels['parameter_option_move_down']) ?>" title="<?= html_escape($labels['parameter_option_move_down']) ?>" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-40"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m5 8 5 5 5-5"/></svg></button></div></td>
        <td class="parameter-option-code-cell px-3 py-2"><span class="parameter-option-mobile-label"><?= html_escape($labels['parameter_option_code_label']) ?></span><input data-option-code type="text" required maxlength="<?= isset($parameter_fields['option_code']['max_length']) && $parameter_fields['option_code']['max_length'] > 0 ? (int) $parameter_fields['option_code']['max_length'] : 32 ?>" autocomplete="off" autocapitalize="none" spellcheck="false" aria-label="<?= html_escape($labels['parameter_option_code_label']) ?>" class="parameter-option-code-input min-h-11 w-full rounded-lg border border-slate-300 px-3 font-mono text-base text-slate-900 sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 read-only:bg-slate-50 read-only:text-slate-500 disabled:bg-slate-50 disabled:text-slate-500"></td>
        <td class="parameter-option-name-cell px-3 py-2"><span class="parameter-option-mobile-label"><?= html_escape($labels['parameter_option_name_label']) ?></span><input data-option-name data-name-title-case type="text" required maxlength="<?= isset($parameter_fields['option_name']['max_length']) && $parameter_fields['option_name']['max_length'] > 0 ? (int) $parameter_fields['option_name']['max_length'] : 64 ?>" aria-label="<?= html_escape($labels['parameter_option_name_label']) ?>" class="parameter-option-name-input min-h-11 w-full rounded-lg border border-slate-300 px-3 text-base text-slate-900 sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 read-only:bg-slate-50 read-only:text-slate-500 disabled:bg-slate-50 disabled:text-slate-500"></td>
        <td class="parameter-option-active-cell px-3 py-2 text-center"><label class="inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-lg px-3 hover:bg-slate-50"><span class="parameter-option-mobile-label"><?= html_escape($labels['parameter_active_label']) ?></span><input data-option-active type="checkbox" value="1" checked class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"><span class="sr-only"><?= html_escape($labels['parameter_active_label']) ?></span></label></td>
        <td class="parameter-option-remove-cell px-3 py-2 text-center"><button type="button" data-option-remove aria-label="<?= html_escape($labels['parameter_option_remove']) ?>" title="<?= html_escape($labels['parameter_option_remove']) ?>" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-rose-700 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-500 disabled:cursor-not-allowed disabled:opacity-50"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" d="M4 5.5h12M8 3.5h4m-6.5 2 1 11h7l1-11M8.5 8v5.5m3-5.5v5.5"/></svg></button></td>
    </tr>
</template>
<script>
(function () {
    var list = document.querySelector('[data-option-list]');
    var addButton = document.querySelector('[data-option-add]');
    var template = document.getElementById('parameter-option-template');
    if (!list || !addButton || !template) { return; }

    function reindex() {
        var rows = list.querySelectorAll('[data-option-row]');
        if (!rows.length) {
            list.appendChild(template.content.firstElementChild.cloneNode(true));
            rows = list.querySelectorAll('[data-option-row]');
        }
        var optionCount = Math.max(1, rows.length);
        for (var index = 0; index < optionCount; index += 1) {
            var row = rows[index];
            row.querySelector('[data-option-code]').name = 'options[' + index + '][option_code]';
            row.querySelector('[data-option-name]').name = 'options[' + index + '][option_name]';
            row.querySelector('[data-option-active]').name = 'options[' + index + '][active_flag]';
            var moveUpButton = row.querySelector('[data-option-move-up]');
            var moveDownButton = row.querySelector('[data-option-move-down]');
            if (moveUpButton) { moveUpButton.disabled = index === 0; }
            if (moveDownButton) { moveDownButton.disabled = index === optionCount - 1; }
        }
    }
    function moveUp(row) {
        var previous = row.previousElementSibling;
        if (previous) { list.insertBefore(row, previous); reindex(); }
    }
    function moveOption(row, direction) {
        if (!row) { return; }
        if (direction === 'up') { moveUp(row); }
        else if (direction === 'down' && row.nextElementSibling) {
            list.insertBefore(row.nextElementSibling, row);
            reindex();
        }
    }
    function addOption() {
        if (list.querySelectorAll('[data-option-row]').length >= 500) { return; }
        var row = template.content.firstElementChild.cloneNode(true);
        list.appendChild(row);
        reindex();
        row.querySelector('[data-option-code]').focus();
    }
    addButton.addEventListener('click', addOption);
    list.addEventListener('click', function (event) {
        var remove = event.target.closest('[data-option-remove]');
        if (remove) {
            var rows = list.querySelectorAll('[data-option-row]');
            var row = remove.closest('[data-option-row]');
            if (rows.length > 1) { row.remove(); }
            else {
                row.querySelector('[data-option-code]').value = '';
                row.querySelector('[data-option-name]').value = '';
                row.querySelector('[data-option-active]').checked = true;
            }
            reindex();
            return;
        }
        var moveUpButton = event.target.closest('[data-option-move-up]');
        var moveDownButton = event.target.closest('[data-option-move-down]');
        if (moveUpButton || moveDownButton) {
            moveOption((moveUpButton || moveDownButton).closest('[data-option-row]'), moveUpButton ? 'up' : 'down');
            return;
        }
    });
    list.addEventListener('input', function (event) {
        var optionInput = event.target.closest('[data-option-code]');
        if (!optionInput) { return; }
        var start = optionInput.selectionStart;
        var normalized = optionInput.value.toLowerCase().replace(/\s+/g, '');
        optionInput.value = normalized;
        if (typeof optionInput.setSelectionRange === 'function' && start !== null) { optionInput.setSelectionRange(Math.min(start, normalized.length), Math.min(start, normalized.length)); }
    });
    list.addEventListener('blur', function (event) {
        var code = event.target.closest('[data-option-code]');
        if (code) { code.value = code.value.toLowerCase().replace(/\s+/g, ''); }
        var name = event.target.closest('[data-name-title-case]');
        if (name) { name.value = titleCaseName(name.value); }
    }, true);
    function titleCaseName(value) {
        return value.trim().toLocaleLowerCase().split(/\s+/).filter(Boolean).map(function (word) {
            return word.charAt(0).toLocaleUpperCase() + word.slice(1);
        }).join(' ');
    }
    document.querySelectorAll('[data-name-title-case]').forEach(function (input) {
        input.addEventListener('blur', function () { input.value = titleCaseName(input.value); });
    });
    var existingOptionCodes = list.querySelectorAll('[data-option-code]');
    for (var optionIndex = 0; optionIndex < existingOptionCodes.length; optionIndex += 1) {
        existingOptionCodes[optionIndex].value = existingOptionCodes[optionIndex].value.toLowerCase().replace(/\s+/g, '');
    }
    var codeInput = document.querySelector('[data-parameter-code-input]');
    if (codeInput && !codeInput.readOnly) {
        codeInput.addEventListener('input', function () {
            var start = codeInput.selectionStart;
            var normalized = codeInput.value.toLowerCase().replace(/\s+/g, '');
            codeInput.value = normalized;
            if (typeof codeInput.setSelectionRange === 'function') { codeInput.setSelectionRange(Math.min(start, normalized.length), Math.min(start, normalized.length)); }
        });
        codeInput.addEventListener('blur', function () { codeInput.value = codeInput.value.toLowerCase().replace(/\s+/g, ''); });
    }
    reindex();
})();
</script>
