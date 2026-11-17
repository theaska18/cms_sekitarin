<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$title = isset($labels[$finance_resource['title_key']]) ? $labels[$finance_resource['title_key']] : $finance_resource['title_key'];
$can_add = $finance_can_add && $finance_write_available;
$can_edit = $finance_can_update && $finance_write_available;
?>
<link rel="stylesheet" href="/assets/css/vendor/datatables.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/datatables.min.css') ? filemtime(FCPATH . 'assets/css/vendor/datatables.min.css') : '1' ?>">
<main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-2">
            <li><?= html_escape(isset($labels['finance']) ? $labels['finance'] : 'Finance') ?></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" class="font-medium text-slate-800"><?= html_escape($title) ?></li>
        </ol>
    </nav>
    <header class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($title) ?></h1>
            <p class="mt-2 text-sm leading-6 text-slate-600"><?= html_escape(isset($labels['finance_setup_intro']) ? $labels['finance_setup_intro'] : 'Manage tenant-scoped Finance setup data.') ?></p>
        </div>
        <?php if ($can_add): ?>
            <a href="<?= html_escape($current_path . '/new') ?>" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><path d="M10 4a.75.75 0 0 1 .75.75v4.5h4.5a.75.75 0 0 1 0 1.5h-4.5v4.5a.75.75 0 0 1-1.5 0v-4.5h-4.5a.75.75 0 0 1 0-1.5h4.5v-4.5A.75.75 0 0 1 10 4Z"/></svg>
                <?= html_escape(isset($labels['finance_add']) ? $labels['finance_add'] : 'Add') ?>
            </a>
        <?php endif; ?>
    </header>

    <?php if (in_array($finance_status, array('created', 'saved'), TRUE)): ?>
        <div role="status" class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-900">
            <?= html_escape($finance_status === 'created'
                ? (isset($labels['finance_created']) ? $labels['finance_created'] : 'Record created.')
                : (isset($labels['finance_saved']) ? $labels['finance_saved'] : 'Changes saved.')) ?>
        </div>
    <?php elseif ($finance_status === 'save_error'): ?>
        <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-medium text-rose-900">
            <?= html_escape(isset($labels['finance_save_error']) ? $labels['finance_save_error'] : 'Could not save the record. Check that its code is unique.') ?>
        </div>
    <?php endif; ?>

    <?php if (!$finance_available): ?>
        <div role="status" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <?= html_escape(isset($labels['module_backend_unavailable']) ? $labels['module_backend_unavailable'] : 'Finance storage is unavailable.') ?>
        </div>
    <?php else: ?>
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="finance-resource-heading">
            <h2 id="finance-resource-heading" class="sr-only"><?= html_escape($title) ?></h2>
            <div class="overflow-x-auto p-3 sm:p-5">
                <table id="finance-resource-table" class="min-w-full text-sm">
                    <thead><tr>
                        <?php if ($can_edit): ?><th scope="col"><?= html_escape(isset($labels['finance_actions']) ? $labels['finance_actions'] : 'Actions') ?></th><?php endif; ?>
                        <?php foreach ($finance_resource['columns'] as $column): ?>
                            <?php
                                $column_label_key = isset($column['label_key']) ? $column['label_key'] : '';
                                $column_label = $column_label_key !== '' && isset($labels[$column_label_key]) ? $labels[$column_label_key] : $column['label'];
                            ?>
                            <th scope="col"><?= html_escape($column_label) ?></th>
                        <?php endforeach; ?>
                    </tr></thead>
                </table>
            </div>
        </section>
    <?php endif; ?>
</main>
<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') : '1' ?>"></script>
<script src="/assets/js/vendor/datatables.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/datatables.min.js') ? filemtime(FCPATH . 'assets/js/vendor/datatables.min.js') : '1' ?>"></script>
<script>
(function () {
    var table = $('#finance-resource-table');
    var canEdit = <?= $can_edit ? 'true' : 'false' ?>;
    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[character];
        });
    }
    if (!table.length || !<?= $finance_available ? 'true' : 'false' ?>) return;
    var columns = [];
    if (canEdit) {
        columns.push({
            data: null, orderable: false, searchable: false,
            render: function (data, type, row) {
                if (type !== 'display') return '';
                var url = <?= json_encode($current_path . '/edit/', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?> + encodeURIComponent(row[<?= json_encode($finance_resource['id']) ?>]);
                return '<a class="font-semibold text-indigo-700 hover:text-indigo-900" href="' + escapeHtml(url) + '"><?= html_escape(isset($labels['finance_edit']) ? $labels['finance_edit'] : 'Edit') ?></a>';
            }
        });
    }
    <?php foreach ($finance_resource['columns'] as $column): ?>
    columns.push({
        data: <?= json_encode($column['name'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        name: <?= json_encode($column['name'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        render: function (value, type) {
            if (type !== 'display') return value;
            <?php if ($column['name'] === 'active_flag' || $column['name'] === 'tax_inclusive_default'): ?>
            return escapeHtml(String(value) === '1' ? '<?= html_escape(isset($labels['finance_yes']) ? $labels['finance_yes'] : 'Yes') ?>' : '<?= html_escape(isset($labels['finance_no']) ? $labels['finance_no'] : 'No') ?>');
            <?php else: ?>
            return escapeHtml(value);
            <?php endif; ?>
        }
    });
    <?php endforeach; ?>
    table.DataTable({
        processing: true,
        serverSide: true,
        deferRender: true,
        autoWidth: false,
        pageLength: 10,
        lengthMenu: [[10, 25, 50], [10, 25, 50]],
        order: [[canEdit ? 1 : 0, 'asc']],
        ajax: {url: <?= json_encode('/admin/finance/' . $finance_resource['slug'] . '/data', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, type: 'GET'},
        columns: columns,
        language: {
            emptyTable: '<?= html_escape(isset($labels['finance_dt_empty']) ? $labels['finance_dt_empty'] : 'No records found') ?>',
            search: '<?= html_escape(isset($labels['finance_dt_search']) ? $labels['finance_dt_search'] : 'Search:') ?>',
            lengthMenu: '<?= html_escape(isset($labels['finance_dt_length']) ? $labels['finance_dt_length'] : 'Show _MENU_') ?>',
            info: '<?= html_escape(isset($labels['finance_dt_info']) ? $labels['finance_dt_info'] : 'Showing _START_ to _END_ of _TOTAL_') ?>',
            infoEmpty: '<?= html_escape(isset($labels['finance_dt_info_empty']) ? $labels['finance_dt_info_empty'] : 'No records') ?>',
            zeroRecords: '<?= html_escape(isset($labels['finance_dt_zero']) ? $labels['finance_dt_zero'] : 'No matching records') ?>',
            processing: '<?= html_escape(isset($labels['finance_dt_processing']) ? $labels['finance_dt_processing'] : 'Loading…') ?>'
        }
    });
})();
</script>
