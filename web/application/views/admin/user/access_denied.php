<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<main class="mx-auto max-w-3xl p-6 sm:p-8">
    <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-5 text-rose-900">
        <h1 class="text-lg font-semibold"><?= html_escape($labels['user_heading']) ?></h1>
        <p class="mt-2"><?= html_escape($labels['user_no_access']) ?></p>
    </div>
</main>
