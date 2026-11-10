from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web" / "application"


class AdminAuthContractTests(unittest.TestCase):
    def test_login_logout_routes_and_session_configuration_are_registered(self):
        routes = (APP / "config" / "routes.php").read_text()
        config = (APP / "config" / "config.php").read_text()
        autoload = (APP / "config" / "autoload.php").read_text()
        self.assertIn("$route['login'] = 'auth/index';", routes)
        self.assertIn("$route['logout'] = 'auth/logout';", routes)
        self.assertIn("$config['sess_driver'] = 'files';", config)
        self.assertIn("$config['sess_expiration'] = 2592000;", config)
        self.assertIn("$config['sess_save_path'] = APPPATH . 'cache';", config)
        self.assertIn("$config['sess_samesite'] = 'Lax';", config)
        self.assertIn("$config['cookie_httponly'] = TRUE;", config)
        self.assertIn("$config['csrf_protection'] = TRUE;", config)
        self.assertIn("'session'", autoload)

    def test_authentication_uses_app_user_and_stores_tenant_identity(self):
        identity = (APP / "libraries" / "Admin_identity.php").read_text()
        self.assertIn("from('app_user u')", identity)
        self.assertIn("join('app_role r'", identity)
        self.assertIn("u.password", identity)
        self.assertIn("password_verify(", identity)
        self.assertIn("2592000", identity)
        self.assertIn("sess_regenerate(TRUE)", identity)
        self.assertIn("'username' =>", identity)
        self.assertIn("'tenant_id' =>", identity)
        self.assertIn("'full_name' =>", identity)
        self.assertNotIn("CMS_ADMIN_PASSWORD_HASH", identity)
        self.assertNotIn("ADMIN_USERNAME", identity)
        self.assertNotIn("ADMIN_PASSWORD", identity)
        self.assertIn("sess_destroy()", identity)

    def test_every_admin_controller_entry_is_guarded_and_login_is_csrf_protected(self):
        admin = (APP / "controllers" / "Admin.php").read_text()
        auth = (APP / "controllers" / "Auth.php").read_text()
        login = (APP / "views" / "auth" / "login.php").read_text()
        self.assertIn("function __construct()", admin)
        self.assertIn("admin_identity->current_user()", admin)
        self.assertIn("csrf_token_name", login)
        self.assertIn("$this->input->method(TRUE) === 'POST'", auth)
        self.assertIn("admin_identity->authenticate", auth)

    def test_public_navigation_switches_to_admin_only_for_valid_session(self):
        pages = (APP / "controllers" / "Pages.php").read_text()
        welcome = (APP / "controllers" / "Welcome.php").read_text()
        public_header = (APP / "views" / "pages" / "header.php").read_text()
        public_footer = (APP / "views" / "pages" / "footer.php").read_text()
        homepage = (APP / "views" / "welcome_message.php").read_text()
        self.assertIn("admin_identity->current_user()", pages)
        self.assertIn("admin_identity->current_user()", welcome)
        for view in (public_header, public_footer, homepage):
            self.assertIn("/login", view)
            self.assertIn("/admin", view)
            self.assertIn("admin_user", view)

    def test_legacy_pages_login_endpoint_does_not_render_prototype(self):
        pages = (APP / "controllers" / "Pages.php").read_text()
        self.assertNotIn("$this->render_page('login', 'Login');", pages)

    def test_logout_is_post_only_csrf_protected_and_expires_cookie(self):
        auth = (APP / "controllers" / "Auth.php").read_text()
        identity = (APP / "libraries" / "Admin_identity.php").read_text()
        config = (APP / "config" / "config.php").read_text()
        header = (APP / "views" / "admin" / "header.php").read_text()
        self.assertIn("$this->input->method(TRUE) !== 'POST'", auth)
        self.assertIn("$this->admin_identity->logout();", auth)
        self.assertIn("$this->CI->session->sess_destroy();", identity)
        self.assertIn("setcookie($cookie_name, '', array(", identity)
        self.assertIn("'expires' => time() - 3600", identity)
        self.assertIn("$config['csrf_protection'] = TRUE;", config)
        self.assertIn('method="post" action="/logout"', header)
        self.assertIn("$csrf_hash", header)

    def test_admin_profile_name_comes_from_session_identity(self):
        header = (APP / "views" / "admin" / "header.php").read_text()
        identity = (APP / "libraries" / "Admin_identity.php").read_text()
        self.assertIn("current_user", header)
        self.assertIn("display_name", header)
        self.assertIn("full_name", identity)
        self.assertIn("employee_id", identity)
        self.assertIn("image", identity)
        self.assertIn("profile_image", header)

    def test_login_password_toggle_is_accessible_and_supports_nine_locales(self):
        login = (APP / "views" / "auth" / "login.php").read_text()
        auth = (APP / "controllers" / "Auth.php").read_text()
        self.assertIn('type="password"', login)
        self.assertIn('aria-pressed="false"', login)
        self.assertIn('aria-controls="login-password"', login)
        self.assertIn("addEventListener('click'", login)
        for locale in ("english", "chinese", "french", "german", "indonesian", "japanese", "korean", "spanish", "arabic"):
            self.assertIn("'" + locale + "'", auth)
        self.assertIn("'rtl'", auth)

    def test_compose_exposes_only_a_password_hash_and_example_does_not_ship_one(self):
        compose = (ROOT / "docker-compose.yml").read_text()
        example = (ROOT / ".env.example").read_text()
        self.assertIn("CMS_ADMIN_PASSWORD_HASH", compose)
        self.assertIn("CMS_ADMIN_PASSWORD_HASH=", example)
        self.assertRegex(example, r"(?m)^CMS_ADMIN_PASSWORD_HASH=$")


if __name__ == "__main__":
    unittest.main()
