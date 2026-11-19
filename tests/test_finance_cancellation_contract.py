from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web" / "application"


class FinanceCancellationContractTests(unittest.TestCase):
    def test_cancellation_store_requests_approval_and_reverses_posted_entries(self):
        source = (APP / "libraries" / "Finance_cancellation_store.php").read_text(encoding="utf-8")
        for marker in (
            "function request(", "function finalize_for_request(", "function create_reversal(",
            "request_action", "fin_cancellation", "approval_workflow_store->submit(", "no_steps", "effective_steps(",
            "source_type' => 'reversal'", "debit_amount' => $line['credit_amount']",
            "credit_amount' => $line['debit_amount']", "status' => 'cancelled'",
        ):
            self.assertIn(marker, source)

    def test_approval_flow_supports_cancel_requests(self):
        source = (APP / "libraries" / "Approval_workflow_store.php").read_text(encoding="utf-8")
        self.assertIn("$request_action = 'submit'", source)
        self.assertIn("'request_action'=>$request_action === 'cancel' ? 'cancel' : 'submit'", source)
        self.assertIn("finalize_for_request(", source)

    def test_cancel_endpoint_and_ui_are_wired(self):
        controller = (APP / "controllers" / "Admin_finance.php").read_text(encoding="utf-8")
        routes = (APP / "config" / "routes.php").read_text(encoding="utf-8")
        script = (ROOT / "web" / "assets" / "js" / "finance-list.js").read_text(encoding="utf-8")
        footer = (APP / "views" / "admin" / "footer.php").read_text(encoding="utf-8")
        self.assertIn("public function cancel_request()", controller)
        self.assertIn("finance_cancellation_store->request(", controller)
        self.assertIn("admin/finance/cancel_request", routes)
        self.assertIn("data-finance-cancel-open", script)
        self.assertIn("data-finance-cancel-toggle", script)
        self.assertIn("if (!menu)", script)
        self.assertIn("finance-cancel-dialog", footer)
        self.assertIn('name="reason"', footer)

    def test_income_and_expense_submit_approval_use_their_menu_codes(self):
        controller = (APP / "controllers" / "Admin_finance.php").read_text(encoding="utf-8")
        submit = controller.split("public function submit_approval", 1)[1].split("private function", 1)[0]
        self.assertIn("$slug === 'income' || $slug === 'expenses' ? 'fin_' . $slug", submit)

    def test_missing_steps_message_is_generic_and_shown_on_all_finance_lists(self):
        locales = sorted(p.name for p in (APP / "language").iterdir() if p.is_dir())
        for locale in locales:
            path = APP / "language" / locale / "finance_lang.php"
            if not path.is_file():
                continue
            content = path.read_text(encoding="utf-8")
            with self.subTest(locale=locale):
                self.assertIn("$lang['finance_status_approval_steps_missing']", content)
                self.assertNotIn("Saldo Awal approval flow", content)
                self.assertNotIn("persetujuan Saldo Awal", content)
        transaction_view = (APP / "views" / "admin" / "finance" / "transaction_index.php").read_text(encoding="utf-8")
        document_view = (APP / "views" / "admin" / "finance" / "document_index.php").read_text(encoding="utf-8")
        self.assertIn("'approval_steps_missing' =>", transaction_view)
        self.assertIn("'approval_steps_missing' =>", document_view)
        self.assertIn("finance_status_approval_steps_missing", transaction_view)
        self.assertIn("finance_status_approval_steps_missing", document_view)

    def test_cancellation_migration_and_default_flow_exist(self):
        migration = (ROOT / "database" / "migrations" / "20261119000700_add_finance_cancellation.sql").read_text(encoding="utf-8")
        flow = (ROOT / "database" / "migrations" / "20261119000800_seed_finance_cancel_flow.sql").read_text(encoding="utf-8")
        schema = (ROOT / "database" / "schema.sql").read_text(encoding="utf-8")
        self.assertIn("request_action", migration)
        self.assertIn("CREATE TABLE IF NOT EXISTS `fin_cancellation`", migration)
        self.assertIn("finance_cancel", flow)
        self.assertNotIn("sys_approval_flow_step", flow)
        self.assertIn("fin_cancellation", schema)
        self.assertIn("request_action", schema)


if __name__ == "__main__":
    unittest.main()
