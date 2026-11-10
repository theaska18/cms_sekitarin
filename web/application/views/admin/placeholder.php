<div class="mb-6">
    <p class="text-xs font-bold uppercase tracking-[.16em] text-emerald-700"><?php echo html_escape($labels['admin_area']); ?></p>
    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl"><?php echo html_escape($page_title); ?></h1>
    <p class="mt-2 text-sm text-slate-500"><span class="font-medium"><?php echo html_escape($labels['path']); ?>:</span> <code class="rounded bg-slate-100 px-2 py-1 text-slate-700"><?php echo html_escape($current_path); ?></code></p>
</div>
<section class="max-w-3xl rounded-2xl border border-amber-200 bg-white p-5 shadow-sm sm:p-7" aria-labelledby="placeholder-heading">
    <div class="flex items-start gap-4">
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-amber-50 text-xl text-amber-800 ring-1 ring-amber-200" aria-hidden="true">i</span>
        <div>
            <h2 id="placeholder-heading" class="text-lg font-bold text-amber-950"><?php echo html_escape($labels['placeholder_title']); ?></h2>
            <p class="mt-2 leading-6 text-amber-900"><?php echo html_escape($labels['placeholder_body']); ?></p>
            <a href="/admin" class="mt-5 inline-flex min-h-11 items-center rounded-lg bg-emerald-800 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2"><?php echo html_escape($labels['dashboard']); ?></a>
        </div>
    </div>
</section>
