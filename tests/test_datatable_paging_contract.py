from pathlib import Path
import re
import unittest

ROOT = Path(__file__).resolve().parents[1]
WEB = ROOT / "web"
APP = WEB / "application"
VIEWS = APP / "views"


class DatatablePagingContractTests(unittest.TestCase):
    def test_server_library_defaults_and_caps_pages(self):
        library = (APP / "libraries" / "Datatable_server.php").read_text(encoding="utf-8")
        self.assertIn("const DEFAULT_LENGTH = 50;", library)
        self.assertIn("const MAX_LENGTH = 200;", library)
        self.assertIn("public static function page_size_options()", library)
        self.assertIn("array(self::DEFAULT_LENGTH, 100, self::MAX_LENGTH)", library)
        self.assertIn("$default_length = min(self::DEFAULT_LENGTH, $max_length);", library)
        self.assertIn("$length = $length <= 0 ? $default_length : min($max_length, $length);", library)
        self.assertIn("min(self::MAX_LENGTH, (int) $options['max_length'])", library)

    def test_shared_javascript_defaults_and_clamps_requests(self):
        script = (WEB / "assets" / "js" / "admin-datatables.js").read_text(encoding="utf-8")
        self.assertIn("defaultLength: 50", script)
        self.assertIn("maxLength: 200", script)
        self.assertIn("options: [50, 100, 200]", script)
        self.assertIn("pageLength: pageDefaults.defaultLength", script)
        self.assertIn("data.length = pageDefaults.maxLength", script)

    def test_finance_filter_panel_exposes_shared_page_sizes(self):
        panel = (APP / "libraries" / "Finance_filter_panel.php").read_text(encoding="utf-8")
        partial = (VIEWS / "admin" / "finance" / "partials" / "filter_panel.php").read_text(encoding="utf-8")
        script = (WEB / "assets" / "js" / "finance-filters.js").read_text(encoding="utf-8")
        self.assertIn("'page_size_default' => 50", panel)
        self.assertIn("'page_size_options' => array(50, 100, 200)", panel)
        self.assertIn("finance_filter_page_size_options", panel)
        self.assertIn("data-page-size-default", partial)
        self.assertIn("foreach ($page_sizes as $page_size)", partial)
        self.assertIn("data-page-size-default", script)
        self.assertNotIn("setValue(panel, 'page_size', '10');", script)

    def test_views_no_longer_use_the_old_page_menus(self):
        offenders = []
        for path in VIEWS.rglob("*.php"):
            text = path.read_text(encoding="utf-8")
            if re.search(r"pageLength:\s*(?:10|25)\b", text):
                offenders.append(str(path))
            if "lengthMenu: [[10, 25, 50], [10, 25, 50]]" in text:
                offenders.append(str(path))
        self.assertEqual([], offenders)

    def test_datatable_controllers_request_up_to_the_shared_cap(self):
        controllers = (APP / "controllers").glob("*.php")
        leftovers = []
        for path in controllers:
            text = path.read_text(encoding="utf-8")
            if re.search(r"'max_length'\s*=>\s*(?:50|100)\s*,\s*'max_search_length'", text):
                leftovers.append(path.name)
        self.assertEqual([], leftovers)

    def test_property_and_tenant_page_choices(self):
        for name in ("Admin_property.php", "Admin_tenant.php"):
            controller = (APP / "controllers" / name).read_text(encoding="utf-8")
            self.assertIn("'per_page_choices' => array(50, 100, 200)", controller)
            self.assertIn("return 50;", controller)
            self.assertIn("if ($requested > 200) {", controller)
            self.assertIn("return $requested <= 100 ? 100 : 200;", controller)


if __name__ == "__main__":
    unittest.main()
