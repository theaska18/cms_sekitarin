<?php
$notifications_heading = isset($notifications_heading) ? $notifications_heading : (isset($admin_notifications_heading) ? $admin_notifications_heading : 'Notifications');
$notifications_intro = isset($notifications_intro) ? $notifications_intro : '';
$notifications_unavailable = isset($notifications_unavailable) ? $notifications_unavailable : '';
$notifications_empty = isset($notifications_empty) ? $notifications_empty : '';
$notifications_empty_hint = isset($notifications_empty_hint) ? $notifications_empty_hint : '';
$notifications_pagination = isset($notifications_pagination) ? $notifications_pagination : '';
$notifications_previous = isset($notifications_previous) ? $notifications_previous : '';
$notifications_next = isset($notifications_next) ? $notifications_next : '';
$notifications = isset($notifications) && is_array($notifications) ? $notifications : array();
$notifications_page = isset($notifications_page) ? (int) $notifications_page : 1;
$notifications_total_pages = isset($notifications_total_pages) ? (int) $notifications_total_pages : 1;
$notifications_error = !empty($notifications_error);
?>
<section class="mx-auto w-full max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <header class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[.14em] text-indigo-600">CMSNivo</p>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900"><?php echo html_escape($notifications_heading); ?></h1>
        <p class="mt-2 text-sm text-slate-500"><?php echo html_escape($notifications_intro); ?></p>
    </header>

    <?php if ($notifications_error): ?>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="status">
            <?php echo html_escape($notifications_unavailable); ?>
        </div>
    <?php elseif (empty($notifications)): ?>
        <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-sm">
            <svg class="mx-auto h-10 w-10 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
            <h2 class="mt-3 text-base font-semibold text-slate-800"><?php echo html_escape($notifications_empty); ?></h2>
            <p class="mt-1 text-sm text-slate-500"><?php echo html_escape($notifications_empty_hint); ?></p>
        </div>
    <?php else: ?>
        <ol class="divide-y divide-slate-200 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <?php foreach ($notifications as $notification): ?>
                <li class="flex gap-4 p-4 sm:p-5">
                    <span class="mt-0.5 grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-indigo-50 text-indigo-700" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m6.36.64-1.42 1.42M21 12h-2m-1.64 6.36-1.42-1.42M12 19v2m-6.36-2.64 1.42-1.42M3 12h2m.64-6.36 1.42 1.42"/><circle cx="12" cy="12" r="4"/></svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <?php if ($notification['title'] !== ''): ?><h2 class="font-semibold text-slate-900"><?php echo html_escape($notification['title']); ?></h2><?php endif; ?>
                        <?php if ($notification['message'] !== ''): ?><p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-600"><?php echo html_escape($notification['message']); ?></p><?php endif; ?>
                        <?php if ($notification['title'] === '' && $notification['message'] === ''): ?><p class="text-sm text-slate-500">#<?php echo (int) $notification['id']; ?></p><?php endif; ?>
                        <?php if ($notification['created_at'] !== ''): ?><time class="mt-2 block text-xs text-slate-400" datetime="<?php echo html_escape($notification['created_at']); ?>"><?php echo html_escape($notification['created_at']); ?></time><?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
        <?php if ($notifications_total_pages > 1): ?>
            <nav class="mt-5 flex items-center justify-between gap-3" aria-label="<?php echo html_escape($notifications_pagination); ?>">
                <span class="text-sm text-slate-500"><?php echo (int) $notifications_page; ?> / <?php echo (int) $notifications_total_pages; ?></span>
                <div class="flex gap-2">
                    <?php if ($notifications_page > 1): ?><a class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" href="/admin/notifications?page=<?php echo (int) ($notifications_page - 1); ?>"><?php echo html_escape($notifications_previous); ?></a><?php endif; ?>
                    <?php if ($notifications_page < $notifications_total_pages): ?><a class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" href="/admin/notifications?page=<?php echo (int) ($notifications_page + 1); ?>"><?php echo html_escape($notifications_next); ?></a><?php endif; ?>
                </div>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>
