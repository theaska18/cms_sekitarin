from pathlib import Path
import re
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web/application"
VIEWS = APP / "views"


class PublicLoginContractTests(unittest.TestCase):
    def test_login_view_uses_compact_form_focused_desktop_layout(self):
        view = (VIEWS / "auth/login.php").read_text()
        self.assertIn('id="login-layout"', view)
        self.assertIn('max-w-4xl', view)
        self.assertIn('md:grid-cols-[0.82fr_1.18fr]', view)
        self.assertIn('md:min-h-[540px]', view)
        self.assertIn('login-brand-panel', view)
        self.assertIn('login-brand-art', view)
        self.assertIn('login-backdrop', view)
        self.assertNotIn('md:min-h-[620px]', view)

    def test_short_desktop_viewport_uses_a_compact_single_column_login_layout(self):
        view = (VIEWS / "auth/login.php").read_text()
        self.assertRegex(view, r"@media \(min-width: 768px\) and \(max-height: 700px\)")
        self.assertRegex(view, r"#login-layout\s*\{[^}]*grid-template-columns:\s*minmax\(0,\s*1fr\)")
        self.assertRegex(view, r"#login-layout\s*>\s*aside\s*\{[^}]*flex-direction:\s*row")
        self.assertRegex(view, r"#login-layout\s*>\s*div:last-child\s*\{[^}]*padding:\s*1rem\s+1\.5rem")
        self.assertRegex(view, r"#login-layout\s*>\s*div:last-child form\[method=\"post\"\]\s*\{[^}]*grid-template-columns:\s*repeat\(2,")

    def test_login_stylesheet_url_versions_the_current_css_asset(self):
        view = (VIEWS / "auth/login.php").read_text()
        self.assertIn("filemtime($tailwind_css_path)", view)
        self.assertIn('href="/assets/css/tailwind.css?v=<?php echo rawurlencode($tailwind_css_version); ?>"', view)

    def test_login_and_logout_routes_use_auth_controller(self):
        routes = (APP / "config/routes.php").read_text()
        auth = (APP / "controllers/Auth.php").read_text()
        pages = (APP / "controllers/Pages.php").read_text()
        self.assertIn("$route['login'] = 'auth/index';", routes)
        self.assertIn("$route['logout'] = 'auth/logout';", routes)
        self.assertIn("$this->input->method(TRUE) === 'POST'", auth)
        self.assertIn("$this->admin_identity->authenticate", auth)
        self.assertIn("header('Location: /login', TRUE, 303);", pages)

    def test_public_navigation_switches_between_login_and_admin_by_session(self):
        sources = (
            VIEWS / "welcome_message.php",
            VIEWS / "pages/header.php",
            VIEWS / "pages/footer.php",
        )
        for path in sources:
            with self.subTest(path=path.name):
                source = path.read_text()
                self.assertIn("$admin_user !== NULL ? '/admin' : '/login'", source)
                self.assertIn("$admin_user['display_name']", source)
        pages = (APP / "controllers/Pages.php").read_text()
        welcome = (APP / "controllers/Welcome.php").read_text()
        self.assertIn("admin_identity->current_user()", pages)
        self.assertIn("admin_identity->current_user()", welcome)

    def test_login_form_is_server_side_and_csrf_protected(self):
        view = (VIEWS / "auth/login.php").read_text()
        self.assertIn('<form method="post" action="/login"', view)
        self.assertIn('name="<?php echo html_escape($csrf_token_name); ?>"', view)
        self.assertRegex(view, r'<input[^>]+name="username"[^>]+autocomplete="username"')
        self.assertRegex(view, r'<input[^>]+name="password"[^>]+type="password"')
        self.assertIn('type="submit"', view)
        self.assertIn('role="alert"', view)
        self.assertNotIn('onsubmit="event.preventDefault(); return false;"', view)

    def test_password_toggle_is_accessible_and_keeps_the_typed_value(self):
        view = (VIEWS / "auth/login.php").read_text()
        self.assertIn('type="password"', view)
        self.assertIn('aria-pressed="false"', view)
        self.assertIn('aria-controls="login-password"', view)
        self.assertIn('padding-inline-end: 4rem', view)
        self.assertIn('type="button"', view)
        self.assertIn("toggle.addEventListener('click'", view)
        self.assertIn("password.type = showPassword ? 'text' : 'password';", view)
        self.assertIn("toggle.setAttribute('aria-pressed', String(showPassword));", view)
        self.assertNotIn('password.value =', view)

    def test_login_input_icons_have_optical_inset_and_vertical_centering(self):
        view = (VIEWS / "auth/login.php").read_text()
        self.assertRegex(view, r"\.login-field-icon\s*\{\s*inset-inline-start:\s*\.75rem;")
        icon_classes = re.findall(r'<svg class="(login-field-icon[^\"]+)"', view)
        self.assertEqual(len(icon_classes), 2)
        for classes in icon_classes:
            with self.subTest(classes=classes):
                self.assertIn("absolute", classes)
                self.assertIn("top-1/2", classes)
                self.assertIn("-translate-y-1/2", classes)

    def test_login_view_has_rtl_and_nine_locale_picker(self):
        view = (VIEWS / "auth/login.php").read_text()
        auth = (APP / "controllers/Auth.php").read_text()
        self.assertIn('dir="<?php echo html_escape($text_direction); ?>"', view)
        self.assertIn('id="login-language" name="lang"', view)
        for locale in (
            "english", "chinese", "french", "german", "indonesian",
            "japanese", "korean", "spanish", "arabic",
        ):
            with self.subTest(locale=locale):
                self.assertIn("'" + locale + "'", auth)
                self.assertIn('value="' + locale + '"', view)
        self.assertIn("'direction' => 'rtl'", auth)


if __name__ == "__main__":
    unittest.main()
