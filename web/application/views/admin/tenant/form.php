<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$is_new = $tenant_mode === 'new';
$title_key = $is_new ? 'tenant_form_new' : 'tenant_form_edit';
$tenant_title = $labels[$title_key];
$tenant_record = is_array($tenant_record) ? $tenant_record : array();
$field_specs = is_array($tenant_fields) ? $tenant_fields : array();
$tenant_errors = is_array($tenant_errors) ? $tenant_errors : array();
?>
<main class="-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-2">
            <li><?= html_escape($labels['system']) ?></li>
            <li aria-hidden="true">/</li>
            <li><a href="/admin/system/tenant" class="hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"><?= html_escape($labels['tenant_heading']) ?></a></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" class="font-medium text-slate-800"><?= html_escape($tenant_title) ?></li>
        </ol>
    </nav>

    <header class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($tenant_title) ?></h1>
        <p class="mt-2 text-base leading-7 text-slate-600"><?= html_escape($labels['tenant_form_intro']) ?></p>
    </header>

    <?php if (!$backend_available): ?>
        <div role="status" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <p class="font-semibold"><?= html_escape($labels['tenant_unavailable']) ?></p>
            <p class="mt-1"><?= html_escape($labels['module_backend_unavailable']) ?></p>
        </div>
    <?php endif; ?>
    <?php if (!$write_available): ?>
        <div role="note" class="mb-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700"><?= html_escape($labels['tenant_write_disabled']) ?></div>
    <?php endif; ?>
    <?php if ($tenant_errors): ?>
        <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
            <p class="font-semibold"><?= html_escape($labels['tenant_invalid']) ?></p>
            <ul class="mt-2 list-inside list-disc">
                <?php foreach ($tenant_errors as $error): ?>
                    <?php $error_key = 'tenant_' . $error; ?>
                    <?php if (isset($labels[$error_key])): ?><li><?= html_escape($labels[$error_key]) ?></li><?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= html_escape($current_path) ?>" method="post" class="w-full rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
        <input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>">
        <div class="space-y-6">
            <div>
                <label for="tenant-code" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['tenant_code_label']) ?></label>
                <div>
                <div class="relative">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.5 4 16.5M15.5 3.5 12 16.5M2.5 8h15M1.5 12h15"/></svg>
                    <input id="tenant-code" name="tenant_code" data-tenant-code-input type="text" required maxlength="<?= isset($field_specs['tenant_code']['max_length']) && $field_specs['tenant_code']['max_length'] > 0 ? (int) $field_specs['tenant_code']['max_length'] : 255 ?>" value="<?= html_escape($is_new ? (isset($tenant_record['tenant_code']) ? $tenant_record['tenant_code'] : '') : $tenant_code) ?>" <?= $is_new ? '' : 'readonly' ?> <?= !$write_available || !$backend_available ? 'disabled' : '' ?> autocomplete="off" autocapitalize="none" spellcheck="false" class="min-h-12 w-full sm:w-64 rounded-lg border border-slate-300 pl-10 pr-3 text-base text-slate-900 sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 disabled:bg-slate-50 disabled:text-slate-500 disabled:opacity-60 read-only:bg-slate-50 read-only:text-slate-500 read-only:opacity-70">
                </div>
                <p class="mt-1.5 text-sm text-slate-500"><?= html_escape($labels['tenant_code_help']) ?></p>
                </div>
            </div>
            <div>
                <label for="tenant-name" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['tenant_name_label']) ?></label>
                <input id="tenant-name" name="tenant_name" data-tenant-name-input type="text" required maxlength="<?= isset($field_specs['tenant_name']['max_length']) && $field_specs['tenant_name']['max_length'] > 0 ? (int) $field_specs['tenant_name']['max_length'] : 255 ?>" value="<?= html_escape(isset($tenant_record['tenant_name']) ? $tenant_record['tenant_name'] : '') ?>" <?= !$write_available || !$backend_available ? 'disabled' : '' ?> autocomplete="off" class="min-h-12 w-full sm:w-[32rem] rounded-lg border border-slate-300 px-3 text-base text-slate-900 sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 disabled:bg-slate-50 disabled:text-slate-500 disabled:opacity-60 read-only:bg-slate-50 read-only:text-slate-500 read-only:opacity-70">
            </div>
            <div>
                <label for="tenant-address" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['tenant_address_label']) ?></label>
                <textarea id="tenant-address" name="address" rows="3" maxlength="<?= isset($field_specs['address']['max_length']) && $field_specs['address']['max_length'] > 0 ? (int) $field_specs['address']['max_length'] : 255 ?>" <?= !$write_available || !$backend_available ? 'disabled' : '' ?> autocomplete="street-address" class="w-full rounded-lg border border-slate-300 px-3 py-3 text-base text-slate-900 sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 disabled:bg-slate-50 disabled:text-slate-500 disabled:opacity-60"><?= html_escape(isset($tenant_record['address']) ? $tenant_record['address'] : '') ?></textarea>
            </div>
            <div>
                <label for="tenant-email" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['tenant_email_label']) ?></label>
                <input id="tenant-email" name="email_address" type="email" required maxlength="64" value="<?= html_escape(isset($tenant_record['email_address']) ? $tenant_record['email_address'] : '') ?>" <?= !$write_available || !$backend_available ? 'disabled' : '' ?> autocomplete="email" class="min-h-12 w-full sm:w-96 rounded-lg border border-slate-300 px-3 text-base text-slate-900 sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 disabled:bg-slate-50 disabled:text-slate-500 disabled:opacity-60 read-only:bg-slate-50 read-only:text-slate-500 read-only:opacity-70">
            </div>
            <div>
                <label for="tenant-phone" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['tenant_phone_label']) ?></label>
                <input id="tenant-phone" name="phone_number" type="tel" required maxlength="16" value="<?= html_escape(isset($tenant_record['phone_number']) ? $tenant_record['phone_number'] : '') ?>" <?= !$write_available || !$backend_available ? 'disabled' : '' ?> autocomplete="tel" class="min-h-12 w-full sm:w-64 rounded-lg border border-slate-300 px-3 text-base text-slate-900 sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 disabled:bg-slate-50 disabled:text-slate-500 disabled:opacity-60 read-only:bg-slate-50 read-only:text-slate-500 read-only:opacity-70">
            </div>
            <div>
                <div class="mb-1.5 flex items-center gap-2">
                    <label id="tenant-due-on-label" for="tenant-due-on" class="text-sm font-semibold text-slate-800"><?= html_escape($labels['tenant_due_on_label']) ?></label>
                    <input id="tenant-due-flag" name="due_flag" type="checkbox" value="1" data-due-flag-input aria-label="<?= html_escape($labels['tenant_due_flag_label']) ?>" <?= !empty($tenant_record['due_flag']) ? 'checked' : '' ?> <?= !$write_available || !$backend_available ? 'disabled' : '' ?> class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 disabled:opacity-50">
                </div>
                <input id="tenant-due-on" name="due_on" type="date" data-due-on-input aria-labelledby="tenant-due-on-label" value="<?= html_escape(isset($tenant_record['due_on']) ? $tenant_record['due_on'] : '') ?>" <?= empty($tenant_record['due_flag']) ? 'readonly' : '' ?> <?= !$write_available || !$backend_available ? 'disabled' : '' ?> class="min-h-12 w-full sm:w-56 rounded-lg border border-slate-300 px-3 text-base text-slate-900 sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 disabled:bg-slate-50 disabled:text-slate-500 disabled:opacity-60 read-only:cursor-not-allowed read-only:bg-slate-50 read-only:text-slate-500 read-only:opacity-70">
            </div>
        </div>
        <div data-tenant-sticky-actions class="sticky bottom-0 z-20 -mx-4 -mb-4 mt-8 flex flex-col-reverse gap-3 border-t border-slate-200 bg-white/10 p-4 shadow-sm backdrop-blur-sm sm:-mx-6 sm:-mb-6 sm:flex-row sm:justify-between sm:px-6 sm:pb-6">
            <a href="/admin/system/tenant" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12.5 4.5 7 10l5.5 5.5"/></svg><?= html_escape($labels['tenant_back']) ?></a>
            <button type="submit" <?= !$write_available || !$backend_available ? 'disabled aria-disabled="true"' : '' ?> class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-300"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4 3.5h10l2.5 2.5v10.5H3.5v-13H4Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6.5 3.5v4h6v-4M6.5 16v-5h7v5"/></svg><?= html_escape($labels['tenant_save']) ?></button>
        </div>
    </form>
</main>
<script>
(function () {
    var dueFlag = document.querySelector('[data-due-flag-input]');
    var dueOn = document.querySelector('[data-due-on-input]');
    if (dueFlag && dueOn) {
        var syncDueOn = function () {
            dueOn.readOnly = !dueFlag.checked;
            dueOn.required = dueFlag.checked;
            if (!dueFlag.checked) { dueOn.value = ''; }
        };
        dueFlag.addEventListener('change', syncDueOn);
        syncDueOn();
    }
    var tenantName = document.querySelector('[data-tenant-name-input]');
    var titleCaseName = function () {
        if (!tenantName) { return; }
        tenantName.value = tenantName.value.split(/(\s+)/).map(function (part) {
            if (!part.trim()) { return part; }
            var letters = Array.from(part);
            letters[0] = letters[0].toLocaleUpperCase();
            return letters.join('');
        }).join('');
    };
    if (tenantName) {
        tenantName.addEventListener('blur', titleCaseName);
    }
    var focusField = document.getElementById(<?= json_encode($is_new ? 'tenant-code' : 'tenant-name') ?>);
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
