<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$approved = $approval_confirmation_decision === 'approved';
$decision_required = $approval_confirmation_decision === NULL;
$token_decision = isset($approval_confirmation_token_decision) ? $approval_confirmation_token_decision : $approval_confirmation_decision;
$created_at_iso = app_utc_iso($approval_confirmation['create_on']);
?>
<main class="mx-auto w-full max-w-4xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-2">
            <li><?= html_escape($labels['system']) ?></li>
            <li aria-hidden="true">/</li>
            <li><a href="/admin/system/approval" class="hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"><?= html_escape($labels['admin_approval']) ?></a></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" class="font-medium text-slate-800"><?= $decision_required ? 'Lihat persetujuan' : 'Konfirmasi' ?></li>
        </ol>
    </nav>
    <header class="mb-7">
        <p class="text-sm font-semibold uppercase tracking-wide text-indigo-700">Persetujuan</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950"><?= $decision_required ? 'Detail permintaan persetujuan' : 'Konfirmasi keputusan' ?></h1>
        <p class="mt-2 text-base leading-7 text-slate-600"><?= $decision_required ? 'Baca rincian permintaan, lalu pilih keputusan Anda.' : 'Tinjau permintaan sebelum keputusan Anda dikirim.' ?></p>
    </header>
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-50 px-5 py-4 sm:px-7">
            <h2 class="text-sm font-semibold text-slate-700"><?= $decision_required ? 'Permintaan menunggu keputusan' : ($approved ? 'Setujui permintaan' : 'Tolak permintaan') ?></h2>
        </div>
        <div class="space-y-5 p-5 sm:p-7">
            <div>
                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Judul permintaan</p>
                <h3 class="text-xl font-semibold text-slate-950"><?= html_escape($approval_confirmation['subject']) ?></h3>
            </div>
            <dl class="grid gap-4 rounded-xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-700 dark:bg-slate-900/60 sm:grid-cols-3">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Dibuat oleh</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-800 dark:text-slate-100"><?= html_escape($approval_confirmation['requester_name'] !== '' ? $approval_confirmation['requester_name'] : 'Tidak diketahui') ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Disubmit oleh</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-800 dark:text-slate-100"><?= html_escape($approval_confirmation['submitted_by_name'] !== '' ? $approval_confirmation['submitted_by_name'] : $approval_confirmation['requester_name']) ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Tanggal pengajuan</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-800 dark:text-slate-100"><time datetime="<?= html_escape($created_at_iso) ?>" data-local-time><?= html_escape($approval_confirmation['create_on']) ?></time></dd>
                </div>
            </dl>
            <div>
                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Rincian</p>
                <p class="whitespace-pre-wrap break-words text-sm leading-6 text-slate-700"><?= $approval_confirmation['details'] !== '' ? html_escape($approval_confirmation['details']) : 'Tidak ada rincian tambahan.' ?></p>
            </div>
            <div class="rounded-xl border <?= $approved ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-rose-200 bg-rose-50 text-rose-900' ?> p-4 text-sm">
                <?= $decision_required ? 'Pilih Setuju, Tolak, atau Minta Revisi untuk melanjutkan.' : ($approved ? 'Anda akan menyetujui permintaan ini.' : 'Anda akan menolak permintaan ini.') ?>
            </div>
            <form method="post" action="<?= html_escape(site_url('admin/system/approval').'?'.http_build_query(array('approval_token'=>$approval_confirmation_token,'approval_decision'=>$token_decision))) ?>" class="border-t border-slate-100 pt-5">
                <input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>">
                <input type="hidden" name="approval_token" value="<?= html_escape($approval_confirmation_token) ?>">
                <?php if ($decision_required): ?>
                    <label for="approval-note" class="mb-1 block text-sm font-semibold text-slate-800">Alasan revisi <span class="font-normal text-slate-500">(wajib jika memilih Minta Revisi)</span></label>
                    <textarea id="approval-note" name="note" maxlength="10000" rows="3" class="w-full rounded-lg border border-slate-300 p-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="Tuliskan alasan jika meminta revisi."></textarea>
                <?php endif; ?>
                <div class="mt-4 flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
                    <a href="/admin/system/approval" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">Kembali</a>
                    <?php if ($decision_required): ?>
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <button type="submit" name="approval_decision" value="rejected" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-rose-600 px-5 text-sm font-semibold text-white hover:bg-rose-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 focus-visible:ring-offset-2">Tolak</button>
                            <button type="submit" name="approval_decision" value="revise" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-amber-600 px-5 text-sm font-semibold text-white hover:bg-amber-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">Minta Revisi</button>
                            <button type="submit" name="approval_decision" value="approved" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-emerald-600 px-5 text-sm font-semibold text-white hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">Setuju</button>
                        </div>
                    <?php else: ?>
                        <button type="submit" name="approval_decision" value="<?= html_escape($approval_confirmation_decision) ?>" class="inline-flex min-h-11 items-center justify-center rounded-lg <?= $approved ? 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500' : 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500' ?> px-5 text-sm font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2">
                            <?= $approved ? 'Konfirmasi persetujuan' : 'Konfirmasi penolakan' ?>
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </section>
</main>
<script>
(function () {
    var form = document.querySelector('form[action*="admin/system/approval"]');
    var note = document.getElementById('approval-note');
    if (!form || !note) return;
    note.addEventListener('input', function () { note.setCustomValidity(''); });
    form.addEventListener('submit', function (event) {
        if (event.submitter && event.submitter.value === 'revise' && note.value.trim() === '') {
            event.preventDefault();
            note.setCustomValidity('Alasan revisi wajib diisi.');
            note.reportValidity();
            note.focus();
        }
    });
})();
</script>
<script>
(function () {
    var nodes = document.querySelectorAll('[data-local-time]');
    if (!nodes.length || typeof Intl === 'undefined' || typeof Intl.DateTimeFormat !== 'function') return;
    var locale = (document.documentElement.getAttribute('lang') || 'id-ID').trim() || 'id-ID';
    var formatter = new Intl.DateTimeFormat(locale, {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        timeZoneName: 'short'
    });
    Array.prototype.forEach.call(nodes, function (node) {
        var date = new Date(node.getAttribute('datetime') || '');
        if (!isNaN(date.getTime())) {
            node.textContent = formatter.format(date);
            node.setAttribute('title', node.getAttribute('datetime'));
        }
    });
})();
</script>
