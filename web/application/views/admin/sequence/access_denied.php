<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
    <section class="rounded-xl border border-rose-200 bg-rose-50/80 p-6 text-rose-950 shadow-sm" role="alert" aria-live="assertive">
        <?php $sequence_access_title_key = isset($sequence_access_title_key) ? $sequence_access_title_key : 'sequence_template_heading'; ?>
        <h1 class="text-lg font-semibold"><?= html_escape(isset($labels[$sequence_access_title_key]) ? $labels[$sequence_access_title_key] : 'Sequence Template') ?></h1>
        <p class="mt-2 text-sm"><?= html_escape(isset($labels['sequence_no_access']) ? $labels['sequence_no_access'] : 'You do not have access to this page.') ?></p>
        <a class="mt-5 inline-flex items-center rounded-md border border-rose-300 bg-white/80 px-3 py-2 text-sm font-medium text-rose-900 hover:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500" href="<?= html_escape(site_url('admin')) ?>">
            <?= html_escape(isset($labels['sequence_back']) ? $labels['sequence_back'] : 'Back') ?>
        </a>
    </section>
</div>
