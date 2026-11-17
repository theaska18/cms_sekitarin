<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$role_title = $labels['role_heading'];
$status_messages = array('created', 'saved', 'deleted', 'delete_error', 'save_error', 'invalid');
$role_status_key = in_array($role_status_key, $status_messages, TRUE) ? $role_status_key : '';
$role_search = isset($role_search) ? (string) $role_search : '';
$role_filter_mode = isset($role_filter_mode) && $role_filter_mode === 'columns' ? 'columns' : 'search';
$role_filters = isset($role_filters) && is_array($role_filters) ? $role_filters : array();
$role_tenants = isset($role_tenants) && is_array($role_tenants) ? $role_tenants : array();
$role_selected_tenant = isset($role_selected_tenant) ? (int) $role_selected_tenant : 0;
$role_sort = isset($role_sort) ? (string) $role_sort : 'role_code';
$role_direction = isset($role_direction) && $role_direction === 'DESC' ? 'DESC' : 'ASC';
$role_page = max(1, (int) $role_page);
$role_page_count = max(1, (int) $role_page_count);
$role_per_page = (int) $role_per_page;
$role_records = is_array($role_records) ? $role_records : array();
$role_page_params = array('filter_mode' => $role_filter_mode, 'search' => $role_search, 'per_page' => $role_per_page, 'sort' => $role_sort, 'direction' => $role_direction);
if ($role_filter_mode === 'columns') {
    $role_page_params['tenant_id'] = $role_selected_tenant;
    foreach (array('role_code', 'role_name', 'description', 'active_flag') as $filter_name) {
        if (isset($role_filters[$filter_name]) && $role_filters[$filter_name] !== '') {
            $role_page_params[$filter_name] = $role_filters[$filter_name];
        }
    }
}
$role_sort_url = function ($field) use ($role_page_params, $role_sort, $role_direction) {
    $next_direction = $role_sort === $field && $role_direction === 'ASC' ? 'DESC' : 'ASC';
    $params = $role_page_params;
    $params['sort'] = $field;
    $params['direction'] = $next_direction;
    return '/admin/application/role?' . http_build_query($params);
};
$has_active_filters = trim($role_search) !== '' || ($role_filter_mode === 'columns' && count(array_filter($role_filters, function ($value) { return $value !== ''; })) > 0);
$empty_title_key = $has_active_filters ? 'role_no_results' : 'role_empty';
$empty_hint_key = $has_active_filters ? 'role_no_results_hint' : 'role_empty_hint';
?>
<main class="-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-2">
            <li><?= html_escape($labels['application']) ?></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" class="font-medium text-slate-800"><?= html_escape($role_title) ?></li>
        </ol>
    </nav>

    <header class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div class="max-w-2xl">
            <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl"><?= html_escape($role_title) ?></h1>
            <p class="mt-2 text-base leading-7 text-slate-600"><?= html_escape($labels['role_intro']) ?></p>
        </div>
        <?php if (!empty($role_create_allowed) && $role_create_available): ?>
            <a href="/admin/application/role/new" class="inline-flex h-11 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-indigo-600 px-4 py-0 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><path d="M10 4a.75.75 0 0 1 .75.75v4.5h4.5a.75.75 0 0 1 0 1.5h-4.5v4.5a.75.75 0 0 1-1.5 0v-4.5h-4.5a.75.75 0 0 1 0-1.5h4.5v-4.5A.75.75 0 0 1 10 4Z"/></svg>
                <?= html_escape($labels['role_add']) ?>
            </a>
        <?php elseif (!empty($role_create_allowed)): ?>
            <span class="inline-flex h-11 shrink-0 cursor-not-allowed items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-slate-200 px-4 py-0 text-sm font-semibold text-slate-500" aria-disabled="true">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><path d="M10 4a.75.75 0 0 1 .75.75v4.5h4.5a.75.75 0 0 1 0 1.5h-4.5v4.5a.75.75 0 0 1-1.5 0v-4.5h-4.5a.75.75 0 0 1 0-1.5h4.5v-4.5A.75.75 0 0 1 10 4Z"/></svg>
                <?= html_escape($labels['role_add']) ?>
            </span>
        <?php endif; ?>
    </header>

    <?php if ($role_status_key !== ''): ?>
        <?php $is_error = in_array($role_status_key, array('delete_error', 'save_error', 'invalid'), TRUE); ?>
        <div role="status" class="mb-5 flex items-start gap-3 rounded-xl border p-4 text-sm <?= $is_error ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900' ?>">
            <span aria-hidden="true" class="mt-0.5"><?= $is_error ? '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><circle cx="10" cy="10" r="7.5"/><path stroke-linecap="round" d="M10 6v4.5m0 3h.01"/></svg>' : '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><circle cx="10" cy="10" r="7.5"/><path stroke-linecap="round" stroke-linejoin="round" d="m6.5 10 2.3 2.3 4.7-4.7"/></svg>' ?></span>
            <p><?= html_escape($labels['role_' . $role_status_key]) ?></p>
        </div>
    <?php endif; ?>
    <?php if (!$role_backend_available): ?>
        <div role="status" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <p class="font-semibold"><?= html_escape($labels['role_unavailable']) ?></p>
            <p class="mt-1"><?= html_escape($labels['module_backend_unavailable']) ?></p>
        </div>
    <?php endif; ?>
    <?php if (!$role_create_available): ?>
        <div role="note" class="mb-5 flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
            <span aria-hidden="true" class="mt-0.5 text-indigo-600"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"><circle cx="10" cy="10" r="7.5"/><path stroke-linecap="round" d="M10 9v4m0-7h.01"/></svg></span>
            <p><?= html_escape($labels['role_write_disabled']) ?></p>
        </div>
    <?php endif; ?>

    <section aria-label="<?= html_escape($role_title) ?>" class="rounded-tl-none rounded-tr-none rounded-bl-2xl rounded-br-2xl border border-slate-200 bg-white shadow-sm">
        <?php if ($role_backend_available): ?>
            <input type="hidden" name="per_page" value="<?= $role_per_page ?>" form="role-filter-form">
        <?php endif; ?>
        <div class="sticky top-16 z-20 flex flex-col gap-4 border-b border-slate-200 bg-white/10 px-3 py-4 shadow-none backdrop-blur-sm sm:px-6 rounded-none" data-role-filter-header>
            <?php if ($role_backend_available): ?>
                <div class="w-full sm:flex sm:flex-wrap sm:justify-end">
                    <div class="flex w-full items-center justify-end gap-2 sm:w-auto">
                        <form id="role-filter-form" action="/admin/application/role" method="get" data-role-live-filter <?= $role_filter_mode === 'columns' ? 'hidden' : '' ?> class="<?= $role_filter_mode === 'columns' ? 'hidden ' : '' ?>flex min-w-0 flex-1 items-center gap-2 sm:w-64 sm:flex-none">
                            <input type="hidden" name="filter_mode" value="search">
                            <label for="role-filter-search" class="sr-only"><?= html_escape($labels['role_search_label']) ?></label>
                            <div class="relative min-w-0 flex-1">
                                <svg data-role-search-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"><circle cx="10.8" cy="10.8" r="6.8"/><path stroke-linecap="round" d="m16 16 4.2 4.2"/></svg>
                                <input id="role-filter-search" type="search" name="search" value="<?= html_escape($role_filter_mode === 'search' ? $role_search : '') ?>" maxlength="100" autocomplete="off" placeholder="<?= html_escape($labels['role_search_hint']) ?>" class="min-h-10 w-full rounded-lg border border-slate-300 bg-white pl-10 pr-3 text-base text-slate-900 placeholder:text-slate-400 sm:text-sm focus:border-emerald-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                            </div>
                            <input type="hidden" name="sort" value="<?= html_escape($role_sort) ?>"><input type="hidden" name="direction" value="<?= html_escape($role_direction) ?>">
                        </form>
                        <button type="button" data-role-column-filter-toggle aria-expanded="<?= $role_filter_mode === 'columns' ? 'true' : 'false' ?>" aria-controls="role-filter-form role-column-filter-panel" data-label-columns="<?= html_escape($labels['role_filter_columns']) ?>" data-label-search="<?= html_escape($labels['role_search_label']) ?>" aria-label="<?= html_escape($role_filter_mode === 'columns' ? $labels['role_search_label'] : $labels['role_filter_columns']) ?>" title="<?= html_escape($role_filter_mode === 'columns' ? $labels['role_search_label'] : $labels['role_filter_columns']) ?>" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                            <svg data-role-filter-icon-columns viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" <?= $role_filter_mode === 'columns' ? 'hidden' : '' ?> class="h-5 w-5 <?= $role_filter_mode === 'columns' ? 'hidden' : '' ?>"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h14M5.5 10h9M8 16h4"/><circle cx="6" cy="4" r="1.2" fill="currentColor"/><circle cx="13" cy="10" r="1.2" fill="currentColor"/><circle cx="10" cy="16" r="1.2" fill="currentColor"/></svg>
                            <svg data-role-filter-icon-search viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" <?= $role_filter_mode === 'columns' ? '' : 'hidden' ?> class="h-5 w-5 <?= $role_filter_mode === 'columns' ? '' : 'hidden' ?>"><circle cx="8.8" cy="8.8" r="5.8"/><path stroke-linecap="round" d="m13.2 13.2 4 4"/></svg>
                        </button>
                    </div>
                    <form id="role-column-filter-panel" action="/admin/application/role" method="get" data-role-column-filter-panel <?= $role_filter_mode === 'columns' ? '' : 'hidden' ?> class="<?= $role_filter_mode === 'columns' ? '' : 'hidden ' ?>mt-3 grid w-full min-w-0 grid-cols-1 gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 sm:grid-cols-2 lg:grid-cols-5">
                        <input type="hidden" name="filter_mode" value="columns"><input type="hidden" name="per_page" value="<?= $role_per_page ?>"><input type="hidden" name="sort" value="<?= html_escape($role_sort) ?>"><input type="hidden" name="direction" value="<?= html_escape($role_direction) ?>">
                        <?php if (!empty($role_tenant_access)): ?><label class="block text-xs font-semibold text-slate-600"><span class="mb-1 block"><?= html_escape($labels['role_tenant_label']) ?></span>
                            <select id="role-filter-tenant" name="tenant_id" class="min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                                <?php foreach ($role_tenants as $tenant): ?><option value="<?= (int) $tenant['tenant_id'] ?>"<?= (int) $tenant['tenant_id'] === $role_selected_tenant ? ' selected' : '' ?>><?= html_escape($tenant['tenant_code'] . ' — ' . $tenant['tenant_name']) ?></option><?php endforeach; ?>
                            </select>
                        </label><?php endif; ?>
                        <?php foreach (array('role_code' => $labels['role_code_label'], 'role_name' => $labels['role_name_label'], 'description' => $labels['role_description_label']) as $field => $field_label): ?>
                            <label class="block text-xs font-semibold text-slate-600"><span class="mb-1 block"><?= html_escape($field_label) ?></span><input type="search" name="<?= html_escape($field) ?>" value="<?= html_escape(isset($role_filters[$field]) ? $role_filters[$field] : '') ?>" maxlength="100" class="min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"></label>
                        <?php endforeach; ?>
                        <label class="block text-xs font-semibold text-slate-600"><span class="mb-1 block"><?= html_escape($labels['role_active_label']) ?></span><select name="active_flag" class="min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"><option value=""><?= html_escape($labels['role_filter_any']) ?></option><option value="1"<?= isset($role_filters['active_flag']) && (string) $role_filters['active_flag'] === '1' ? ' selected' : '' ?>><?= html_escape($labels['role_active_yes']) ?></option><option value="0"<?= isset($role_filters['active_flag']) && (string) $role_filters['active_flag'] === '0' ? ' selected' : '' ?>><?= html_escape($labels['role_active_no']) ?></option></select></label>
                        <div class="flex items-end"><button type="submit" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m3 10 14-6-6 14-2-6-6-2Z"/></svg><?= html_escape($labels['role_filter_apply']) ?></button></div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <div class="max-w-full overflow-x-auto overscroll-x-contain md:overflow-visible rounded-b-2xl" tabindex="0" role="region" aria-label="<?= html_escape($role_title) ?>">
            <table class="w-full min-w-[42rem] table-auto divide-y divide-slate-200 text-left text-sm">
                <thead class="md:sticky md:z-10 bg-slate-50/10 text-xs font-semibold uppercase tracking-wide text-slate-500 backdrop-blur-sm rounded-none" style="top: calc(4rem + var(--role-filter-header-height, 4.5rem));">
                    <tr>
                        <th scope="col" aria-label="<?= html_escape($labels['role_actions']) ?>" class="w-14 min-w-[3.5rem] px-1 py-3.5 sm:px-3"></th>
                        <?php if (!empty($role_tenant_access)): ?><th scope="col" class="min-w-[10rem] whitespace-nowrap px-2 py-3.5 sm:px-6"><?= html_escape($labels['role_tenant_label']) ?></th><?php endif; ?>
                        <?php foreach (array('role_code' => $labels['role_code_label'], 'role_name' => $labels['role_name_label']) as $sort_field => $sort_label): ?>
                            <?php $sort_active = $role_sort === $sort_field; $sort_aria = !$sort_active ? 'none' : ($role_direction === 'ASC' ? 'ascending' : 'descending'); ?>
                            <th scope="col" aria-sort="<?= html_escape($sort_aria) ?>" class="<?= $sort_field === 'role_code' ? 'min-w-[8rem] whitespace-nowrap' : 'min-w-[10rem] break-words' ?> px-2 py-3.5 sm:px-6">
                                <a href="<?= html_escape($role_sort_url($sort_field)) ?>" class="inline-flex min-h-8 items-center gap-1.5 rounded-md hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                                    <?= html_escape($sort_label) ?>
                                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="h-3.5 w-3.5 <?= $sort_active && $role_direction === 'DESC' ? 'rotate-180' : '' ?> <?= $sort_active ? 'text-indigo-600' : 'text-slate-400' ?>"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3v10m0 0 3.5-3.5M8 13l-3.5-3.5"/></svg>
                                </a>
                            </th>
                        <?php endforeach; ?>
                        <th scope="col" class="min-w-[12rem] break-words px-2 py-3.5 sm:px-6"><?= html_escape($labels['role_description_label']) ?></th>
                        <th scope="col" class="min-w-[7rem] whitespace-nowrap px-2 py-3.5 sm:px-6"><?= html_escape($labels['role_active_label']) ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if ($role_backend_available && count($role_records) > 0): ?>
                        <?php $action_menu_index = 0; foreach ($role_records as $role): ?>
                            <?php $role_code_value = (string) $role['role_code']; $action_menu_id = 'role-actions-menu-' . (int) $action_menu_index++; ?>
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="w-14 min-w-[3.5rem] whitespace-nowrap px-1 py-3 text-left sm:px-3 sm:py-4">
                                    <?php if ($role_has_actions): ?>
                                        <div class="relative inline-flex">
                                            <button type="button" data-role-actions-toggle aria-label="<?= html_escape($labels['role_actions']) ?>" aria-haspopup="menu" aria-expanded="false" aria-controls="<?= html_escape($action_menu_id) ?>" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                                <svg data-role-actions-icon viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><circle cx="10" cy="4" r="1.6"/><circle cx="10" cy="10" r="1.6"/><circle cx="10" cy="16" r="1.6"/></svg>
                                            </button>
                                            <div id="<?= html_escape($action_menu_id) ?>" data-role-actions-menu hidden role="menu" class="fixed z-[60] mt-1 w-40 rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-lg">
                                                <?php if (!empty($role_update_available)): ?><a href="/admin/application/role/change/<?= html_escape(rawurlencode($role_code_value)) ?>/<?= (int) $role['tenant_id'] ?>" role="menuitem" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none">
                                                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m12.8 3.2 4 4M3.5 16.5l3.6-.7L16.3 6.6a1.4 1.4 0 0 0-2-2L5.1 13.8l-1.6 2.7Z"/></svg>
                                                    <?= html_escape($labels['role_edit']) ?>
                                                </a><?php endif; ?>
                                                <?php if (!empty($role_menu_access_available)): ?><a href="/admin/application/role/menu/<?= html_escape(rawurlencode($role_code_value)) ?>/<?= (int) $role['tenant_id'] ?>" role="menuitem" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-medium text-indigo-700 hover:bg-indigo-50 focus:bg-indigo-50 focus:outline-none">
                                                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><rect x="3" y="4" width="14" height="12" rx="1.5"/><path stroke-linecap="round" d="M7 7.5h6M7 10h6m-6 2.5h3"/></svg>
                                                    <?= html_escape($labels['role_menu_access']) ?>
                                                </a><?php endif; ?>
                                                <?php if (!empty($role_delete_available)): ?><button type="button" data-role-delete-open="1" data-role-code="<?= html_escape($role_code_value) ?>" data-role-tenant-id="<?= (int) $role['tenant_id'] ?>" data-role-name="<?= html_escape($role['role_name']) ?>" aria-haspopup="dialog" aria-controls="role-delete-dialog" role="menuitem" class="flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-sm font-medium text-rose-700 hover:bg-rose-50 focus:bg-rose-50 focus:outline-none">
                                                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" d="M4 5.5h12M8 3.5h4m-6.5 2 1 11h7l1-11M8.5 8v5.5m3-5.5v5.5"/></svg>
                                                    <?= html_escape($labels['role_delete']) ?>
                                                </button><?php endif; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <?php if (!empty($role_tenant_access)): ?><td class="min-w-[10rem] break-words px-2 py-3 text-slate-700 sm:px-6 sm:py-4"><?= html_escape(trim((string) (isset($role['tenant_code']) ? $role['tenant_code'] : '') . ' — ' . (isset($role['tenant_name']) ? $role['tenant_name'] : ''))) ?></td><?php endif; ?>
                                <th scope="row" class="min-w-[8rem] whitespace-nowrap break-all px-2 py-3 font-mono text-xs font-semibold text-slate-700 sm:px-6 sm:py-4"><?= html_escape($role_code_value) ?></th>
                                <td class="min-w-[10rem] break-words px-2 py-3 font-medium text-slate-900 sm:px-6 sm:py-4"><?= html_escape($role['role_name']) ?></td>
                                <td class="min-w-[12rem] break-words px-2 py-3 text-slate-700 sm:px-6 sm:py-4"><?= html_escape(isset($role['description']) ? $role['description'] : '') ?></td>
                                <td class="min-w-[7rem] whitespace-nowrap px-2 py-3 sm:px-6 sm:py-4"><span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold <?= !empty($role['active_flag']) ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>"><span aria-hidden="true" class="h-1.5 w-1.5 rounded-full <?= !empty($role['active_flag']) ? 'bg-emerald-500' : 'bg-slate-400' ?>"></span><?= html_escape($labels[!empty($role['active_flag']) ? 'role_active_yes' : 'role_active_no']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= !empty($role_tenant_access) ? 6 : 5 ?>" class="px-4 py-14 text-center sm:px-6">
                                <div class="mx-auto max-w-md">
                                    <div aria-hidden="true" class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-500"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"><circle cx="10.8" cy="10.8" r="6.8"/><path stroke-linecap="round" d="m16 16 4.2 4.2"/></svg></div>
                                    <p class="font-medium text-slate-800"><?= html_escape($role_backend_available ? $labels[$empty_title_key] : $labels['role_unavailable']) ?></p>
                                    <?php if ($role_backend_available): ?><p class="mt-1 text-sm text-slate-500"><?= html_escape($labels[$empty_hint_key]) ?></p><?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($role_backend_available): ?>
            <div class="flex flex-col gap-3 border-t border-slate-200 px-3 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <form action="/admin/application/role" method="get" class="flex items-center gap-2 self-center sm:flex-1 sm:self-auto">
                    <?php foreach ($role_page_params as $param_name => $param_value): ?><?php if ($param_name !== 'page' && $param_name !== 'per_page'): ?><input type="hidden" name="<?= html_escape($param_name) ?>" value="<?= html_escape($param_value) ?>"><?php endif; ?><?php endforeach; ?>
                    <label for="role-page-size" class="whitespace-nowrap text-sm font-medium text-slate-600"><?= html_escape($labels['role_per_page']) ?></label>
                    <select id="role-page-size" name="per_page" onchange="this.form.submit()" class="min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                        <?php foreach ($role_per_page_choices as $choice): ?><option value="<?= (int) $choice ?>"<?= $choice === $role_per_page ? ' selected' : '' ?>><?= (int) $choice ?></option><?php endforeach; ?>
                    </select>
                </form>
                <div class="flex flex-col gap-3 sm:flex-[2] sm:flex-row sm:items-center sm:justify-end">
                    <nav aria-label="<?= html_escape($labels['role_pagination']) ?>" class="flex w-full items-center justify-end gap-1 sm:flex-1" role="navigation">
                    <?php if ($role_page > 1): ?><?php $role_page_params['page'] = $role_page - 1; ?><a rel="prev" href="/admin/application/role?<?= html_escape(http_build_query($role_page_params)) ?>" aria-label="<?= html_escape($labels['role_previous']) ?>" title="<?= html_escape($labels['role_previous']) ?>" class="inline-flex h-10 w-10 flex-none items-center justify-center rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12.5 4.5 7 10l5.5 5.5"/></svg></a><?php else: ?><span aria-disabled="true" aria-label="<?= html_escape($labels['role_previous']) ?>" title="<?= html_escape($labels['role_previous']) ?>" class="inline-flex h-10 w-10 flex-none items-center justify-center rounded-lg border border-slate-200 text-slate-400"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12.5 4.5 7 10l5.5 5.5"/></svg></span><?php endif; ?>
                    <p class="whitespace-nowrap text-center text-xs text-slate-600 sm:text-sm" aria-live="polite"><?= html_escape(sprintf($labels['role_pagination_summary'], $role_page, $role_page_count, number_format((int) $role_total, 0, ',', '.'))) ?></p>
                    <?php if ($role_page < $role_page_count): ?><?php $role_page_params['page'] = $role_page + 1; ?><a rel="next" href="/admin/application/role?<?= html_escape(http_build_query($role_page_params)) ?>" aria-label="<?= html_escape($labels['role_next']) ?>" title="<?= html_escape($labels['role_next']) ?>" class="inline-flex h-10 w-10 flex-none items-center justify-center rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus:ring-offset-2"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="m7.5 4.5 5.5 5.5-5.5 5.5"/></svg></a><?php else: ?><span aria-disabled="true" aria-label="<?= html_escape($labels['role_next']) ?>" title="<?= html_escape($labels['role_next']) ?>" class="inline-flex h-10 w-10 flex-none items-center justify-center rounded-lg border border-slate-200 text-slate-400"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="m7.5 4.5 5.5 5.5-5.5 5.5"/></svg></span><?php endif; ?>
                    </nav>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($role_has_actions): ?>
        <div id="role-delete-dialog" hidden role="dialog" aria-modal="true" aria-labelledby="role-delete-title" aria-describedby="role-delete-description" tabindex="-1" class="fixed inset-0 z-50 items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl sm:p-7">
                <div aria-hidden="true" class="mb-4 flex h-11 w-11 items-center justify-center rounded-full bg-rose-50 text-lg font-bold text-rose-700">!</div>
                <h2 id="role-delete-title" class="text-xl font-semibold text-slate-950"><?= html_escape($labels['role_confirm_delete']) ?></h2>
                <p id="role-delete-description" class="mt-2 text-sm leading-6 text-slate-600"><?= html_escape($labels['role_confirm_delete']) ?></p>
                <p class="mt-3 break-all rounded-lg bg-slate-50 px-3 py-2 font-mono text-sm text-slate-800" data-role-delete-label></p>
                <form action="/admin/application/role/delete" method="post" class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>"><input type="hidden" name="role_code" value="" data-role-delete-code><input type="hidden" name="tenant_id" value="" data-role-delete-tenant>
                    <button type="button" data-role-delete-cancel class="min-h-11 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"><?= html_escape($labels['role_cancel']) ?></button>
                    <button type="submit" data-role-delete-confirm class="min-h-11 rounded-lg bg-rose-600 px-4 text-sm font-semibold text-white hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"><?= html_escape($labels['role_confirm_delete']) ?></button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</main>
<script>
(function () {
    var roleFilterHeader = document.querySelector('[data-role-filter-header]');
    function updateRoleTableStickyOffset() {
        if (roleFilterHeader) {
            document.documentElement.style.setProperty('--role-filter-header-height', roleFilterHeader.getBoundingClientRect().height + 'px');
        }
    }
    updateRoleTableStickyOffset();
    if (roleFilterHeader && typeof ResizeObserver === 'function') {
        new ResizeObserver(updateRoleTableStickyOffset).observe(roleFilterHeader);
    } else {
        window.addEventListener('resize', updateRoleTableStickyOffset);
    }
    var filterForm = document.querySelector('[data-role-live-filter]');
    var searchInput = filterForm ? filterForm.querySelector('[name="search"]') : null;
    var filterTimer = null;
    if (filterForm && searchInput) {
        if (searchInput.value.length) { searchInput.focus(); if (typeof searchInput.setSelectionRange === 'function') { searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length); } }
        searchInput.addEventListener('input', function () { window.clearTimeout(filterTimer); filterTimer = window.setTimeout(function () { if (typeof filterForm.requestSubmit === 'function') { filterForm.requestSubmit(); } else { filterForm.submit(); } }, 350); });
    }
    var columnFilterToggle = document.querySelector('[data-role-column-filter-toggle]');
    var columnFilterPanel = document.querySelector('[data-role-column-filter-panel]');
    if (columnFilterToggle && columnFilterPanel) {
        var searchForm = document.querySelector('[data-role-live-filter]');
        var columnIcon = columnFilterToggle.querySelector('[data-role-filter-icon-columns]');
        var searchIcon = columnFilterToggle.querySelector('[data-role-filter-icon-search]');
        function setFilterMode(showColumns) {
            if (searchForm) { searchForm.hidden = showColumns; searchForm.classList.toggle('hidden', showColumns); }
            columnFilterPanel.hidden = !showColumns;
            columnFilterPanel.classList.toggle('hidden', !showColumns);
            columnFilterToggle.setAttribute('aria-expanded', showColumns ? 'true' : 'false');
            var nextLabel = showColumns ? columnFilterToggle.getAttribute('data-label-search') : columnFilterToggle.getAttribute('data-label-columns');
            columnFilterToggle.setAttribute('aria-label', nextLabel);
            columnFilterToggle.setAttribute('title', nextLabel);
            if (columnIcon) { columnIcon.toggleAttribute('hidden', showColumns); columnIcon.classList.toggle('hidden', showColumns); }
            if (searchIcon) { searchIcon.toggleAttribute('hidden', !showColumns); searchIcon.classList.toggle('hidden', !showColumns); }
        }
        columnFilterToggle.addEventListener('click', function () {
            var showColumns = columnFilterPanel.hidden;
            setFilterMode(showColumns);
            if (showColumns) {
                var tenantSelect = columnFilterPanel.querySelector('[name="tenant_id"]');
                if (tenantSelect) { tenantSelect.focus(); }
            } else if (searchForm) {
                var searchField = searchForm.querySelector('[name="search"]');
                if (searchField) { searchField.focus(); }
            }
        });
    }
    var dialog = document.getElementById('role-delete-dialog');
    var opener = null;
    var cancelButton = dialog ? dialog.querySelector('[data-role-delete-cancel]') : null;
    var roleLabel = dialog ? dialog.querySelector('[data-role-delete-label]') : null;
    var roleCode = dialog ? dialog.querySelector('[data-role-delete-code]') : null;
    var roleTenant = dialog ? dialog.querySelector('[data-role-delete-tenant]') : null;
    var actionMenus = Array.prototype.slice.call(document.querySelectorAll('[data-role-actions-menu]'));
    function actionToggle(menu) { return document.querySelector('[aria-controls="' + menu.id + '"][data-role-actions-toggle]'); }
    function closeActionMenus() { actionMenus.forEach(function (menu) { menu.hidden = true; var toggle = actionToggle(menu); if (toggle) { toggle.setAttribute('aria-expanded', 'false'); } }); }
    function openActionMenu(toggle, menu) {
        closeActionMenus(); menu.hidden = false; toggle.setAttribute('aria-expanded', 'true');
        var triggerRect = toggle.getBoundingClientRect(); var menuRect = menu.getBoundingClientRect(); var top = triggerRect.bottom + 4;
        if (top + menuRect.height > window.innerHeight - 8 && triggerRect.top - menuRect.height - 4 >= 8) { top = triggerRect.top - menuRect.height - 4; }
        var left = Math.min(Math.max(8, triggerRect.right - menuRect.width), window.innerWidth - menuRect.width - 8);
        menu.style.top = Math.max(8, top) + 'px'; menu.style.left = Math.max(8, left) + 'px'; var firstItem = menu.querySelector('[role="menuitem"]'); if (firstItem) { firstItem.focus(); }
    }
    function closeDialog() { if (!dialog) { return; } dialog.hidden = true; dialog.classList.remove('flex'); if (opener) { opener.focus(); } }
    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-role-actions-toggle]');
        if (toggle) { var menu = document.getElementById(toggle.getAttribute('aria-controls')); if (menu && menu.hidden) { openActionMenu(toggle, menu); } else { closeActionMenus(); toggle.focus(); } return; }
        var menuItem = event.target.closest('[data-role-actions-menu] [role="menuitem"]'); if (menuItem || !event.target.closest('[data-role-actions-menu]')) { closeActionMenus(); }
        if (!dialog) { return; }
        var openButton = event.target.closest('[data-role-delete-open]');
        if (openButton) { var menuContainer = openButton.closest('[data-role-actions-menu]'); opener = menuContainer ? actionToggle(menuContainer) : openButton; roleLabel.textContent = openButton.getAttribute('data-role-name') || openButton.getAttribute('data-role-code') || ''; roleCode.value = openButton.getAttribute('data-role-code') || ''; if (roleTenant) { roleTenant.value = openButton.getAttribute('data-role-tenant-id') || ''; } dialog.hidden = false; dialog.classList.add('flex'); cancelButton.focus(); return; }
        if (event.target.closest('[data-role-delete-cancel]')) { closeDialog(); }
    });
    document.addEventListener('keydown', function (event) {
        var currentItem = event.target.closest('[data-role-actions-menu] [role="menuitem"]');
        if (currentItem && (event.key === 'ArrowDown' || event.key === 'ArrowUp' || event.key === 'Home' || event.key === 'End')) { event.preventDefault(); var items = Array.prototype.slice.call(currentItem.closest('[role="menu"]').querySelectorAll('[role="menuitem"]')); var currentIndex = items.indexOf(currentItem); var nextIndex = event.key === 'Home' ? 0 : event.key === 'End' ? items.length - 1 : (currentIndex + (event.key === 'ArrowDown' ? 1 : items.length - 1)) % items.length; items[nextIndex].focus(); return; }
        if (event.key === 'Escape' && event.target.closest('[data-role-actions-menu]')) { var menu = event.target.closest('[data-role-actions-menu]'); var toggle = actionToggle(menu); closeActionMenus(); if (toggle) { toggle.focus(); } event.preventDefault(); return; }
        if (event.key === 'Tab' && event.target.closest('[data-role-actions-menu]')) { closeActionMenus(); }
    });
    document.addEventListener('scroll', closeActionMenus, true); window.addEventListener('resize', closeActionMenus);
    if (!dialog) { return; }
    dialog.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') { event.preventDefault(); closeDialog(); return; }
        if (event.key === 'Tab') { var focusable = Array.prototype.slice.call(dialog.querySelectorAll('button:not([disabled])')); if (!focusable.length) { return; } var first = focusable[0]; var last = focusable[focusable.length - 1]; if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); } else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); } }
    });
})();
</script>
