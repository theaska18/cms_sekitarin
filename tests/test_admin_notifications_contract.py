from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web/application"
CONTROLLER = APP / "controllers/Admin_notifications.php"
VIEW = APP / "views/admin/notifications/index.php"
ADMIN = APP / "controllers/Admin.php"
LANGUAGE_DIR = APP / "language"
LOCALES = ("english", "indonesian", "japanese", "chinese", "arabic", "korean", "spanish", "french", "german")


class AdminNotificationsContractTests(unittest.TestCase):
    def test_controller_uses_scoped_server_side_filters_and_paging(self):
        controller = CONTROLLER.read_text()
        for marker in (
            "DEFAULT_PAGE_SIZE = 20",
            "array(20, 50, 100)",
            "apply_notification_filters",
            "->like('title', $search)",
            "->or_like('message', $search)",
            "->where('read_flag', 0)",
            "->where('read_flag', 1)",
            "->limit($per_page, ($page - 1) * $per_page)",
            "->where('user_id', $user_id)",
            "selected_notification",
            "notifications_filter_active",
        ):
            self.assertIn(marker, controller)
        selected = controller.split("if ($selected_notification_id > 0) {", 1)[1]
        self.assertIn("->where('notification_id', $selected_notification_id)", selected)
        self.assertIn("->where('user_id', $user_id)", selected)

    def test_notification_feed_and_interval_refresh_are_wired(self):
        routes = (APP / "config" / "routes.php").read_text()
        controller = CONTROLLER.read_text()
        header = (APP / "views/admin/header.php").read_text()
        footer = (APP / "views/admin/footer.php").read_text()
        script = (ROOT / "web/assets/js/admin-notifications.js").read_text()
        self.assertIn("admin/notifications/feed", routes)
        self.assertIn("public function feed()", controller)
        self.assertIn("->where('view_flag', 0)", controller)
        self.assertIn("->where('user_id', $user_id)", controller)
        self.assertIn("app_utc_iso($row[$order_date_field])", controller)
        for marker in ("data-notification-feed-url", 'data-notification-interval="10000"', "admin-notifications-items", "admin-notification-badge"):
            self.assertIn(marker, header)
        self.assertIn("assets/js/admin-notifications.js", footer)
        for marker in ("setInterval(poll, interval)", "fetch(feedUrl", "formatLocalTimes(items)", "data-local-datetime"):
            self.assertIn(marker, script)

    def test_view_has_numbered_pagination_at_the_bottom_of_the_list(self):
        view = VIEW.read_text()
        for marker in (
            "notifications_page_link",
            "aria-current=\"page\"",
            "notifications_first",
            "notifications_last",
            "notifications_previous",
            "notifications_next",
            "notifications_total_pages",
        ):
            self.assertIn(marker, view)
        self.assertIn("!empty($notifications)", view)
        self.assertNotIn('name="q"', view)
        self.assertIn("http_build_query", view)
        self.assertIn("notification-list-scroll", view)
        self.assertIn("notifications_page_icon", view)
        self.assertIn("<?php echo $notifications_page_icon('first'); ?>", view)
        self.assertIn("<?php echo $notifications_page_icon('previous'); ?>", view)
        self.assertIn("<?php echo $notifications_page_icon('next'); ?>", view)
        self.assertIn("<?php echo $notifications_page_icon('last'); ?>", view)

    def test_admin_renderer_forwards_notification_filter_labels(self):
        admin = ADMIN.read_text()
        for key in (
            "search_label", "search_placeholder", "filter_status", "filter_all",
            "filter_unread", "filter_read", "filter_apply", "filter_reset",
            "per_page_label", "first", "last", "no_results", "no_results_hint",
        ):
            with self.subTest(key=key):
                self.assertIn("'" + key + "'", admin)

    def test_notification_filter_labels_exist_in_all_locales(self):
        keys = (
            "notifications_search_label", "notifications_search_placeholder",
            "notifications_filter_status", "notifications_filter_all",
            "notifications_filter_unread", "notifications_filter_read",
            "notifications_filter_apply", "notifications_filter_reset",
            "notifications_per_page_label", "notifications_first",
            "notifications_last", "notifications_no_results",
            "notifications_no_results_hint",
        )
        for locale in LOCALES:
            with self.subTest(locale=locale):
                content = (LANGUAGE_DIR / locale / "notifications_lang.php").read_text(encoding="utf-8")
                for key in keys:
                    self.assertIn("$lang['" + key + "']", content)


if __name__ == "__main__":
    unittest.main()
