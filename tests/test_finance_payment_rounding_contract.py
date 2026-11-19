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

    def test_invoice_lists_show_paid_and_outstanding_status(self):
        store = (APP / "libraries/Finance_document_store.php").read_text(encoding="utf-8")
        for marker in (
            "function paid_sql", "apply_payment_status_filter",
            "paid_amount", "outstanding_amount", "payment_status",
        ):
            self.assertIn(marker, store)
        controller = CONTROLLER.read_text(encoding="utf-8")
        self.assertIn("finance_document_payment_filter_config", controller)
        self.assertIn("finance_payment_status_paid", controller)
        view = (APP / "views/admin/finance/document_index.php").read_text(encoding="utf-8")
        for marker in (
            "payment_status", "outstanding_amount", "finance_payment_status_paid",
            "finance_payment_status_partial", "finance_payment_status_unpaid",
        ):
            self.assertIn(marker, view)
        detail = (APP / "views/admin/finance/document_view.php").read_text(encoding="utf-8")
        self.assertIn("finance_document_financials", detail)
        self.assertIn("finance_payment_outstanding", detail)

    def test_payment_form_has_pay_all_and_specific_errors(self):
        view = (APP / "views/admin/finance/payment_form.php").read_text(encoding="utf-8")
        for marker in (
            'id="pay-all"', 'name="pay_all"', 'finance_payment_pay_all',
            'amountInput.disabled=checked', 'setAmount(currentOutstanding)',
            "strpos($error, 'payment_') === 0", 'finance_payment_error_',
        ):
            self.assertIn(marker, view)
        for marker in (
            "data-payment-summary-outstanding", "Intl.NumberFormat", "function shouldAutoFill",
            "isNewPayment", "setAmount(currentOutstanding)",
        ):
            self.assertIn(marker, view)
        controller = CONTROLLER.read_text(encoding="utf-8")
        self.assertIn("$values['pay_all'] = $this->input->post('pay_all', TRUE) ? 1 : 0;", controller)
        self.assertIn("$store->document_financials($tenant, $kind, $values['document_id'])", controller)
        store = STORE.read_text(encoding="utf-8")
        self.assertIn("if ($outstanding <= 0.000001)", store)
        self.assertIn("continue;", store)

    def test_payment_list_maps_draft_saved_status(self):
        view = (APP / "views/admin/finance/payment_index.php").read_text(encoding="utf-8")
        self.assertIn("'draft_saved' =>", view)
        self.assertIn("finance_doc_status_draft_saved", view)
        self.assertIn("$ok_statuses", view)
        self.assertIn("$statuses[$finance_status]", view)
        controller = CONTROLLER.read_text(encoding="utf-8")
        self.assertIn("set_flashdata('finance_status', $status)", controller)
        self.assertIn("'draft_saved'", controller)

    def test_rounding_tolerance_input_is_limited_to_two_decimals(self):
        controller = CONTROLLER.read_text(encoding="utf-8")
        self.assertIn("$field['type'] !== 'decimal'", controller)
        self.assertIn("number_format((float) $record[$name], 2, '.', '')", controller)
        self.assertIn("/^\\d{1,14}(?:\\.\\d{1,2})?$/", controller)
        form = (APP / "views/admin/finance/resource_form.php").read_text(encoding="utf-8")
        self.assertIn('data-decimal-limit="2"', form)
        self.assertIn("currency-input.js", form)

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
