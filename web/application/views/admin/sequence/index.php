<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$sequence_title = $labels['sequence_heading'];
$page_params = array('search' => $search, 'per_page' => $per_page, 'sort' => $sort, 'direction' => $direction);
$make_sort_url = function ($column) use ($search, $per_page, $sort, $direction) {
    $next_direction = $sort === $column && $direction === 'ASC' ? 'DESC' : 'ASC';
    return '/admin/system/sequence?' . http_build_query(array(
        'search' => $search,
        'per_page' => $per_page,
        'sort' => $column,
        'direction' => $next_direction,
    ));
};
$has_active_filter = trim((string) $search) !== '';
$empty_message = $has_active_filter ? $labels['sequence_no_results'] : $labels['sequence_empty'];
$empty_hint = $has_active_filter ? $labels['sequence_no_results_hint'] : $labels['sequence_empty_hint'];
$columns = array(
    'sequence_code' => array('label' => $labels['sequence_sequence_code'], 'class' => 'whitespace-nowrap'),
    'sequence_name' => array('label' => $labels['sequence_sequence_name'], 'class' => 'min-w-[8rem] sm:min-w-56'),
    'description' => array('label' => $labels['sequence_description'], 'class' => 'min-w-[14rem] sm:min-w-64'),
    'repeat_type' => array('label' => $labels['sequence_repeat_type'], 'class' => 'min-w-[9rem]'),
    'menu_code' => array('label' => $labels['sequence_menu'], 'class' => 'min-w-[14rem]'),
    'last_value' => array('label' => $labels['sequence_last_value'], 'class' => 'whitespace-nowrap'),
    'last_sequence' => array('label' => $labels['sequence_last_sequence'], 'class' => 'whitespace-nowrap'),
    'format' => array('label' => $labels['sequence_format'], 'class' => 'min-w-[10rem]'),
);
?>
<main class="-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-2">
            <li><?= html_escape($labels['system']) ?></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" class="font-medium text-slate-800"><?= html_escape($sequence_title) ?></li>
        </ol>
    </nav>

    <header class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div class="max-w-2xl">
            <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl"><?= html_escape($sequence_title) ?></h1>
            <p class="mt-2 text-base leading-7 text-slate-600"><?= html_escape($labels['sequence_intro']) ?></p>
        </div>
        <?php if ($sequence_add_allowed): ?>
            <?php if ($write_available): ?>
                <a href="/admin/system/sequence/new" class="inline-flex h-11 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-indigo-600 px-4 py-0 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><path d="M10 4a.75.75 0 0 1 .75.75v4.5h4.5a.75.75 0 0 1 0 1.5h-4.5v4.5a.75.75 0 0 1-1.5 0v-4.5h-4.5a.75.75 0 0 1 0-1.5h4.5v-4.5A.75.75 0 0 1 10 4Z"/></svg>
                    <?= html_escape($labels['sequence_add']) ?>
                </a>
            <?php else: ?>
                <span aria-disabled="true" class="inline-flex h-11 shrink-0 cursor-not-allowed items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-slate-200 px-4 py-0 text-sm font-semibold text-slate-500">
                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><path d="M10 4a.75.75 0 0 1 .75.75v4.5h4.5a.75.75 0 0 1 0 1.5h-4.5v4.5a.75.75 0 0 1-1.5 0v-4.5h-4.5a.75.75 0 0 1 0-1.5h4.5v-4.5A.75.75 0 0 1 10 4Z"/></svg>
                    <?= html_escape($labels['sequence_add']) ?>
                </span>
            <?php endif; ?>
        <?php endif; ?>
    </header>

    <?php if (!$backend_available): ?>
        <div role="status" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <p class="font-semibold"><?= html_escape($labels['sequence_unavailable']) ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($sequence_notice) && in_array($sequence_notice, array('sequence_created', 'sequence_saved', 'sequence_deleted', 'sequence_delete_error'), TRUE)): ?>
        <?php $sequence_notice_error = $sequence_notice === 'sequence_delete_error'; ?>
        <div role="status" class="mb-5 flex items-start gap-3 rounded-xl border p-4 text-sm <?= $sequence_notice_error ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900' ?>">
            <span aria-hidden="true" class="mt-0.5">
                <?= $sequence_notice_error ? '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><circle cx="10" cy="10" r="7.5"/><path stroke-linecap="round" d="M10 6v4.5m0 3h.01"/></svg>' : '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><circle cx="10" cy="10" r="7.5"/><path stroke-linecap="round" stroke-linejoin="round" d="m6.5 10 2.3 2.3 4.7-4.7"/></svg>' ?>
            </span>
            <p><?= html_escape($labels[$sequence_notice]) ?></p>
        </div>
    <?php endif; ?>

    <?php if (!$write_available): ?>
        <div role="note" class="mb-5 flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
            <span aria-hidden="true" class="mt-0.5 text-indigo-600"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"><circle cx="10" cy="10" r="7.5"/><path stroke-linecap="round" d="M10 9v4m0-7h.01"/></svg></span>
            <p><?= html_escape($labels['sequence_write_disabled']) ?></p>
        </div>
    <?php endif; ?>

    <section id="sequence-list" data-sequence-list aria-label="<?= html_escape($sequence_title) ?>" class="rounded-tl-none rounded-tr-none rounded-bl-2xl rounded-br-2xl border border-slate-200 bg-white shadow-sm">
        <?php if ($backend_available): ?>
            <input type="hidden" name="per_page" value="<?= (int) $per_page ?>" form="sequence-filter-form">
        <?php endif; ?>
        <div id="sequence-filter-bar" class="sticky top-16 z-30 flex flex-col gap-4 border-b border-slate-200 bg-white/10 px-3 py-4 shadow-none backdrop-blur-sm sm:flex-row sm:items-start sm:justify-end sm:px-6 rounded-none">
            <?php if (!$backend_available): ?>
                <p class="text-sm text-slate-500 sm:ml-auto" role="status" aria-live="polite"><?= html_escape($labels['sequence_unavailable']) ?></p>
            <?php endif; ?>
            <?php if ($backend_available): ?>
                <form id="sequence-filter-form" action="/admin/system/sequence" method="get" data-sequence-live-filter class="w-full sm:ml-auto sm:w-64 sm:shrink-0">
                    <label for="sequence-filter-search" class="sr-only"><?= html_escape($labels['sequence_search_label']) ?></label>
                    <div class="relative">
                        <svg data-sequence-search-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"><circle cx="10.8" cy="10.8" r="6.8"/><path stroke-linecap="round" d="m16 16 4.2 4.2"/></svg>
                        <input id="sequence-filter-search" type="search" name="search" value="<?= html_escape($search) ?>" maxlength="120" autocomplete="off" placeholder="<?= html_escape($labels['sequence_search_hint']) ?>" class="min-h-10 w-full rounded-lg border border-slate-300 bg-white pl-10 pr-3 text-base text-slate-900 placeholder:text-slate-400 sm:text-sm focus:border-emerald-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                    </div>
                    <input type="hidden" name="sort" value="<?= html_escape($sort) ?>">
                    <input type="hidden" name="direction" value="<?= html_escape($direction) ?>">
                </form>
            <?php endif; ?>
        </div>

        <div data-sequence-table-scroll class="overflow-x-auto rounded-b-2xl">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <caption class="sr-only"><?= html_escape($sequence_title) ?></caption>
                <thead data-sequence-sticky-header class="bg-slate-50/10 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="w-16 px-2 py-3.5 text-right sm:px-6"><span class="sr-only"><?= html_escape($labels['sequence_actions']) ?></span></th>
                        <?php foreach ($columns as $column => $meta): ?>
                            <?php $sort_active = $sort === $column; $sort_aria = !$sort_active ? 'none' : ($direction === 'ASC' ? 'ascending' : 'descending'); ?>
                            <th scope="col" aria-sort="<?= html_escape($sort_aria) ?>" class="<?= $meta['class'] ?> px-2 py-3.5 sm:px-6">
                                <a href="<?= html_escape($make_sort_url($column)) ?>" class="inline-flex min-h-8 items-center gap-1.5 rounded-md hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                                    <?= html_escape($meta['label']) ?>
                                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="h-3.5 w-3.5 <?= $sort_active && $direction === 'DESC' ? 'rotate-180' : '' ?> <?= $sort_active ? 'text-indigo-600' : 'text-slate-400' ?>"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3v10m0 0 3.5-3.5M8 13l-3.5-3.5"/></svg>
                                </a>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if ($backend_available && count($records) > 0): ?>
                        <?php foreach ($records as $record): ?>
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="w-16 px-2 py-2 sm:px-6">
                                    <?php if ($sequence_actions_available): ?>
                                    <details class="relative" data-sequence-actions>
                                        <summary aria-label="<?= html_escape($labels['sequence_actions']) ?>: <?= html_escape((string) $record['sequence_code']) ?>" class="ml-auto flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 [&::-webkit-details-marker]:hidden">
                                            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><circle cx="10" cy="4" r="1.5"/><circle cx="10" cy="10" r="1.5"/><circle cx="10" cy="16" r="1.5"/></svg>
                                        </summary>
                                        <div data-sequence-actions-menu class="fixed z-50 w-40 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg">
                                            <?php if ($sequence_update_allowed): ?>
                                                <?php if ($write_available): ?>
                                                <a href="/admin/system/sequence/change/<?= rawurlencode((string) $record['sequence_code']) ?>" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                                                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m13.5 3.5 3 3M4 16l3.6-.7L16 6.9a2.1 2.1 0 0 0-3-3L4.7 12.3 4 16Z"/></svg>
                                                    <?= html_escape($labels['sequence_edit']) ?>
                                                </a>
                                                <?php else: ?>
                                                    <span class="flex min-h-10 items-center px-3 text-sm text-slate-400" aria-disabled="true"><?= html_escape($labels['sequence_write_disabled']) ?></span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <?php if ($sequence_delete_allowed): ?>
                                                <?php if ($write_available): ?>
                                                <form action="/admin/system/sequence/delete" method="post" data-web-confirm data-web-confirm-title="<?= html_escape($labels['sequence_delete']) ?>" data-web-confirm-message="<?= html_escape($labels['sequence_confirm_delete']) ?>" data-web-confirm-accept="<?= html_escape($labels['sequence_delete']) ?>">
                                                    <input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>">
                                                    <input type="hidden" name="sequence_code" value="<?= html_escape((string) $record['sequence_code']) ?>">
                                                    <button type="submit" class="flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-left text-sm font-medium text-rose-700 hover:bg-rose-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500">
                                                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6h11m-9.5 0 .7 10h7.6l.7-10M8 6V4h4v2m-3 3v4m2-4v4"/></svg>
                                                        <?= html_escape($labels['sequence_delete']) ?>
                                                    </button>
                                                </form>
                                                <?php else: ?>
                                                    <span class="flex min-h-10 items-center px-3 text-sm text-slate-400" aria-disabled="true"><?= html_escape($labels['sequence_write_disabled']) ?></span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                            <?php endif; ?>
                                        </div>
                                    </details>
                                    <?php endif; ?>
                                </td>
                                <th scope="row" class="whitespace-nowrap px-2 py-3 font-mono text-xs font-semibold text-slate-700 sm:px-6 sm:py-4"><?= html_escape((string) $record['sequence_code']) ?></th>
                                <td class="min-w-[8rem] break-words px-2 py-3 font-medium text-slate-900 sm:min-w-56 sm:px-6 sm:py-4"><?= html_escape((string) $record['sequence_name']) ?></td>
                                <td class="min-w-[14rem] break-words px-2 py-3 text-slate-600 sm:min-w-64 sm:px-6 sm:py-4"><?= html_escape((string) $record['description']) ?></td>
                                <td class="whitespace-nowrap px-2 py-3 text-slate-700 sm:px-6 sm:py-4"><?= html_escape((string) $record['repeat_type']) ?></td>
                                <td class="min-w-[14rem] whitespace-nowrap px-2 py-3 text-slate-700 sm:px-6 sm:py-4"><?= html_escape((string) $record['menu_label']) ?></td>
                                <td class="whitespace-nowrap px-2 py-3 font-mono text-xs text-slate-700 sm:px-6 sm:py-4"><?= html_escape((string) $record['last_value']) ?></td>
                                <td class="whitespace-nowrap px-2 py-3 font-mono text-xs text-slate-700 sm:px-6 sm:py-4"><?= html_escape((string) $record['last_sequence']) ?></td>
                                <td class="whitespace-nowrap px-2 py-3 font-mono text-xs text-slate-700 sm:px-6 sm:py-4"><?= html_escape((string) $record['format']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="px-4 py-14 text-center sm:px-6">
                                <div class="sticky left-0 mx-0 max-w-md sm:static sm:mx-auto rounded-none" style="width: min(448px, calc(100vw - 4rem));">
                                    <div aria-hidden="true" class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"><circle cx="10.8" cy="10.8" r="6.8"/><path stroke-linecap="round" d="m16 16 4.2 4.2"/></svg>
                                    </div>
                                    <p class="font-medium text-slate-800"><?= html_escape($backend_available ? $empty_message : $labels['sequence_unavailable']) ?></p>
                                    <?php if ($backend_available): ?><p class="mt-1 text-sm text-slate-500"><?= html_escape($empty_hint) ?></p><?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($backend_available && $total_records > 0): ?>
            <div class="flex flex-col gap-3 border-t border-slate-200 px-3 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <form action="/admin/system/sequence" method="get" class="flex items-center justify-center gap-2 sm:justify-start">
                    <input type="hidden" name="search" value="<?= html_escape($search) ?>">
                    <input type="hidden" name="sort" value="<?= html_escape($sort) ?>">
                    <input type="hidden" name="direction" value="<?= html_escape($direction) ?>">
                    <label for="sequence-page-size" class="whitespace-nowrap text-sm font-medium text-slate-600"><?= html_escape($labels['sequence_per_page']) ?></label>
                    <select id="sequence-page-size" name="per_page" onchange="this.form.submit()" class="min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                        <?php foreach (array(10, 25, 50) as $choice): ?>
                            <option value="<?= (int) $choice ?>"<?= $choice === $per_page ? ' selected' : '' ?>><?= (int) $choice ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <nav aria-label="<?= html_escape($sequence_title) ?> — <?= html_escape($labels['sequence_page']) ?>" class="flex w-full items-center justify-end gap-2 sm:ml-auto sm:w-auto sm:flex-1 sm:justify-end">
                    <?php if ($page > 1): ?>
                        <?php $page_params['page'] = $page - 1; ?>
                        <a rel="prev" aria-label="<?= html_escape($labels['sequence_previous']) ?>" title="<?= html_escape($labels['sequence_previous']) ?>" href="/admin/system/sequence?<?= html_escape(http_build_query($page_params)) ?>" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12.5 4.5 7 10l5.5 5.5"/></svg>
                        </a>
                    <?php else: ?>
                        <span aria-disabled="true" aria-label="<?= html_escape($labels['sequence_previous']) ?>" title="<?= html_escape($labels['sequence_previous']) ?>" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-400">
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12.5 4.5 7 10l5.5 5.5"/></svg>
                        </span>
                    <?php endif; ?>
                    <span class="min-w-[11rem] text-center text-sm text-slate-600" aria-live="polite"><?= html_escape(sprintf($labels['sequence_page_of'], $page, $page_count, number_format((int) $total_records, 0, ',', '.'))) ?></span>
                    <?php if ($page < $page_count): ?>
                        <?php $page_params['page'] = $page + 1; ?>
                        <a rel="next" aria-label="<?= html_escape($labels['sequence_next']) ?>" title="<?= html_escape($labels['sequence_next']) ?>" href="/admin/system/sequence?<?= html_escape(http_build_query($page_params)) ?>" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="m7.5 4.5 5.5 5.5-5.5 5.5"/></svg>
                        </a>
                    <?php else: ?>
                        <span aria-disabled="true" aria-label="<?= html_escape($labels['sequence_next']) ?>" title="<?= html_escape($labels['sequence_next']) ?>" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-400">
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="m7.5 4.5 5.5 5.5-5.5 5.5"/></svg>
                        </span>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    </section>
</main>
<div id="sequence-sticky-header-clone" aria-hidden="true" hidden class="fixed z-20 overflow-hidden border-b border-slate-200 bg-slate-50/10 shadow-sm"></div>
<script>
(function () {
    var list = document.querySelector('[data-sequence-list]');
    var filterBar = document.getElementById('sequence-filter-bar');
    var thead = document.querySelector('[data-sequence-sticky-header]');
    var tableScroll = document.querySelector('[data-sequence-table-scroll]');
    var cloneHost = document.getElementById('sequence-sticky-header-clone');
    var table = thead ? thead.closest('table') : null;
    if (list && filterBar && thead && tableScroll && cloneHost && table) {
        var clonedTable = table.cloneNode(false);
        var clonedHead = thead.cloneNode(true);
        clonedHead.removeAttribute('data-sequence-sticky-header');
        clonedHead.querySelectorAll('a').forEach(function (link) { link.tabIndex = -1; });
        clonedTable.setAttribute('aria-hidden', 'true');
        clonedTable.appendChild(clonedHead);
        cloneHost.appendChild(clonedTable);

        var updateStickyHeader = function () {
            var filterRect = filterBar.getBoundingClientRect();
            var headerRect = thead.getBoundingClientRect();
            var listRect = list.getBoundingClientRect();
            var tableScrollRect = tableScroll.getBoundingClientRect();
            var headerHeight = headerRect.height;
            var stickyTop = filterRect.bottom;
            var visible = headerHeight > 0 && headerRect.top <= stickyTop && listRect.bottom > stickyTop + headerHeight && stickyTop < window.innerHeight;

            if (!visible) {
                cloneHost.hidden = true;
                return;
            }

            cloneHost.hidden = false;
            cloneHost.style.top = stickyTop + 'px';
            cloneHost.style.left = (tableScrollRect.left + tableScroll.clientLeft) + 'px';
            cloneHost.style.width = tableScroll.clientWidth + 'px';
            cloneHost.style.height = headerHeight + 'px';
            var tableWidth = table.getBoundingClientRect().width;
            clonedTable.style.width = tableWidth + 'px';
            clonedTable.style.minWidth = tableWidth + 'px';

            var sourceCells = thead.querySelectorAll('th');
            var clonedCells = clonedHead.querySelectorAll('th');
            sourceCells.forEach(function (cell, index) {
                if (clonedCells[index]) {
                    var width = cell.getBoundingClientRect().width;
                    clonedCells[index].style.width = width + 'px';
                    clonedCells[index].style.minWidth = width + 'px';
                    clonedCells[index].style.maxWidth = width + 'px';
                }
            });
            clonedTable.style.transform = 'translateX(0px)';
            if (sourceCells.length && clonedCells.length) {
                var horizontalOffset = sourceCells[0].getBoundingClientRect().left - clonedCells[0].getBoundingClientRect().left;
                clonedTable.style.transform = 'translateX(' + horizontalOffset + 'px)';
            }
        };
        var scheduleStickyHeaderUpdate = function () { window.requestAnimationFrame(updateStickyHeader); };
        window.addEventListener('scroll', scheduleStickyHeaderUpdate, { passive: true });
        window.addEventListener('resize', scheduleStickyHeaderUpdate, { passive: true });
        tableScroll.addEventListener('scroll', scheduleStickyHeaderUpdate, { passive: true });
        if (typeof ResizeObserver === 'function') {
            var stickyHeaderResizeObserver = new ResizeObserver(scheduleStickyHeaderUpdate);
            stickyHeaderResizeObserver.observe(list);
            stickyHeaderResizeObserver.observe(filterBar);
            stickyHeaderResizeObserver.observe(tableScroll);
        }
        scheduleStickyHeaderUpdate();
    }

    var actionMenus = Array.prototype.slice.call(document.querySelectorAll('[data-sequence-actions]'));
    var positionActionMenu = function (details) {
        if (!details.open) { return; }
        var summary = details.querySelector('summary');
        var menu = details.querySelector('[data-sequence-actions-menu]');
        if (!summary || !menu) { return; }
        var anchor = summary.getBoundingClientRect();
        var menuBounds = menu.getBoundingClientRect();
        var left = Math.min(anchor.right - menuBounds.width, window.innerWidth - menuBounds.width - 8);
        var top = anchor.bottom + 4;
        if (top + menuBounds.height > window.innerHeight - 8) {
            top = anchor.top - menuBounds.height - 4;
        }
        top = Math.max(8, Math.min(top, window.innerHeight - menuBounds.height - 8));
        menu.style.left = Math.max(8, left) + 'px';
        menu.style.top = top + 'px';
    };
    actionMenus.forEach(function (details) {
        details.addEventListener('toggle', function () { positionActionMenu(details); });
    });
    document.addEventListener('pointerdown', function (event) {
        actionMenus.forEach(function (details) {
            if (!details.open) { return; }
            var menu = details.querySelector('[data-sequence-actions-menu]');
            var trigger = details.querySelector('summary');
            if ((!menu || !menu.contains(event.target)) && (!trigger || !trigger.contains(event.target))) {
                details.open = false;
            }
        });
    }, true);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            actionMenus.forEach(function (details) {
                if (!details.open) { return; }
                details.open = false;
                var trigger = details.querySelector('summary');
                if (trigger) { trigger.focus(); }
            });
        }
    });
    var repositionActionMenus = function () {
        actionMenus.forEach(positionActionMenu);
    };
    window.addEventListener('scroll', repositionActionMenus, true);
    window.addEventListener('resize', repositionActionMenus, { passive: true });

    var filterForm = document.querySelector('[data-sequence-live-filter]');
    var searchInput = filterForm ? filterForm.querySelector('[name="search"]') : null;
    var filterTimer = null;
    if (filterForm && searchInput) {
        if (searchInput.value.length) {
            searchInput.focus();
            if (typeof searchInput.setSelectionRange === 'function') {
                searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
            }
        }
        searchInput.addEventListener('input', function () {
            window.clearTimeout(filterTimer);
            filterTimer = window.setTimeout(function () {
                if (typeof filterForm.requestSubmit === 'function') { filterForm.requestSubmit(); }
                else { filterForm.submit(); }
            }, 350);
        });
    }
}());
</script>
