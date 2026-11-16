<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
$approved = $approval_confirmation_decision === 'approved';
$decision_required = $approval_confirmation_decision === NULL;
$token_decision = isset($approval_confirmation_token_decision) ? $approval_confirmation_token_decision : $approval_confirmation_decision;
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
            <div>
                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Rincian</p>
                <p class="whitespace-pre-wrap break-words text-sm leading-6 text-slate-700"><?= $approval_confirmation['details'] !== '' ? html_escape($approval_confirmation['details']) : 'Tidak ada rincian tambahan.' ?></p>
            </div>
            <div class="rounded-xl border <?= $approved ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-rose-200 bg-rose-50 text-rose-900' ?> p-4 text-sm">
                <?= $decision_required ? 'Pilih Setuju atau Tolak untuk melanjutkan.' : ($approved ? 'Anda akan menyetujui permintaan ini.' : 'Anda akan menolak permintaan ini.') ?>
            </div>
            <form method="post" action="<?= html_escape(site_url('admin/system/approval').'?'.http_build_query(array('approval_token'=>$approval_confirmation_token,'approval_decision'=>$token_decision))) ?>" class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-between">
                <input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>">
                <input type="hidden" name="approval_token" value="<?= html_escape($approval_confirmation_token) ?>">
                <a href="/admin/system/approval" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">Kembali</a>
                <?php if ($decision_required): ?>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <button type="submit" name="approval_decision" value="rejected" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-rose-600 px-5 text-sm font-semibold text-white hover:bg-rose-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 focus-visible:ring-offset-2">Tolak</button>
                        <button type="submit" name="approval_decision" value="approved" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-emerald-600 px-5 text-sm font-semibold text-white hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">Setuju</button>
                    </div>
                <?php else: ?>
                    <button type="submit" name="approval_decision" value="<?= html_escape($approval_confirmation_decision) ?>" class="inline-flex min-h-11 items-center justify-center rounded-lg <?= $approved ? 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500' : 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500' ?> px-5 text-sm font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2">
                        <?= $approved ? 'Konfirmasi persetujuan' : 'Konfirmasi penolakan' ?>
                    </button>
                <?php endif; ?>
            </form>
        </div>
    </section>
</main>
