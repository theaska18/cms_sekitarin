<?php
$module_title = isset($labels[$module['key']]) ? $labels[$module['key']] : $page_title;
$module_group = isset($labels[$module['group_key']]) ? $labels[$module['group_key']] : '';
$module_description = isset($labels[$module['description_key']]) ? $labels[$module['description_key']] : '';
$module_focus = isset($labels[$module['focus_key']]) ? $labels[$module['focus_key']] : '';
?>
<div class="space-y-6 py-1">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <p class="flex flex-wrap items-center gap-x-2 text-xs font-bold uppercase tracking-[.16em] text-emerald-700">
                <span><?php echo html_escape($module_group); ?></span>
                <span aria-hidden="true" class="text-slate-300">/</span>
                <span><?php echo html_escape($labels['module_overview']); ?></span>
            </p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl"><?php echo html_escape($module_title); ?></h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600"><?php echo html_escape($module_description); ?></p>
        </div>
        <span class="inline-flex min-h-10 shrink-0 items-center gap-2 self-start rounded-full border border-emerald-200 bg-emerald-50 px-3 text-xs font-semibold text-emerald-800">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l2.5 1.5M20 12a8 8 0 11-16 0 8 8 0 0116 0z" /></svg>
            <?php echo html_escape($labels['module_read_only']); ?>
        </span>
    </div>

    <section class="grid grid-cols-1 gap-5 xl:grid-cols-2" aria-label="<?php echo html_escape($module_title); ?>" aria-labelledby="module-overview-heading">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex items-start gap-4">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-800 ring-1 ring-emerald-100" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 11v5M12 8h.01" /></svg>
                </span>
                <div class="min-w-0">
                    <h2 id="module-overview-heading" class="text-base font-bold text-slate-900"><?php echo html_escape($labels['module_focus']); ?></h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600"><?php echo html_escape($module_focus); ?></p>
                </div>
            </div>
        </article>

        <aside class="rounded-2xl border border-amber-200 bg-amber-50/70 p-5 shadow-sm sm:p-6" aria-labelledby="module-status-heading">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="module-status-heading" class="text-base font-bold text-slate-900"><?php echo html_escape($labels['module_status']); ?></h2>
                <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-amber-900 ring-1 ring-amber-200"><?php echo html_escape($labels['module_read_only']); ?></span>
            </div>
            <div class="mt-4 flex items-start gap-3 text-sm leading-6 text-amber-950" role="status">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L2.6 17.2A2 2 0 004.3 20h15.4a2 2 0 001.7-2.8L13.7 3.9a2 2 0 00-3.4 0z" /></svg>
                <p><?php echo html_escape($labels['module_backend_unavailable']); ?></p>
            </div>
        </aside>
    </section>

    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm sm:flex sm:items-center sm:gap-3">
        <p class="text-xs font-bold uppercase tracking-[.14em] text-slate-500"><?php echo html_escape($labels['path']); ?></p>
        <code class="mt-1 block break-all text-sm text-slate-700 sm:mt-0"><?php echo html_escape($requested_path); ?></code>
    </div>
</div>
