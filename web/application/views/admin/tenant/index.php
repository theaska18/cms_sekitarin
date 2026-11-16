<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$tenant_title = $labels['tenant_heading'];
$status_messages = array('created', 'saved', 'deleted', 'not_found', 'save_error', 'delete_error', 'invalid');
$status_key = in_array($status_key, $status_messages, TRUE) ? $status_key : '';
?>
<link rel="stylesheet" href="/assets/css/vendor/datatables.min.css?v=<?= filemtime(FCPATH . 'assets/css/vendor/datatables.min.css') ?>">
<main class="-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-2">
            <li><?= html_escape($labels['system']) ?></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" class="font-medium text-slate-800"><?= html_escape($tenant_title) ?></li>
        </ol>
    </nav>

    <header class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div class="max-w-2xl">
            <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl"><?= html_escape($tenant_title) ?></h1>
            <p class="mt-2 text-base leading-7 text-slate-600"><?= html_escape($labels['tenant_intro']) ?></p>
        </div>
        <?php if ($tenant_add_authorized && $write_available): ?>
            <a href="/admin/system/tenant/new" class="inline-flex h-11 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-indigo-600 px-4 py-0 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><path d="M10 4a.75.75 0 0 1 .75.75v4.5h4.5a.75.75 0 0 1 0 1.5h-4.5v4.5a.75.75 0 0 1-1.5 0v-4.5h-4.5a.75.75 0 0 1 0-1.5h4.5v-4.5A.75.75 0 0 1 10 4Z"/></svg>
                <?= html_escape($labels['tenant_add']) ?>
            </a>
        <?php elseif ($tenant_add_authorized): ?>
            <span class="inline-flex h-11 shrink-0 cursor-not-allowed items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-slate-200 px-4 py-0 text-sm font-semibold text-slate-500" aria-disabled="true">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><path d="M10 4a.75.75 0 0 1 .75.75v4.5h4.5a.75.75 0 0 1 0 1.5h-4.5v4.5a.75.75 0 0 1-1.5 0v-4.5h-4.5a.75.75 0 0 1 0-1.5h4.5v-4.5A.75.75 0 0 1 10 4Z"/></svg>
                <?= html_escape($labels['tenant_add']) ?>
            </span>
        <?php endif; ?>
    </header>

    <?php if ($status_key !== ''): ?>
        <?php $is_error = in_array($status_key, array('not_found', 'save_error', 'delete_error', 'invalid'), TRUE); ?>
        <div role="status" class="mb-5 flex items-start gap-3 rounded-xl border p-4 text-sm <?= $is_error ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900' ?>">
            <span aria-hidden="true" class="mt-0.5"><?= $is_error ? '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><circle cx="10" cy="10" r="7.5"/><path stroke-linecap="round" d="M10 6v4.5m0 3h.01"/></svg>' : '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><circle cx="10" cy="10" r="7.5"/><path stroke-linecap="round" stroke-linejoin="round" d="m6.5 10 2.3 2.3 4.7-4.7"/></svg>' ?></span>
            <p><?= html_escape($labels['tenant_' . $status_key]) ?></p>
        </div>
    <?php endif; ?>

    <?php if (!$backend_available): ?>
        <div role="status" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <p class="font-semibold"><?= html_escape($labels['tenant_unavailable']) ?></p>
            <p class="mt-1"><?= html_escape($labels['module_backend_unavailable']) ?></p>
        </div>
    <?php endif; ?>
    <?php if (!$write_available): ?>
        <div role="note" class="mb-5 flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
            <span aria-hidden="true" class="mt-0.5 text-indigo-600"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"><circle cx="10" cy="10" r="7.5"/><path stroke-linecap="round" d="M10 9v4m0-7h.01"/></svg></span>
            <p><?= html_escape($labels['tenant_write_disabled']) ?></p>
        </div>
    <?php endif; ?>

    <section aria-labelledby="tenant-list-heading" class="rounded-tl-none rounded-tr-none rounded-bl-2xl rounded-br-2xl border border-slate-200 bg-white shadow-sm">
        <h2 id="tenant-list-heading" class="sr-only"><?= html_escape($tenant_title) ?></h2>
        <div class="overflow-x-auto rounded-b-2xl">
            <table id="tenant-data-table" class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" aria-label="<?= html_escape($labels['tenant_actions']) ?>" class="w-12 px-1 py-3.5 sm:px-3"></th>
                        <th scope="col" class="whitespace-nowrap px-2 py-3.5 sm:px-6"><?= html_escape($labels['tenant_code_label']) ?></th>
                        <th scope="col" class="min-w-[8rem] px-2 py-3.5 sm:min-w-56 sm:px-6"><?= html_escape($labels['tenant_name_label']) ?></th>
                        <th scope="col" class="min-w-[10rem] px-2 py-3.5 sm:px-6"><?= html_escape($labels['tenant_email_label']) ?></th>
                        <th scope="col" class="whitespace-nowrap px-2 py-3.5 sm:px-6"><?= html_escape($labels['tenant_phone_label']) ?></th>
                        <th scope="col" class="whitespace-nowrap px-2 py-3.5 sm:px-6"><?= html_escape($labels['tenant_due_on_label']) ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if ($backend_available && count($tenant_records) > 0): ?>
                        <?php $action_menu_index = 0; foreach ($tenant_records as $tenant): ?>
                            <?php
                            $tenant_code = (string) $tenant['tenant_code'];
                            $action_menu_id = 'tenant-actions-menu-initial-' . (int) $action_menu_index++;
                            ?>
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="whitespace-nowrap px-1 py-3 text-left sm:px-3 sm:py-4">
                                    <?php if ($write_available && $tenant_has_actions): ?>
                                        <div class="relative inline-flex">
                                            <button type="button" data-tenant-actions-toggle aria-label="<?= html_escape($labels['tenant_actions']) ?>" aria-haspopup="menu" aria-expanded="false" aria-controls="<?= html_escape($action_menu_id) ?>" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                                <svg data-tenant-actions-icon viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><circle cx="10" cy="4" r="1.6"/><circle cx="10" cy="10" r="1.6"/><circle cx="10" cy="16" r="1.6"/></svg>
                                            </button>
                                            <div id="<?= html_escape($action_menu_id) ?>" data-tenant-actions-menu hidden role="menu" class="fixed z-[60] mt-1 w-40 rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-lg">
                                                <?php if ($tenant_update_authorized): ?>
                                                    <a href="/admin/system/tenant/change/<?= html_escape(rawurlencode($tenant_code)) ?>" data-tenant-edit-action role="menuitem" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none">
                                                        <svg data-tenant-edit-icon viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m12.8 3.2 4 4M3.5 16.5l3.6-.7L16.3 6.6a1.4 1.4 0 0 0-2-2L5.1 13.8l-1.6 2.7Z"/></svg>
                                                        <?= html_escape($labels['tenant_edit']) ?>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($tenant_menu_access_authorized): ?>
                                                    <a href="/admin/system/tenant/menu/<?= html_escape(rawurlencode($tenant_code)) ?>" data-tenant-menu-access-action role="menuitem" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none">
                                                        <svg data-tenant-menu-access-icon viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><rect x="3" y="3" width="14" height="14" rx="2"/><path stroke-linecap="round" d="M7 3v14M10 7h4m-4 3h4m-4 3h4"/></svg>
                                                        <?= html_escape($labels['tenant_menu_access']) ?>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($tenant_delete_authorized): ?>
                                                    <button type="button" data-tenant-delete-open="1" data-tenant-code="<?= html_escape($tenant_code) ?>" data-tenant-name="<?= html_escape($tenant['tenant_name']) ?>" aria-haspopup="dialog" aria-controls="tenant-delete-dialog" role="menuitem" class="flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-sm font-medium text-rose-700 hover:bg-rose-50 focus:bg-rose-50 focus:outline-none">
                                                        <svg data-tenant-delete-icon viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" d="M4 5.5h12M8 3.5h4m-6.5 2 1 11h7l1-11M8.5 8v5.5m3-5.5v5.5"/></svg>
                                                        <?= html_escape($labels['tenant_delete']) ?>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <th scope="row" class="whitespace-nowrap px-2 py-3 font-mono text-xs font-semibold text-slate-700 sm:px-6 sm:py-4"><?= html_escape($tenant_code) ?></th>
                                <td class="min-w-[8rem] break-words px-2 py-3 font-medium text-slate-900 sm:min-w-56 sm:px-6 sm:py-4"><?= html_escape($tenant['tenant_name']) ?></td>
                                <td class="break-words px-2 py-3 text-slate-700 sm:px-6 sm:py-4"><?= html_escape($tenant['email_address']) ?></td>
                                <td class="whitespace-nowrap px-2 py-3 text-slate-700 sm:px-6 sm:py-4"><?= html_escape($tenant['phone_number']) ?></td>
                                <td class="whitespace-nowrap px-2 py-3 text-slate-700 sm:px-6 sm:py-4"><?= html_escape($tenant['due_on']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php elseif (!$backend_available): ?>
                        <tr>
                            <td class="px-4 py-6 text-center text-sm text-slate-500" colspan="6"><?= html_escape($labels['tenant_unavailable']) ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <?php if ($write_available && $tenant_delete_authorized): ?>
        <div id="tenant-delete-dialog" hidden role="dialog" aria-modal="true" aria-labelledby="tenant-delete-title" aria-describedby="tenant-delete-description" tabindex="-1" class="fixed inset-0 z-50 items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl sm:p-7">
                <div aria-hidden="true" class="mb-4 flex h-11 w-11 items-center justify-center rounded-full bg-rose-50 text-lg font-bold text-rose-700">!</div>
                <h2 id="tenant-delete-title" class="text-xl font-semibold text-slate-950"><?= html_escape($labels['tenant_confirm_title']) ?></h2>
                <p id="tenant-delete-description" class="mt-2 text-sm leading-6 text-slate-600"><?= html_escape($labels['tenant_confirm_body']) ?></p>
                <p class="mt-3 break-all rounded-lg bg-slate-50 px-3 py-2 font-mono text-sm text-slate-800" data-tenant-delete-label></p>
                <form action="/admin/system/tenant/delete" method="post" class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>">
                    <input type="hidden" name="tenant_code" value="" data-tenant-delete-code>
                    <button type="button" data-tenant-delete-cancel class="min-h-11 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"><?= html_escape($labels['tenant_cancel']) ?></button>
                    <button type="submit" data-tenant-delete-confirm class="min-h-11 rounded-lg bg-rose-600 px-4 text-sm font-semibold text-white hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"><?= html_escape($labels['tenant_confirm_delete']) ?></button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</main>
<script src="/assets/js/vendor/jquery.min.js?v=<?= filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') ?>"></script>
<script src="/assets/js/vendor/datatables.min.js?v=<?= filemtime(FCPATH . 'assets/js/vendor/datatables.min.js') ?>"></script>
<script>
(function () {
    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[character];
        });
    }

    var tenantTable = $('#tenant-data-table');
    var tableLanguage = <?= json_encode(array(
        'emptyTable' => $labels['tenant_dt_empty'],
        'info' => $labels['tenant_dt_info'],
        'infoEmpty' => $labels['tenant_dt_info_empty'],
        'infoFiltered' => $labels['tenant_dt_info_filtered'],
        'lengthMenu' => $labels['tenant_dt_length'],
        'loadingRecords' => $labels['tenant_dt_loading'],
        'processing' => $labels['tenant_dt_processing'],
        'search' => $labels['tenant_dt_search'],
        'zeroRecords' => $labels['tenant_dt_zero'],
        'paginate' => array(
            'first' => $labels['tenant_dt_first'],
            'last' => $labels['tenant_dt_last'],
            'next' => $labels['tenant_next'],
            'previous' => $labels['tenant_previous'],
        ),
    ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var pagingLabels = tableLanguage.paginate;
    var pagingIcons = {
        first: '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m11.5 4.5-5.5 5.5 5.5 5.5M17 4.5 11.5 10l5.5 5.5"/></svg>',
        previous: '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m12.5 4.5-5.5 5.5 5.5 5.5"/></svg>',
        next: '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m7.5 4.5 5.5 5.5-5.5 5.5"/></svg>',
        last: '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m8.5 4.5 5.5 5.5-5.5 5.5M3 4.5 8.5 10 3 15.5"/></svg>'
    };
    tableLanguage.paginate = pagingIcons;
    tableLanguage.aria = {paginate: pagingLabels};
    var canUpdate = <?= $write_available && $tenant_update_authorized ? 'true' : 'false' ?>;
    var canMenu = <?= $write_available && $tenant_menu_access_authorized ? 'true' : 'false' ?>;
    var canDelete = <?= $write_available && $tenant_delete_authorized ? 'true' : 'false' ?>;
    var actionLabels = {
        actions: <?= json_encode($labels['tenant_actions'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        edit: <?= json_encode($labels['tenant_edit'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        menuAccess: <?= json_encode($labels['tenant_menu_access'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        delete: <?= json_encode($labels['tenant_delete'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
    };

    if (tenantTable.length && <?= $backend_available ? 'true' : 'false' ?>) {
        tenantTable.DataTable({
            processing: true,
            serverSide: true,
            deferRender: true,
            autoWidth: false,
            pageLength: <?= (int) $per_page ?>,
            lengthMenu: [[10, 25, 50], [10, 25, 50]],
            displayStart: <?= max(0, ($current_page - 1) * $per_page) ?>,
            search: {search: <?= json_encode($filters['search'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>},
            order: [[<?= $sort === 'tenant_name' ? 2 : 1 ?>, <?= json_encode(strtolower($direction)) ?>]],
            language: tableLanguage,
            ajax: {url: '/admin/system/tenant/data', type: 'GET', dataSrc: 'data'},
            columns: [
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row, meta) {
                        if (type !== 'display') return '';
                        if (!canUpdate && !canMenu && !canDelete) return '<span class="text-xs text-slate-400">—</span>';
                        var code = String(row.tenant_code || '');
                        var codeAttr = escapeHtml(code);
                        var nameAttr = escapeHtml(row.tenant_name || code);
                        var menuId = 'tenant-actions-menu-' + meta.row;
                        var menuItems = '';
                        if (canUpdate) {
                            menuItems += '<a href="/admin/system/tenant/change/' + encodeURIComponent(code) + '" data-tenant-edit-action role="menuitem" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none"><svg data-tenant-edit-icon viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m12.8 3.2 4 4M3.5 16.5l3.6-.7L16.3 6.6a1.4 1.4 0 0 0-2-2L5.1 13.8l-1.6 2.7Z"/></svg>' + escapeHtml(actionLabels.edit) + '</a>';
                        }
                        if (canMenu) {
                            menuItems += '<a href="/admin/system/tenant/menu/' + encodeURIComponent(code) + '" data-tenant-menu-access-action role="menuitem" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none"><svg data-tenant-menu-access-icon viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><rect x="3" y="3" width="14" height="14" rx="2"/><path stroke-linecap="round" d="M7 3v14M10 7h4m-4 3h4m-4 3h4"/></svg>' + escapeHtml(actionLabels.menuAccess) + '</a>';
                        }
                        if (canDelete) {
                            menuItems += '<button type="button" data-tenant-delete-open="1" data-tenant-code="' + codeAttr + '" data-tenant-name="' + nameAttr + '" aria-haspopup="dialog" aria-controls="tenant-delete-dialog" role="menuitem" class="flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-sm font-medium text-rose-700 hover:bg-rose-50 focus:bg-rose-50 focus:outline-none"><svg data-tenant-delete-icon viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" d="M4 5.5h12M8 3.5h4m-6.5 2 1 11h7l1-11M8.5 8v5.5m3-5.5v5.5"/></svg>' + escapeHtml(actionLabels.delete) + '</button>';
                        }
                        return '<div class="relative inline-flex"><button type="button" data-tenant-actions-toggle aria-label="' + escapeHtml(actionLabels.actions) + '" aria-haspopup="menu" aria-expanded="false" aria-controls="' + menuId + '" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"><svg data-tenant-actions-icon viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><circle cx="10" cy="4" r="1.6"/><circle cx="10" cy="10" r="1.6"/><circle cx="10" cy="16" r="1.6"/></svg></button><div id="' + menuId + '" data-tenant-actions-menu hidden role="menu" class="fixed z-[60] mt-1 w-40 rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-lg">' + menuItems + '</div></div>';
                    }
                },
                {
                    data: 'tenant_code',
                    name: 'tenant_code',
                    className: 'whitespace-nowrap px-2 py-3 font-mono text-xs font-semibold text-slate-700 sm:px-6 sm:py-4',
                    render: function (data, type) { return type === 'display' ? escapeHtml(data) : data; }
                },
                {
                    data: 'tenant_name',
                    name: 'tenant_name',
                    className: 'min-w-[8rem] break-words px-2 py-3 font-medium text-slate-900 sm:min-w-56 sm:px-6 sm:py-4',
                    render: function (data, type) { return type === 'display' ? escapeHtml(data) : data; }
                },
                {
                    data: 'email_address',
                    orderable: false,
                    className: 'break-words px-2 py-3 text-slate-700 sm:px-6 sm:py-4',
                    render: function (data, type) { return type === 'display' ? escapeHtml(data) : data; }
                },
                {
                    data: 'phone_number',
                    orderable: false,
                    className: 'whitespace-nowrap px-2 py-3 text-slate-700 sm:px-6 sm:py-4',
                    render: function (data, type) { return type === 'display' ? escapeHtml(data) : data; }
                },
                {
                    data: 'due_on',
                    orderable: false,
                    className: 'whitespace-nowrap px-2 py-3 text-slate-700 sm:px-6 sm:py-4',
                    render: function (data, type) { return type === 'display' ? escapeHtml(data) : data; }
                }
            ]
        });
    }

    var dialog = document.getElementById('tenant-delete-dialog');
    var opener = null;
    var cancelButton = dialog ? dialog.querySelector('[data-tenant-delete-cancel]') : null;
    var tenantLabel = dialog ? dialog.querySelector('[data-tenant-delete-label]') : null;
    var tenantCode = dialog ? dialog.querySelector('[data-tenant-delete-code]') : null;

    function actionToggle(menu) {
        return document.querySelector('[aria-controls="' + menu.id + '"][data-tenant-actions-toggle]');
    }

    function closeActionMenus() {
        Array.prototype.forEach.call(document.querySelectorAll('[data-tenant-actions-menu]'), function (menu) {
            menu.hidden = true;
            var toggle = actionToggle(menu);
            if (toggle) { toggle.setAttribute('aria-expanded', 'false'); }
        });
    }

    function openActionMenu(toggle, menu) {
        closeActionMenus();
        menu.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        var triggerRect = toggle.getBoundingClientRect();
        var menuRect = menu.getBoundingClientRect();
        var top = triggerRect.bottom + 4;
        if (top + menuRect.height > window.innerHeight - 8 && triggerRect.top - menuRect.height - 4 >= 8) {
            top = triggerRect.top - menuRect.height - 4;
        }
        var left = Math.min(Math.max(8, triggerRect.right - menuRect.width), window.innerWidth - menuRect.width - 8);
        menu.style.top = Math.max(8, top) + 'px';
        menu.style.left = Math.max(8, left) + 'px';
        var firstItem = menu.querySelector('[role="menuitem"]');
        if (firstItem) { firstItem.focus(); }
    }

    function closeDialog() {
        if (!dialog) { return; }
        dialog.hidden = true;
        dialog.classList.remove('flex');
        if (opener) { opener.focus(); }
    }

    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-tenant-actions-toggle]');
        if (toggle) {
            var menu = document.getElementById(toggle.getAttribute('aria-controls'));
            if (menu && menu.hidden) { openActionMenu(toggle, menu); }
            else {
                closeActionMenus();
                toggle.focus();
            }
            return;
        }

        var menuItem = event.target.closest('[data-tenant-actions-menu] [role="menuitem"]');
        if (menuItem || !event.target.closest('[data-tenant-actions-menu]')) { closeActionMenus(); }
        if (!dialog) { return; }

        var openButton = event.target.closest('[data-tenant-delete-open]');
        if (openButton) {
            var menuContainer = openButton.closest('[data-tenant-actions-menu]');
            opener = menuContainer ? actionToggle(menuContainer) : openButton;
            tenantLabel.textContent = openButton.getAttribute('data-tenant-name') || openButton.getAttribute('data-tenant-code') || '';
            tenantCode.value = openButton.getAttribute('data-tenant-code') || '';
            dialog.hidden = false;
            dialog.classList.add('flex');
            cancelButton.focus();
            return;
        }
        if (event.target.closest('[data-tenant-delete-cancel]')) { closeDialog(); }
    });

    document.addEventListener('keydown', function (event) {
        var currentItem = event.target.closest('[data-tenant-actions-menu] [role="menuitem"]');
        if (currentItem && (event.key === 'ArrowDown' || event.key === 'ArrowUp' || event.key === 'Home' || event.key === 'End')) {
            event.preventDefault();
            var items = Array.prototype.slice.call(currentItem.closest('[role="menu"]').querySelectorAll('[role="menuitem"]'));
            var currentIndex = items.indexOf(currentItem);
            var nextIndex = event.key === 'Home' ? 0 : event.key === 'End' ? items.length - 1 : (currentIndex + (event.key === 'ArrowDown' ? 1 : items.length - 1)) % items.length;
            items[nextIndex].focus();
            return;
        }
        if (event.key === 'Escape' && event.target.closest('[data-tenant-actions-menu]')) {
            var menu = event.target.closest('[data-tenant-actions-menu]');
            var toggle = actionToggle(menu);
            closeActionMenus();
            if (toggle) { toggle.focus(); }
            event.preventDefault();
            return;
        }
        if (event.key === 'Tab' && event.target.closest('[data-tenant-actions-menu]')) { closeActionMenus(); }
    });

    document.addEventListener('scroll', closeActionMenus, true);
    window.addEventListener('resize', closeActionMenus);

    if (!dialog) { return; }
    dialog.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') { event.preventDefault(); closeDialog(); return; }
        if (event.key === 'Tab') {
            var focusable = Array.prototype.slice.call(dialog.querySelectorAll('button:not([disabled])'));
            if (!focusable.length) { return; }
            var first = focusable[0];
            var last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
})();
</script>
