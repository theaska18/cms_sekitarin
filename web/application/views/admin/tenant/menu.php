<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$tenant_menu_record = is_array($tenant_menu_record) ? $tenant_menu_record : array();
$tenant_menu_errors = is_array($tenant_menu_errors) ? $tenant_menu_errors : array();
?>
<link rel="stylesheet" href="/assets/css/vendor/datatables.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/datatables.min.css') ? filemtime(FCPATH . 'assets/css/vendor/datatables.min.css') : '1' ?>">
<main class="-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-2">
            <li><?= html_escape($labels['system']) ?></li><li aria-hidden="true">/</li>
            <li><a href="/admin/system/tenant" class="hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"><?= html_escape($labels['tenant_heading']) ?></a></li>
            <li aria-hidden="true">/</li><li aria-current="page" class="font-medium text-slate-800"><?= html_escape($labels['tenant_menu_access']) ?></li>
        </ol>
    </nav>

    <header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($labels['tenant_menu_access']) ?></h1>
            <p class="mt-2 text-base leading-7 text-slate-600"><?= html_escape(isset($tenant_menu_record['tenant_name']) ? $tenant_menu_record['tenant_name'] : '') ?> <span class="text-slate-500">(<?= html_escape(isset($tenant_menu_record['tenant_code']) ? $tenant_menu_record['tenant_code'] : '') ?>)</span></p>
        </div>

    </header>

    <?php if (!$tenant_menu_backend_available): ?>
        <div role="status" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <p class="font-semibold"><?= html_escape($labels['tenant_unavailable']) ?></p>
            <p class="mt-1"><?= html_escape($labels['module_backend_unavailable']) ?></p>
        </div>
    <?php endif; ?>
    <?php if (!$tenant_menu_write_available): ?>
        <div role="note" class="mb-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700"><?= html_escape($labels['tenant_write_disabled']) ?></div>
    <?php endif; ?>
    <?php if ($tenant_menu_status_key === 'saved'): ?>
        <div role="status" class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900"><?= html_escape($labels['tenant_saved']) ?></div>
    <?php elseif ($tenant_menu_status_key === 'save_error' || $tenant_menu_errors): ?>
        <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900"><?= html_escape($labels['tenant_save_error']) ?></div>
    <?php endif; ?>

    <?php if ($tenant_menu_backend_available): ?>
    <form action="<?= html_escape($current_path) ?>" method="post" class="w-full rounded-t-2xl rounded-b-none border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        <input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>">
        <?php $this->load->view('admin/menu_access/table', array(
            'labels' => $labels,
            'menu_access_rows' => $menu_access_rows,
            'menu_access_table_id' => $menu_access_table_id,
            'menu_access_datatable_url' => $menu_access_datatable_url,
            'menu_access_toggle_url' => $menu_access_toggle_url,
            'menu_access_write_available' => $menu_access_write_available,
        )); ?>
        <div data-tenant-menu-sticky-actions class="sticky bottom-0 z-20 -mx-4 -mb-4 mt-5 flex items-center justify-between gap-3 border-t border-slate-200 bg-white/10 p-4 shadow-none backdrop-blur-sm sm:-mx-6 sm:-mb-6 sm:px-6 sm:pb-6">
            <a href="/admin/system/tenant" class="admin-back-link inline-flex min-h-11 items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12.5 4.5 7 10l5.5 5.5M7.5 10h10"/></svg>
                <?= html_escape($labels['tenant_back']) ?>
            </a>
            <div class="flex items-center gap-3">
                <span data-menu-access-status role="status" aria-live="polite" class="text-sm font-medium text-slate-600"></span>
                <noscript>
                    <button type="submit" <?= !$tenant_menu_write_available ? 'disabled' : '' ?> class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4 3.5h10l2.5 2.5v10.5H3.5v-13zM6 3.5v5h8v-5M6 16.5v-5h8v5"/></svg>
                        <?= html_escape($labels['tenant_save']) ?>
                    </button>
                </noscript>
            </div>
        </div>
    </form>
    <?php else: ?>
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-12 text-center text-sm text-slate-600"><?= html_escape($labels['tenant_unavailable']) ?></div>
    <?php endif; ?>
</main>
<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') : '1' ?>"></script>
<script src="/assets/js/vendor/datatables.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/datatables.min.js') ? filemtime(FCPATH . 'assets/js/vendor/datatables.min.js') : '1' ?>"></script>
<script src="/assets/js/admin-datatables.js?v=<?= is_file(FCPATH . 'assets/js/admin-datatables.js') ? filemtime(FCPATH . 'assets/js/admin-datatables.js') : '1' ?>"></script>
<script src="/assets/js/admin-menu-access.js?v=<?= is_file(FCPATH . 'assets/js/admin-menu-access.js') ? filemtime(FCPATH . 'assets/js/admin-menu-access.js') : '1' ?>"></script>
