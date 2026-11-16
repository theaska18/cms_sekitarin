<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$editing = !empty($approval_flow);
$flow = $editing && is_array($approval_flow) ? $approval_flow : array();
$flow_steps = isset($approval_flow_steps) && is_array($approval_flow_steps) ? array_values($approval_flow_steps) : array();
if (!$flow_steps) { $flow_steps = array(array('approver_type' => 'superior')); }
$title = $editing ? 'Ubah alur persetujuan' : 'Tambah alur persetujuan';
?>
<link rel="stylesheet" href="/assets/css/jquery-ui.css?v=<?= filemtime(FCPATH . 'assets/css/jquery-ui.css') ?>">
<main class="-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 lg:px-8"
      data-approval-flow-editor
      data-employee-search-url="/admin/hr/approvalFlow/employee_search"
      data-organization-search-url="/admin/hr/approvalFlow/organization_search">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-2">
            <li>HR</li><li aria-hidden="true">/</li>
            <li><a href="/admin/hr/approvalFlow" class="hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">Approval Flow</a></li>
            <li aria-hidden="true">/</li><li aria-current="page" class="font-medium text-slate-800"><?= html_escape($editing ? 'Ubah' : 'Tambah') ?></li>
        </ol>
    </nav>
    <header class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($title) ?></h1>
        <p class="mt-2 text-base leading-7 text-slate-600">Atasan pertama mengikuti unit pemohon. Setelah langkah Organisasi, atasan diambil dari unit induknya; setelah langkah lain, mengikuti unit approver sebelumnya.</p>
    </header>
    <?php if (!$approval_flow_available): ?>
        <div role="status" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <p class="font-semibold">Penyimpanan alur belum tersedia.</p><p class="mt-1">Pastikan migrasi selesai dan tenant aktif.</p>
        </div>
    <?php else: ?>
    <form method="post" id="approval-flow-form" action="/admin/hr/approvalFlow/save" class="w-full rounded-t-2xl rounded-b-none border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
        <input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>">
        <?php if ($editing): ?><input type="hidden" name="flow_id" value="<?= (int)$flow['flow_id'] ?>"><?php endif; ?>
        <div class="space-y-6">
            <div>
                <label for="flow-name" class="mb-1.5 block text-sm font-semibold text-slate-800">Nama alur</label>
                <input id="flow-name" name="flow_name" type="text" required maxlength="120" autocomplete="off" value="<?= html_escape(isset($flow['flow_name']) ? $flow['flow_name'] : '') ?>" class="min-h-12 w-full rounded-lg border border-slate-300 px-3 text-base text-slate-900 sm:w-[32rem] sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            </div>
            <div>
                <label for="flow-type" class="mb-1.5 block text-sm font-semibold text-slate-800">Kode jenis permintaan</label>
                <input id="flow-type" name="request_type" type="text" required maxlength="64" pattern="[A-Za-z0-9_.-]+" autocomplete="off" autocapitalize="none" spellcheck="false" value="<?= html_escape(isset($flow['request_type']) ? $flow['request_type'] : '') ?>" placeholder="leave_request" aria-describedby="flow-type-help" class="min-h-12 w-full rounded-lg border border-slate-300 px-3 text-base text-slate-900 sm:w-64 sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                <p id="flow-type-help" class="mt-1.5 text-sm text-slate-500">Gunakan huruf, angka, titik, garis bawah, atau tanda hubung.</p>
            </div>
            <section aria-labelledby="approval-steps-heading">
                <div class="mb-3">
                    <h2 id="approval-steps-heading" class="text-lg font-semibold text-slate-900">Daftar tahap persetujuan</h2>
                    <p class="mt-1 text-sm text-slate-500">Tahap diproses berurutan. Jika approver tidak dapat ditentukan, permintaan masuk ke antrean Error Approval untuk ditangani admin.</p>
                </div>
                <ol data-approval-step-list class="space-y-4">
                    <?php foreach ($flow_steps as $index => $step): ?>
                    <?php
                    $step_type = isset($step['approver_type']) ? (string)$step['approver_type'] : 'superior';
                    $employee_id = isset($step['approver_employee_id']) ? (string)$step['approver_employee_id'] : '';
                    $employee_label = !empty($step['employee_name']) ? $step['employee_name'].' ('.$step['employee_code'].')' : '';
                    $org_id = isset($step['org_id']) ? (string)$step['org_id'] : '';
                    $org_label = !empty($step['org_name']) ? $step['org_code'].' — '.$step['org_name'] : '';
                    ?>
                    <li data-approval-step class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h3 data-step-title class="font-semibold text-slate-800">Tahap <?= (int)$index + 1 ?></h3>
                            <button type="button" data-remove-approval-step class="rounded-lg px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Hapus tahap</button>
                        </div>
                        <fieldset>
                            <legend class="mb-2 text-sm font-semibold text-slate-700">Jenis approver</legend>
                            <div class="flex flex-wrap gap-2">
                                <label class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 text-sm">
                                    <input type="radio" data-approver-type value="superior" <?= $step_type === 'superior' ? 'checked' : '' ?>>
                                    <span>Atasan</span>
                                </label>
                                <label class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 text-sm">
                                    <input type="radio" data-approver-type value="organization" <?= $step_type === 'organization' ? 'checked' : '' ?>>
                                    <span>Organisasi</span>
                                </label>
                                <label class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 text-sm">
                                    <input type="radio" data-approver-type value="employee" <?= $step_type === 'employee' ? 'checked' : '' ?>>
                                    <span>Karyawan</span>
                                </label>
                                <?php if ($step_type === 'user'): ?>
                                <label class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-amber-300 bg-amber-50 px-3 text-sm">
                                    <input type="radio" data-approver-type value="user" checked>
                                    <span>Akun lama: <?= html_escape(isset($step['username']) ? $step['username'] : 'approver') ?></span>
                                </label>
                                <input type="hidden" data-legacy-user-id value="<?= (int)$step['approver_user_id'] ?>">
                                <?php endif; ?>
                            </div>
                        </fieldset>
                        <div data-organization-target class="mt-3" <?= $step_type === 'organization' ? '' : 'hidden' ?>>
                            <label data-organization-label class="mb-1 block text-sm font-semibold text-slate-700">Pilih organisasi</label>
                            <input type="search" data-organization-search autocomplete="off" aria-autocomplete="list" placeholder="Cari kode atau nama organisasi…" value="<?= html_escape($org_label) ?>" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-base text-slate-900 sm:max-w-xl sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                            <input type="hidden" data-organization-id value="<?= html_escape($org_id) ?>">
                            <div data-organization-status class="sr-only" role="status" aria-live="polite"></div>
                        </div>
                        <div data-employee-target class="mt-3" <?= $step_type === 'employee' ? '' : 'hidden' ?>>
                            <label data-employee-label class="mb-1 block text-sm font-semibold text-slate-700">Pilih karyawan</label>
                            <input type="search" data-employee-search autocomplete="off" aria-autocomplete="list" placeholder="Cari nama atau kode karyawan…" value="<?= html_escape($employee_label) ?>" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-base text-slate-900 sm:max-w-xl sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                            <input type="hidden" data-employee-id value="<?= html_escape($employee_id) ?>">
                            <div data-employee-status class="sr-only" role="status" aria-live="polite"></div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ol>
                <div class="mt-3 flex justify-end">
                    <button type="button" data-add-approval-step class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">
                        <span aria-hidden="true">＋</span>Tambah tahap
                    </button>
                </div>
            </section>
        </div>
        <div class="sticky bottom-0 z-20 -mx-5 -mb-5 mt-8 flex flex-col-reverse gap-3 border-t border-slate-100 bg-white/10 px-5 pb-3 pt-4 backdrop-blur-sm shadow-none sm:-mx-8 sm:-mb-8 sm:flex-row sm:justify-between sm:px-8">
            <a href="/admin/hr/approvalFlow" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Kembali</a>
            <button type="submit" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"><?= html_escape($editing ? 'Simpan perubahan' : 'Simpan alur') ?></button>
        </div>
    </form>
    <?php endif; ?>
</main>
<template id="approval-step-template">
    <li data-approval-step class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
        <div class="mb-3 flex items-center justify-between gap-3"><h3 data-step-title class="font-semibold text-slate-800">Tahap</h3><button type="button" data-remove-approval-step class="rounded-lg px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Hapus tahap</button></div>
        <fieldset><legend class="mb-2 text-sm font-semibold text-slate-700">Jenis approver</legend><div class="flex flex-wrap gap-2">
            <label class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 text-sm"><input type="radio" data-approver-type value="superior"><span>Atasan</span></label>
            <label class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 text-sm"><input type="radio" data-approver-type value="organization"><span>Organisasi</span></label>
            <label class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 text-sm"><input type="radio" data-approver-type value="employee"><span>Karyawan</span></label>
        </div></fieldset>
        <div data-organization-target class="mt-3" hidden><label data-organization-label class="mb-1 block text-sm font-semibold text-slate-700">Pilih organisasi</label><input type="search" data-organization-search autocomplete="off" aria-autocomplete="list" placeholder="Cari kode atau nama organisasi…" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-base text-slate-900 sm:max-w-xl sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"><input type="hidden" data-organization-id><div data-organization-status class="sr-only" role="status" aria-live="polite"></div></div>
        <div data-employee-target class="mt-3" hidden><label data-employee-label class="mb-1 block text-sm font-semibold text-slate-700">Pilih karyawan</label><input type="search" data-employee-search autocomplete="off" aria-autocomplete="list" placeholder="Cari nama atau kode karyawan…" class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-base text-slate-900 sm:max-w-xl sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"><input type="hidden" data-employee-id><div data-employee-status class="sr-only" role="status" aria-live="polite"></div></div>
    </li>
</template>
<script src="/assets/js/vendor/jquery.min.js?v=<?= filemtime(FCPATH . 'assets/js/vendor/jquery.min.js') ?>"></script>
<script src="/assets/js/jquery-ui.min.js?v=<?= filemtime(FCPATH . 'assets/js/jquery-ui.min.js') ?>"></script>
<script>
(function () {
    var editor = document.querySelector('[data-approval-flow-editor]');
    var list = editor && editor.querySelector('[data-approval-step-list]');
    var template = document.getElementById('approval-step-template');
    if (!editor || !list || !template || !window.jQuery || !window.jQuery.ui) return;
    var $ = window.jQuery;
    var stepNumber = 0;
    var employeeUrl = editor.getAttribute('data-employee-search-url');
    var organizationUrl = editor.getAttribute('data-organization-search-url');

    function updateStep(row, index) {
        row.querySelector('[data-step-title]').textContent = 'Tahap ' + (index + 1);
        row.querySelectorAll('[data-approver-type]').forEach(function (radio) {
            radio.name = 'steps[' + index + '][approver_type]';
            if (radio.value === 'user') {
                row.querySelector('[data-legacy-user-id]').name = 'steps[' + index + '][approver_user_id]';
            }
        });
        var employeeSearch = row.querySelector('[data-employee-search]');
        var employeeId = row.querySelector('[data-employee-id]');
        var organizationSearch = row.querySelector('[data-organization-search]');
        var organizationId = row.querySelector('[data-organization-id]');
        if (employeeSearch) { employeeSearch.name = 'steps[' + index + '][employee_label]'; employeeSearch.id = 'approval-step-' + index + '-employee'; row.querySelector('[data-employee-label]').htmlFor = employeeSearch.id; }
        if (employeeId) { employeeId.name = 'steps[' + index + '][approver_employee_id]'; }
        if (organizationSearch) { organizationSearch.name = 'steps[' + index + '][organization_label]'; organizationSearch.id = 'approval-step-' + index + '-organization'; row.querySelector('[data-organization-label]').htmlFor = organizationSearch.id; }
        if (organizationId) { organizationId.name = 'steps[' + index + '][org_id]'; }
        if (row.dataset.approverTypeBound !== 'true') {
            row.querySelectorAll('[data-approver-type]').forEach(function (radio) {
                radio.addEventListener('change', function () { updateTargets(row); });
            });
            row.dataset.approverTypeBound = 'true';
        }
        if (employeeSearch && !$(employeeSearch).data('ui-autocomplete')) {
            $(employeeSearch).autocomplete({
                minLength: 2, delay: 250,
                source: function (request, response) { $.getJSON(employeeUrl, {q: request.term}).done(response).fail(function () { response([]); }); },
                focus: function () { return false; },
                select: function (event, ui) { employeeSearch.value = ui.item.label; employeeId.value = ui.item.id; return false; }
            });
            employeeSearch.addEventListener('input', function () { employeeId.value = ''; });
        }
        if (organizationSearch && !$(organizationSearch).data('ui-autocomplete')) {
            $(organizationSearch).autocomplete({
                minLength: 2, delay: 250,
                source: function (request, response) { $.getJSON(organizationUrl, {q: request.term}).done(response).fail(function () { response([]); }); },
                focus: function () { return false; },
                select: function (event, ui) { organizationSearch.value = ui.item.label; organizationId.value = ui.item.id; return false; }
            });
            organizationSearch.addEventListener('input', function () { organizationId.value = ''; });
        }
        updateTargets(row);
    }

    function updateTargets(row) {
        var selected = row.querySelector('[data-approver-type]:checked');
        var type = selected ? selected.value : 'superior';
        var employeeTarget = row.querySelector('[data-employee-target]');
        var organizationTarget = row.querySelector('[data-organization-target]');
        if (employeeTarget) employeeTarget.hidden = type !== 'employee';
        if (organizationTarget) organizationTarget.hidden = type !== 'organization';
        if (type !== 'employee' && row.querySelector('[data-employee-id]')) {
            row.querySelector('[data-employee-id]').value = '';
            row.querySelector('[data-employee-search]').value = '';
        }
        if (type !== 'organization' && row.querySelector('[data-organization-id]')) {
            row.querySelector('[data-organization-id]').value = '';
            row.querySelector('[data-organization-search]').value = '';
        }
    }

    function renumber() {
        Array.prototype.forEach.call(list.querySelectorAll('[data-approval-step]'), function (row, index) { updateStep(row, index); });
        stepNumber = list.querySelectorAll('[data-approval-step]').length;
    }

    list.addEventListener('click', function (event) {
        var remove = event.target.closest('[data-remove-approval-step]');
        if (!remove) return;
        var rows = list.querySelectorAll('[data-approval-step]');
        if (rows.length <= 1) return;
        remove.closest('[data-approval-step]').remove();
        renumber();
    });
    editor.querySelector('[data-add-approval-step]').addEventListener('click', function () {
        var row = template.content.firstElementChild.cloneNode(true);
        var index = stepNumber++;
        row.querySelector('[data-approver-type][value="superior"]').checked = true;
        list.appendChild(row);
        updateStep(row, index);
    });
    renumber();
})();
</script>
