<?php $is_change = $property_mode === 'change'; ?>
<main class="mx-auto w-full max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6">
        <p class="text-sm font-medium text-slate-500">Admin / System / <a href="/admin/system/property" class="underline hover:text-indigo-700">Property</a></p>
        <h1 class="mt-1 text-2xl font-semibold text-slate-900"><?= $is_change ? 'Edit Properti' : 'Tambah Properti' ?></h1>
    </div>

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <?php if (!$backend_available): ?>
            <div role="status" class="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">
                <?= html_escape($labels['module_backend_unavailable']) ?>
            </div>
        <?php endif; ?>
        <div role="status" class="mb-5 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
            Form baca/tulis belum diaktifkan. Perubahan memerlukan autentikasi admin dan perlindungan CSRF; tidak ada data yang akan disimpan.
        </div>

        <?php if ($is_change && $property_record !== NULL): ?>
            <dl class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-slate-600">Kode Propert</dt>
                    <dd class="mt-1 break-all text-sm text-slate-900"><?= html_escape($property_record['property_code']) ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-600">Nama Properti</dt>
                    <dd class="mt-1 break-words text-sm text-slate-900"><?= html_escape($property_record['property_name']) ?></dd>
                </div>
            </dl>
        <?php elseif (!$is_change): ?>
            <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2" aria-label="Form tambah nonaktif">
                <div>
                    <label for="property-new-code" class="mb-1 block text-sm font-medium text-slate-700">Kode Propert</label>
                    <input id="property-new-code" type="text" disabled class="min-h-11 w-full rounded-lg border border-slate-300 bg-slate-100 px-3 text-sm text-slate-500" placeholder="Belum tersedia">
                </div>
                <div>
                    <label for="property-new-name" class="mb-1 block text-sm font-medium text-slate-700">Nama Properti</label>
                    <input id="property-new-name" type="text" disabled class="min-h-11 w-full rounded-lg border border-slate-300 bg-slate-100 px-3 text-sm text-slate-500" placeholder="Belum tersedia">
                </div>
            </div>
        <?php endif; ?>

        <div class="flex flex-wrap gap-3">
            <button type="button" disabled aria-disabled="true" class="min-h-11 cursor-not-allowed rounded-lg bg-slate-300 px-4 py-2 text-sm font-semibold text-white">Simpan belum tersedia</button>
            <a href="/admin/system/property" class="inline-flex min-h-11 items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Kembali ke daftar</a>
        </div>
    </section>
</main>
