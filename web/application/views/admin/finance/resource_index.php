<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$title = isset($labels[$finance_resource['title_key']]) ? $labels[$finance_resource['title_key']] : $finance_resource['title_key'];
$can_add = $finance_can_add && $finance_write_available;
$can_edit = $finance_can_update && $finance_write_available;
?>
<link rel="stylesheet" href="/assets/css/vendor/datatables.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/datatables.min.css') ? filemtime(FCPATH . 'assets/css/vendor/datatables.min.css') : '1' ?>">
<main class="mx-auto w-full max-w-7xl px-2 py-6 sm:px-6 sm:py-8 lg:px-8">
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
<?php $this->load->view('admin/finance/partials/filter_panel', array('finance_filter_date'=>FALSE,'finance_filter_status_options'=>array(),'finance_filter_flag_options'=>array('1'=>isset($labels['finance_filter_active'])?$labels['finance_filter_active']:'Active','0'=>isset($labels['finance_filter_inactive'])?$labels['finance_filter_inactive']:'Inactive'),'finance_filter_target'=>'#finance-resource-table')); ?>
    <?php if ($finance_resource['slug'] === 'categories'): ?>
    <section class="finance-data-exchange mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-5 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-start gap-3">
                <div class="finance-data-exchange-icon flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2h16v-2"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-base font-semibold text-slate-900">Category Data Exchange</h2>
                        <span class="finance-data-exchange-badge rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">CSV</span>
                    </div>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600"><?= html_escape(isset($labels['finance_category_import_hint']) ? $labels['finance_category_import_hint'] : 'Export categories from an existing tenant, then import the CSV into a new tenant.') ?></p>
                </div>
            </div>
            <div class="flex shrink-0 flex-col gap-2 sm:flex-row sm:items-center">
                <?php if ($finance_can_export): ?>
                <a href="<?= html_escape($current_path . '/export') ?>" class="finance-data-exchange-export inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10 3v9m0 0 3.5-3.5M10 12 6.5 8.5M4 16h12"/></svg>
                    <?= html_escape(isset($labels['finance_category_export']) ? $labels['finance_category_export'] : 'Export Categories') ?>
                </a>
                <?php endif; ?>
                <?php if ($finance_can_import): ?>
                <form method="post" action="<?= html_escape($current_path . '/import') ?>" enctype="multipart/form-data" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <input type="hidden" name="<?= html_escape($finance_csrf_name) ?>" value="<?= html_escape($finance_csrf_hash) ?>">
                    <input id="category-file" type="file" name="category_file" accept=".csv,text/csv" required class="sr-only" data-category-file-input data-category-file-default="<?= html_escape(isset($labels['finance_category_choose_file']) ? $labels['finance_category_choose_file'] : 'Choose CSV') ?>">
                    <label for="category-file" class="finance-data-exchange-file inline-flex min-h-10 cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 text-sm font-medium text-slate-600 hover:border-indigo-400 hover:text-indigo-700">
                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10 13V4m0 0L6.5 7.5M10 4l3.5 3.5M4 16h12"/></svg>
                        <span data-category-file-label><?= html_escape(isset($labels['finance_category_choose_file']) ? $labels['finance_category_choose_file'] : 'Choose CSV') ?></span>
                    </label>
                    <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700"><?= html_escape(isset($labels['finance_category_import']) ? $labels['finance_category_import'] : 'Import Categories') ?></button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($finance_resource['slug'] === 'tax_configuration'): ?>
    <section class="finance-data-exchange mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-5 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-start gap-3">
                <div class="finance-data-exchange-icon flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2h16v-2"/></svg>
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-base font-semibold text-slate-900">Tax Data Exchange</h2>
                        <span class="finance-data-exchange-badge rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">CSV</span>
                    </div>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600"><?= html_escape(isset($labels['finance_tax_import_hint']) ? $labels['finance_tax_import_hint'] : 'Export tax configuration from an existing tenant, then import the CSV into a new tenant.') ?></p>
                </div>
            </div>
            <div class="flex shrink-0 flex-col gap-2 sm:flex-row sm:items-center">
                <?php if ($finance_can_export): ?>
                <a href="<?= html_escape($current_path . '/export') ?>" class="finance-data-exchange-export inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10 3v9m0 0 3.5-3.5M10 12 6.5 8.5M4 16h12"/></svg>
                    <?= html_escape(isset($labels['finance_tax_export']) ? $labels['finance_tax_export'] : 'Export Taxes') ?>
                </a>
                <?php endif; ?>
                <?php if ($finance_can_import): ?>
                <form method="post" action="<?= html_escape($current_path . '/import') ?>" enctype="multipart/form-data" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <input type="hidden" name="<?= html_escape($finance_csrf_name) ?>" value="<?= html_escape($finance_csrf_hash) ?>">
                    <input id="tax-file" type="file" name="tax_file" accept=".csv,text/csv" required class="sr-only" data-tax-file-input data-tax-file-default="<?= html_escape(isset($labels['finance_tax_choose_file']) ? $labels['finance_tax_choose_file'] : 'Choose CSV') ?>">
                    <label for="tax-file" class="finance-data-exchange-file inline-flex min-h-10 cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 text-sm font-medium text-slate-600 hover:border-indigo-400 hover:text-indigo-700">
                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10 13V4m0 0L6.5 7.5M10 4l3.5 3.5M4 16h12"/></svg>
                        <span data-tax-file-label><?= html_escape(isset($labels['finance_tax_choose_file']) ? $labels['finance_tax_choose_file'] : 'Choose CSV') ?></span>
                    </label>
                    <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700"><?= html_escape(isset($labels['finance_tax_import']) ? $labels['finance_tax_import'] : 'Import Taxes') ?></button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($finance_status === 'tax_imported'): ?>
        <div role="status" class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-900"><?= html_escape(sprintf(isset($labels['finance_tax_import_success']) ? $labels['finance_tax_import_success'] : 'Taxes imported: %d.', (int) $finance_tax_import_count)) ?></div>
    <?php elseif ($finance_status === 'tax_import_error'): ?>
        <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"><p class="font-semibold"><?= html_escape(isset($labels['finance_tax_import_error']) ? $labels['finance_tax_import_error'] : 'Tax import failed.') ?></p><ul class="mt-2 list-inside list-disc"><?php foreach ((array) $finance_tax_import_errors as $error): ?><li><?= html_escape($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <?php if ($finance_status === 'category_imported'): ?>
        <div role="status" class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-900"><?= html_escape(sprintf(isset($labels['finance_category_import_success']) ? $labels['finance_category_import_success'] : 'Categories imported: %d.', (int) $finance_category_import_count)) ?></div>
    <?php elseif ($finance_status === 'category_import_error'): ?>
        <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"><p class="font-semibold"><?= html_escape(isset($labels['finance_category_import_error']) ? $labels['finance_category_import_error'] : 'Category import failed.') ?></p><ul class="mt-2 list-inside list-disc"><?php foreach ((array) $finance_category_import_errors as $error): ?><li><?= html_escape($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
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
        <section class="overflow-hidden border border-slate-200 bg-white shadow-sm" aria-labelledby="finance-resource-heading">
            <h2 id="finance-resource-heading" class="sr-only"><?= html_escape($title) ?></h2>
            <div class="overflow-x-auto">
                <table id="finance-resource-table" class="finance-list-table min-w-full whitespace-nowrap text-sm">
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
<script src="/assets/js/finance-list.js?v=<?= is_file(FCPATH . 'assets/js/finance-list.js') ? filemtime(FCPATH . 'assets/js/finance-list.js') : '1' ?>"></script>
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
            render: function (data, type, row, meta) {
                if (type !== 'display') return '';
                var url = <?= json_encode($current_path . '/edit/', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?> + encodeURIComponent(row[<?= json_encode($finance_resource['id']) ?>]);
                var menuId = 'resource-actions-' + meta.row;
                return '<div class="relative inline-flex"><button type="button" data-finance-menu-toggle aria-label="<?= html_escape(isset($labels['finance_actions']) ? $labels['finance_actions'] : 'Actions') ?>" aria-haspopup="menu" aria-expanded="false" aria-controls="' + menuId + '" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"><svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><circle cx="10" cy="4" r="1.6"/><circle cx="10" cy="10" r="1.6"/><circle cx="10" cy="16" r="1.6"/></svg></button><div id="' + menuId + '" data-finance-menu hidden role="menu" class="fixed z-[60] mt-1 w-40 rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-lg"><a href="' + escapeHtml(url) + '" role="menuitem" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none"><?= html_escape(isset($labels['finance_edit']) ? $labels['finance_edit'] : 'Edit') ?></a></div></div>';
            }
        });
    }
    <?php foreach ($finance_resource['columns'] as $column): ?>
    columns.push({
        data: <?= json_encode($column['name'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        name: <?= json_encode($column['name'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        render: function (value, type) {
            if (type !== 'display') return value;
            <?php if (!empty($column['value_label_keys'])): ?>
            var optionLabels = <?= json_encode(array_intersect_key($labels, array_flip(array_values($column['value_label_keys']))), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            <?php foreach ($column['value_label_keys'] as $option_value => $option_key): ?>
            if (String(value) === <?= json_encode((string) $option_value) ?> && optionLabels[<?= json_encode($option_key) ?>]) return escapeHtml(optionLabels[<?= json_encode($option_key) ?>]);
            <?php endforeach; ?>
            <?php endif; ?>
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
    var taxFile = document.querySelector('[data-tax-file-input]');
    if (taxFile) {
        taxFile.addEventListener('change', function () {
            var label = document.querySelector('[data-tax-file-label]');
            if (!label) return;
            label.textContent = this.files && this.files[0] ? this.files[0].name : (this.getAttribute('data-tax-file-default') || 'Choose CSV');
        });
    }
    var categoryFile = document.querySelector('[data-category-file-input]');
    if (categoryFile) {
        categoryFile.addEventListener('change', function () {
            var label = document.querySelector('[data-category-file-label]');
            if (!label) return;
            label.textContent = this.files && this.files[0] ? this.files[0].name : (this.getAttribute('data-category-file-default') || 'Choose CSV');
        });
    }
})();
</script>
