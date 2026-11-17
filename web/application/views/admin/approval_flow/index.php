<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$title = 'Alur persetujuan';
$page_params = array('search' => $filters['search'], 'per_page' => $per_page);
$has_search = trim((string)$filters['search']) !== '';
$status_messages = array(
    'saved' => 'Tahapan alur berhasil disimpan.',
    'error' => 'Tahapan alur gagal disimpan. Periksa data dan hak akses.',
);
?>
<main class="-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-2"><li>HR</li><li aria-hidden="true">/</li><li aria-current="page" class="font-medium text-slate-800"><?= html_escape($title) ?></li></ol>
    </nav>
    <header class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div class="max-w-2xl">
            <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl"><?= html_escape($title) ?></h1>
            <p class="mt-2 text-base leading-7 text-slate-600">Atur tahapan persetujuan untuk template alur yang menunya dapat diakses tenant. Tambah dan hapus template dilakukan di System → Approval Flow Template.</p>
        </div>
    </header>

    <?php if ($approval_flow_status !== '' && isset($status_messages[$approval_flow_status])): ?>
        <div role="status" class="mb-5 rounded-xl border p-4 text-sm <?= $approval_flow_status === 'error' ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900' ?>"><?= html_escape($status_messages[$approval_flow_status]) ?></div>
    <?php endif; ?>
    <?php if (!$approval_flow_available): ?>
        <div role="status" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <p class="font-semibold">Penyimpanan alur belum tersedia.</p>
            <p class="mt-1">Pastikan migrasi selesai dan tenant aktif.</p>
        </div>
    <?php endif; ?>

    <section aria-labelledby="approval-flow-list-heading" class="rounded-tl-none rounded-tr-none rounded-bl-2xl rounded-br-2xl border border-slate-200 bg-white shadow-sm">
        <h2 id="approval-flow-list-heading" class="sr-only"><?= html_escape($title) ?></h2>
        <?php if ($approval_flow_available): ?><input type="hidden" name="per_page" value="<?= (int)$per_page ?>" form="approval-flow-filter-form"><?php endif; ?>
        <div data-approval-filter-header class="sticky top-16 z-20 flex flex-col gap-4 border-b border-slate-200 bg-white/10 px-3 py-4 backdrop-blur-sm sm:flex-row sm:items-start sm:justify-end sm:px-6 rounded-none">
            <?php if ($approval_flow_available): ?>
                <form id="approval-flow-filter-form" action="/admin/hr/approvalFlow" method="get" data-approval-live-filter class="w-full sm:w-64 sm:shrink-0">
                    <label for="approval-flow-search" class="sr-only">Cari alur persetujuan</label>
                    <div class="relative">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"><circle cx="10.8" cy="10.8" r="6.8"/><path stroke-linecap="round" d="m16 16 4.2 4.2"/></svg>
                        <input id="approval-flow-search" type="search" name="search" value="<?= html_escape($filters['search']) ?>" maxlength="120" autocomplete="off" placeholder="Cari alur" class="min-h-10 w-full rounded-lg border border-slate-300 bg-white pl-10 pr-3 text-base text-slate-900 placeholder:text-slate-400 sm:text-sm focus:border-emerald-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                    </div>
                </form>
            <?php endif; ?>
        </div>
        <div class="overflow-x-auto rounded-b-2xl">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="md:sticky md:z-10 bg-slate-50/10 text-xs font-semibold uppercase tracking-wide text-slate-500 backdrop-blur-sm">
                    <tr>
                        <th scope="col" aria-label="Tindakan" class="w-14 px-1 py-3.5 sm:px-3"></th>
                        <th scope="col" class="whitespace-nowrap px-2 py-3.5 sm:px-6">Kode alur</th>
                        <th scope="col" class="min-w-[10rem] px-2 py-3.5 sm:px-6">Nama alur</th>
                        <th scope="col" class="min-w-[12rem] px-2 py-3.5 sm:px-6">Menu</th>
                        <th scope="col" class="whitespace-nowrap px-2 py-3.5 sm:px-6">Sumber tahap</th>
                        <th scope="col" class="whitespace-nowrap px-2 py-3.5 sm:px-6">Tahap</th>
                        <th scope="col" class="whitespace-nowrap px-2 py-3.5 sm:px-6">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!$approval_flow_available || !$approval_flows): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-14 text-center sm:px-6">
                                <div class="mx-auto max-w-md">
                                    <div aria-hidden="true" class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-500"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"><circle cx="10.8" cy="10.8" r="6.8"/><path stroke-linecap="round" d="m16 16 4.2 4.2"/></svg></div>
                                    <p class="font-medium text-slate-800"><?= !$approval_flow_available ? 'Penyimpanan alur belum tersedia.' : ($has_search ? 'Tidak ada alur yang cocok dengan pencarian.' : 'Belum ada template alur yang dapat diakses.') ?></p>
                                    <?php if ($has_search): ?><p class="mt-1 text-sm text-slate-500">Ubah kata kunci pencarian, lalu coba lagi.</p><?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $menu_index = 0; foreach ($approval_flows as $flow): $menu_id = 'approval-flow-actions-' . $menu_index++; ?>
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="whitespace-nowrap px-1 py-3 text-left sm:px-3 sm:py-4">
                                    <?php if ($approval_flow_can_update): ?>
                                        <div class="relative inline-flex">
                                            <button type="button" data-flow-actions-toggle aria-label="Tindakan" aria-haspopup="menu" aria-expanded="false" aria-controls="<?= html_escape($menu_id) ?>" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><circle cx="10" cy="4" r="1.6"/><circle cx="10" cy="10" r="1.6"/><circle cx="10" cy="16" r="1.6"/></svg>
                                            </button>
                                            <div id="<?= html_escape($menu_id) ?>" data-flow-actions-menu hidden role="menu" class="fixed z-[60] mt-1 w-40 rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-lg">
                                                <a href="/admin/hr/approvalFlow/edit/<?= html_escape(rawurlencode((string)$flow['flow_code'])) ?>" role="menuitem" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none">
                                                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m12.8 3.2 4 4M3.5 16.5l3.6-.7L16.3 6.6a1.4 1.4 0 0 0-2-2L5.1 13.8l-1.6 2.7Z"/></svg>
                                                    Ubah tahapan
                                                </a>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <th scope="row" class="whitespace-nowrap px-2 py-3 font-mono text-xs font-semibold text-slate-700 sm:px-6 sm:py-4"><?= html_escape($flow['flow_code']) ?></th>
                                <td class="min-w-[10rem] break-words px-2 py-3 font-medium text-slate-900 sm:px-6 sm:py-4"><?= html_escape($flow['flow_name']) ?></td>
                                <td class="min-w-[12rem] break-words px-2 py-3 font-mono text-xs text-slate-600 sm:px-6 sm:py-4"><?= html_escape(isset($flow['menu_codes']) ? $flow['menu_codes'] : '') ?></td>
                                <td class="whitespace-nowrap px-2 py-3 text-slate-700 sm:px-6 sm:py-4"><?= html_escape($flow['step_source'] === 'tenant' ? 'Tenant' : 'Sistem') ?></td>
                                <td class="whitespace-nowrap px-2 py-3 text-slate-700 sm:px-6 sm:py-4"><?= (int)$flow['step_count'] ?></td>
                                <td class="whitespace-nowrap px-2 py-3 sm:px-6 sm:py-4"><span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold <?= !empty($flow['active_flag']) ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>"><span aria-hidden="true" class="h-1.5 w-1.5 rounded-full <?= !empty($flow['active_flag']) ? 'bg-emerald-500' : 'bg-slate-400' ?>"></span><?= !empty($flow['active_flag']) ? 'Aktif' : 'Nonaktif' ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($approval_flow_available && $total > 0): ?>
            <div class="flex flex-col gap-3 border-t border-slate-200 px-3 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <form action="/admin/hr/approvalFlow" method="get" class="flex items-center gap-2 self-center sm:flex-1 sm:self-auto">
                    <input type="hidden" name="search" value="<?= html_escape($filters['search']) ?>">
                    <label for="approval-flow-page-size" class="whitespace-nowrap text-sm font-medium text-slate-600">Baris per halaman</label>
                    <select id="approval-flow-page-size" name="per_page" onchange="this.form.submit()" class="min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                        <?php foreach (array(10,25,50) as $choice): ?><option value="<?= $choice ?>"<?= $choice===$per_page?' selected':'' ?>><?= $choice ?></option><?php endforeach; ?>
                    </select>
                </form>
                <nav aria-label="Navigasi halaman" class="flex w-full items-center justify-end gap-1 sm:flex-1">
                    <a <?= $page<=1?'aria-disabled="true" tabindex="-1"':'rel="prev"' ?> href="/admin/hr/approvalFlow?<?= html_escape(http_build_query($page_params+array('page'=>max(1,$page-1)))) ?>" aria-label="Halaman sebelumnya" title="Sebelumnya" class="inline-flex h-10 w-10 flex-none items-center justify-center rounded-lg border <?= $page<=1?'border-slate-200 text-slate-400 pointer-events-none':'border-slate-300 text-slate-700 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2' ?>"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12.5 4.5 7 10l5.5 5.5"/></svg></a>
                    <p class="whitespace-nowrap text-center text-xs text-slate-600 sm:text-sm" aria-live="polite">Halaman <?= (int)$page ?> dari <?= (int)$pages ?> dari <?= number_format((int)$total,0,',','.') ?> data</p>
                    <a <?= $page>=$pages?'aria-disabled="true" tabindex="-1"':'rel="next"' ?> href="/admin/hr/approvalFlow?<?= html_escape(http_build_query($page_params+array('page'=>min($pages,$page+1)))) ?>" aria-label="Halaman berikutnya" title="Berikutnya" class="inline-flex h-10 w-10 flex-none items-center justify-center rounded-lg border <?= $page>=$pages?'border-slate-200 text-slate-400 pointer-events-none':'border-slate-300 text-slate-700 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2' ?>"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="m7.5 4.5 5.5 5.5-5.5 5.5"/></svg></a>
                </nav>
            </div>
        <?php endif; ?>
    </section>
</main>
<script>
(function () {
    var form = document.querySelector('[data-approval-live-filter]');
    var input = form && form.querySelector('[name="search"]');
    var timer;
    if (form && input) {
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                if (form.requestSubmit) form.requestSubmit(); else form.submit();
            }, 350);
        });
    }
    function closeActionMenus() {
        document.querySelectorAll('[data-flow-actions-menu]').forEach(function (menu) {
            menu.hidden = true;
            var toggle = document.querySelector('[aria-controls="' + menu.id + '"][data-flow-actions-toggle]');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
        });
    }
    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-flow-actions-toggle]');
        if (toggle) {
            var menu = document.getElementById(toggle.getAttribute('aria-controls'));
            var opening = menu && menu.hidden;
            closeActionMenus();
            if (opening) {
                var rect = toggle.getBoundingClientRect();
                menu.hidden = false;
                menu.style.top = Math.max(8, Math.min(rect.bottom + 4, window.innerHeight - menu.getBoundingClientRect().height - 8)) + 'px';
                menu.style.left = Math.max(8, Math.min(rect.right - menu.getBoundingClientRect().width, window.innerWidth - menu.getBoundingClientRect().width - 8)) + 'px';
                toggle.setAttribute('aria-expanded', 'true');
                var first = menu.querySelector('[role="menuitem"]');
                if (first) first.focus();
            }
            return;
        }
        if (!event.target.closest('[data-flow-actions-menu]')) closeActionMenus();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeActionMenus();
    });
}());
</script>
