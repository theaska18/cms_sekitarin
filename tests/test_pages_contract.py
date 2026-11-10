from pathlib import Path
import re
import unittest


ROOT = Path(__file__).resolve().parents[1]
ROUTES = ROOT / "web/application/config/routes.php"
PAGES_CONTROLLER = ROOT / "web/application/controllers/Pages.php"
VIEWS = ROOT / "web/application/views/pages"
HOMEPAGE = ROOT / "web/application/views/welcome_message.php"
ROOT_HTACCESS = ROOT / "web/.htaccess"


class SeparatePagesContractTests(unittest.TestCase):
    def test_public_and_admin_urls_have_explicit_routes(self):
        routes = ROUTES.read_text()
        for url, action in (
            ("features", "pages/features"),
            ("about", "pages/about"),
            ("contact", "pages/contact"),
            ("admin", "admin/index"),
        ):
            with self.subTest(url=url):
                self.assertRegex(
                    routes,
                    rf"(?m)^\$route\['{url}'\]\s*=\s*'{action}';",
                )

    def test_each_public_page_has_its_own_view_with_a_page_heading(self):
        for slug in ("features", "about", "contact"):
            with self.subTest(page=slug):
                view = VIEWS / f"{slug}.php"
                self.assertTrue(view.is_file(), f"Missing view: {view}")
                self.assertRegex(view.read_text(), r"<h1\b")

    def test_pages_controller_keeps_public_pages_and_admin_has_its_own_controller(self):
        self.assertTrue(PAGES_CONTROLLER.is_file(), f"Missing controller: {PAGES_CONTROLLER}")
        controller = PAGES_CONTROLLER.read_text()
        for method in ("features", "about", "contact"):
            with self.subTest(method=method):
                self.assertRegex(controller, rf"public function {method}\s*\(")
        admin_controller = ROOT / "web/application/controllers/Admin.php"
        self.assertTrue(admin_controller.is_file(), f"Missing dashboard controller: {admin_controller}")
        self.assertRegex(admin_controller.read_text(), r"public function index\s*\(")

    def test_clean_public_urls_rewrite_to_ci3_front_controller(self):
        self.assertTrue(ROOT_HTACCESS.is_file(), f"Missing root rewrite rules: {ROOT_HTACCESS}")
        rewrite = ROOT_HTACCESS.read_text()
        self.assertRegex(rewrite, r"(?mi)^\s*RewriteEngine\s+On\s*$")
        self.assertRegex(rewrite, r"(?mi)^\s*RewriteCond\s+%\{REQUEST_FILENAME\}\s+!-f\s*$")
        self.assertRegex(rewrite, r"(?mi)^\s*RewriteCond\s+%\{REQUEST_FILENAME\}\s+!-d\s*$")
        self.assertRegex(rewrite, r"(?mi)^\s*RewriteRule\s+\^\(\.\*\)\$\s+index\.php/\$1\s+\[L\]\s*$")

    def test_shared_navigation_has_home_and_marks_the_current_page(self):
        header = (VIEWS / "header.php").read_text()
        for item in (
            "array('label' => 'Home', 'url' => '/', 'page' => 'home')",
            "array('label' => 'Features', 'url' => '/features', 'page' => 'features')",
            "array('label' => 'About', 'url' => '/about', 'page' => 'about')",
            "array('label' => 'Contact', 'url' => '/contact', 'page' => 'contact')",
        ):
            with self.subTest(item=item):
                self.assertIn(item, header)
        self.assertIn("' aria-current=\"page\"'", header)
        self.assertIn('href="<?php echo html_escape($item[\'url\']); ?>"', header)
        self.assertIn('href="/assets/css/tailwind.css?v=', header)

    def test_shared_header_is_opaque_sticky_and_has_large_navigation_targets(self):
        header = (VIEWS / "header.php").read_text()
        css = (ROOT / "tailwind/input.css").read_text()
        self.assertIn('class="site-header sticky top-0 z-50', header)
        self.assertIn('class="site-nav-link', header)
        self.assertIn(".site-header {", css)
        self.assertRegex(css, r"\.site-header\s*\{[^}]*position:\s*sticky;")
        self.assertRegex(css, r"\.site-header\s*\{[^}]*background-color:\s*#080d1b;")
        self.assertRegex(css, r"\.site-nav-link\s*\{[^}]*min-height:\s*44px;")

    def test_navigation_background_remains_opaque_at_desktop_breakpoint(self):
        for source_name, source in (
            ("shared page header", (VIEWS / "header.php").read_text()),
            ("homepage header", HOMEPAGE.read_text()),
        ):
            with self.subTest(source=source_name):
                nav = re.search(r'<nav[^>]*id="primary-navigation"[^>]*class="([^"]+)"', source)
                self.assertIsNotNone(nav, "Missing primary navigation")
                classes = nav.group(1).split()
                self.assertNotIn("md:bg-transparent", classes)
                self.assertIn("bg-ink", classes)

    def test_public_header_menu_design_matches_homepage(self):
        homepage = HOMEPAGE.read_text()
        shared_header = (VIEWS / "header.php").read_text()
        component_patterns = (
            ("header layout", r'<div class="([^\"]*site-header__inner[^\"]*)">'),
            ("brand link", r'<header\b.*?<a[^>]*href="/"[^>]*class="([^\"]+)"'),
            ("brand image", r'<header\b.*?<img[^>]*class="([^\"]+)"'),
            ("brand label", r'<header\b.*?<span class="([^\"]+)"'),
            ("menu toggle", r'<button[^>]*id="menu-toggle"[^>]*class="([^\"]+)"'),
            ("navigation", r'<nav[^>]*id="primary-navigation"[^>]*class="([^\"]+)"'),
            ("navigation links layout", r'<nav[^>]*id="primary-navigation"[^>]*>.*?<ul class="([^\"]+)"'),
        )
        for label, pattern in component_patterns:
            with self.subTest(component=label):
                home_match = re.search(pattern, homepage, re.S)
                public_match = re.search(pattern, shared_header, re.S)
                self.assertIsNotNone(home_match, f"Homepage is missing {label}")
                self.assertIsNotNone(public_match, f"Public header is missing {label}")
                self.assertEqual(public_match.group(1), home_match.group(1))

        for label, source in (("homepage", homepage), ("public pages", shared_header)):
            with self.subTest(component=label):
                self.assertIn("md:gap-7", source)
                self.assertIn('aria-label="Toggle navigation"', source)
                self.assertIn("site-nav-link--cta", source)

    def test_public_page_heroes_and_page_specific_content_have_shared_visual_treatment(self):
        for slug, marker in (
            ("features", "feature-card"),
            ("about", "value-card"),
            ("contact", "contact-card"),
        ):
            view = (VIEWS / f"{slug}.php").read_text()
            with self.subTest(page=slug):
                self.assertIn("page-hero", view)
                self.assertIn(marker, view)

    def test_footer_has_consistent_public_links_and_accessible_targets(self):
        footer = (VIEWS / "footer.php").read_text()
        for href in ("/", "/features", "/about", "/contact"):
            with self.subTest(href=href):
                self.assertIn(f'href="{href}"', footer)
        self.assertIn('class="site-footer', footer)
        self.assertIn('class="footer-link', footer)
        css = (ROOT / "tailwind/input.css").read_text()
        self.assertIn(".site-footer {", css)
        self.assertRegex(css, r"\.footer-link\s*\{[^}]*min-height:\s*44px;")

    def test_homepage_navigation_uses_separate_routes(self):
        homepage = HOMEPAGE.read_text()
        hrefs = set(re.findall(r'href="([^"]+)"', homepage))
        for href in ("/features", "/about", "/contact"):
            with self.subTest(href=href):
                self.assertIn(href, hrefs)
        self.assertIn("$admin_user !== NULL ? '/admin' : '/login'", homepage)
        self.assertIn('class="site-header sticky top-0 z-50', homepage)
        self.assertRegex(homepage, r'<a[^>]*href="/"[^>]*aria-current="page"[^>]*>Home</a>')
        self.assertIn('class="site-nav-link', homepage)
        self.assertIn('class="site-footer', homepage)


if __name__ == "__main__":
    unittest.main()
