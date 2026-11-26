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
            "api/v1/accounts/search",
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

    def test_phase_one_docs_exist(self):
        self.assertTrue((ROOT / "docs" / "SEKITAR_ERD.md").is_file())
        self.assertTrue((ROOT / "docs" / "SEKITAR_API_V1.md").is_file())


if __name__ == "__main__":
    unittest.main()
