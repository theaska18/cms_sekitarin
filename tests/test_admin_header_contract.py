"""Regression contract for the shared CMSNivo admin header navigation."""

from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]
HEADER = ROOT / "web/application/views/admin/header.php"
FOOTER = ROOT / "web/application/views/admin/footer.php"
CONTROLLER = ROOT / "web/application/controllers/Admin.php"


class AdminHeaderContractTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.header = HEADER.read_text(encoding="utf-8")
        cls.footer = FOOTER.read_text(encoding="utf-8")
        cls.controller = CONTROLLER.read_text(encoding="utf-8")
        cls.css = (ROOT / "tailwind/input.css").read_text(encoding="utf-8")

    def test_brand_uses_the_requested_logo_asset(self):
        self.assertIn('src="/assets/logo_box.png"', self.header)
        brand_start = self.header.index('<a href="/admin"')
        brand_end = self.header.index("</a>", brand_start)
        self.assertIn("logo_box.png", self.header[brand_start:brand_end])

    def test_home_action_uses_an_accessible_web_dialog_without_native_prompts(self):
        self.assertIn('id="admin-home-link"', self.header)
        self.assertIn('type="button"', self.header)
        self.assertIn(">Home</span>", self.header)
        self.assertIn('id="admin-home-dialog"', self.footer)
        self.assertIn('class="fixed inset-0 z-[60] hidden flex items-center justify-center p-4"', self.footer)
        self.assertIn('role="dialog"', self.footer)
        self.assertIn('aria-modal="true"', self.footer)
        self.assertIn('aria-labelledby="admin-home-dialog-title"', self.footer)
        self.assertIn('id="admin-home-confirm" href="/"', self.footer)
        self.assertIn('id="admin-home-cancel"', self.footer)
        self.assertNotIn("window.confirm", self.footer)
        self.assertNotIn("window.prompt", self.footer)
        self.assertIn("event.key === 'Escape'", self.footer)
        self.assertIn("event.key === 'Tab'", self.footer)
        self.assertIn("appShell.inert", self.footer)

    def test_language_options_render_flags_and_visible_accessible_locale_names(self):
        self.assertIn('id="admin-language"', self.header)
        self.assertIn("$language_option['flag']", self.header)
        self.assertIn("$language_option['label']", self.header)
        self.assertIn('aria-label="<?php echo html_escape($language_option[\'label\']); ?>"', self.header)
        self.assertIn('class="admin-language-options absolute left-0 top-full z-50 mt-2 grid grid-cols-2', self.header)
        self.assertIn('<span class="min-w-0 flex-1"><?php echo html_escape($language_option[\'label\']); ?></span>', self.header)
        self.assertIn('aria-selected="<?php echo $locale === $language_code ? \'true\' : \'false\'; ?>"', self.header)
        self.assertIn("width: min(20rem, calc(100vw - 1rem));", self.css)
        for flag in ("🇬🇧", "🇮🇩", "🇯🇵", "🇨🇳", "🇸🇦", "🇰🇷", "🇪🇸", "🇫🇷", "🇩🇪"):
            with self.subTest(flag=flag):
                self.assertIn(flag, self.controller)
        self.assertIn('name="lang"', self.header)

    def test_language_popup_and_custom_trigger_are_hidden_until_initialized(self):
        self.assertIn('id="admin-language-toggle" type="button" hidden class=', self.header)
        self.assertIn('id="admin-language-options" role="listbox"', self.header)
        self.assertIn('aria-label="<?php echo html_escape($labels[\'language\']); ?>" hidden class=', self.header)
        self.assertIn("listbox.hidden = false", self.footer)
        self.assertIn("listbox.hidden = true", self.footer)
        self.assertIn("toggle.hidden = false", self.footer)
        self.assertIn("function positionList()", self.footer)
        self.assertIn("window.addEventListener('resize', positionList)", self.footer)
        self.assertIn("event.key === 'ArrowLeft' && document.documentElement.dir === 'rtl'", self.footer)
        self.assertIn("#admin-language-toggle[hidden]", self.css)
        self.assertIn("#admin-language-options[hidden]", self.css)

    def test_admin_topbar_has_no_shadow_or_scrolled_state(self):
        topbar_start = self.header.index('<header class="admin-topbar')
        topbar_end = self.header.index(">", topbar_start)
        self.assertNotIn("shadow", self.header[topbar_start:topbar_end])
        self.assertIn('class="admin-topbar', self.header)
        self.assertNotIn("admin-topbar sticky top-0 z-30 border-b border-slate-200 bg-white", self.header)
        self.assertNotIn("admin-topbar--scrolled", self.footer)
        self.assertNotIn(".admin-topbar.admin-topbar--scrolled", self.css)

    def test_admin_topbar_is_solid_without_shadow_or_transparency(self):
        base_start = self.css.index("    .admin-topbar {")
        base_end = self.css.index("    .admin-language-options {", base_start)
        base = self.css[base_start:base_end]
        self.assertIn("background-color: #6ee7b7;", base)
        self.assertIn("color: #022c22;", base)
        self.assertNotIn("box-shadow", base)
        self.assertNotIn("backdrop-filter", base)
        self.assertNotIn("rgba(255, 255, 255, 0.58)", self.css)
        self.assertNotIn("@supports ((backdrop-filter:", self.css)
        self.assertNotIn("admin-topbar--scrolled", self.css)

    def test_sidebar_labels_use_single_line_ellipsis_with_tooltips(self):
        theme = (ROOT / "web/assets/css/admin-theme.css").read_text(encoding="utf-8")
        self.assertIn("#admin-sidebar .admin-menu-label", theme)
        self.assertIn("text-overflow: ellipsis", theme)
        self.assertIn("white-space: nowrap", theme)
        self.assertIn('title="<?php echo html_escape($item[\'label\']); ?>"', self.header)
        self.assertIn('title="<?php echo html_escape($group[\'label\']); ?>"', self.header)

    def test_mobile_sidebar_open_does_not_focus_its_filter(self):
        self.assertIn("if (!window.matchMedia('(max-width: 767px)').matches)", self.footer)
        self.assertIn("firstControl.focus()", self.footer)

    def test_mobile_filter_uses_at_least_16px_to_prevent_browser_zoom(self):
        filter_start = self.header.index('id="admin-sidebar-filter"')
        class_start = self.header.index('class="admin-sidebar-filter', filter_start)
        filter_input = self.header[class_start:self.header.index(">", class_start)]
        self.assertIn("text-base", filter_input)
        self.assertIn("md:text-sm", filter_input)

    def test_profile_menu_focusout_does_not_cancel_a_mobile_tap_before_click(self):
        self.assertIn("window.setTimeout(function ()", self.footer)
        self.assertIn("if (!menu.contains(document.activeElement)) setOpen(false, false)", self.footer)

    def test_old_settings_control_is_replaced_by_exact_dropdown_destinations(self):
        self.assertIn('href="/admin/setting"', self.header)
        self.assertIn('href="/admin/profile"', self.header)
        self.assertNotIn('href="/admin/settings"', self.header)
        self.assertIn("admin-profile-menu", self.header)

    def test_profile_dropdown_exposes_accessible_keyboard_controls(self):
        self.assertIn('id="admin-profile-toggle"', self.header)
        self.assertIn('aria-haspopup="menu"', self.header)
        self.assertIn('role="menu"', self.header)
        self.assertIn('role="menuitem"', self.header)
        self.assertIn('aria-expanded="false"', self.header)
        self.assertIn('aria-controls="admin-profile-menu"', self.header)
        self.assertIn("event.key === 'Escape'", self.footer)
        self.assertIn("event.key === 'ArrowDown'", self.footer)

    def test_profile_toggle_has_no_decorative_down_chevron(self):
        start = self.header.index('id="admin-profile-toggle"')
        end = self.header.index("</button>", start)
        self.assertNotIn("<svg", self.header[start:end])

    def test_admin_footer_uses_dynamic_year_and_centered_copyright(self):
        footer_start = self.footer.index('<footer ')
        footer_end = self.footer.index('</footer>', footer_start)
        footer_markup = self.footer[footer_start:footer_end]
        self.assertIn('class="border-t border-slate-200 pt-4 text-center text-xs text-slate-500"', footer_markup)
        self.assertIn('© <?php echo date(\'Y\'); ?> Asep Kamaludin. All rights reserved.', footer_markup)

    def test_language_controls_are_borderless(self):
        for control_id in ('id="admin-language"', 'id="admin-language-toggle"'):
            start = self.header.index(control_id)
            end = self.header.index(">", start)
            self.assertNotIn("border-slate-300", self.header[start:end])
            self.assertNotIn(" border ", self.header[start:end])

    def test_home_action_moves_into_mobile_sidebar_before_dashboard(self):
        nav_start = self.header.index('id="admin-sidebar-navigation"')
        nav_end = self.header.index("</nav>", nav_start)
        navigation = self.header[nav_start:nav_end]
        self.assertIn('id="admin-home-link-mobile"', navigation)
        self.assertLess(navigation.index('id="admin-home-link-mobile"'), navigation.index('data-menu-label="<?php echo html_escape($labels[\'dashboard\']); ?>"'))
        desktop_start = self.header.index('id="admin-home-link"')
        desktop_end = self.header.index(">", desktop_start)
        self.assertIn("hidden md:inline-flex", self.header[desktop_start:desktop_end])
        self.assertIn("getElementById('admin-home-link-mobile')", self.footer)

    def test_mobile_sidebar_uses_centered_banner_logo(self):
        sidebar_start = self.header.index('id="admin-sidebar"')
        sidebar_end = self.header.index("</aside>", sidebar_start)
        sidebar = self.header[sidebar_start:sidebar_end]
        self.assertIn('src="/assets/logo_banner.png"', sidebar)
        logo_start = sidebar.index('src="/assets/logo_banner.png"')
        logo_start = sidebar.rfind("<img", 0, logo_start)
        logo_end = sidebar.index(">", logo_start)
        self.assertIn("mx-auto", sidebar[logo_start:logo_end])
        logo_container_start = sidebar.rfind("<div", 0, logo_start)
        logo_container_end = sidebar.index(">", logo_container_start)
        self.assertIn("md:hidden", sidebar[logo_container_start:logo_container_end])
        link_start = sidebar.rfind("<a ", 0, logo_start)
        link_end = sidebar.index("</a>", logo_start)
        self.assertIn('href="/admin"', sidebar[link_start:link_end])

    def test_logout_is_post_only_csrf_protected_and_uses_session_invalidation(self):
        auth = (ROOT / "web/application/controllers/Auth.php").read_text()
        identity = (ROOT / "web/application/libraries/Admin_identity.php").read_text()
        self.assertIn('method="post" action="/logout"', self.header)
        self.assertIn("$csrf_token_name", self.header)
        self.assertIn("$this->input->method(TRUE) !== 'POST'", auth)
        self.assertIn("$this->admin_identity->logout();", auth)
        self.assertIn("sess_destroy()", identity)
        self.assertIn("setcookie($cookie_name, '', array(", identity)


if __name__ == "__main__":
    unittest.main()
