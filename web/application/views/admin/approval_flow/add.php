<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$editing = !empty($approval_flow);
$flow = $editing && is_array($approval_flow) ? $approval_flow : array();
$selected_approvers = isset($flow['approver_user_ids']) && is_array($flow['approver_user_ids']) ? array_map('intval', $flow['approver_user_ids']) : array();
$title = $editing ? 'Ubah alur persetujuan' : 'Tambah alur persetujuan';
?>
<main class="-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-2">
            <li>HR</li><li aria-hidden="true">/</li>
            <li><a href="/admin/hr/approvalFlow" class="hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">Approval Flow</a></li>
            <li aria-hidden="true">/</li><li aria-current="page" class="font-medium text-slate-800"><?= html_escape($editing ? 'Ubah' : 'Tambah') ?></li>
        </ol>
    </nav>
    <header class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($title) ?></h1>
        <p class="mt-2 text-base leading-7 text-slate-600">Tentukan jenis permintaan dan urutan pemberi persetujuan.</p>
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
            <fieldset>
                <legend class="mb-1.5 block text-sm font-semibold text-slate-800">Approver untuk tahapan</legend>
                <select id="flow-approvers" required multiple name="approver_user_id[]" aria-describedby="flow-approvers-help" class="min-h-36 w-full max-w-4xl rounded-lg border border-slate-300 p-2 text-base text-slate-900 sm:text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <?php foreach ($approval_users as $user): ?>
                    <option value="<?= (int)$user['user_id'] ?>" <?= in_array((int)$user['user_id'], $selected_approvers, TRUE) ? 'selected' : '' ?>><?= html_escape($user['username']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p id="flow-approvers-help" class="mt-1.5 text-sm text-slate-500">Gunakan Ctrl/Cmd untuk memilih beberapa pengguna. Urutan tahap mengikuti urutan pengguna yang ditampilkan.</p>
            </fieldset>
        </div>
        <div class="sticky bottom-0 z-20 -mx-5 -mb-5 mt-8 flex flex-col-reverse gap-3 border-t border-slate-100 bg-white/10 px-5 pb-3 pt-4 backdrop-blur-sm shadow-none sm:-mx-8 sm:-mb-8 sm:flex-row sm:justify-between sm:px-8">
            <a href="/admin/hr/approvalFlow" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12.5 4.5 7 10l5.5 5.5"/></svg>Kembali</a>
            <button type="submit" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4 3.5h10l2.5 2.5v10.5H3.5v-13H4Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6.5 3.5v4h6v-4M6.5 16v-5h7v5"/></svg><?= html_escape($editing ? 'Simpan perubahan' : 'Simpan alur') ?></button>
        </div>
    </form>
    <?php endif; ?>
</main>
<?php if ($approval_flow_available): ?><script>
(function(){var first=document.getElementById('flow-name');if(first&&!first.disabled&&!first.readOnly&&first.getClientRects().length){first.focus();}})();
</script><?php endif; ?>
