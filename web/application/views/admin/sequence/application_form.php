<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$sequence_record = is_array($sequence_record) ? $sequence_record : array();
$sequence_errors = is_array($sequence_errors) ? $sequence_errors : array();
$error_labels = array();
foreach ($sequence_errors as $error) {
    $key = 'sequence_' . $error;
    if (isset($labels[$key])) $error_labels[] = $labels[$key];
}
?>
<main class="px-0 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-2">
            <li><?= html_escape($labels['application']) ?></li>
            <li aria-hidden="true">/</li>
            <li><a href="/admin/application/sequence" class="hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"><?= html_escape($labels['sequence_heading']) ?></a></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" class="font-medium text-slate-800"><?= html_escape($labels['sequence_form_edit']) ?></li>
        </ol>
    </nav>
    <header class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight text-slate-950"><?= html_escape($labels['sequence_form_edit']) ?></h1>
        <p class="mt-2 text-base leading-7 text-slate-600"><?= html_escape($labels['sequence_application_form_intro']) ?></p>
    </header>

    <?php if (empty($sequence_record['configured'])): ?>
        <div role="status" class="mb-5 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-950">
            <?= html_escape($labels['sequence_application_not_configured']) ?>
        </div>
    <?php endif; ?>
    <?php if ($error_labels): ?>
        <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
            <p class="font-semibold"><?= html_escape($labels['sequence_invalid']) ?></p>
            <ul class="mt-2 list-inside list-disc"><?php foreach ($error_labels as $error_label): ?><li><?= html_escape($error_label) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form action="<?= html_escape($current_path) ?>" method="post" class="rounded-t-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-8">
        <input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>">
        <div class="space-y-6">
            <div>
                <label for="application-sequence-code" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['sequence_sequence_code']) ?></label>
                <input id="application-sequence-code" type="text" readonly value="<?= html_escape($sequence_record['sequence_code']) ?>" class="min-h-12 w-full max-w-xl cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-600">
            </div>
            <div>
                <label for="application-sequence-name" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['sequence_sequence_name']) ?></label>
                <input id="application-sequence-name" type="text" readonly value="<?= html_escape($sequence_record['sequence_name']) ?>" class="min-h-12 w-full max-w-xl cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-600">
            </div>
            <div>
                <label for="application-sequence-last-value" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['sequence_last_value']) ?></label>
                <input id="application-sequence-last-value" name="last_value" type="number" min="0" max="2147483647" step="1" inputmode="numeric" required value="<?= html_escape((string) $sequence_record['last_value']) ?>" <?= $sequence_write_available ? '' : 'disabled' ?> class="min-h-12 w-full max-w-[12rem] rounded-lg border border-slate-300 bg-white px-3 font-mono text-base text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500 sm:text-sm">
                <p class="mt-1.5 text-sm text-slate-500"><?= html_escape($labels['sequence_last_value_help']) ?></p>
            </div>
            <div>
                <label for="application-sequence-repeat-type" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['sequence_repeat_type']) ?></label>
                <select id="application-sequence-repeat-type" name="repeat_type" required <?= $sequence_write_available ? '' : 'disabled' ?> class="min-h-12 w-full max-w-sm rounded-lg border border-slate-300 bg-white px-3 text-base text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500 sm:text-sm">
                    <?php foreach ($sequence_repeat_type_options as $option): ?>
                        <?php if (isset($option['option_code'], $option['option_name'])): ?>
                            <option value="<?= html_escape($option['option_code']) ?>" <?= (string) $sequence_record['repeat_type'] === (string) $option['option_code'] ? 'selected' : '' ?>><?= html_escape($option['option_name']) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="application-sequence-format" class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['sequence_format']) ?></label>
                <input id="application-sequence-format" name="format" type="text" maxlength="45" required value="<?= html_escape($sequence_record['format']) ?>" <?= $sequence_write_available ? '' : 'disabled' ?> class="min-h-12 w-full max-w-xl rounded-lg border border-slate-300 bg-white px-3 text-base text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500 sm:text-sm">
                <p class="mt-1.5 text-sm text-slate-500"><?= html_escape($labels['sequence_format_help']) ?></p>
                <ul class="mt-2 grid gap-x-5 gap-y-1 rounded-lg bg-slate-50 p-3 text-sm text-slate-600 sm:grid-cols-2">
                    <li class="sm:col-span-2"><code class="rounded bg-white px-1.5 py-0.5 font-semibold text-slate-800">N</code> — <?= html_escape($labels['sequence_format_token_n']) ?></li>
                    <li><code class="rounded bg-white px-1.5 py-0.5 font-semibold text-slate-800">d</code> — <?= html_escape($labels['sequence_format_token_d']) ?></li>
                    <li><code class="rounded bg-white px-1.5 py-0.5 font-semibold text-slate-800">D</code> — <?= html_escape($labels['sequence_format_token_D']) ?></li>
                    <li><code class="rounded bg-white px-1.5 py-0.5 font-semibold text-slate-800">m</code> — <?= html_escape($labels['sequence_format_token_m']) ?></li>
                    <li><code class="rounded bg-white px-1.5 py-0.5 font-semibold text-slate-800">M</code> — <?= html_escape($labels['sequence_format_token_M']) ?></li>
                    <li><code class="rounded bg-white px-1.5 py-0.5 font-semibold text-slate-800">y</code> — <?= html_escape($labels['sequence_format_token_y']) ?></li>
                    <li><code class="rounded bg-white px-1.5 py-0.5 font-semibold text-slate-800">Y</code> — <?= html_escape($labels['sequence_format_token_Y']) ?></li>
                </ul>
            </div>
            <div>
                <span class="mb-1.5 block text-sm font-semibold text-slate-800"><?= html_escape($labels['sequence_use_flag']) ?></span>
                <label class="flex min-h-11 items-center gap-3 text-sm text-slate-700">
                    <input type="checkbox" name="use_flag" value="1" <?= (string) $sequence_record['use_flag'] === '1' ? 'checked' : '' ?> <?= $sequence_write_available && $sequence_auto_number_allowed ? '' : 'disabled' ?> class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 disabled:opacity-60">
                    <span><?= html_escape($labels['sequence_use_flag_help']) ?></span>
                </label>
                <?php if (!$sequence_auto_number_allowed): ?><p class="mt-1.5 text-sm text-slate-500"><?= html_escape($labels['sequence_auto_number_permission_required']) ?></p><?php endif; ?>
            </div>
        </div>
        <div class="mt-8 flex flex-col-reverse gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:justify-between">
            <a href="/admin/application/sequence" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"><?= html_escape($labels['sequence_back']) ?></a>
            <button type="submit" <?= $sequence_write_available ? '' : 'disabled aria-disabled="true"' ?> class="inline-flex min-h-11 items-center justify-center rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:bg-slate-300"><?= html_escape($labels['sequence_save']) ?></button>
        </div>
    </form>
</main>
