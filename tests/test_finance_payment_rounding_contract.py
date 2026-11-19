from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web" / "application"
MIGRATION = ROOT / "database/migrations/20261119001100_add_payment_rounding_settings.sql"
SCHEMA = ROOT / "database/schema.sql"
STORE = APP / "libraries/Finance_payment_store.php"
CONTROLLER = APP / "controllers/Admin_finance.php"


class FinancePaymentRoundingContractTests(unittest.TestCase):
    def test_settings_support_rounding_tolerance_and_accounts(self):
        migration = MIGRATION.read_text(encoding="utf-8")
        schema = SCHEMA.read_text(encoding="utf-8")
        for marker in (
            "rounding_tolerance", "rounding_gain_account_id",
            "rounding_loss_account_id", "decimal(20,6)",
        ):
            self.assertIn(marker, migration)
            self.assertIn(marker, schema)
        controller = CONTROLLER.read_text(encoding="utf-8")
        for marker in (
            "finance_field_rounding_tolerance",
            "finance_field_rounding_gain_account_id",
            "finance_field_rounding_loss_account_id",
            "'default' => '1.00'",
        ):
            self.assertIn(marker, controller)

    def test_payment_store_caps_allocation_and_writes_off_rounding(self):
        source = STORE.read_text(encoding="utf-8")
        for marker in (
            "rounding_tolerance", "rounding_gain_account_id", "rounding_loss_account_id",
            "$tolerance", "$applied", "$rounding", "rounding_account_missing",
            "Rounding difference", "'amount' => $applied",
        ):
            self.assertIn(marker, source)
        self.assertIn("$amount > $outstanding + $tolerance", source)
        self.assertIn("$outstanding <= 0.000001", source)

    def test_rounding_labels_exist(self):
        for locale in ("indonesian", "english"):
            content = (APP / "language" / locale / "finance_lang.php").read_text(encoding="utf-8")
            with self.subTest(locale=locale):
                self.assertIn("finance_field_rounding_tolerance", content)
                self.assertIn("finance_field_rounding_gain_account_id", content)
                self.assertIn("finance_field_rounding_loss_account_id", content)
                self.assertIn("finance_payment_error_rounding_account_missing", content)


if __name__ == "__main__":
    unittest.main()
