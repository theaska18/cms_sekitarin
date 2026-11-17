<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <p class="text-xs font-bold uppercase tracking-[.16em] text-emerald-700"><?php echo html_escape($labels['admin_area']); ?></p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl"><?php echo html_escape($labels['dashboard']); ?></h1>
    </div>
    <a href="/" class="inline-flex min-h-11 items-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-emerald-200 hover:bg-emerald-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 sm:hidden"><?php echo html_escape($labels['back_home']); ?></a>
</div>
<section class="relative isolate overflow-hidden rounded-3xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-emerald-100 p-5 shadow-sm sm:p-8 lg:p-10" aria-labelledby="welcome-heading">
    <div class="absolute -right-12 -top-16 -z-10 h-56 w-56 rounded-full border-[24px] border-emerald-100/70" aria-hidden="true"></div>
    <div class="grid gap-7 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
    <div class="max-w-3xl">
        <p class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white/80 px-3 py-1 text-xs font-bold uppercase tracking-[.12em] text-emerald-800">
            <span class="h-2 w-2 rounded-full bg-emerald-500" aria-hidden="true"></span><?php echo html_escape($web_name); ?>
        </p>
        <h2 id="welcome-heading" class="mt-4 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl"><?php echo html_escape($labels['welcome_title']); ?></h2>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base"><?php echo html_escape($labels['welcome_body']); ?></p>
    </div>
    <?php if (!empty($dashboard_tenant_name)): ?>
        <div class="min-w-0 rounded-2xl border border-white/90 bg-white/80 px-4 py-3 shadow-sm backdrop-blur sm:px-5">
            <p class="text-xs font-bold uppercase tracking-[.12em] text-slate-500">Tenant</p>
            <p class="mt-1 break-words text-base font-bold text-slate-900 sm:text-lg"><?php echo html_escape($dashboard_tenant_name); ?></p>
        </div>
    <?php endif; ?>
    </div>
</section>
<section class="mt-8" aria-labelledby="popular-menus-heading">
    <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
        <p class="text-sm font-medium text-slate-500"><?php echo html_escape($labels['popular_menu_description']); ?></p>
        <h2 id="popular-menus-heading" class="mt-1 text-lg font-bold tracking-tight text-slate-900"><?php echo html_escape($labels['popular_menu_heading']); ?></h2>
    </div>
    <?php if (!empty($popular_menu_items)): ?>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <?php foreach ($popular_menu_items as $popular_item): ?>
                <a href="<?php echo html_escape($popular_item['route']); ?>" class="group flex min-h-20 items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-emerald-300 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2">
                    <span class="flex min-w-0 items-center gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-800 ring-1 ring-emerald-100" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 17l6-6 4 4 8-9M15 6h6v6"/></svg></span>
                        <span class="truncate text-sm font-bold text-slate-800 group-hover:text-emerald-800"><?php echo html_escape($popular_item['label']); ?></span>
                    </span>
                    <span class="shrink-0 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800"><?php echo html_escape(number_format($popular_item['visit_count'])); ?> <?php echo html_escape($labels['popular_menu_visits']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-6 text-sm text-slate-500"><?php echo html_escape($labels['popular_menu_empty']); ?></p>
    <?php endif; ?>
</section>
