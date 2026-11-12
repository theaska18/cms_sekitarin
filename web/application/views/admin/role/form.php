<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$is_new = $role_mode === 'new';
$title_key = $is_new ? 'role_form_new' : 'role_form_edit';
$role_title = $labels[$title_key];
$role_record = is_array($role_record) ? $role_record : array();
$field_specs = is_array($role_field_specs) ? $role_field_specs : array();
$role_tenants = is_array($role_tenants) ? $role_tenants : array();
$role_errors = is_array($role_errors) ? $role_errors : array();
?>
<main class="-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-2">
            <li><?= html_escape($labels['application']) ?></li>
            <li aria-hidden="true">/</li>
            <li><a href="/admin/application/role" class="hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"><?= html_escape($labels['role_heading']) ?></a></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" class="font-medium text-slate-800"><?= html_escape($role_title) ?></li>
        </ol>
    </nav>

    <header class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($role_title) ?></h1>
        <p class="mt-2 text-base leading-7 text-slate-600"><?= html_escape($labels['role_form_intro']) ?></p>
    </header>

    <?php if (!$role_backend_available): ?>
        <div role="status" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <p class="font-semibold"><?= html_escape($labels['role_unavailable']) ?></p>
            <p class="mt-1"><?= html_escape($labels['module_backend_unavailable']) ?></p>
        </div>
    <?php endif; ?>
    <?php if (!$role_write_available): ?>
        <div role="note" class="mb-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700"><?= html_escape($labels['role_write_disabled']) ?></div>
    <?php endif; ?>
    <?php if ($role_errors): ?>
        <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
            <p class="font-semibold"><?= html_escape($labels['role_invalid']) ?></p>
            <ul class="mt-2 list-inside list-disc">
                <?php foreach ($role_errors as $error): ?>
                    <?php $error_key = 'role_' . $error; ?>
                    <?php if (isset($labels[$error_key])): ?><li><?= html_escape($labels[$error_key]) ?></li><?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= html_escape($current_path) ?>" method="post" class="w-full rounded-t-2xl rounded-b-none border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
        <input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>">
        <div class="space-y-6">
            <?php if ($is_new): ?>
                <?php if (!empty($role_tenant_access)): ?>
                <div>
                    <label for="role-tenant" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['role_tenant_label']) ?></label>
                    <select id="role-tenant" name="tenant_id" required <?= !$role_write_available || !$role_backend_available ? 'disabled' : '' ?> class="min-h-12 w-full sm:w-[32rem] rounded-lg border border-slate-300 bg-white px-3 text-base text-slate-900 sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 disabled:bg-slate-50 disabled:text-slate-500 disabled:opacity-60">
                        <?php if (!$role_tenant_default_available): ?><option value="unavailable" selected disabled><?= html_escape($labels['role_tenant_placeholder']) ?></option><?php endif; ?>
                        <?php foreach ($role_tenants as $tenant): ?>
                            <?php $tenant_id = (string) $tenant['tenant_id']; ?>
                            <option value="<?= html_escape($tenant_id) ?>" <?= isset($role_record['tenant_id']) && (string) $role_record['tenant_id'] === $tenant_id ? 'selected' : '' ?>><?= html_escape($tenant['tenant_code'] . ' — ' . $tenant['tenant_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                    <input type="hidden" name="tenant_id" value="<?= html_escape(isset($role_record['tenant_id']) ? $role_record['tenant_id'] : '') ?>">
                <?php endif; ?>
            <?php endif; ?>
            <div>
                <label for="role-code" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['role_code_label']) ?></label>
                <div>
                    <div class="relative">
                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.5 4 16.5M15.5 3.5 12 16.5M2.5 8h15M1.5 12h15"/></svg>
                        <input id="role-code" name="role_code" data-role-code-input type="text" required maxlength="<?= isset($field_specs['role_code']['max_length']) && $field_specs['role_code']['max_length'] > 0 ? (int) $field_specs['role_code']['max_length'] : 255 ?>" value="<?= html_escape($is_new ? (isset($role_record['role_code']) ? $role_record['role_code'] : '') : $role_code) ?>" <?= $is_new ? '' : 'readonly' ?> <?= !$role_write_available || !$role_backend_available ? 'disabled' : '' ?> autocomplete="off" autocapitalize="none" spellcheck="false" class="min-h-12 w-full sm:w-64 rounded-lg border border-slate-300 pl-10 pr-3 text-base text-slate-900 sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 disabled:bg-slate-50 disabled:text-slate-500 disabled:opacity-60 read-only:bg-slate-50 read-only:text-slate-500 read-only:opacity-70">
                    </div>
                    <p class="mt-1.5 text-sm text-slate-500"><?= html_escape($labels['role_code_help']) ?></p>
                </div>
            </div>
            <div>
                <label for="role-name" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['role_name_label']) ?></label>
                <input id="role-name" name="role_name" data-role-name-input type="text" required maxlength="<?= isset($field_specs['role_name']['max_length']) && $field_specs['role_name']['max_length'] > 0 ? (int) $field_specs['role_name']['max_length'] : 255 ?>" value="<?= html_escape(isset($role_record['role_name']) ? $role_record['role_name'] : '') ?>" <?= !$role_write_available || !$role_backend_available ? 'disabled' : '' ?> autocomplete="off" class="min-h-12 w-full sm:w-[32rem] rounded-lg border border-slate-300 px-3 text-base text-slate-900 sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 disabled:bg-slate-50 disabled:text-slate-500 disabled:opacity-60 read-only:bg-slate-50 read-only:text-slate-500 read-only:opacity-70">
            </div>
            <div>
                <label for="role-description" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['role_description_label']) ?></label>
                <textarea id="role-description" name="description" rows="3" maxlength="<?= isset($field_specs['description']['max_length']) && $field_specs['description']['max_length'] > 0 ? (int) $field_specs['description']['max_length'] : 255 ?>" <?= !$role_write_available || !$role_backend_available ? 'disabled' : '' ?> class="w-full rounded-lg border border-slate-300 px-3 py-3 text-base text-slate-900 sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 disabled:bg-slate-50 disabled:text-slate-500 disabled:opacity-60"><?= html_escape(isset($role_record['description']) ? $role_record['description'] : '') ?></textarea>
            </div>
            <div>
                <input type="hidden" name="active_flag" value="0">
                <label for="active-flag" class="inline-flex min-h-11 items-center gap-3 rounded-lg border border-slate-200 px-3 text-sm font-medium text-slate-800">
                    <input id="active-flag" name="active_flag" type="checkbox" value="1" <?= (int) (isset($role_record['active_flag']) ? $role_record['active_flag'] : 1) === 1 ? 'checked' : '' ?> <?= !$role_write_available || !$role_backend_available ? 'disabled' : '' ?> class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-2 focus:ring-indigo-500 disabled:opacity-60">
                    <span><?= html_escape($labels['role_active_yes']) ?></span>
                </label>
            </div>
        </div>
        <div data-role-form-actions class="sticky bottom-0 z-20 -mx-5 -mb-5 mt-8 flex items-center justify-between gap-3 border-t border-slate-200 bg-white/10 px-5 pb-5 pt-4 backdrop-blur-sm sm:-mx-8 sm:-mb-8 sm:px-8 sm:pb-8">
            <a href="/admin/application/role" class="admin-back-link inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12.5 4.5 7 10l5.5 5.5"/></svg><?= html_escape($labels['role_back']) ?></a>
            <button type="submit" <?= !$role_write_available || !$role_backend_available ? 'disabled aria-disabled="true"' : '' ?> class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-300"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4 3.5h10l2.5 2.5v10.5H3.5v-13H4Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6.5 3.5v4h6v-4M6.5 16v-5h7v5"/></svg><?= html_escape($labels['role_save']) ?></button>
        </div>
    </form>
</main>
<script>
(function () {
    var code = document.querySelector('[data-role-code-input]');
    var name = document.querySelector('[data-role-name-input]');
    if (name) {
        name.addEventListener('blur', function () {
            name.value = name.value.split(/(\s+)/).map(function (part) {
                if (!part.trim()) { return part; }
                var letters = Array.from(part);
                letters[0] = letters[0].toLocaleUpperCase();
                return letters.join('');
            }).join('');
        });
    }
    if (!code) { return; }
    <?php if ($is_new): ?>
    function normalizeCode() {
        var cursor = code.selectionStart;
        var beforeCursor = code.value.slice(0, cursor).toLowerCase().replace(/\s+/g, '');
        code.value = code.value.toLowerCase().replace(/\s+/g, '');
        code.setSelectionRange(beforeCursor.length, beforeCursor.length);
    }
    code.addEventListener('input', normalizeCode);
    normalizeCode();
    <?php endif; ?>
    code.focus();
    if (code.value.length && typeof code.setSelectionRange === 'function') {
        code.setSelectionRange(code.value.length, code.value.length);
    }
})();
</script>
