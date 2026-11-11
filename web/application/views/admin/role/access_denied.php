<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$message_key = isset($role_access_message_key) && isset($labels[$role_access_message_key])
    ? $role_access_message_key
    : (isset($role_access_message_key) && isset($labels['role_' . $role_access_message_key])
        ? 'role_' . $role_access_message_key
        : 'role_no_access');
?>
<main class="mx-auto w-full max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
    <section role="alert" class="rounded-2xl border border-rose-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex items-start gap-4">
            <span aria-hidden="true" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-700">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6v4m0 3h.01M3.8 16.2h12.4a1 1 0 0 0 .87-1.49L10.87 3.8a1 1 0 0 0-1.74 0L2.93 14.71a1 1 0 0 0 .87 1.49Z"/></svg>
            </span>
            <div>
                <h1 class="text-xl font-semibold text-slate-950"><?= html_escape($labels['role_heading']) ?></h1>
                <p class="mt-2 text-sm leading-6 text-slate-700"><?= html_escape($labels[$message_key]) ?></p>
                <a href="/admin" class="mt-5 inline-flex min-h-10 items-center gap-2 rounded-lg bg-indigo-700 px-4 text-sm font-semibold text-white hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M11.5 4.5 6 10l5.5 5.5M6.5 10H17"/></svg>
                    <?= html_escape($labels['back_home']) ?>
                </a>
            </div>
        </div>
    </section>
</main>
