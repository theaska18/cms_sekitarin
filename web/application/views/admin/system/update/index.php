<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$check = isset($update_check) && is_array($update_check) ? $update_check : array();
$release = isset($check['release']) && is_array($check['release']) ? $check['release'] : NULL;
$available = !empty($check['ok']) && !empty($check['update_available']) && $release !== NULL;
$current_version = isset($check['current_version']) ? (string) $check['current_version'] : '—';
$latest_version = isset($check['latest_version']) ? (string) $check['latest_version'] : '—';
$notice = isset($update_notice) ? (string) $update_notice : '';
$result = isset($update_result) && is_array($update_result) ? $update_result : NULL;
$active = isset($update_active) && is_array($update_active) ? $update_active : NULL;
$history = isset($update_history) && is_array($update_history) ? $update_history : array();
$history_migrations = isset($update_history_migrations) && is_array($update_history_migrations) ? $update_history_migrations : array();
$checks = isset($update_checks) && is_array($update_checks) ? $update_checks : array();
$runner_ready = !empty($checks['writable']) && !empty($checks['archive']) && !empty($checks['exec']) && !empty($checks['mysql_client']);
$schema_available = !empty($update_schema_available);
$can_run = !empty($update_can_run) && $available && $runner_ready && $active === NULL && $schema_available;
$error_detail = isset($check['detail']) && $check['detail'] !== '' ? (string) $check['detail'] : '';

$status_labels = array(
    'pending' => 'update_status_pending',
    'downloading' => 'update_status_downloading',
    'backing_up' => 'update_status_backing_up',
    'installing' => 'update_status_installing',
    'migrating' => 'update_status_migrating',
    'success' => 'update_status_success',
    'failed' => 'update_status_failed',
    'rolled_back' => 'update_status_rolled_back',
);
$status_classes = array(
    'pending' => 'border-slate-200 bg-slate-100 text-slate-700',
    'downloading' => 'border-sky-200 bg-sky-50 text-sky-800',
    'backing_up' => 'border-amber-200 bg-amber-50 text-amber-900',
    'installing' => 'border-indigo-200 bg-indigo-50 text-indigo-800',
    'migrating' => 'border-violet-200 bg-violet-50 text-violet-800',
    'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
    'failed' => 'border-rose-200 bg-rose-50 text-rose-800',
    'rolled_back' => 'border-orange-200 bg-orange-50 text-orange-900',
);

if (!empty($access_denied)) {
    ?>
    <main class="-mx-4 w-[calc(100%+2rem)] px-4 py-8 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 lg:px-8">
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-5 text-sm font-medium text-rose-900">
            <?= html_escape($labels['update_access_denied']) ?>
        </div>
    </main>
    <?php return; ?>
<?php }
?>
<style>
.update-page{--update-ink:#172033;--update-muted:#64748b;--update-line:#e2e8f0;--update-surface:#fff;color:var(--update-ink)}
html[data-theme="dark"] .update-page,body[data-theme="dark"] .update-page{--update-ink:#e6eaf2;--update-muted:#a3afc2;--update-line:#303a4b;--update-surface:#151c29}
html[data-theme="dark"] .update-card,body[data-theme="dark"] .update-card{background:var(--update-surface);border-color:var(--update-line)}
html[data-theme="dark"] .update-page h1,html[data-theme="dark"] .update-page h2,html[data-theme="dark"] .update-page h3,body[data-theme="dark"] .update-page h1,body[data-theme="dark"] .update-page h2,body[data-theme="dark"] .update-page h3{color:#eef2f8}
html[data-theme="dark"] .update-page .update-muted,body[data-theme="dark"] .update-page .update-muted{color:var(--update-muted)}
html[data-theme="dark"] .update-page .update-soft,body[data-theme="dark"] .update-page .update-soft{background:#1b2433;border-color:#344055;color:#d5dbea}
html[data-theme="dark"] .update-page .update-table th,body[data-theme="dark"] .update-page .update-table th{background:#1b2433;color:#a3afc2}
html[data-theme="dark"] .update-page .update-table td,body[data-theme="dark"] .update-page .update-table td{border-color:#2b3545;color:#d5dbea}
html[data-theme="dark"] .update-page .update-log,body[data-theme="dark"] .update-page .update-log{background:#0f172a;border-color:#303a4b;color:#c7d2e5}
</style>
<main class="update-page -mx-4 w-[calc(100%+2rem)] px-4 py-6 sm:mx-auto sm:w-full sm:max-w-7xl sm:px-6 sm:py-8 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-5 text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-2">
            <li><?= html_escape($labels['system']) ?></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" class="font-medium text-slate-800"><?= html_escape($labels['update_heading']) ?></li>
        </ol>
    </nav>

    <header class="mb-8 max-w-3xl">
        <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl"><?= html_escape($labels['update_heading']) ?></h1>
        <p class="mt-2 text-base leading-7 text-slate-600 update-muted"><?= html_escape($labels['update_intro']) ?></p>
    </header>

    <?php if ($notice !== ''): ?>
        <?php
        $notice_is_error = in_array($notice, array('check_failed', 'update_failed'), TRUE);
        $notice_key = 'update_notice_' . $notice;
        $notice_text = isset($labels[$notice_key]) ? $labels[$notice_key] : $notice;
        ?>
        <div role="status" class="mb-5 flex items-start gap-3 rounded-xl border p-4 text-sm <?= $notice_is_error ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900' ?>">
            <span aria-hidden="true" class="mt-0.5 text-base"><?= $notice_is_error ? '!' : '✓' ?></span>
            <p><?= html_escape($notice_text) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($active !== NULL): ?>
        <div role="status" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <p class="font-semibold"><?= html_escape($labels['update_active_title']) ?></p>
            <p class="mt-1"><?= html_escape($labels['update_active_body']) ?></p>
            <p class="mt-2 font-mono text-xs">v<?= html_escape((string) $active['version']) ?> · <?= html_escape((string) $active['status']) ?></p>
        </div>
    <?php endif; ?>

    <?php if (!$schema_available): ?>
        <div role="alert" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <p><?= html_escape($labels['update_schema_unavailable']) ?></p>
            <?php if (!empty($update_can_run)): ?>
                <p class="mt-2"><?= html_escape($labels['update_setup_schema_hint']) ?></p>
                <form method="post" action="/admin/system/update/schema" class="mt-3">
                    <input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>">
                    <button type="submit" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-amber-700 px-4 text-sm font-semibold text-white shadow-sm hover:bg-amber-800 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10 3v10m0 0 4-4m-4 4-4-4M4 17h12"/></svg>
                        <?= html_escape($labels['update_setup_schema']) ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <section aria-labelledby="update-overview-heading" class="update-card mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        <h2 id="update-overview-heading" class="sr-only"><?= html_escape($labels['update_heading']) ?></h2>
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="update-soft rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 update-muted"><?= html_escape($labels['update_current_version']) ?></p>
                <p class="mt-1 text-2xl font-bold tracking-tight text-slate-950">v<?= html_escape($current_version) ?></p>
            </div>
            <div class="update-soft rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 update-muted"><?= html_escape($labels['update_latest_version']) ?></p>
                <p class="mt-1 text-2xl font-bold tracking-tight text-slate-950">v<?= html_escape($latest_version) ?></p>
            </div>
            <div class="update-soft rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 update-muted"><?= html_escape($labels['update_status']) ?></p>
                <p class="mt-1 text-sm font-semibold <?= $available ? 'text-amber-800' : 'text-emerald-800' ?>">
                    <?= html_escape($available ? $labels['update_available_title'] : $labels['update_up_to_date']) ?>
                </p>
            </div>
        </div>

        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="break-all text-xs text-slate-500 update-muted">
                <span class="font-semibold"><?= html_escape($labels['update_source']) ?>:</span>
                <code><?= html_escape($update_link) ?></code>
            </p>
            <form method="post" action="/admin/system/update/check">
                <input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>">
                <button type="submit" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M16 10a6 6 0 1 1-1.8-4.3M16 4v4h-4"/></svg>
                    <?= html_escape($labels['update_check']) ?>
                </button>
            </form>
        </div>
    </section>

    <?php if (!empty($check['ok']) && !$available): ?>
        <section class="update-card mb-6 rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-bold text-emerald-950"><?= html_escape($labels['update_up_to_date']) ?></h2>
            <p class="mt-1 text-sm text-emerald-900"><?= html_escape($labels['update_up_to_date_body']) ?></p>
        </section>
    <?php elseif (empty($check['ok']) && !empty($check['error'])): ?>
        <section class="update-card mb-6 rounded-2xl border border-rose-200 bg-rose-50/60 p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-bold text-rose-950"><?= html_escape($labels['update_notice_check_failed']) ?></h2>
            <?php $error_key = 'update_error_' . (string) $check['error']; ?>
            <p class="mt-1 text-sm text-rose-900"><?= html_escape(isset($labels[$error_key]) ? $labels[$error_key] : (string) $check['error']) ?></p>
            <?php if ($error_detail !== ''): ?>
                <p class="mt-2 break-all font-mono text-xs text-rose-800"><?= html_escape($error_detail) ?></p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($available): ?>
        <section aria-labelledby="update-release-heading" class="update-card mb-6 rounded-2xl border border-indigo-200 bg-white p-5 shadow-sm sm:p-7">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-[.16em] text-indigo-600"><?= html_escape($labels['update_available_title']) ?></p>
                    <h2 id="update-release-heading" class="mt-1 text-2xl font-bold tracking-tight text-slate-950">v<?= html_escape((string) $release['version']) ?></h2>
                    <?php if (!empty($release['released_at'])): ?>
                        <p class="mt-1 text-sm text-slate-500 update-muted"><?= html_escape($labels['update_released']) ?>: <?= html_escape((string) $release['released_at']) ?></p>
                    <?php endif; ?>
                </div>
                <?php if (!empty($update_can_run)): ?>
                    <form method="post" action="/admin/system/update/run" data-web-confirm data-web-confirm-title="<?= html_escape($labels['update_run']) ?>" data-web-confirm-message="<?= html_escape($labels['update_confirm_run']) ?>" data-web-confirm-accept="<?= html_escape($labels['update_run']) ?>">
                        <input type="hidden" name="<?= html_escape($csrf_token_name) ?>" value="<?= html_escape($csrf_hash) ?>">
                        <button type="submit" <?= $can_run ? '' : 'disabled aria-disabled="true"' ?> class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500">
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M10 3v10m0 0 4-4m-4 4-4-4M4 17h12"/></svg>
                            <?= html_escape($labels['update_run']) ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <p class="mt-4 text-sm leading-6 text-slate-600 update-muted"><?= html_escape(sprintf($labels['update_available_body'], (string) $release['version'])) ?></p>

            <?php if (!empty($release['changelog'])): ?>
                <div class="mt-5 rounded-xl border border-slate-200 p-4 update-soft">
                    <h3 class="text-sm font-semibold text-slate-800"><?= html_escape($labels['update_changelog']) ?></h3>
                    <div class="mt-1 text-sm leading-6 text-slate-700 update-muted"><?= nl2br(html_escape((string) $release['changelog'])) ?></div>
                </div>
            <?php endif; ?>

            <div class="mt-5">
                <h3 class="text-sm font-semibold text-slate-800"><?= html_escape($labels['update_migrations']) ?></h3>
                <?php if (!empty($release['migrations']) && is_array($release['migrations'])): ?>
                    <ul class="mt-2 space-y-1 font-mono text-xs text-slate-600 update-muted">
                        <?php foreach ($release['migrations'] as $migration): ?>
                            <li><?= html_escape((string) $migration) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="mt-1 text-sm text-slate-500 update-muted"><?= html_escape($labels['update_no_migrations']) ?></p>
                <?php endif; ?>
            </div>

            <?php if (!$runner_ready): ?>
                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
                    <p class="font-semibold"><?= html_escape($labels['update_cannot_run']) ?></p>
                    <p class="mt-1"><?= html_escape($labels['update_runner_unavailable']) ?></p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        <?php if (empty($checks['writable'])): ?><li><?= html_escape($labels['update_runner_writable']) ?></li><?php endif; ?>
                        <?php if (empty($checks['archive'])): ?><li><?= html_escape($labels['update_runner_archive']) ?></li><?php endif; ?>
                        <?php if (empty($checks['exec']) || empty($checks['mysql_client'])): ?><li><?= html_escape($labels['update_runner_mysql']) ?></li><?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($result !== NULL): ?>
        <section aria-labelledby="update-result-heading" class="update-card mb-6 rounded-2xl border p-5 shadow-sm sm:p-6 <?= !empty($result['ok']) ? 'border-emerald-200 bg-emerald-50/60' : 'border-rose-200 bg-rose-50/60' ?>">
            <h2 id="update-result-heading" class="text-lg font-bold <?= !empty($result['ok']) ? 'text-emerald-950' : 'text-rose-950' ?>">
                <?= html_escape(!empty($result['ok']) ? $labels['update_notice_updated'] : $labels['update_notice_update_failed']) ?>
            </h2>
            <?php if (empty($result['ok']) && !empty($result['error'])): ?>
                <?php $result_key = 'update_error_' . (string) $result['error']; ?>
                <p class="mt-1 text-sm <?= !empty($result['ok']) ? 'text-emerald-900' : 'text-rose-900' ?>"><?= html_escape(isset($labels[$result_key]) ? $labels[$result_key] : (string) $result['error']) ?></p>
            <?php endif; ?>
            <details class="mt-4">
                <summary class="cursor-pointer text-sm font-semibold text-slate-700"><?= html_escape($labels['update_log']) ?></summary>
                <pre class="update-log mt-2 max-h-80 overflow-auto rounded-xl border border-slate-200 bg-slate-950 p-4 text-xs leading-5 text-slate-100"><?= html_escape(!empty($result['log']) ? (string) $result['log'] : $labels['update_no_log']) ?></pre>
            </details>
        </section>
    <?php endif; ?>

    <section aria-labelledby="update-history-heading" class="update-card rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-5 sm:p-6">
            <h2 id="update-history-heading" class="text-lg font-bold text-slate-950"><?= html_escape($labels['update_history']) ?></h2>
            <p class="mt-1 text-sm text-slate-500 update-muted"><?= html_escape($labels['update_history_intro']) ?></p>
        </div>
        <div class="overflow-x-auto">
            <table class="update-table min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="whitespace-nowrap px-4 py-3"><?= html_escape($labels['update_version']) ?></th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3"><?= html_escape($labels['update_previous_version']) ?></th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3"><?= html_escape($labels['update_status']) ?></th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3"><?= html_escape($labels['update_started']) ?></th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3"><?= html_escape($labels['update_finished']) ?></th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3"><?= html_escape($labels['update_migrations']) ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (count($history) > 0): ?>
                        <?php foreach ($history as $entry): ?>
                            <?php
                            $status = isset($entry['status']) ? (string) $entry['status'] : '';
                            $status_text = isset($status_labels[$status], $labels[$status_labels[$status]])
                                ? $labels[$status_labels[$status]] : $status;
                            $status_class = isset($status_classes[$status]) ? $status_classes[$status] : $status_classes['pending'];
                            $update_id = (int) $entry['update_id'];
                            $migrations = isset($history_migrations[$update_id]) ? $history_migrations[$update_id] : array();
                            ?>
                            <tr>
                                <th scope="row" class="whitespace-nowrap px-4 py-3 font-mono text-xs font-semibold text-slate-800">v<?= html_escape((string) $entry['version']) ?></th>
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-600">v<?= html_escape((string) $entry['previous_version']) ?></td>
                                <td class="whitespace-nowrap px-4 py-3"><span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold <?= $status_class ?>"><?= html_escape($status_text) ?></span></td>
                                <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600"><?= html_escape((string) $entry['started_on']) ?></td>
                                <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600"><?= html_escape((string) $entry['finished_on']) ?></td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    <?php if ($migrations): ?>
                                        <details>
                                            <summary class="cursor-pointer font-semibold text-indigo-700"><?= count($migrations) ?></summary>
                                            <ul class="mt-1 space-y-1 font-mono text-[11px]">
                                                <?php foreach ($migrations as $migration): ?>
                                                    <li><?= html_escape((string) $migration['migration_name']) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </details>
                                    <?php else: ?>
                                        <span class="text-slate-400">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500"><?= html_escape($labels['update_no_history']) ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
