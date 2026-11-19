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
$notifications_back = isset($notifications_back) ? $notifications_back : '';
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
$notifications_actions = isset($notifications_actions) ? $notifications_actions : 'Notification actions';
$notifications_delete = isset($notifications_delete) ? $notifications_delete : 'Delete';
$notifications_delete_confirm_title = isset($notifications_delete_confirm_title) ? $notifications_delete_confirm_title : 'Delete notification?';
$notifications_delete_confirm_body = isset($notifications_delete_confirm_body) ? $notifications_delete_confirm_body : 'This notification will be permanently deleted.';
$notifications_confirm_delete = isset($notifications_confirm_delete) ? $notifications_confirm_delete : 'Delete notification';
$notifications_cancel = isset($notifications_cancel) ? $notifications_cancel : 'Cancel';
$notifications_approval_view = isset($notifications_approval_view) ? $notifications_approval_view : 'View approval';
$notifications_search_label = isset($notifications_search_label) ? $notifications_search_label : 'Search';
$notifications_search_placeholder = isset($notifications_search_placeholder) ? $notifications_search_placeholder : 'Search title or message';
$notifications_filter_status = isset($notifications_filter_status) ? $notifications_filter_status : 'Status';
$notifications_filter_all = isset($notifications_filter_all) ? $notifications_filter_all : 'All';
$notifications_filter_unread = isset($notifications_filter_unread) ? $notifications_filter_unread : 'Unread';
$notifications_filter_read = isset($notifications_filter_read) ? $notifications_filter_read : 'Read';
$notifications_filter_apply = isset($notifications_filter_apply) ? $notifications_filter_apply : 'Apply';
$notifications_filter_reset = isset($notifications_filter_reset) ? $notifications_filter_reset : 'Reset';
$notifications_per_page_label = isset($notifications_per_page_label) ? $notifications_per_page_label : 'Per page';
$notifications_first = isset($notifications_first) ? $notifications_first : 'First';
$notifications_last = isset($notifications_last) ? $notifications_last : 'Last';
$notifications_no_results = isset($notifications_no_results) ? $notifications_no_results : 'No notifications match this filter.';
$notifications_no_results_hint = isset($notifications_no_results_hint) ? $notifications_no_results_hint : 'Try another search or reset the filter.';
$notifications = isset($notifications) && is_array($notifications) ? $notifications : array();
$notifications_search = isset($notifications_search) ? (string) $notifications_search : '';
$notifications_status = isset($notifications_status) ? (string) $notifications_status : 'all';
$notifications_per_page_value = isset($notifications_per_page) ? (int) $notifications_per_page : 20;
$notifications_page_sizes = isset($notifications_page_sizes) && is_array($notifications_page_sizes) ? $notifications_page_sizes : array(20, 50, 100);
$notifications_page = isset($notifications_page) ? (int) $notifications_page : 1;
$notifications_total_pages = isset($notifications_total_pages) ? (int) $notifications_total_pages : 1;
$notifications_error = !empty($notifications_error);
$selected_notification_id = isset($selected_notification_id) ? (int) $selected_notification_id : 0;
$selected_notification = isset($selected_notification) && is_array($selected_notification) ? $selected_notification : NULL;
if ($selected_notification === NULL) {
    foreach ($notifications as $notification) {
        if ((int) $notification['id'] === $selected_notification_id) {
            $selected_notification = $notification;
            break;
        }
    }
}
$approval_notification = function ($message) use ($notifications_approval_view) {
    $actions = array();
    $lines = preg_split('/\r?\n/', (string) $message);
    $body = array();
    $legacy_approval = FALSE;
    $expected_host = strtolower((string) parse_url(site_url(), PHP_URL_HOST));
    foreach ($lines as $line) {
        $line = trim($line);
        if (preg_match('/^Lihat:\s*(https?:\/\/\S+)$/u', $line, $matches)) {
            $parts = parse_url($matches[1]);
            $query = array();
            if (is_array($parts) && strtolower(isset($parts['host']) ? $parts['host'] : '') === $expected_host && isset($parts['path']) && $parts['path'] === '/admin/system/approval') {
                parse_str(isset($parts['query']) ? $parts['query'] : '', $query);
                if (isset($query['approval_token'], $query['approval_decision'])
                    && preg_match('/^[a-f0-9]{64}$/', $query['approval_token'])
                    && $query['approval_decision'] === 'view') {
                    $actions[] = array('label' => $notifications_approval_view, 'url' => $matches[1], 'decision' => 'view');
                    continue;
                }
            }
        }
        // Old notifications had separate direct approve/reject links. Replace
        // them with one neutral link to the approvals inbox, never action buttons.
        if (preg_match('/^(Setuju|Tidak setuju):\s*(https?:\/\/\S+)$/u', $line, $matches)) {
            $parts = parse_url($matches[2]);
            $query = array();
            if (is_array($parts) && strtolower(isset($parts['host']) ? $parts['host'] : '') === $expected_host
                && isset($parts['path']) && $parts['path'] === '/admin/system/approval') {
                parse_str(isset($parts['query']) ? $parts['query'] : '', $query);
                $expected_decision = $matches[1] === 'Setuju' ? 'approved' : 'rejected';
                if (isset($query['approval_token'], $query['approval_decision'])
                    && preg_match('/^[a-f0-9]{64}$/', $query['approval_token'])
                    && $query['approval_decision'] === $expected_decision) {
                    $legacy_approval = TRUE;
                    continue;
                }
            }
        }
        $body[] = $line;
    }
    if ($legacy_approval && !$actions) {
        $actions[] = array('label' => $notifications_approval_view, 'url' => '/admin/system/approval', 'decision' => 'view');
    }
    return array(implode("\n", $body), $actions);
};
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
$notifications_filter_query = array();
if ($notifications_search !== '') {
    $notifications_filter_query['q'] = $notifications_search;
}
if ($notifications_status !== 'all') {
    $notifications_filter_query['status'] = $notifications_status;
}
if ($notifications_per_page_value !== 20) {
    $notifications_filter_query['per_page'] = $notifications_per_page_value;
}
$notifications_page_link = function ($target_page) use ($notifications_filter_query) {
    return '/admin/notifications?' . http_build_query(array_merge($notifications_filter_query, array('page' => (int) $target_page)));
};
$notifications_item_link = function ($notification_id) use ($notifications_filter_query, $notifications_page) {
    return '/admin/notifications?' . http_build_query(array_merge($notifications_filter_query, array(
        'selected' => (int) $notification_id,
        'page' => (int) $notifications_page,
    )));
};
$notifications_page_icon = function ($name) {
    $paths = array(
        'first' => '<path stroke-linecap="round" stroke-linejoin="round" d="m11 5-5 5 5 5M16 5l-5 5 5 5"/>',
        'previous' => '<path stroke-linecap="round" stroke-linejoin="round" d="m12.5 5-5 5 5 5"/>',
        'next' => '<path stroke-linecap="round" stroke-linejoin="round" d="m7.5 5 5 5-5 5"/>',
        'last' => '<path stroke-linecap="round" stroke-linejoin="round" d="m9 5 5 5-5 5M4 5l5 5-5 5"/>',
    );
    $path = isset($paths[$name]) ? $paths[$name] : $paths['next'];
    return '<svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" focusable="false">' . $path . '</svg>';
};
?>
<section class="mx-auto w-full max-w-7xl px-0 py-6 sm:px-6 sm:py-8 lg:px-8">
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
                <h2 class="mt-3 text-base font-semibold text-slate-800"><?php echo html_escape(!empty($notifications_filter_active) ? $notifications_no_results : $notifications_empty); ?></h2>
                <p class="mt-1 text-sm text-slate-500"><?php echo html_escape(!empty($notifications_filter_active) ? $notifications_no_results_hint : $notifications_empty_hint); ?></p>
            </div>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 items-stretch gap-4 lg:grid-cols-[minmax(16rem,0.85fr)_minmax(0,1.65fr)]">
            <section class="<?php echo $selected_notification !== NULL ? 'hidden lg:block' : 'block'; ?> overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-label="<?php echo html_escape($notifications_list_label); ?>">
                <div class="border-b border-slate-300 px-4 py-4 sm:px-5">
                    <h2 class="text-sm font-semibold text-slate-800"><?php echo html_escape($notifications_list_label); ?></h2>
                </div>
                <ol class="notification-list-scroll max-h-[36rem] divide-y divide-slate-300 overflow-y-auto">
                    <?php foreach ($notifications as $notification): ?>
                        <?php
                        $is_selected = ((int) $notification['id'] === $selected_notification_id);
                        $is_read = in_array(strtolower((string) $notification['read_flag']), array('1', 'true', 'read', 'yes'), TRUE);
                        $status_label = $is_read ? $notifications_read : $notifications_unread;
                        ?>
                        <li class="notification-list-row <?php echo $is_selected ? 'is-selected bg-indigo-50/70' : 'bg-white'; ?>">
                            <a href="<?php echo html_escape($notifications_item_link($notification['id'])); ?>" data-notification-unread="<?php echo $is_read ? 'false' : 'true'; ?>" class="notification-list-item block min-w-0 border-l-4 px-2 py-4 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-indigo-500 sm:px-3 <?php echo $is_selected ? 'border-indigo-600' : 'border-transparent'; ?>"<?php echo $is_selected ? ' aria-current="true"' : ''; ?>>
                                <div class="flex items-center gap-2">
                                    <h3 class="min-w-0 flex-1 truncate text-sm font-semibold text-slate-900"><?php echo html_escape($notification['title'] !== '' ? $notification['title'] : '#' . (int) $notification['id']); ?></h3>
                                    <?php if (!$is_read): ?><span class="h-2 w-2 shrink-0 rounded-full bg-blue-600" aria-label="<?php echo html_escape($notifications_unread); ?>" title="<?php echo html_escape($notifications_unread); ?>"></span><?php endif; ?>
                                </div>
                                <?php if ($notification['message'] !== ''): ?><p class="notification-message-preview mt-2 block truncate text-xs leading-5 text-slate-600"><?php echo html_escape($message_preview($notification['message'])); ?></p><?php endif; ?>
                                <?php if ($notification['created_at'] !== ''): ?><time class="mt-1 block text-xs font-medium text-slate-500" datetime="<?php echo html_escape(app_utc_iso($notification['created_at'])); ?>" title="<?php echo html_escape(app_utc_iso($notification['created_at'])); ?>"><?php echo html_escape($relative_time($notification['created_at'])); ?></time><?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ol>
                <?php if (!empty($notifications)): ?>
                    <nav class="flex flex-col gap-3 border-t border-slate-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between" aria-label="<?php echo html_escape($notifications_pagination); ?>">
                        <span class="text-sm text-slate-500"><?php echo (int) $notifications_page; ?> / <?php echo (int) $notifications_total_pages; ?></span>
                        <div class="flex flex-wrap items-center gap-1">
                            <?php if ($notifications_page > 1): ?>
                                <a class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50" href="<?php echo html_escape($notifications_page_link(1)); ?>" aria-label="<?php echo html_escape($notifications_first); ?>" title="<?php echo html_escape($notifications_first); ?>"><?php echo $notifications_page_icon('first'); ?></a>
                                <a class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50" href="<?php echo html_escape($notifications_page_link($notifications_page - 1)); ?>" aria-label="<?php echo html_escape($notifications_previous); ?>" title="<?php echo html_escape($notifications_previous); ?>"><?php echo $notifications_page_icon('previous'); ?></a>
                            <?php else: ?>
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-400" aria-disabled="true" aria-label="<?php echo html_escape($notifications_first); ?>"><?php echo $notifications_page_icon('first'); ?></span>
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-400" aria-disabled="true" aria-label="<?php echo html_escape($notifications_previous); ?>"><?php echo $notifications_page_icon('previous'); ?></span>
                            <?php endif; ?>
                            <?php
                            $page_start = max(1, $notifications_page - 2);
                            $page_end = min($notifications_total_pages, $notifications_page + 2);
                            if ($page_start > 1) {
                                echo '<a class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-slate-300 px-2 text-sm font-medium text-slate-700 hover:bg-slate-50" href="' . html_escape($notifications_page_link(1)) . '">1</a>';
                                if ($page_start > 2) {
                                    echo '<span class="px-1 text-sm text-slate-400">…</span>';
                                }
                            }
                            for ($page_number = $page_start; $page_number <= $page_end; $page_number++) {
                                $page_class = $page_number === $notifications_page
                                    ? 'bg-indigo-600 text-white'
                                    : 'border border-slate-300 text-slate-700 hover:bg-slate-50';
                                echo '<a class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg px-2 text-sm font-semibold ' . $page_class . '" href="' . html_escape($notifications_page_link($page_number)) . '"' . ($page_number === $notifications_page ? ' aria-current="page"' : '') . '>' . (int) $page_number . '</a>';
                            }
                            if ($page_end < $notifications_total_pages) {
                                if ($page_end < $notifications_total_pages - 1) {
                                    echo '<span class="px-1 text-sm text-slate-400">…</span>';
                                }
                                echo '<a class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-slate-300 px-2 text-sm font-medium text-slate-700 hover:bg-slate-50" href="' . html_escape($notifications_page_link($notifications_total_pages)) . '">' . (int) $notifications_total_pages . '</a>';
                            }
                            ?>
                            <?php if ($notifications_page < $notifications_total_pages): ?>
                                <a class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50" href="<?php echo html_escape($notifications_page_link($notifications_page + 1)); ?>" aria-label="<?php echo html_escape($notifications_next); ?>" title="<?php echo html_escape($notifications_next); ?>"><?php echo $notifications_page_icon('next'); ?></a>
                                <a class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50" href="<?php echo html_escape($notifications_page_link($notifications_total_pages)); ?>" aria-label="<?php echo html_escape($notifications_last); ?>" title="<?php echo html_escape($notifications_last); ?>"><?php echo $notifications_page_icon('last'); ?></a>
                            <?php else: ?>
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-400" aria-disabled="true" aria-label="<?php echo html_escape($notifications_next); ?>"><?php echo $notifications_page_icon('next'); ?></span>
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-400" aria-disabled="true" aria-label="<?php echo html_escape($notifications_last); ?>"><?php echo $notifications_page_icon('last'); ?></span>
                            <?php endif; ?>
                        </div>
                    </nav>
                <?php endif; ?>
            </section>

            <section class="<?php echo $selected_notification !== NULL ? 'block' : 'hidden lg:block'; ?> min-h-80 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7" aria-live="polite">
                <?php if ($selected_notification !== NULL): ?>
                    <a href="<?php echo html_escape($notifications_page_link($notifications_page)); ?>" class="mb-5 inline-flex items-center gap-2 rounded-lg text-sm font-semibold text-indigo-700 hover:text-indigo-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 lg:hidden">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6M9 12h12"/></svg>
                        <?php echo html_escape($notifications_back); ?>
                    </a>
                    <article>
                        <div class="flex items-start gap-3">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-indigo-50 text-indigo-700" aria-hidden="true">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path stroke-linecap="round" stroke-linejoin="round" d="M10 21h4"/></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start gap-2">
                                    <h2 class="min-w-0 flex-1 text-lg font-semibold leading-snug text-slate-900"><?php echo html_escape($selected_notification['title'] !== '' ? $selected_notification['title'] : '#' . (int) $selected_notification['id']); ?></h2>
                                    <div class="relative -mt-1 shrink-0">
                                        <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500" aria-label="<?php echo html_escape($notifications_actions); ?>" aria-haspopup="menu" aria-expanded="false" data-notification-menu-toggle aria-controls="notification-detail-actions">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="5" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="12" cy="19" r="1.8"/></svg>
                                        </button>
                                        <div id="notification-detail-actions" class="absolute right-0 top-full z-20 mt-1 hidden min-w-36 rounded-lg border border-slate-200 bg-white py-1 shadow-lg" role="menu" data-notification-menu>
                                            <button type="button" role="menuitem" class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm font-medium text-red-700 hover:bg-red-50 focus:bg-red-50 focus:outline-none" data-notification-delete-open data-id="<?php echo (int) $selected_notification['id']; ?>">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6m4-6v6M5 7l1 13h12l1-13M9 7V4h6v3"/></svg>
                                                <?php echo html_escape($notifications_delete); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php $selected_is_read = in_array(strtolower((string) $selected_notification['read_flag']), array('1', 'true', 'read', 'yes'), TRUE); ?>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <?php if ($selected_notification['created_at'] !== ''): ?><time class="text-sm font-medium text-slate-500" datetime="<?php echo html_escape(app_utc_iso($selected_notification['created_at'])); ?>" title="<?php echo html_escape(app_utc_iso($selected_notification['created_at'])); ?>"><?php echo html_escape($relative_time($selected_notification['created_at'])); ?></time><?php endif; ?>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?php echo $selected_is_read ? 'bg-slate-100 text-slate-600' : 'bg-indigo-100 text-indigo-700'; ?>"><?php echo html_escape($selected_is_read ? $notifications_read : $notifications_unread); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6 border-t border-slate-100 pt-5">
                        <?php list($notification_body, $approval_actions) = $approval_notification($selected_notification['message']); ?>
                        <p class="whitespace-pre-line break-words text-sm leading-7 text-slate-700"><?php echo html_escape($notification_body); ?></p>
                        <?php if (!empty($approval_actions)): ?>
                            <div class="mt-5 flex flex-wrap gap-3" aria-label="<?php echo html_escape($notifications_approval_view); ?>">
                                <?php foreach ($approval_actions as $action): ?>
                                    <a href="<?php echo html_escape($action['url']); ?>" class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                        <?php echo html_escape($action['label']); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
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

<div id="notification-delete-dialog" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true" aria-labelledby="notification-delete-title" aria-describedby="notification-delete-description" data-notification-dialog>
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <div class="flex items-start gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-red-100 text-red-700" aria-hidden="true"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v5m0 3h.01M10.3 3.9 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3l-7.5-13.1a2 2 0 0 0-3.4 0Z"/></svg></span>
            <div><h2 id="notification-delete-title" class="text-lg font-semibold text-slate-900"><?php echo html_escape($notifications_delete_confirm_title); ?></h2><p id="notification-delete-description" class="mt-2 text-sm leading-6 text-slate-600"><?php echo html_escape($notifications_delete_confirm_body); ?></p></div>
        </div>
        <form class="mt-6 flex justify-end gap-3" method="post" action="/admin/notifications/delete" data-notification-delete-form>
            <input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>" value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
            <input type="hidden" name="notification_id" value="" data-notification-delete-id>
            <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500" data-notification-dialog-close><?php echo html_escape($notifications_cancel); ?></button>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6m4-6v6M5 7l1 13h12l1-13M9 7V4h6v3"/></svg><?php echo html_escape($notifications_confirm_delete); ?></button>
        </form>
    </div>
</div>
<script>
document.addEventListener('click', function (event) {
    var menuButton = event.target.closest('[data-notification-menu-toggle]');
    var dialog = document.querySelector('[data-notification-dialog]');
    if (menuButton) {
        var menu = menuButton.parentElement.querySelector('[data-notification-menu]');
        document.querySelectorAll('[data-notification-menu]').forEach(function (other) {
            if (other !== menu) { other.classList.add('hidden'); other.parentElement.querySelector('[data-notification-menu-toggle]').setAttribute('aria-expanded', 'false'); }
        });
        menu.classList.toggle('hidden'); menuButton.setAttribute('aria-expanded', menu.classList.contains('hidden') ? 'false' : 'true'); return;
    }
    var deleteButton = event.target.closest('[data-notification-delete-open]');
    if (deleteButton) {
        document.querySelector('[data-notification-delete-id]').value = deleteButton.getAttribute('data-id');
        document.querySelectorAll('[data-notification-menu]').forEach(function (menu) { menu.classList.add('hidden'); });
        dialog.classList.remove('hidden'); dialog.classList.add('flex'); dialog.querySelector('[data-notification-dialog-close]').focus(); return;
    }
    if (event.target.closest('[data-notification-dialog-close]') || event.target === dialog) { dialog.classList.add('hidden'); dialog.classList.remove('flex'); }
    if (!event.target.closest('[data-notification-menu]')) {
        document.querySelectorAll('[data-notification-menu]').forEach(function (menu) { menu.classList.add('hidden'); menu.parentElement.querySelector('[data-notification-menu-toggle]').setAttribute('aria-expanded', 'false'); });
    }
});
document.addEventListener('keydown', function (event) {
    var dialog = document.querySelector('[data-notification-dialog]');
    if (event.key === 'Escape') { document.querySelectorAll('[data-notification-menu]').forEach(function (menu) { menu.classList.add('hidden'); }); dialog.classList.add('hidden'); dialog.classList.remove('flex'); }
});
</script>
