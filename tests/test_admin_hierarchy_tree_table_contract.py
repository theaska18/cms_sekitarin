from pathlib import Path
import re
import unittest

ROOT = Path(__file__).resolve().parents[1]


class AdminHierarchyTreeTableContractTests(unittest.TestCase):
    def test_hierarchy_is_rendered_as_accessible_tree_table(self):
        view = (ROOT / "web/application/views/admin/hierarchy/index.php").read_text()
        store = (ROOT / "web/application/libraries/Approval_workflow_store.php").read_text()
        self.assertIn('aria-labelledby="org-chart-heading"', view)
        self.assertIn('role="treegrid"', view)
        self.assertIn('role="rowgroup"', view)
        self.assertIn('role="row"', view)
        self.assertIn('role="gridcell"', view)
        self.assertIn('data-tree-row', view)
        self.assertIn('data-delete-org=', view)
        self.assertIn('data-parent-org=', view)
        self.assertNotIn('data-panel="child"', view)
        self.assertNotIn('hierarchy-tree-table__actions', view)
        self.assertNotIn('data-panel="update"', view)
        self.assertNotIn('data-inline-child=', view)
        form = (ROOT / "web/application/views/admin/hierarchy/new.php").read_text()
        controller = (ROOT / "web/application/controllers/Admin_hierarchy.php").read_text()
        self.assertIn('name="parent_org"', form)
        self.assertIn("$this->input->get('parent_org', TRUE)", controller)
        self.assertIn("$record['parent_org'] = (string) (int) $parent", controller)
        self.assertIn("if ($this->input->method(TRUE) !== 'POST')", controller)
        self.assertIn('hierarchy_org_exists(', controller)
        self.assertIn('create_child_org(', controller)
        self.assertNotIn('data-panel="update"', view)
        self.assertIn('role="treeitem"', view)
        self.assertIn('aria-level=', view)
        self.assertIn('aria-expanded=', view)
        self.assertNotIn('data-tree-toggle', view)
        self.assertIn('aria-colcount="4"', view)
        self.assertIn('data-tree-children', view)
        self.assertIn("$render_node($child_id, $level + 1)", view)
        self.assertNotIn('Atur induk unit organisasi', view)
        self.assertNotIn('aria-label="Ringkasan hierarki"', view)
        self.assertNotIn('Unit berinduk', view)
        self.assertIn("$labels['hierarchy_members']", view)
        self.assertIn("$org['member_names']", view)
        self.assertIn("AS member_names", store)
        self.assertIn("$labels['hierarchy_unassigned']", view)

    def test_tree_table_styles_are_in_admin_theme(self):
        css = (ROOT / "web/assets/css/admin-theme.css").read_text()
        self.assertIn(".hierarchy-tree-table", css)
        self.assertIn('html[data-theme="dark"] #admin-app-shell .hierarchy-tree-table', css)
        self.assertIn('html[data-theme="dark"] .hierarchy-tree-table__action-menu', css)
        self.assertIn('html[dir="rtl"] #admin-app-shell .hierarchy-tree-table__indent', css)
        self.assertIn('admin-theme.css?v=', (ROOT / "web/application/views/admin/header.php").read_text())

    def test_hierarchy_translations_are_complete_in_all_supported_locales(self):
        languages = ROOT / "web/application/language"
        locales = ("english", "indonesian", "japanese", "chinese", "arabic", "korean", "spanish", "french", "german")
        key_pattern = re.compile(r"\$lang\['([^']+)'\]")
        english = set(key_pattern.findall((languages / "english/hierarchy_lang.php").read_text(encoding="utf-8")))
        self.assertTrue(english)
        for locale in locales:
            with self.subTest(locale=locale):
                translated = set(key_pattern.findall((languages / locale / "hierarchy_lang.php").read_text(encoding="utf-8")))
                self.assertEqual(translated, english)


if __name__ == "__main__":
    unittest.main()
