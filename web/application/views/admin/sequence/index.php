<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$sequence_title = $labels['sequence_template_heading'];
?>
<link rel="stylesheet" href="/assets/css/vendor/datatables.min.css?v=<?= filemtime(FCPATH . 'assets/css/vendor/datatables.min.css') ?>">
<main class="-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-2">
            <li><?= html_escape($labels['system']) ?></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" class="font-medium text-slate-800"><?= html_escape($sequence_title) ?></li>
        </ol>
    </nav>

    <header class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div class="max-w-2xl">
            <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl"><?= html_escape($sequence_title) ?></h1>
            <p class="mt-2 text-base leading-7 text-slate-600"><?= html_escape($labels['sequence_intro']) ?></p>
        </div>
        <?php if ($sequence_add_allowed): ?>
            <?php if ($write_available): ?>
                <a href="/admin/system/sequence/new" class="inline-flex h-11 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <?= html_escape($labels['sequence_add']) ?>
                </a>
            <?php else: ?>
                <span aria-disabled="true" class="inline-flex h-11 shrink-0 cursor-not-allowed items-center justify-center rounded-xl bg-slate-200 px-4 text-sm font-semibold text-slate-500"><?= html_escape($labels['sequence_add']) ?></span>
            <?php endif; ?>
        <?php endif; ?>
    </header>

    <?php if (!$backend_available): ?>
        <div role="status" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <p class="font-semibold"><?= html_escape($labels['sequence_unavailable']) ?></p>
        </div>
    <?php endif; ?>
    <?php if (isset($sequence_notice) && in_array($sequence_notice, array('sequence_created', 'sequence_saved', 'sequence_deleted', 'sequence_delete_error'), TRUE)): ?>
        <?php $sequence_notice_error = $sequence_notice === 'sequence_delete_error'; ?>
        <div role="status" class="mb-5 rounded-xl border p-4 text-sm <?= $sequence_notice_error ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900' ?>">
            <?= html_escape($labels[$sequence_notice]) ?>
        </div>
    <?php endif; ?>
    <?php if (!$write_available): ?>
        <div role="note" class="mb-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700"><?= html_escape($labels['sequence_write_disabled']) ?></div>
    <?php endif; ?>

    <section aria-label="<?= html_escape($sequence_title) ?>" class="rounded-tl-none rounded-tr-none rounded-bl-2xl rounded-br-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table id="sequence-data-table" class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <caption class="sr-only"><?= html_escape($sequence_title) ?></caption>
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" aria-label="<?= html_escape($labels['sequence_actions']) ?>" class="w-12 px-1 py-3.5 sm:px-3"></th>
                        <th scope="col" class="whitespace-nowrap px-2 py-3.5 sm:px-6"><?= html_escape($labels['sequence_sequence_code']) ?></th>
                        <th scope="col" class="min-w-[8rem] px-2 py-3.5 sm:min-w-56 sm:px-6"><?= html_escape($labels['sequence_sequence_name']) ?></th>
                        <th scope="col" class="min-w-[14rem] px-2 py-3.5 sm:min-w-64 sm:px-6"><?= html_escape($labels['sequence_description']) ?></th>
                        <th scope="col" class="min-w-[9rem] px-2 py-3.5 sm:px-6"><?= html_escape($labels['sequence_repeat_type']) ?></th>
                        <th scope="col" class="min-w-[14rem] px-2 py-3.5 sm:px-6"><?= html_escape($labels['sequence_menu']) ?></th>
                        <th scope="col" class="min-w-[10rem] px-2 py-3.5 sm:px-6"><?= html_escape($labels['sequence_format']) ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100"></tbody>
            </table>
        </div>
    </section>
</main>
<?php if ($write_available): ?>
    <div id="sequence-delete-dialog" hidden role="dialog" aria-modal="true" aria-labelledby="sequence-delete-title" aria-describedby="sequence-delete-description" tabindex="-1" class="fixed inset-0 z-50 items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl sm:p-7">
            <div aria-hidden="true" class="mb-4 flex h-11 w-11 items-center justify-center rounded-full bg-rose-50 text-lg font-bold text-rose-700">!</div>
            <h2 id="sequence-delete-title" class="text-xl font-semibold text-slate-950"><?= html_escape($labels['sequence_delete']) ?></h2>
            <p id="sequence-delete-description" class="mt-2 text-sm leading-6 text-slate-600"><?= html_escape($labels['sequence_confirm_delete']) ?></p>
            <p class="mt-3 break-all rounded-lg bg-slate-50 px-3 py-2 font-mono text-sm text-slate-800" data-sequence-delete-label></p>
            <form action="/admin/system/sequence/delete" method="post" class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>">
                <input type="hidden" name="sequence_code" value="" data-sequence-delete-code>
                <button type="button" data-sequence-delete-cancel class="min-h-11 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"><?= html_escape($labels['sequence_cancel']) ?></button>
                <button type="submit" class="min-h-11 rounded-lg bg-rose-600 px-4 text-sm font-semibold text-white hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"><?= html_escape($labels['sequence_delete']) ?></button>
            </form>
        </div>
    </div>
<?php endif; ?>
<script src="/assets/js/vendor/jquery.min.js?v=<?= filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') ?>"></script>
<script src="/assets/js/vendor/datatables.min.js?v=<?= filemtime(FCPATH . 'assets/js/vendor/datatables.min.js') ?>"></script>
<script>
(function () {
    var table = $('#sequence-data-table');
    var enabled = <?= $backend_available ? 'true' : 'false' ?>;
    if (!table.length || !enabled) return;

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[character];
        });
    }

    var canUpdate = <?= $write_available && $sequence_update_allowed ? 'true' : 'false' ?>;
    var canDelete = <?= $write_available && $sequence_delete_allowed ? 'true' : 'false' ?>;
    var labels = {
        actions: <?= json_encode($labels['sequence_actions'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        edit: <?= json_encode($labels['sequence_edit'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        delete: <?= json_encode($labels['sequence_delete'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
    };
    var language = <?= json_encode(array(
        'emptyTable' => $labels['sequence_dt_empty'],
        'info' => $labels['sequence_dt_info'],
        'infoEmpty' => $labels['sequence_dt_info_empty'],
        'infoFiltered' => $labels['sequence_dt_info_filtered'],
        'lengthMenu' => $labels['sequence_dt_length'],
        'loadingRecords' => $labels['sequence_dt_loading'],
        'processing' => $labels['sequence_dt_processing'],
        'search' => $labels['sequence_dt_search'],
        'zeroRecords' => $labels['sequence_dt_zero'],
        'paginate' => array(
            'first' => $labels['sequence_dt_first'],
            'last' => $labels['sequence_dt_last'],
            'next' => $labels['sequence_next'],
            'previous' => $labels['sequence_previous']
        )
    ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var pagingLabels = language.paginate;
    var pagingIcons = {
        first: '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m11.5 4.5-5.5 5.5 5.5 5.5M17 4.5 11.5 10l5.5 5.5"/></svg>',
        previous: '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m12.5 4.5-5.5 5.5 5.5 5.5"/></svg>',
        next: '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m7.5 4.5 5.5 5.5-5.5 5.5"/></svg>',
        last: '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m8.5 4.5 5.5 5.5-5.5 5.5M3 4.5 8.5 10 3 15.5"/></svg>'
    };
    language.paginate = pagingIcons;
    language.aria = {paginate: pagingLabels};

    table.DataTable({
        processing: true,
        serverSide: true,
        deferRender: true,
        autoWidth: false,
        searchDelay: 350,
        pageLength: 10,
        lengthMenu: [[10, 25, 50], [10, 25, 50]],
        order: [[1, 'asc']],
        language: language,
        ajax: {url: '/admin/system/sequence/data', type: 'GET', dataSrc: 'data'},
        columns: [
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function (data, type, row, meta) {
                    if (type !== 'display' || (!canUpdate && !canDelete)) return '';
                    var code = String(row.sequence_code || '');
                    var safeCode = escapeHtml(code);
                    var menuId = 'sequence-actions-menu-' + meta.row;
                    var menuItems = '';
                    if (canUpdate) {
                        menuItems += '<a href="/admin/system/sequence/change/' + encodeURIComponent(code) + '" role="menuitem" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m13.5 3.5 3 3M4 16l3.6-.7L16 6.9a2.1 2.1 0 0 0-3-3L4.7 12.3 4 16Z"/></svg>' + escapeHtml(labels.edit) + '</a>';
                    }
                    if (canDelete) {
                        menuItems += '<button type="button" data-sequence-delete-open="1" data-sequence-code="' + safeCode + '" data-sequence-name="' + escapeHtml(row.sequence_name || code) + '" aria-haspopup="dialog" aria-controls="sequence-delete-dialog" role="menuitem" class="flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-sm font-medium text-rose-700 hover:bg-rose-50 focus:bg-rose-50 focus:outline-none"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" d="M4 6h12m-10 0 .8 11h6.4L14 6M8 6V4h4v2m-3 3v5m2-5v5"/></svg>' + escapeHtml(labels.delete) + '</button>';
                    }
                    return '<div class="relative inline-flex"><button type="button" data-sequence-actions-toggle aria-label="' + escapeHtml(labels.actions) + '" aria-haspopup="menu" aria-expanded="false" aria-controls="' + menuId + '" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"><svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><circle cx="10" cy="4" r="1.6"/><circle cx="10" cy="10" r="1.6"/><circle cx="10" cy="16" r="1.6"/></svg></button><div id="' + menuId + '" data-sequence-actions-menu hidden role="menu" class="fixed z-[60] mt-1 w-40 rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-lg">' + menuItems + '</div></div>';
                }
            },
            {data: 'sequence_code', name: 'sequence_code', className: 'whitespace-nowrap px-2 py-3 font-mono text-xs font-semibold text-slate-700 sm:px-6 sm:py-4', render: function (data, type) { return type === 'display' ? escapeHtml(data) : data; }},
            {data: 'sequence_name', name: 'sequence_name', className: 'min-w-[8rem] break-words px-2 py-3 font-medium text-slate-900 sm:min-w-56 sm:px-6 sm:py-4', render: function (data, type) { return type === 'display' ? escapeHtml(data) : data; }},
            {data: 'description', name: 'description', className: 'min-w-[14rem] break-words px-2 py-3 text-slate-600 sm:min-w-64 sm:px-6 sm:py-4', render: function (data, type) { return type === 'display' ? escapeHtml(data) : data; }},
            {data: 'repeat_type', name: 'repeat_type', className: 'whitespace-nowrap px-2 py-3 text-slate-700 sm:px-6 sm:py-4', render: function (data, type) { return type === 'display' ? escapeHtml(data) : data; }},
            {data: 'menu_label', name: 'menu_codes', className: 'min-w-[14rem] break-words px-2 py-3 text-slate-700 sm:px-6 sm:py-4', render: function (data, type) { return type === 'display' ? escapeHtml(data) : data; }},
            {data: 'format', name: 'format', className: 'min-w-[10rem] break-words px-2 py-3 font-mono text-xs text-slate-700 sm:px-6 sm:py-4', render: function (data, type) { return type === 'display' ? escapeHtml(data) : data; }}
        ]
    });

    var dialog = document.getElementById('sequence-delete-dialog');
    var opener = null;
    var cancelButton = dialog ? dialog.querySelector('[data-sequence-delete-cancel]') : null;
    var sequenceLabel = dialog ? dialog.querySelector('[data-sequence-delete-label]') : null;
    var sequenceCode = dialog ? dialog.querySelector('[data-sequence-delete-code]') : null;
    function actionToggle(menu) {
        return document.querySelector('[aria-controls="' + menu.id + '"][data-sequence-actions-toggle]');
    }
    function closeActionMenus() {
        document.querySelectorAll('[data-sequence-actions-menu]').forEach(function (menu) {
            menu.hidden = true;
            var toggle = actionToggle(menu);
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
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
        if (firstItem) firstItem.focus();
    }
    function closeDialog() {
        if (!dialog) return;
        dialog.hidden = true;
        dialog.classList.remove('flex');
        if (opener) opener.focus();
    }
    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-sequence-actions-toggle]');
        if (toggle) {
            var menu = document.getElementById(toggle.getAttribute('aria-controls'));
            if (menu && menu.hidden) openActionMenu(toggle, menu);
            else { closeActionMenus(); toggle.focus(); }
            return;
        }
        var menuItem = event.target.closest('[data-sequence-actions-menu] [role="menuitem"]');
        if (menuItem || !event.target.closest('[data-sequence-actions-menu]')) closeActionMenus();
        if (!dialog) return;
        var openButton = event.target.closest('[data-sequence-delete-open]');
        if (openButton) {
            var menuContainer = openButton.closest('[data-sequence-actions-menu]');
            opener = menuContainer ? actionToggle(menuContainer) : openButton;
            sequenceLabel.textContent = openButton.getAttribute('data-sequence-name') || openButton.getAttribute('data-sequence-code') || '';
            sequenceCode.value = openButton.getAttribute('data-sequence-code') || '';
            dialog.hidden = false;
            dialog.classList.add('flex');
            cancelButton.focus();
            return;
        }
        if (event.target.closest('[data-sequence-delete-cancel]')) closeDialog();
    });
    document.addEventListener('keydown', function (event) {
        var currentItem = event.target.closest('[data-sequence-actions-menu] [role="menuitem"]');
        if (currentItem && ['ArrowDown', 'ArrowUp', 'Home', 'End'].indexOf(event.key) !== -1) {
            event.preventDefault();
            var items = Array.prototype.slice.call(currentItem.closest('[role="menu"]').querySelectorAll('[role="menuitem"]'));
            var index = items.indexOf(currentItem);
            var next = event.key === 'Home' ? 0 : event.key === 'End' ? items.length - 1
                : (index + (event.key === 'ArrowDown' ? 1 : items.length - 1)) % items.length;
            items[next].focus();
            return;
        }
        if (event.key === 'Escape' && event.target.closest('[data-sequence-actions-menu]')) {
            var menu = event.target.closest('[data-sequence-actions-menu]');
            var toggle = actionToggle(menu);
            closeActionMenus();
            if (toggle) toggle.focus();
            event.preventDefault();
            return;
        }
        if (event.key === 'Tab' && event.target.closest('[data-sequence-actions-menu]')) closeActionMenus();
    });
    document.addEventListener('scroll', closeActionMenus, true);
    window.addEventListener('resize', closeActionMenus);
    if (!dialog) return;
    dialog.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') { event.preventDefault(); closeDialog(); return; }
        if (event.key === 'Tab') {
            var focusable = Array.prototype.slice.call(dialog.querySelectorAll('button:not([disabled])'));
            if (!focusable.length) return;
            var first = focusable[0];
            var last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
}());
</script>
