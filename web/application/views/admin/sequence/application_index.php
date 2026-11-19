<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$sequence_title = $labels['sequence_heading'];
?>
<link rel="stylesheet" href="/assets/css/vendor/datatables.min.css?v=<?= filemtime(FCPATH . 'assets/css/vendor/datatables.min.css') ?>">
<main class="-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-2">
            <li><?= html_escape($labels['application']) ?></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" class="font-medium text-slate-800"><?= html_escape($sequence_title) ?></li>
        </ol>
    </nav>
    <header class="mb-8">
        <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl"><?= html_escape($sequence_title) ?></h1>
        <p class="mt-2 max-w-3xl text-base leading-7 text-slate-600"><?= html_escape($labels['sequence_application_intro']) ?></p>
    </header>

    <?php if (!$backend_available): ?>
        <div role="status" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <p class="font-semibold"><?= html_escape($labels['sequence_unavailable']) ?></p>
        </div>
    <?php endif; ?>
    <?php if (in_array($sequence_notice, array('sequence_application_saved', 'sequence_default_saved', 'sequence_default_error'), TRUE)): ?>
        <?php $notice_error = $sequence_notice === 'sequence_default_error'; ?>
        <div role="status" class="mb-5 rounded-xl border p-4 text-sm <?= $notice_error ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900' ?>"><?= html_escape($labels[$sequence_notice]) ?></div>
    <?php endif; ?>
    <?php if (!$write_available): ?>
        <div role="note" class="mb-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700"><?= html_escape($labels['sequence_write_disabled']) ?></div>
    <?php endif; ?>

    <section aria-label="<?= html_escape($sequence_title) ?>" class="border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table id="application-sequence-data-table" class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <caption class="sr-only"><?= html_escape($sequence_title) ?></caption>
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" aria-label="<?= html_escape($labels['sequence_actions']) ?>" class="w-12 px-1 py-3.5 sm:px-3"></th>
                        <th scope="col" class="whitespace-nowrap px-2 py-3.5 sm:px-6"><?= html_escape($labels['sequence_sequence_code']) ?></th>
                        <th scope="col" class="min-w-[8rem] px-2 py-3.5 sm:min-w-56 sm:px-6"><?= html_escape($labels['sequence_sequence_name']) ?></th>
                        <th scope="col" class="min-w-[9rem] px-2 py-3.5 sm:px-6"><?= html_escape($labels['sequence_repeat_type']) ?></th>
                        <th scope="col" class="min-w-[10rem] px-2 py-3.5 sm:px-6"><?= html_escape($labels['sequence_format']) ?></th>
                        <th scope="col" class="whitespace-nowrap px-2 py-3.5 sm:px-6"><?= html_escape($labels['sequence_last_value']) ?></th>
                        <th scope="col" class="whitespace-nowrap px-2 py-3.5 sm:px-6"><?= html_escape($labels['sequence_use_flag']) ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100"></tbody>
            </table>
        </div>
    </section>
</main>
<script src="/assets/js/vendor/jquery.min.js?v=<?= filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') ?>"></script>
<script src="/assets/js/vendor/datatables.min.js?v=<?= filemtime(FCPATH . 'assets/js/vendor/datatables.min.js') ?>"></script>
<script>
(function () {
    var table = $('#application-sequence-data-table');
    if (!table.length || <?= $backend_available ? 'false' : 'true' ?>) return;
    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[character];
        });
    }
    var canUpdate = <?= $write_available && $sequence_update_allowed ? 'true' : 'false' ?>;
    var canSetDefault = <?= $write_available && $sequence_set_default_allowed ? 'true' : 'false' ?>;
    var csrfName = <?= json_encode($csrf_token_name, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var csrfHash = <?= json_encode($csrf_hash, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var labels = {
        actions: <?= json_encode($labels['sequence_actions'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        edit: <?= json_encode($labels['sequence_edit'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        setDefault: <?= json_encode($labels['sequence_set_to_default'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        confirmDefault: <?= json_encode($labels['sequence_confirm_set_to_default'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        unconfigured: <?= json_encode($labels['sequence_application_not_configured_short'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        use: <?= json_encode($labels['sequence_use_flag_enabled'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        unused: <?= json_encode($labels['sequence_use_flag_disabled'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
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
    language.paginate = {
        first: '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m11.5 4.5-5.5 5.5 5.5 5.5M17 4.5 11.5 10l5.5 5.5"/></svg>',
        previous: '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m12.5 4.5-5.5 5.5 5.5 5.5"/></svg>',
        next: '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m7.5 4.5 5.5 5.5-5.5 5.5"/></svg>',
        last: '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m8.5 4.5 5.5 5.5-5.5 5.5M3 4.5 8.5 10 3 15.5"/></svg>'
    };
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
        ajax: {url: '/admin/application/sequence/data', type: 'GET', dataSrc: 'data'},
        columns: [
            {
                data: null, orderable: false, searchable: false,
                render: function (data, type, row, meta) {
                    if (type !== 'display') return '';
                    if (!canUpdate && !(canSetDefault && row.configured)) return '<span class="text-xs text-slate-400">—</span>';
                    var code = String(row.sequence_code || '');
                    var menuId = 'application-sequence-actions-' + meta.row;
                    var menuItems = '';
                    if (canUpdate) {
                        menuItems += '<a href="/admin/application/sequence/change/' + encodeURIComponent(code) + '" role="menuitem" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m13.5 3.5 3 3M4 16l3.6-.7L16 6.9a2.1 2.1 0 0 0-3-3L4.7 12.3 4 16Z"/></svg>' + escapeHtml(labels.edit) + '</a>';
                    }
                    if (canSetDefault && row.configured) {
                        menuItems += '<form action="/admin/application/sequence/set_default/' + encodeURIComponent(code) + '" method="post" data-web-confirm data-web-confirm-title="' + escapeHtml(labels.setDefault) + '" data-web-confirm-message="' + escapeHtml(labels.confirmDefault) + '" data-web-confirm-accept="' + escapeHtml(labels.setDefault) + '"><input type="hidden" name="' + escapeHtml(csrfName) + '" value="' + escapeHtml(csrfHash) + '"><button type="submit" role="menuitem" class="flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-sm font-medium text-indigo-700 hover:bg-indigo-50 focus:bg-indigo-50 focus:outline-none"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4 10a6 6 0 0 1 10.2-4.2L16 7.5M16 4v3.5h-3.5M16 10a6 6 0 0 1-10.2 4.2L4 12.5M4 16v-3.5h3.5"/></svg>' + escapeHtml(labels.setDefault) + '</button></form>';
                    }
                    return '<div class="relative inline-flex"><button type="button" data-application-sequence-actions-toggle aria-label="' + escapeHtml(labels.actions) + '" aria-haspopup="menu" aria-expanded="false" aria-controls="' + menuId + '" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"><svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><circle cx="10" cy="4" r="1.6"/><circle cx="10" cy="10" r="1.6"/><circle cx="10" cy="16" r="1.6"/></svg></button><div id="' + menuId + '" data-application-sequence-actions-menu hidden role="menu" class="fixed z-[60] mt-1 w-40 rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-lg">' + menuItems + '</div></div>';
                }
            },
            {data: 'sequence_code', name: 'sequence_code', className: 'whitespace-nowrap px-2 py-3 font-mono text-xs font-semibold text-slate-700 sm:px-6 sm:py-4', render: function (data, type) { return type === 'display' ? escapeHtml(data) : data; }},
            {data: 'sequence_name', name: 'sequence_name', className: 'min-w-[8rem] break-words px-2 py-3 font-medium text-slate-900 sm:min-w-56 sm:px-6 sm:py-4', render: function (data, type, row) {
                if (type !== 'display') return data;
                var name = escapeHtml(data);
                return row.configured ? name : name + '<span class="mt-1 block text-xs font-normal text-sky-700">' + escapeHtml(labels.unconfigured) + '</span>';
            }},
            {data: 'repeat_type', name: 'repeat_type', className: 'whitespace-nowrap px-2 py-3 text-slate-700 sm:px-6 sm:py-4', render: function (data, type) { return type === 'display' ? escapeHtml(data) : data; }},
            {data: 'format', name: 'format', className: 'min-w-[10rem] break-words px-2 py-3 font-mono text-xs text-slate-700 sm:px-6 sm:py-4', render: function (data, type) { return type === 'display' ? escapeHtml(data) : data; }},
            {data: 'last_value', name: 'last_value', className: 'whitespace-nowrap px-2 py-3 font-mono text-xs text-slate-700 sm:px-6 sm:py-4', render: function (data, type) { return type === 'display' ? escapeHtml(data == null ? '0' : data) : data; }},
            {data: 'use_flag', name: 'use_flag', className: 'whitespace-nowrap px-2 py-3 sm:px-6 sm:py-4', render: function (data, type, row) {
                if (type !== 'display') return data;
                if (!row.configured) return '<span class="text-slate-500">' + escapeHtml(labels.unused) + '</span>';
                return String(data) === '1'
                    ? '<span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800">' + escapeHtml(labels.use) + '</span>'
                    : '<span class="text-slate-500">' + escapeHtml(labels.unused) + '</span>';
            }}
        ]
    });
    function actionToggle(menu) {
        return document.querySelector('[aria-controls="' + menu.id + '"][data-application-sequence-actions-toggle]');
    }
    function closeActionMenus() {
        document.querySelectorAll('[data-application-sequence-actions-menu]').forEach(function (menu) {
            menu.hidden = true;
            var toggle = actionToggle(menu);
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
        });
    }
    function openActionMenu(toggle, menu) {
        closeActionMenus();
        menu.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        var trigger = toggle.getBoundingClientRect();
        var bounds = menu.getBoundingClientRect();
        var top = trigger.bottom + 4;
        if (top + bounds.height > window.innerHeight - 8 && trigger.top - bounds.height - 4 >= 8) {
            top = trigger.top - bounds.height - 4;
        }
        menu.style.top = Math.max(8, top) + 'px';
        menu.style.left = Math.max(8, Math.min(trigger.right - bounds.width, window.innerWidth - bounds.width - 8)) + 'px';
        var firstItem = menu.querySelector('[role="menuitem"]');
        if (firstItem) firstItem.focus();
    }
    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-application-sequence-actions-toggle]');
        if (toggle) {
            var menu = document.getElementById(toggle.getAttribute('aria-controls'));
            if (menu && menu.hidden) openActionMenu(toggle, menu);
            else { closeActionMenus(); toggle.focus(); }
            return;
        }
        if (event.target.closest('[data-application-sequence-actions-menu] [role="menuitem"]')
            || !event.target.closest('[data-application-sequence-actions-menu]')) closeActionMenus();
    });
    document.addEventListener('keydown', function (event) {
        var item = event.target.closest('[data-application-sequence-actions-menu] [role="menuitem"]');
        if (item && ['ArrowDown', 'ArrowUp', 'Home', 'End'].indexOf(event.key) !== -1) {
            event.preventDefault();
            item.focus();
            return;
        }
        if (event.key === 'Escape' && event.target.closest('[data-application-sequence-actions-menu]')) {
            var menu = event.target.closest('[data-application-sequence-actions-menu]');
            var toggle = actionToggle(menu);
            closeActionMenus();
            if (toggle) toggle.focus();
            event.preventDefault();
        } else if (event.key === 'Tab' && event.target.closest('[data-application-sequence-actions-menu]')) {
            closeActionMenus();
        }
    });
    document.addEventListener('scroll', closeActionMenus, true);
    window.addEventListener('resize', closeActionMenus);
}());
</script>
