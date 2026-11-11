<main class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
    <section role="alert" class="rounded-2xl border border-rose-200 bg-rose-50 p-6 text-rose-950 shadow-sm">
        <div class="flex items-start gap-3">
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="mt-0.5 h-6 w-6 shrink-0"><circle cx="10" cy="10" r="7.5"/><path stroke-linecap="round" d="M10 6v4.5m0 3h.01"/></svg>
            <div>
                <h1 class="text-xl font-semibold"><?= html_escape(isset($tenant_title) ? $tenant_title : $labels['tenant_heading']) ?></h1>
                <p class="mt-2"><?= html_escape($labels[$tenant_access_message_key]) ?></p>
            </div>
        </div>
        <a href="/admin" class="mt-5 inline-flex min-h-10 items-center rounded-lg border border-rose-300 bg-white px-4 text-sm font-semibold text-rose-900 hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-500"><?= html_escape($labels['tenant_back']) ?></a>
    </section>
</main>
