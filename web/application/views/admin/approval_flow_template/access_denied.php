<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$approval_flow_template_access_message_key = isset($approval_flow_template_access_message_key)
    ? (string) $approval_flow_template_access_message_key
    : 'approval_flow_template_no_access';
$approval_flow_template_access_message = isset($labels[$approval_flow_template_access_message_key])
    ? $labels[$approval_flow_template_access_message_key]
    : $labels['approval_flow_template_no_access'];
?>
<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
    <section class="rounded-xl border border-rose-200 bg-rose-50/80 p-6 text-rose-950 shadow-sm" role="alert" aria-live="assertive">
        <h1 class="text-lg font-semibold"><?= html_escape($labels['approval_flow_template_heading']) ?></h1>
        <p class="mt-2 text-sm"><?= html_escape($approval_flow_template_access_message) ?></p>
        <a class="mt-5 inline-flex items-center rounded-md border border-rose-300 bg-white/80 px-3 py-2 text-sm font-medium text-rose-900 hover:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500" href="<?= html_escape(site_url('admin')) ?>">
            <?= html_escape($labels['approval_flow_template_back']) ?>
        </a>
    </section>
</div>
