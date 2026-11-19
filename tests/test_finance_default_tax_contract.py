from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web" / "application"
MIGRATION = ROOT / "database/migrations/20261119001200_add_default_tax_settings.sql"
SCHEMA = ROOT / "database/schema.sql"
CONTROLLER = APP / "controllers/Admin_finance.php"


class FinanceDefaultTaxContractTests(unittest.TestCase):
    def test_default_tax_settings_columns_exist(self):
        migration = MIGRATION.read_text(encoding="utf-8")
        schema = SCHEMA.read_text(encoding="utf-8")
        for column in ("default_output_tax_id", "default_input_tax_id"):
            with self.subTest(column=column):
                self.assertIn("`" + column + "`", migration)
                self.assertIn("`" + column + "`", schema)
                self.assertIn("REFERENCES `fin_tax`", schema)

    def test_forms_prefill_configured_default_tax(self):
        controller = CONTROLLER.read_text(encoding="utf-8")
        for marker in (
            "function finance_default_tax_id",
            "'default_output_tax_id'",
            "'default_input_tax_id'",
            "default_output_tax_id' : 'default_input_tax_id",
            "finance_field_default_output_tax_id",
            "finance_field_default_input_tax_id",
            "'nullable' => TRUE",
        ):
            self.assertIn(marker, controller)
        self.assertEqual(controller.count("$this->finance_default_tax_id("), 2)

    def test_default_tax_labels_exist(self):
        for locale in ("indonesian", "english"):
            content = (APP / "language" / locale / "finance_lang.php").read_text(encoding="utf-8")
            with self.subTest(locale=locale):
                self.assertIn("finance_field_default_output_tax_id", content)
                self.assertIn("finance_field_default_input_tax_id", content)
                self.assertIn("finance_resource_field_default_output_tax_id", content)
                self.assertIn("finance_resource_field_default_input_tax_id", content)


if __name__ == "__main__":
    unittest.main()
