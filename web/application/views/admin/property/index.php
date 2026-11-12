<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!empty($access_denied)) {
    ?>
    <main class="-mx-4 w-[calc(100%+2rem)] px-4 py-8 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 lg:px-8">
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-5 text-sm font-medium text-rose-900">
            <?= html_escape($labels['property_access_denied']) ?>
        </div>
    </main>
    <?php return; ?>
<?php }

$property_title = $labels['property_heading'];
$status_messages = array('created', 'saved', 'deleted', 'not_found', 'save_error', 'delete_error', 'invalid');
$status_key = in_array($status_key, $status_messages, TRUE) ? $status_key : '';
$page_params = array('search' => $filters['search'], 'per_page' => $per_page, 'sort' => $sort, 'direction' => $direction);
$property_sort_url = function ($field) use ($filters, $per_page, $sort, $direction) {
    $next_direction = $sort === $field && $direction === 'ASC' ? 'DESC' : 'ASC';
    return '/admin/system/property?' . http_build_query(array(
        'search' => $filters['search'],
        'per_page' => $per_page,
        'sort' => $field,
        'direction' => $next_direction,
    ));
};
$has_active_filters = trim((string) $filters['search']) !== '';
$empty_title_key = $has_active_filters ? 'property_no_results' : 'property_empty';
$empty_hint_key = $has_active_filters ? 'property_no_results_hint' : 'property_empty_hint';
?>
<main class="-mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-2">
            <li><?= html_escape($labels['system']) ?></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" class="font-medium text-slate-800"><?= html_escape($property_title) ?></li>
        </ol>
    </nav>

    <header class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div class="max-w-2xl">
            <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl"><?= html_escape($property_title) ?></h1>
            <p class="mt-2 text-base leading-7 text-slate-600"><?= html_escape($labels['property_intro']) ?></p>
        </div>
        <?php if ($can_add): ?>
            <?php if ($write_available): ?>
                <a href="/admin/system/property/new" class="inline-flex h-11 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-indigo-600 px-4 py-0 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><path d="M10 4a.75.75 0 0 1 .75.75v4.5h4.5a.75.75 0 0 1 0 1.5h-4.5v4.5a.75.75 0 0 1-1.5 0v-4.5h-4.5a.75.75 0 0 1 0-1.5h4.5v-4.5A.75.75 0 0 1 10 4Z"/></svg>
                    <?= html_escape($labels['property_add']) ?>
                </a>
            <?php else: ?>
                <span class="inline-flex h-11 shrink-0 cursor-not-allowed items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-slate-200 px-4 py-0 text-sm font-semibold text-slate-500" aria-disabled="true">
                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><path d="M10 4a.75.75 0 0 1 .75.75v4.5h4.5a.75.75 0 0 1 0 1.5h-4.5v4.5a.75.75 0 0 1-1.5 0v-4.5h-4.5a.75.75 0 0 1 0-1.5h4.5v-4.5A.75.75 0 0 1 10 4Z"/></svg>
                    <?= html_escape($labels['property_add']) ?>
                </span>
            <?php endif; ?>
        <?php endif; ?>
    </header>

    <?php if ($status_key !== ''): ?>
        <?php $is_error = in_array($status_key, array('not_found', 'save_error', 'delete_error', 'invalid'), TRUE); ?>
        <div role="status" class="mb-5 flex items-start gap-3 rounded-xl border p-4 text-sm <?= $is_error ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900' ?>">
            <span aria-hidden="true" class="mt-0.5"><?= $is_error ? '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><circle cx="10" cy="10" r="7.5"/><path stroke-linecap="round" d="M10 6v4.5m0 3h.01"/></svg>' : '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><circle cx="10" cy="10" r="7.5"/><path stroke-linecap="round" stroke-linejoin="round" d="m6.5 10 2.3 2.3 4.7-4.7"/></svg>' ?></span>
            <p><?= html_escape($labels['property_' . $status_key]) ?></p>
        </div>
    <?php endif; ?>

    <?php if (!$backend_available): ?>
        <div role="status" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <p class="font-semibold"><?= html_escape($labels['property_unavailable']) ?></p>
            <p class="mt-1"><?= html_escape($labels['module_backend_unavailable']) ?></p>
        </div>
    <?php endif; ?>
    <?php if (!$write_available): ?>
        <div role="note" class="mb-5 flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
            <span aria-hidden="true" class="mt-0.5 text-indigo-600"><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"><circle cx="10" cy="10" r="7.5"/><path stroke-linecap="round" d="M10 9v4m0-7h.01"/></svg></span>
            <p><?= html_escape($labels['property_write_disabled']) ?></p>
        </div>
    <?php endif; ?>

    <section aria-labelledby="property-list-heading" class="rounded-tl-none rounded-tr-none border border-slate-200 bg-white shadow-sm">
        <h2 id="property-list-heading" class="sr-only"><?= html_escape($property_title) ?></h2>
        <?php if ($backend_available): ?>
            <input type="hidden" name="per_page" value="<?= (int) $per_page ?>" form="property-filter-form">
        <?php endif; ?>
        <div data-property-filter-header class="sticky top-16 z-20 flex flex-col gap-4 border-b border-slate-200 bg-white/10 px-3 py-4 backdrop-blur sm:flex-row sm:items-start sm:justify-end sm:px-6 rounded-none">
            <?php if (!$backend_available): ?>
                <p class="text-sm text-slate-500"><?= html_escape($labels['property_unavailable']) ?></p>
            <?php endif; ?>
            <?php if ($backend_available): ?>
                <form id="property-filter-form" action="/admin/system/property" method="get" data-property-live-filter class="w-full sm:w-64 sm:shrink-0">
                    <label for="property-filter-search" class="sr-only"><?= html_escape($labels['property_search_label']) ?></label>
                    <div class="relative">
                        <svg data-property-search-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"><circle cx="10.8" cy="10.8" r="6.8"/><path stroke-linecap="round" d="m16 16 4.2 4.2"/></svg>
                        <input id="property-filter-search" type="search" name="search" value="<?= html_escape($filters['search']) ?>" maxlength="120" autocomplete="off" placeholder="<?= html_escape($labels['property_search_hint']) ?>" class="min-h-10 w-full rounded-lg border border-slate-300 bg-white pl-10 pr-3 text-base text-slate-900 placeholder:text-slate-400 sm:text-sm focus:border-emerald-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                    </div>
                    <input type="hidden" name="sort" value="<?= html_escape($sort) ?>">
                    <input type="hidden" name="direction" value="<?= html_escape($direction) ?>">
                </form>
            <?php endif; ?>
        </div>
        <div class="overflow-x-auto rounded-b-2xl">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" aria-label="<?= html_escape($labels['property_actions']) ?>" class="w-12 px-1 py-3.5 sm:px-3"></th>
                        <?php foreach (array('property_code' => $labels['property_code_label'], 'property_name' => $labels['property_name_label']) as $sort_field => $sort_label): ?>
                            <?php $sort_active = $sort === $sort_field; $sort_aria = !$sort_active ? 'none' : ($direction === 'ASC' ? 'ascending' : 'descending'); ?>
                            <th scope="col" aria-sort="<?= html_escape($sort_aria) ?>" class="<?= $sort_field === 'property_code' ? 'whitespace-nowrap' : 'min-w-[8rem] sm:min-w-56' ?> px-2 py-3.5 sm:px-6">
                                <a href="<?= html_escape($property_sort_url($sort_field)) ?>" class="inline-flex min-h-8 items-center gap-1.5 rounded-md hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                                    <?= html_escape($sort_label) ?>
                                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="h-3.5 w-3.5 <?= $sort_active && $direction === 'DESC' ? 'rotate-180' : '' ?> <?= $sort_active ? 'text-indigo-600' : 'text-slate-400' ?>"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3v10m0 0 3.5-3.5M8 13l-3.5-3.5"/></svg>
                                </a>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if ($backend_available && count($property_records) > 0): ?>
                        <?php $action_menu_index = 0; foreach ($property_records as $property): ?>
                            <?php $property_code = (string) $property['property_code']; $action_menu_id = 'property-actions-menu-' . (int) $action_menu_index++; ?>
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="whitespace-nowrap px-1 py-3 text-left sm:px-3 sm:py-4">
                                    <?php if ($write_available && ($can_update || $can_delete)): ?>
                                        <div class="relative inline-flex">
                                            <button type="button" data-property-actions-toggle aria-label="<?= html_escape($labels['property_actions']) ?>" aria-haspopup="menu" aria-expanded="false" aria-controls="<?= html_escape($action_menu_id) ?>" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                                <svg data-property-actions-icon viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5"><circle cx="10" cy="4" r="1.6"/><circle cx="10" cy="10" r="1.6"/><circle cx="10" cy="16" r="1.6"/></svg>
                                            </button>
                                            <div id="<?= html_escape($action_menu_id) ?>" data-property-actions-menu hidden role="menu" class="fixed z-30 mt-1 w-40 rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-lg">
                                                <?php if ($can_update): ?>
                                                    <a href="/admin/system/property/change/<?= html_escape(rawurlencode($property_code)) ?>" data-property-edit-action role="menuitem" class="flex min-h-10 items-center gap-2 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none">
                                                        <svg data-property-edit-icon viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="m12.8 3.2 4 4M3.5 16.5l3.6-.7L16.3 6.6a1.4 1.4 0 0 0-2-2L5.1 13.8l-1.6 2.7Z"/></svg>
                                                        <?= html_escape($labels['property_edit']) ?>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($can_delete): ?>
                                                    <button type="button" data-property-delete-open="1" data-property-code="<?= html_escape($property_code) ?>" data-property-name="<?= html_escape($property['property_name']) ?>" aria-haspopup="dialog" aria-controls="property-delete-dialog" role="menuitem" class="flex min-h-10 w-full items-center gap-2 rounded-lg px-3 text-sm font-medium text-rose-700 hover:bg-rose-50 focus:bg-rose-50 focus:outline-none">
                                                        <svg data-property-delete-icon viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" d="M4 5.5h12M8 3.5h4m-6.5 2 1 11h7l1-11M8.5 8v5.5m3-5.5v5.5"/></svg>
                                                        <?= html_escape($labels['property_delete']) ?>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <th scope="row" class="whitespace-nowrap px-2 py-3 font-mono text-xs font-semibold text-slate-700 sm:px-6 sm:py-4"><?= html_escape($property_code) ?></th>
                                <td class="min-w-[8rem] break-words px-2 py-3 font-medium text-slate-900 sm:min-w-56 sm:px-6 sm:py-4"><?= html_escape($property['property_name']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="px-4 py-14 text-center sm:px-6">
                                <div class="mx-auto max-w-md">
                                    <div aria-hidden="true" class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"><circle cx="10.8" cy="10.8" r="6.8"/><path stroke-linecap="round" d="m16 16 4.2 4.2"/></svg>
                                    </div>
                                    <p class="font-medium text-slate-800"><?= html_escape($backend_available ? $labels[$empty_title_key] : $labels['property_unavailable']) ?></p>
                                    <?php if ($backend_available): ?><p class="mt-1 text-sm text-slate-500"><?= html_escape($labels[$empty_hint_key]) ?></p><?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($backend_available && $total_records > 0): ?>
            <div class="flex flex-col gap-3 border-t border-slate-200 px-3 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <form action="/admin/system/property" method="get" class="flex items-center gap-2 self-center sm:flex-1 sm:self-auto">
                    <input type="hidden" name="search" value="<?= html_escape($filters['search']) ?>">
                    <input type="hidden" name="sort" value="<?= html_escape($sort) ?>">
                    <input type="hidden" name="direction" value="<?= html_escape($direction) ?>">
                    <label for="property-page-size" class="whitespace-nowrap text-sm font-medium text-slate-600"><?= html_escape($labels['property_per_page']) ?></label>
                    <select id="property-page-size" name="per_page" onchange="this.form.submit()" class="min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                        <?php foreach ($per_page_choices as $choice): ?>
                            <option value="<?= (int) $choice ?>"<?= $choice === $per_page ? ' selected' : '' ?>><?= (int) $choice ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <div class="flex flex-col gap-3 sm:flex-[2] sm:flex-row sm:items-center sm:justify-end">
                    <nav aria-label="<?= html_escape($labels['property_heading']) ?> — <?= html_escape($labels['property_page']) ?>" class="flex w-full items-center justify-end gap-1 sm:flex-1" role="navigation">
                    <?php if ($current_page > 1): ?>
                        <?php $page_params['page'] = $current_page - 1; ?>
                        <a rel="prev" href="/admin/system/property?<?= html_escape(http_build_query($page_params)) ?>" aria-label="<?= html_escape($labels['property_previous']) ?>" title="<?= html_escape($labels['property_previous']) ?>" class="inline-flex h-10 w-10 flex-none items-center justify-center rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12.5 4.5 7 10l5.5 5.5"/></svg>
                        </a>
                    <?php else: ?>
                        <span aria-disabled="true" aria-label="<?= html_escape($labels['property_previous']) ?>" title="<?= html_escape($labels['property_previous']) ?>" class="inline-flex h-10 w-10 flex-none items-center justify-center rounded-lg border border-slate-200 text-slate-400">
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12.5 4.5 7 10l5.5 5.5"/></svg>
                        </span>
                    <?php endif; ?>
                    <p class="whitespace-nowrap text-center text-xs text-slate-600 sm:text-sm" aria-live="polite"><?= html_escape($labels['property_page']) ?> <?= (int) $current_page ?> <?= html_escape($labels['property_page_of']) ?> <?= (int) $page_count ?> <?= html_escape(sprintf($labels['property_from_data'], number_format((int) $total_records, 0, ',', '.'))) ?></p>
                    <?php if ($current_page < $page_count): ?>
                        <?php $page_params['page'] = $current_page + 1; ?>
                        <a rel="next" href="/admin/system/property?<?= html_escape(http_build_query($page_params)) ?>" aria-label="<?= html_escape($labels['property_next']) ?>" title="<?= html_escape($labels['property_next']) ?>" class="inline-flex h-10 w-10 flex-none items-center justify-center rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="m7.5 4.5 5.5 5.5-5.5 5.5"/></svg>
                        </a>
                    <?php else: ?>
                        <span aria-disabled="true" aria-label="<?= html_escape($labels['property_next']) ?>" title="<?= html_escape($labels['property_next']) ?>" class="inline-flex h-10 w-10 flex-none items-center justify-center rounded-lg border border-slate-200 text-slate-400">
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="m7.5 4.5 5.5 5.5-5.5 5.5"/></svg>
                        </span>
                    <?php endif; ?>
                    </nav>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($write_available): ?>
        <div id="property-delete-dialog" hidden role="dialog" aria-modal="true" aria-labelledby="property-delete-title" aria-describedby="property-delete-description" tabindex="-1" class="fixed inset-0 z-50 items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl sm:p-7">
                <div aria-hidden="true" class="mb-4 flex h-11 w-11 items-center justify-center rounded-full bg-rose-50 text-lg font-bold text-rose-700">!</div>
                <h2 id="property-delete-title" class="text-xl font-semibold text-slate-950"><?= html_escape($labels['property_confirm_title']) ?></h2>
                <p id="property-delete-description" class="mt-2 text-sm leading-6 text-slate-600"><?= html_escape($labels['property_confirm_body']) ?></p>
                <p class="mt-3 break-all rounded-lg bg-slate-50 px-3 py-2 font-mono text-sm text-slate-800" data-property-delete-label></p>
                <form action="/admin/system/property/delete" method="post" class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>">
                    <input type="hidden" name="property_code" value="" data-property-delete-code>
                    <button type="button" data-property-delete-cancel class="min-h-11 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"><?= html_escape($labels['property_cancel']) ?></button>
                    <button type="submit" data-property-delete-confirm class="min-h-11 rounded-lg bg-rose-600 px-4 text-sm font-semibold text-white hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2"><?= html_escape($labels['property_confirm_delete']) ?></button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</main>
<script>
(function () {
    var filterForm = document.querySelector('[data-property-live-filter]');
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

    var dialog = document.getElementById('property-delete-dialog');
    var opener = null;
    var cancelButton = dialog ? dialog.querySelector('[data-property-delete-cancel]') : null;
    var propertyLabel = dialog ? dialog.querySelector('[data-property-delete-label]') : null;
    var propertyCode = dialog ? dialog.querySelector('[data-property-delete-code]') : null;
    var actionMenus = Array.prototype.slice.call(document.querySelectorAll('[data-property-actions-menu]'));

    function actionToggle(menu) {
        return document.querySelector('[aria-controls="' + menu.id + '"][data-property-actions-toggle]');
    }

    function closeActionMenus() {
        actionMenus.forEach(function (menu) {
            menu.hidden = true;
            var toggle = actionToggle(menu);
            if (toggle) { toggle.setAttribute('aria-expanded', 'false'); }
        });
    }

    function openActionMenu(toggle, menu) {
        closeActionMenus();
        menu.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        var triggerRect = toggle.getBoundingClientRect();
        var menuRect = menu.getBoundingClientRect();
        var top = triggerRect.bottom + 4;
        if (top + menuRect.height > window.innerHeight - 8 && triggerRect.top - menuRect.height - 4 >= 8) {
            top = triggerRect.top - menuRect.height - 4;
        }
        var left = Math.min(Math.max(8, triggerRect.right - menuRect.width), window.innerWidth - menuRect.width - 8);
        menu.style.top = Math.max(8, top) + 'px';
        menu.style.left = Math.max(8, left) + 'px';
        var firstItem = menu.querySelector('[role="menuitem"]');
        if (firstItem) { firstItem.focus(); }
    }

    function closeDialog() {
        if (!dialog) { return; }
        dialog.hidden = true;
        dialog.classList.remove('flex');
        if (opener) { opener.focus(); }
    }

    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-property-actions-toggle]');
        if (toggle) {
            var menu = document.getElementById(toggle.getAttribute('aria-controls'));
            if (menu && menu.hidden) { openActionMenu(toggle, menu); }
            else {
                closeActionMenus();
                toggle.focus();
            }
            return;
        }

        var menuItem = event.target.closest('[data-property-actions-menu] [role="menuitem"]');
        if (menuItem || !event.target.closest('[data-property-actions-menu]')) { closeActionMenus(); }
        if (!dialog) { return; }

        var openButton = event.target.closest('[data-property-delete-open]');
        if (openButton) {
            var menuContainer = openButton.closest('[data-property-actions-menu]');
            opener = menuContainer ? actionToggle(menuContainer) : openButton;
            propertyLabel.textContent = openButton.getAttribute('data-property-name') || openButton.getAttribute('data-property-code') || '';
            propertyCode.value = openButton.getAttribute('data-property-code') || '';
            dialog.hidden = false;
            dialog.classList.add('flex');
            cancelButton.focus();
            return;
        }
        if (event.target.closest('[data-property-delete-cancel]')) { closeDialog(); }
    });

    document.addEventListener('keydown', function (event) {
        var currentItem = event.target.closest('[data-property-actions-menu] [role="menuitem"]');
        if (currentItem && (event.key === 'ArrowDown' || event.key === 'ArrowUp' || event.key === 'Home' || event.key === 'End')) {
            event.preventDefault();
            var items = Array.prototype.slice.call(currentItem.closest('[role="menu"]').querySelectorAll('[role="menuitem"]'));
            var currentIndex = items.indexOf(currentItem);
            var nextIndex = event.key === 'Home' ? 0 : event.key === 'End' ? items.length - 1 : (currentIndex + (event.key === 'ArrowDown' ? 1 : items.length - 1)) % items.length;
            items[nextIndex].focus();
            return;
        }
        if (event.key === 'Escape' && event.target.closest('[data-property-actions-menu]')) {
            var menu = event.target.closest('[data-property-actions-menu]');
            var toggle = actionToggle(menu);
            closeActionMenus();
            if (toggle) { toggle.focus(); }
            event.preventDefault();
            return;
        }
        if (event.key === 'Tab' && event.target.closest('[data-property-actions-menu]')) { closeActionMenus(); }
    });

    document.addEventListener('scroll', closeActionMenus, true);
    window.addEventListener('resize', closeActionMenus);

    if (!dialog) { return; }
    dialog.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') { event.preventDefault(); closeDialog(); return; }
        if (event.key === 'Tab') {
            var focusable = Array.prototype.slice.call(dialog.querySelectorAll('button:not([disabled])'));
            if (!focusable.length) { return; }
            var first = focusable[0];
            var last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
})();
</script>
