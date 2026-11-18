<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$flow = isset($approval_flow) && is_array($approval_flow) ? $approval_flow : array();
$flow_steps = isset($approval_flow_steps) && is_array($approval_flow_steps) ? array_values($approval_flow_steps) : array();
$flow_code = isset($flow['flow_code']) ? (string)$flow['flow_code'] : '';
$flow_name = isset($flow['flow_name']) ? (string)$flow['flow_name'] : '';
$flow_menus = isset($flow['menu_codes']) && is_array($flow['menu_codes']) ? $flow['menu_codes'] : array();
$flow_source = isset($flow['step_source']) && $flow['step_source'] === 'tenant' ? 'Tenant' : 'Sistem';
$flow_disabled = empty($approval_flow_available);
$flow_endpoint = isset($approval_flow_employee_search_url) ? $approval_flow_employee_search_url : '/admin/hr/approvalFlow/employee_search';
$flow_org_endpoint = isset($approval_flow_organization_search_url) ? $approval_flow_organization_search_url : '/admin/hr/approvalFlow/organization_search';
?>
<main class="-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 lg:px-8"
      data-approval-flow-editor
      data-step-label="Tahap"
      data-step-editor-disabled="<?= $flow_disabled ? '1' : '0' ?>"
      data-step-editor-allow-empty="1"
      data-employee-search-url="<?= html_escape($flow_endpoint) ?>"
      data-organization-search-url="<?= html_escape($flow_org_endpoint) ?>">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-2">
            <li>HR</li><li aria-hidden="true">/</li>
            <li><a href="/admin/hr/approvalFlow" class="hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">Alur Persetujuan</a></li>
            <li aria-hidden="true">/</li><li aria-current="page" class="font-medium text-slate-800">Ubah tahapan</li>
        </ol>
    </nav>
    <header class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight text-slate-950">Ubah tahapan alur persetujuan</h1>
        <p class="mt-2 text-base leading-7 text-slate-600">Perubahan tahapan disimpan sebagai pengaturan tenant. Template sistem tidak diubah.</p>
    </header>
    <?php if (!$approval_flow_available): ?>
        <div role="status" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <p class="font-semibold">Penyimpanan alur belum tersedia.</p><p class="mt-1">Pastikan migrasi selesai dan tenant aktif.</p>
        </div>
    <?php else: ?>
    <form method="post" action="/admin/hr/approvalFlow/save" class="w-full rounded-t-2xl rounded-b-none border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
        <input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>">
        <input type="hidden" name="flow_code" value="<?= html_escape($flow_code) ?>">
        <div class="space-y-6">
            <div class="approval-flow-info-panel rounded-xl border border-slate-200 p-4">
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Kode alur</dt>
                        <dd class="mt-1 break-all font-mono text-sm font-semibold text-slate-800"><?= html_escape($flow_code) ?></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Nama alur</dt>
                        <dd class="mt-1 break-words text-sm font-semibold text-slate-800"><?= html_escape($flow_name) ?></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Menu</dt>
                        <dd class="mt-1 break-words font-mono text-xs text-slate-700"><?= html_escape(implode(', ', $flow_menus)) ?></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Sumber tahap saat ini</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-800"><?= html_escape($flow_source) ?></dd>
                    </div>
                </dl>
            </div>

            <?php $this->load->view('admin/approval_flow/_step_editor', array(
                'step_editor_steps' => $flow_steps,
                'step_editor_disabled' => $flow_disabled,
                'step_editor_allow_empty' => TRUE,
                'step_editor_labels' => array(
                    'heading' => 'Daftar tahap persetujuan',
                    'help' => 'Tahap diproses berurutan. Alur tanpa tahapan akan otomatis disetujui. Jika approver tidak dapat ditentukan, permintaan masuk ke antrean Error Approval untuk ditangani admin.',
                    'step_title' => 'Tahap',
                    'approver_type' => 'Jenis approver',
                    'superior' => 'Atasan',
                    'organization' => 'Organisasi',
                    'employee' => 'Karyawan',
                    'legacy_user' => 'Akun lama',
                    'remove_step' => 'Hapus tahap',
                    'add_step' => 'Tambah tahap',
                    'choose_organization' => 'Pilih organisasi',
                    'search_organization' => 'Cari kode atau nama organisasi…',
                    'choose_employee' => 'Pilih karyawan',
                    'search_employee' => 'Cari nama atau kode karyawan…',
                ),
            )); ?>
        </div>
        <div class="sticky bottom-0 z-20 -mx-5 -mb-5 mt-8 flex flex-col-reverse gap-3 border-t border-slate-100 bg-white/10 px-5 pb-3 pt-4 backdrop-blur-sm shadow-none sm:-mx-8 sm:-mb-8 sm:flex-row sm:justify-between sm:px-8">
            <a href="/admin/hr/approvalFlow" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Kembali</a>
            <button type="submit" <?= $flow_disabled ? 'disabled aria-disabled="true"' : '' ?> class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-300">Simpan tahapan</button>
        </div>
    </form>
    <?php endif; ?>
</main>
<link rel="stylesheet" href="/assets/css/vendor/jquery-ui.min.css?v=<?= is_file(FCPATH . 'assets/css/vendor/jquery-ui.min.css') ? filemtime(FCPATH . 'assets/css/vendor/jquery-ui.min.css') : '1' ?>">
<script src="/assets/js/vendor/jquery.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') : '1' ?>"></script>
<script src="/assets/js/vendor/jquery-ui.min.js?v=<?= is_file(FCPATH . 'assets/js/vendor/jquery-ui.min.js') ? filemtime(FCPATH . 'assets/js/vendor/jquery-ui.min.js') : '1' ?>"></script>
<script src="/assets/js/approval-step-editor.js?v=<?= is_file(FCPATH . 'assets/js/approval-step-editor.js') ? filemtime(FCPATH . 'assets/js/approval-step-editor.js') : '1' ?>"></script>
