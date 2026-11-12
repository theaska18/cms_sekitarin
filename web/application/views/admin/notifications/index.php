<?php
$notifications_heading = isset($notifications_heading) ? $notifications_heading : '';
$notifications_intro = isset($notifications_intro) ? $notifications_intro : '';
$notifications_breadcrumb_parent = isset($notifications_breadcrumb_parent) ? $notifications_breadcrumb_parent : '';
$notifications_unavailable = isset($notifications_unavailable) ? $notifications_unavailable : '';
$notifications_empty = isset($notifications_empty) ? $notifications_empty : '';
$notifications_empty_hint = isset($notifications_empty_hint) ? $notifications_empty_hint : '';
$notifications_pagination = isset($notifications_pagination) ? $notifications_pagination : '';
$notifications_previous = isset($notifications_previous) ? $notifications_previous : '';
$notifications_next = isset($notifications_next) ? $notifications_next : '';
$notifications_select_prompt = isset($notifications_select_prompt) ? $notifications_select_prompt : '';
$notifications_select_hint = isset($notifications_select_hint) ? $notifications_select_hint : '';
$notifications_list_label = isset($notifications_list_label) ? $notifications_list_label : '';
$notifications_read = isset($notifications_read) ? $notifications_read : '';
$notifications_unread = isset($notifications_unread) ? $notifications_unread : '';
$notifications_time_just_now = isset($notifications_time_just_now) ? $notifications_time_just_now : '';
$notifications_time_units = array(
    'year' => isset($notifications_time_year) ? $notifications_time_year : '',
    'month' => isset($notifications_time_month) ? $notifications_time_month : '',
    'week' => isset($notifications_time_week) ? $notifications_time_week : '',
    'day' => isset($notifications_time_day) ? $notifications_time_day : '',
    'hour' => isset($notifications_time_hour) ? $notifications_time_hour : '',
    'minute' => isset($notifications_time_minute) ? $notifications_time_minute : ''
);
$notifications_time_ago = isset($notifications_time_ago) ? $notifications_time_ago : '';
$notifications = isset($notifications) && is_array($notifications) ? $notifications : array();
$notifications_page = isset($notifications_page) ? (int) $notifications_page : 1;
$notifications_total_pages = isset($notifications_total_pages) ? (int) $notifications_total_pages : 1;
$notifications_error = !empty($notifications_error);
$selected_notification_id = isset($selected_notification_id) ? (int) $selected_notification_id : 0;
$selected_notification = NULL;
foreach ($notifications as $notification) {
    if ((int) $notification['id'] === $selected_notification_id) {
        $selected_notification = $notification;
        break;
    }
}
$message_preview = function ($message) {
    $message = trim(preg_replace('/\\s+/u', ' ', strip_tags((string) $message)));
    if ($message === '') {
        return '';
    }
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($message, 'UTF-8') > 120 ? mb_substr($message, 0, 120, 'UTF-8') . '…' : $message;
    }
    return strlen($message) > 120 ? substr($message, 0, 120) . '…' : $message;
};
$relative_time = function ($value) use ($notifications_time_just_now, $notifications_time_units, $notifications_time_ago) {
    $timestamp = strtotime((string) $value);
    if ($timestamp === FALSE) {
        return '';
    }
    $seconds = max(0, time() - $timestamp);
    if ($seconds < 60) return $notifications_time_just_now;
    $units = array(
        31536000 => 'year', 2592000 => 'month', 604800 => 'week',
        86400 => 'day', 3600 => 'hour', 60 => 'minute'
    );
    foreach ($units as $size => $unit) {
        if ($seconds >= $size) {
            return (int) floor($seconds / $size) . ' ' . (isset($notifications_time_units[$unit]) ? $notifications_time_units[$unit] : '') . ' ' . $notifications_time_ago;
        }
    }
    return $notifications_time_just_now;
};
?>
<section class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <nav aria-label="<?php echo html_escape($notifications_heading); ?>" class="mb-5 text-sm text-slate-500"><ol class="flex flex-wrap items-center gap-2"><li><?php echo html_escape($notifications_breadcrumb_parent); ?></li><li aria-hidden="true">/</li><li aria-current="page" class="font-medium text-slate-800"><?php echo html_escape($notifications_heading); ?></li></ol></nav>
            <header class="mb-8"><h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl"><?php echo html_escape($notifications_heading); ?></h1><p class="mt-2 text-base leading-7 text-slate-600"><?php echo html_escape($notifications_intro); ?></p></header>

    <?php if ($notifications_error): ?>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="status">
            <?php echo html_escape($notifications_unavailable); ?>
        </div>
    <?php elseif (empty($notifications)): ?>
        <div class="grid min-h-80 place-items-center rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-sm">
            <div>
                <svg class="mx-auto h-10 w-10 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                <h2 class="mt-3 text-base font-semibold text-slate-800"><?php echo html_escape($notifications_empty); ?></h2>
                <p class="mt-1 text-sm text-slate-500"><?php echo html_escape($notifications_empty_hint); ?></p>
            </div>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 items-stretch gap-4 lg:grid-cols-[minmax(16rem,0.85fr)_minmax(0,1.65fr)]">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-label="<?php echo html_escape($notifications_list_label); ?>">
                <div class="border-b border-slate-200 px-4 py-4 sm:px-5">
                    <h2 class="text-sm font-semibold text-slate-800"><?php echo html_escape($notifications_list_label); ?></h2>
                </div>
                <ol class="max-h-[36rem] divide-y divide-slate-100 overflow-y-auto">
                    <?php foreach ($notifications as $notification): ?>
                        <?php
                        $is_selected = ((int) $notification['id'] === $selected_notification_id);
                        $is_read = in_array(strtolower((string) $notification['read_flag']), array('1', 'true', 'read', 'yes'), TRUE);
                        $status_label = $is_read ? $notifications_read : $notifications_unread;
                        ?>
                        <li>
                            <a href="/admin/notifications?selected=<?php echo (int) $notification['id']; ?>&page=<?php echo (int) $notifications_page; ?>" data-notification-unread="<?php echo $is_read ? 'false' : 'true'; ?>" class="notification-list-item block border-l-4 px-4 py-4 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-indigo-500 sm:px-5 <?php echo $is_selected ? 'border-indigo-600 bg-indigo-50/70' : 'border-transparent'; ?>"<?php echo $is_selected ? ' aria-current="true"' : ''; ?>>
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="min-w-0 flex-1 truncate text-sm font-semibold text-slate-900"><?php echo html_escape($notification['title'] !== '' ? $notification['title'] : '#' . (int) $notification['id']); ?></h3>
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 <?php echo $is_read ? 'text-slate-400' : 'text-indigo-600'; ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-label="<?php echo html_escape($status_label); ?>" role="img" title="<?php echo html_escape($status_label); ?>"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                </div>
                                <?php if ($notification['message'] !== ''): ?><p class="notification-message-preview mt-2 text-xs leading-5 text-slate-600"><?php echo html_escape($message_preview($notification['message'])); ?></p><?php endif; ?>
                                <time class="mt-2 block text-xs font-medium text-slate-500" datetime="<?php echo html_escape($notification['created_at']); ?>" title="<?php echo html_escape($notification['created_at']); ?>"><?php echo html_escape($relative_time($notification['created_at'])); ?></time>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ol>
                <?php if ($notifications_total_pages > 1): ?>
                    <nav class="flex items-center justify-between gap-3 border-t border-slate-200 px-4 py-3" aria-label="<?php echo html_escape($notifications_pagination); ?>">
                        <span class="text-sm text-slate-500"><?php echo (int) $notifications_page; ?> / <?php echo (int) $notifications_total_pages; ?></span>
                        <div class="flex gap-2">
                            <?php if ($notifications_page > 1): ?><a class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" href="/admin/notifications?page=<?php echo (int) ($notifications_page - 1); ?>"><?php echo html_escape($notifications_previous); ?></a><?php endif; ?>
                            <?php if ($notifications_page < $notifications_total_pages): ?><a class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" href="/admin/notifications?page=<?php echo (int) ($notifications_page + 1); ?>"><?php echo html_escape($notifications_next); ?></a><?php endif; ?>
                        </div>
                    </nav>
                <?php endif; ?>
            </section>

            <section class="min-h-80 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7" aria-live="polite">
                <?php if ($selected_notification !== NULL): ?>
                    <article>
                        <div class="flex items-start gap-3">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-indigo-50 text-indigo-700" aria-hidden="true">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path stroke-linecap="round" stroke-linejoin="round" d="M10 21h4"/></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <h2 class="text-lg font-semibold leading-snug text-slate-900"><?php echo html_escape($selected_notification['title'] !== '' ? $selected_notification['title'] : '#' . (int) $selected_notification['id']); ?></h2>
                                <?php $selected_is_read = in_array(strtolower((string) $selected_notification['read_flag']), array('1', 'true', 'read', 'yes'), TRUE); ?>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <?php if ($selected_notification['created_at'] !== ''): ?><time class="text-sm font-medium text-slate-500" datetime="<?php echo html_escape($selected_notification['created_at']); ?>" title="<?php echo html_escape($selected_notification['created_at']); ?>"><?php echo html_escape($relative_time($selected_notification['created_at'])); ?></time><?php endif; ?>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?php echo $selected_is_read ? 'bg-slate-100 text-slate-600' : 'bg-indigo-100 text-indigo-700'; ?>"><?php echo html_escape($selected_is_read ? $notifications_read : $notifications_unread); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6 border-t border-slate-100 pt-5">
                            <p class="whitespace-pre-line break-words text-sm leading-7 text-slate-700"><?php echo html_escape($selected_notification['message']); ?></p>
                        </div>
                    </article>
                <?php else: ?>
                    <div class="grid h-full min-h-64 place-items-center text-center">
                        <div>
                            <svg class="mx-auto h-10 w-10 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 3v6h5M9 13h6m-6 4h6"/></svg>
                            <h2 class="mt-3 text-base font-semibold text-slate-700"><?php echo html_escape($notifications_select_prompt); ?></h2>
                            <p class="mt-1 max-w-sm text-sm text-slate-500"><?php echo html_escape($notifications_select_hint); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    <?php endif; ?>
</section>
