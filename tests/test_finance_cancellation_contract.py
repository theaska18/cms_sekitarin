from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
APP = ROOT / "web" / "application"


class FinanceCancellationContractTests(unittest.TestCase):
    def test_cancellation_store_requests_approval_and_reverses_posted_entries(self):
        source = (APP / "libraries" / "Finance_cancellation_store.php").read_text(encoding="utf-8")
        for marker in (
            "function request(", "function finalize_for_request(", "function create_reversal(",
            "request_action", "fin_cancellation", "approval_workflow_store->submit(",
            "finalize_for_request($request_id, 'approved')", "auto_approved",
            "source_type' => 'reversal'", "debit_amount' => $line['credit_amount']",
            "credit_amount' => $line['debit_amount']", "status' => 'cancelled'",
        ):
            self.assertIn(marker, source)

    def test_cancellation_validates_cash_balance_before_reversal(self):
        store = (APP / "libraries" / "Finance_cancellation_store.php").read_text(encoding="utf-8")
        for marker in (
            "validate_reversal_balance", "reversal_context", "finance_balance_guard",
            "validate_journal_lines", "balance_check", "trans_begin",
            "trans_rollback", "cancel_pending",
        ):
            self.assertIn(marker, store)
        self.assertIn("array('status' => (string) $cancellation['previous_status'])", store)
        controller = (APP / "controllers" / "Admin_finance.php").read_text(encoding="utf-8")
        self.assertIn("$error === 'insufficient_balance' ? 'insufficient_balance'", controller)
        document_view = (APP / "views" / "admin" / "finance" / "document_index.php").read_text(encoding="utf-8")
        self.assertIn("'insufficient_balance' =>", document_view)

    def test_invoices_with_active_payments_cannot_be_cancelled(self):
        store = (APP / "libraries" / "Finance_cancellation_store.php").read_text(encoding="utf-8")
        for marker in (
            "linked_payments_exist", "payment_allocation", "payment_table",
            "'draft', 'pending_approval', 'approved', 'revise', 'posted'",
            "'error' => 'has_payments'",
        ):
            self.assertIn(marker, store)
        self.assertGreaterEqual(store.count("linked_payments_exist("), 3)
        controller = (APP / "controllers" / "Admin_finance.php").read_text(encoding="utf-8")
        self.assertIn("$error === 'has_payments' ? 'document_has_payments'", controller)
        document_view = (APP / "views" / "admin" / "finance" / "document_index.php").read_text(encoding="utf-8")
        self.assertIn("'document_has_payments' =>", document_view)
        self.assertIn("finance_status_document_has_payments", document_view)
        transaction_view = (APP / "views" / "admin" / "finance" / "transaction_index.php").read_text(encoding="utf-8")
        self.assertIn("'document_has_payments' =>", transaction_view)

    def test_cancellation_reversal_respects_closed_periods(self):
        store = (APP / "libraries" / "Finance_cancellation_store.php").read_text(encoding="utf-8")
        self.assertIn("reversal_period_status", store)
        self.assertIn("fin_accounting_period", store)
        self.assertIn("'status', 'open'", store)
        self.assertIn("period_ambiguous", store)
        controller = (APP / "controllers" / "Admin_finance.php").read_text(encoding="utf-8")
        self.assertIn("in_array($error, array('period_closed', 'period_ambiguous'), TRUE)", controller)
        transaction_view = (APP / "views" / "admin" / "finance" / "transaction_index.php").read_text(encoding="utf-8")
        self.assertIn("'period_closed' =>", transaction_view)

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
        journal_view = (APP / "views" / "admin" / "finance" / "journal_index.php").read_text(encoding="utf-8")
        self.assertIn("finance-list.js", journal_view)
        self.assertIn("opening_balances", script)
        self.assertIn("fin_opening_balances", script)
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

    def test_flows_without_steps_are_auto_approved(self):
        store = (APP / "libraries" / "Finance_cancellation_store.php").read_text(encoding="utf-8")
        controller = (APP / "controllers" / "Admin_finance.php").read_text(encoding="utf-8")
        self.assertNotIn("'error' => 'no_steps'", store)
        self.assertNotIn("effective_steps(", store)
        self.assertIn("$result['status'] === 'auto_approved'", store)
        self.assertIn("$this->finalize_for_request($request_id, 'approved')", store)
        self.assertIn("'cancel_auto_approved'", controller)
        for locale in sorted(p.name for p in (APP / "language").iterdir() if p.is_dir()):
            path = APP / "language" / locale / "finance_lang.php"
            if not path.is_file():
                continue
            with self.subTest(locale=locale):
                self.assertIn("$lang['finance_status_cancel_auto_approved']", path.read_text(encoding="utf-8"))
        for method in (
            "finance_opening_balance_start_approval",
            "finance_manual_journal_start_approval",
            "finance_document_start_approval",
            "finance_payment_start_approval",
            "finance_reconciliation_start_approval",
            "finance_transaction_start_approval",
            "finance_transfer_start_approval",
        ):
            with self.subTest(method=method):
                body = controller.split("private function " + method + "(", 1)[1]
                body = body.split("private function ", 1)[0].split("public function ", 1)[0]
                self.assertNotIn("approval_steps_missing", body)
                self.assertIn("auto_approved", body)

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
