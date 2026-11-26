from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web" / "application"
API = APP / "controllers" / "api" / "v1"
MIGRATIONS = ROOT / "database" / "migrations"


class SekitarApiContractTests(unittest.TestCase):
    def test_v1_routes_are_explicit(self):
        routes = (APP / "config" / "routes.php").read_text()
        expected = [
            "api/v1/auth/register",
            "api/v1/auth/login",
            "api/v1/auth/refresh",
            "api/v1/auth/logout",
            "api/v1/auth/email/verify",
            "api/v1/auth/email/resend",
            "api/v1/auth/password/forgot",
            "api/v1/auth/password/reset",
            "api/v1/me",
            "api/v1/me/consents",
            "api/v1/me/devices",
            "api/v1/account-types",
            "api/v1/media",
            "api/v1/media/(:num)",
            "api/v1/contacts",
            "api/v1/contacts/(:num)",
            "api/v1/blocks",
            "api/v1/blocks/(:num)",
            "api/v1/reports",
            "api/v1/conversations",
            "api/v1/conversations/(:num)",
            "api/v1/conversations/(:num)/messages",
            "api/v1/conversations/(:num)/read",
            "api/v1/conversations/(:num)/typing",
            "api/v1/conversations/(:num)/members",
            "api/v1/conversations/(:num)/live-location",
            "api/v1/messages/(:num)",
            "api/v1/nearby",
            "api/v1/accounts/handle-available",
            "api/v1/accounts/lookup",
            "api/v1/feed",
            "api/v1/posts",
            "api/v1/posts/(:num)",
            "api/v1/posts/(:num)/like",
            "api/v1/posts/(:num)/comments",
            "api/v1/posts/(:num)/report",
            "api/v1/comments/(:num)",
            "api/v1/accounts/(:num)/posts",
            "api/v1/accounts/(:num)/services",
            "api/v1/services/(:num)",
            "api/v1/orders",
            "api/v1/orders/(:num)",
            "api/v1/orders/(:num)/accept",
            "api/v1/orders/(:num)/progress",
            "api/v1/orders/(:num)/deliver",
            "api/v1/orders/(:num)/complete",
            "api/v1/orders/(:num)/cancel",
            "api/v1/orders/(:num)/payment",
            "api/v1/me/push-token",
            "api/v1/notifications",
            "api/v1/notifications/(:num)/read",
            "api/v1/notifications/read-all",
            "api/v1/accounts/search",
            "api/v1/accounts/(:num)/location",
            "api/v1/accounts/(:num)/presence",
            "api/v1/accounts/(:num)/hours",
            "api/v1/accounts/(:num)/members",
            "api/v1/accounts/(:num)/members/(:num)",
            "api/v1/accounts/(:num)/activate",
            "api/v1/accounts/(:num)",
        ]
        for route in expected:
            with self.subTest(route=route):
                self.assertIn("$route['" + route + "']", routes)

    def test_api_is_exempt_from_csrf_and_receives_bearer_header(self):
        config = (APP / "config" / "config.php").read_text()
        htaccess = (ROOT / "web" / ".htaccess").read_text()
        self.assertIn("'api/v1/.*'", config)
        self.assertIn("E=HTTP_AUTHORIZATION", htaccess)
        self.assertLess(
            htaccess.index("E=HTTP_AUTHORIZATION"),
            htaccess.index("RewriteRule ^(.*)$ index.php"),
        )

    def test_identity_migration_creates_tables_and_hashes_tokens(self):
        sql = (MIGRATIONS / "20261126000200_sekitar_identity.sql").read_text()
        for table in ("sek_user", "sek_user_token", "sek_device", "sek_consent", "sek_rate_limit"):
            with self.subTest(table=table):
                self.assertIn("CREATE TABLE `" + table + "`", sql)
        self.assertIn("`token_hash` char(64) NOT NULL", sql)
        self.assertIn("`access_token_hash` char(64) NOT NULL", sql)
        self.assertIn("`refresh_token_hash` char(64) NOT NULL", sql)
        self.assertIn("UNIQUE KEY `uk_sek_user_email`", sql)

    def test_account_migration_seeds_types_and_spatial_columns(self):
        sql = (MIGRATIONS / "20261126000300_sekitar_accounts.sql").read_text()
        for table in ("sek_account", "sek_account_member", "sek_account_location", "sek_account_presence", "sek_media"):
            with self.subTest(table=table):
                self.assertIn("CREATE TABLE `" + table + "`", sql)
        for type_code in ("personal", "warung", "restoran", "tambal_ban", "driver_motor",
                          "driver_mobil", "kurir_makanan", "terapis", "jasa_umum"):
            with self.subTest(type_code=type_code):
                self.assertIn("'" + type_code + "'", sql)
        self.assertIn("point NOT NULL SRID 4326", sql)
        self.assertIn("SPATIAL KEY", sql)

    def test_auth_library_hashes_tokens_and_verifies_passwords(self):
        auth = (APP / "libraries" / "Api_auth.php").read_text()
        model = (APP / "models" / "Sek_user_model.php").read_text()
        self.assertIn("hash('sha256'", auth)
        self.assertIn("random_bytes(32)", auth)
        self.assertIn("const ACCESS_TTL = 3600;", auth)
        self.assertIn("const REFRESH_TTL = 5184000;", auth)
        self.assertIn("revoke_all_for_user", auth)
        self.assertIn("PASSWORD_ARGON2ID", model)
        self.assertIn("password_verify(", (API / "Sek_auth.php").read_text())

    def test_account_endpoints_require_membership(self):
        base = (API / "Sek_api_base.php").read_text()
        accounts = (API / "Sek_accounts.php").read_text()
        self.assertIn("X-Account-Id", base)
        self.assertIn("member_role", base)
        self.assertIn("account_context_for", accounts)
        self.assertIn("Hanya owner atau manager", accounts)
        self.assertIn("member_role", accounts)

    def test_social_migration_adds_contacts_blocks_and_reports(self):
        sql = (MIGRATIONS / "20261126000400_sekitar_social.sql").read_text()
        for table in ("sek_contact", "sek_block", "sek_report"):
            with self.subTest(table=table):
                self.assertIn("CREATE TABLE `" + table + "`", sql)
        self.assertIn("UNIQUE KEY `uk_sek_contact_owner_account`", sql)
        self.assertIn("PRIMARY KEY (`blocker_account_id`, `blocked_account_id`)", sql)

    def test_media_upload_is_validated_and_paths_are_confined(self):
        media = (API / "Sek_media.php").read_text()
        self.assertIn("is_uploaded_file", media)
        self.assertIn("finfo_open", media)
        self.assertIn("random_bytes(16)", media)
        self.assertIn("move_uploaded_file", media)
        self.assertIn("realpath(FCPATH . 'data/sekitar-media')", media)
        self.assertIn("strpos($path, $base . DIRECTORY_SEPARATOR) !== 0", media)
        self.assertIn("is_post_media_visible", media)
        self.assertIn("is_chat_media_visible", media)
        self.assertIn("MAX_IMAGE_BYTES", media)
        self.assertIn("MAX_VIDEO_BYTES", media)
        self.assertIn("video/mp4", media)

    def test_blocking_hides_accounts_and_contacts(self):
        model = (APP / "models" / "Sek_account_model.php").read_text()
        accounts = (API / "Sek_accounts.php").read_text()
        contacts = (API / "Sek_contacts.php").read_text()
        self.assertIn("function blocked_between", model)
        self.assertIn("function blocked_account_ids", model)
        self.assertIn("blocked_between", accounts)
        self.assertIn("blocked_account_ids", contacts)

    def test_hours_and_members_are_scoped_to_owner_or_manager(self):
        accounts = (API / "Sek_accounts.php").read_text()
        members = (API / "Sek_members.php").read_text()
        self.assertIn("replace_hours", accounts)
        self.assertIn("Hanya owner atau manager", accounts)
        self.assertIn("Hanya owner yang dapat menambah anggota", members)
        self.assertIn("Owner tidak dapat diubah atau dihapus", members)

    def test_chat_migration_creates_conversations_messages_and_live_location(self):
        sql = (MIGRATIONS / "20261126000500_sekitar_chat.sql").read_text()
        for table in ("sek_conversation", "sek_conversation_member", "sek_message",
                      "sek_message_receipt", "sek_live_location"):
            with self.subTest(table=table):
                self.assertIn("CREATE TABLE `" + table + "`", sql)
        self.assertIn("uk_sek_conversation_direct", sql)
        self.assertIn("point NOT NULL SRID 4326", sql)

    def test_chat_model_checks_membership_and_marks_read(self):
        model = (APP / "models" / "Sek_chat_model.php").read_text()
        conv = (API / "Sek_conversations.php").read_text()
        messages = (API / "Sek_messages.php").read_text()
        self.assertIn("function membership", model)
        self.assertIn("left_at IS NULL", model)
        self.assertIn("INSERT INTO sek_message_receipt", model)
        self.assertIn("function mark_read", model)
        self.assertIn("conversation_for", conv)
        self.assertIn("conversation_membership", messages)
        self.assertIn("Persetujuan lokasi belum diberikan", conv)

    def test_realtime_gateway_authenticates_and_checks_membership(self):
        server = (ROOT / "ws" / "server.js").read_text()
        realtime = (APP / "libraries" / "Sek_realtime.php").read_text()
        compose = (ROOT / "docker-compose.yml").read_text()
        self.assertIn("createHash('sha256')", server)
        self.assertIn("d.access_expires_at > UTC_TIMESTAMP()", server)
        self.assertIn("sek_conversation_member", server)
        self.assertIn("timingSafeEqual", server)
        self.assertIn("SEKITAR_WS_PUBLISH_URL", realtime)
        self.assertIn("X-Internal-Secret", realtime)
        self.assertIn("sekitar-ws:", compose)
        self.assertIn("8090:8090", compose)

    def test_nearby_uses_spatial_distance_and_presence_rules(self):
        model = (APP / "models" / "Sek_account_model.php").read_text()
        places = (API / "Sek_places.php").read_text()
        self.assertIn("ST_Distance_Sphere", model)
        self.assertIn("ST_SRID(POINT(?, ?), 4326)", model)
        self.assertIn("DATE_SUB(UTC_TIMESTAMP(), INTERVAL 5 MINUTE)", model)
        self.assertIn("allows_presence = 1", model)
        self.assertIn("blocked_account_ids", model)
        self.assertIn("presence|", places)
        self.assertIn("Persetujuan lokasi belum diberikan", places)
        self.assertIn("is_own", places)
        self.assertIn("is_active", places)
        self.assertIn("live_sharing", places)
        self.assertIn("location_mode", places)
        posts = (APP / "controllers" / "api" / "v1" / "Sek_posts.php").read_text()
        self.assertIn("feed_source", posts)
        self.assertIn("nearby_account_distances", posts)
        self.assertTrue(
            (MIGRATIONS / "20261126000900_sekitar_location_mode.sql").is_file()
        )
        self.assertTrue(
            (MIGRATIONS / "20261126001000_sekitar_account_active.sql").is_file()
        )
        self.assertTrue(
            (MIGRATIONS / "20261126001100_sekitar_account_active_expiry.sql").is_file()
        )
        accounts = (APP / "controllers" / "api" / "v1" / "Sek_accounts.php").read_text()
        self.assertIn("location_mode", accounts)
        self.assertIn("is_active", accounts)
        self.assertIn("active_until", accounts)
        self.assertIn("account_is_active", (API / "Sek_api_base.php").read_text())

    def test_post_migration_and_feed_scope(self):
        sql = (MIGRATIONS / "20261126000600_sekitar_posts.sql").read_text()
        for table in ("sek_post", "sek_post_media", "sek_post_like", "sek_post_comment"):
            with self.subTest(table=table):
                self.assertIn("CREATE TABLE `" + table + "`", sql)
        self.assertIn("ADD COLUMN `post_id`", sql)

        model = (APP / "models" / "Sek_post_model.php").read_text()
        controller = (API / "Sek_posts.php").read_text()
        account_model = (APP / "models" / "Sek_account_model.php").read_text()
        social_model = (APP / "models" / "Sek_social_model.php").read_text()
        self.assertIn("function feed", model)
        self.assertIn("before_id", model)
        self.assertIn("function update", model)
        self.assertIn("update_post", controller)
        self.assertIn("Tidak dapat mengubah post", controller)
        self.assertIn("contact_account_ids", controller)
        self.assertIn("nearby_account_distances", controller)
        self.assertIn("radius", controller)
        self.assertIn("blocked_account_ids", account_model)
        self.assertIn("function contact_account_ids", social_model)

    def test_notification_and_push_pipeline(self):
        sql = (MIGRATIONS / "20261126000700_sekitar_notifications.sql").read_text()
        self.assertIn("CREATE TABLE `sek_notification`", sql)
        self.assertIn("CREATE TABLE `sek_push_queue`", sql)
        self.assertIn("idx_sek_push_due", sql)

        notifier = (APP / "libraries" / "Sek_notifier.php").read_text()
        model = (APP / "models" / "Sek_notification_model.php").read_text()
        worker = (ROOT / "tools" / "push_worker.php").read_text()
        compose = (ROOT / "docker-compose.yml").read_text()
        self.assertIn("sek_push_queue", notifier)
        self.assertIn("push_token", notifier)
        self.assertIn("function mark_all_read", model)
        self.assertIn("class FcmClient", worker)
        self.assertIn("fcm.googleapis.com/v1/projects/", worker)
        self.assertIn("fcm_not_configured", worker)
        self.assertIn("sekitar-push:", compose)
        self.assertIn("fcm-service-account.json", compose)

    def test_order_migration_and_state_machine(self):
        sql = (MIGRATIONS / "20261126000800_sekitar_orders.sql").read_text()
        for table in ("sek_service", "sek_order", "sek_order_item", "sek_order_event"):
            with self.subTest(table=table):
                self.assertIn("CREATE TABLE `" + table + "`", sql)
        for status in ("pending", "accepted", "in_progress", "delivering", "completed", "cancelled"):
            with self.subTest(status=status):
                self.assertIn(status, sql)

        model = (APP / "models" / "Sek_order_model.php").read_text()
        orders = (API / "Sek_orders.php").read_text()
        services = (API / "Sek_services.php").read_text()
        self.assertIn("function create_order", model)
        self.assertIn("function transition", model)
        self.assertIn("sek_order_event", model)
        self.assertIn("expected_kind", orders)
        self.assertIn("provider_action_order", orders)
        self.assertIn("Hanya penyedia", orders)
        self.assertIn("Hanya owner atau manager", services)

    def test_username_contact_and_group_management(self):
        auth = (API / "Sek_auth.php").read_text()
        accounts = (API / "Sek_accounts.php").read_text()
        me = (API / "Sek_me.php").read_text()
        conversations = (API / "Sek_conversations.php").read_text()
        account_model = (APP / "models" / "Sek_account_model.php").read_text()
        self.assertIn("password_confirmation", auth)
        self.assertIn("username", auth)
        self.assertIn("handle_available", accounts)
        self.assertIn("function lookup", accounts)
        self.assertIn("valid_handle", account_model)
        self.assertIn("find_by_handle", account_model)
        self.assertIn("type_code', 'personal'", me)
        self.assertIn("function update", conversations)
        self.assertIn("avatar_media_id", conversations)
        self.assertIn("username", conversations)

    def test_database_language_file_prevents_masked_errors(self):
        self.assertTrue((APP / "language" / "indonesian" / "db_lang.php").is_file())

    def test_phase_one_docs_exist(self):
        self.assertTrue((ROOT / "docs" / "SEKITAR_ERD.md").is_file())
        self.assertTrue((ROOT / "docs" / "SEKITAR_API_V1.md").is_file())
        self.assertTrue((ROOT / "docs" / "SEKITAR_REALTIME.md").is_file())
        self.assertTrue((ROOT / "docs" / "SEKITAR_PUSH.md").is_file())
        self.assertTrue((ROOT / "docs" / "SEKITAR_ORDERS.md").is_file())


if __name__ == "__main__":
    unittest.main()
